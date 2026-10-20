<?php
namespace AdPilot\Commerce;

use AdPilot\Core\{Problem, Store};
use AdPilot\Pricing\Money;

/** Optional HPOS-safe adapter. Uses WC CRUD only, never order-post SQL. */
final class WooCommerce
{
    public function __construct(private readonly Store $store, private readonly Commerce $commerce)
    {
    }

    public function ready(): bool
    {
        return class_exists('WooCommerce') && (bool) apply_filters('adpilot_checkout_ready', false, get_current_blog_id());
    }

    public function start(array $order, int $actor): array
    {
        if (!$this->ready()) { throw new Problem('checkout_unavailable', 'WooCommerce gateway, tax and settlement policy must be certified before checkout is available.', 409); }
        if ($order['status'] !== 'pending_payment') { throw new Problem('invalid_order_state', 'This order cannot start checkout.', 409); }
        $hold = $this->store->one('inventory_holds', ['id' => $order['hold_id']]);
        if (!$hold || $hold['status'] !== 'active' || $hold['expires_at'] <= gmdate('Y-m-d H:i:s')) {
            throw new Problem('hold_expired', 'Refresh inventory before starting checkout.', 409);
        }
        if ($order['woo_order_id']) {
            $woo = wc_get_order((int) $order['woo_order_id']);
            if (!$woo || (int) $woo->get_customer_id() !== $actor) { throw new Problem('order_mapping_invalid', 'Checkout mapping requires review.', 409); }
            return ['checkout_url' => $woo->get_checkout_payment_url()];
        }
        $quote = $this->store->one('quotes', ['id' => $order['quote_id']]);
        $snapshot = json_decode($quote['offer_snapshot_json'], true, 32, JSON_THROW_ON_ERROR);
        $precision = $snapshot['terms']['currency_precision'];
        if ($precision !== wc_get_price_decimals()) { throw new Problem('currency_precision_mismatch', 'WooCommerce currency precision differs from the signed quote policy.', 409); }
        $total = self::decimal((int) $order['total_minor'], $precision);
        $woo = wc_create_order(['customer_id' => $actor]);
        if (is_wp_error($woo)) { throw new Problem('checkout_creation_failed', 'WooCommerce could not create checkout.', 503); }
        $item = new \WC_Order_Item_Fee();
        $item->set_name(__('Advertising placement', 'adpilot'));
        $item->set_tax_status('none');
        $item->set_total($total);
        $woo->add_item($item);
        $woo->set_currency($order['currency']);
        $woo->set_total($total);
        $woo->update_meta_data('_adpilot_order_id', $order['public_id']);
        $woo->update_meta_data('_adpilot_site_id', get_current_blog_id());
        $woo->save();
        try {
            $this->store->update('advertising_orders', $order, ['woo_order_id' => $woo->get_id()]);
        } catch (\Throwable $error) {
            $woo->update_status('cancelled', 'AdPilot checkout mapping conflicted; no capture requested.');
            throw $error;
        }
        return ['checkout_url' => $woo->get_checkout_payment_url()];
    }

    public function paymentComplete(int $wooId): void
    {
        if (!$this->ready()) { return; }
        $woo = wc_get_order($wooId);
        if (!$woo || !$woo->is_paid() || !$woo->get_date_paid() || (int) $woo->get_meta('_adpilot_site_id') !== get_current_blog_id()) { return; }
        $order = $this->store->one('advertising_orders', ['public_id' => (string) $woo->get_meta('_adpilot_order_id'), 'woo_order_id' => $wooId]);
        if (!$order) { return; }
        $quote = $this->store->one('quotes', ['id' => $order['quote_id']]);
        $snapshot = json_decode($quote['offer_snapshot_json'], true, 32, JSON_THROW_ON_ERROR);
        $precision = $snapshot['terms']['currency_precision'];
        $captured = Money::fromDecimal((string) $woo->get_total(), $woo->get_currency(), $precision);
        $this->commerce->settleVerified($order, $captured, $wooId . ':' . $woo->get_transaction_id());
    }

    public static function decimal(int $minor, int $precision): string
    {
        if ($precision === 0) { return (string) $minor; }
        $digits = str_pad((string) $minor, $precision + 1, '0', STR_PAD_LEFT);
        return substr($digits, 0, -$precision) . '.' . substr($digits, -$precision);
    }
}
