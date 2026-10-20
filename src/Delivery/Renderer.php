<?php
namespace AdPilot\Delivery;

use AdPilot\Core\{Ids, Store};

final class Renderer
{
    public function __construct(private readonly Store $store)
    {
    }

    public function render(string $kind, string $id, bool $preview = false): string
    {
        if ($preview) {
            return '<div class="adp-editor-preview">' . esc_html__('AdPilot placement preview · nonbillable', 'adpilot') . '</div>';
        }
        if (!preg_match('/^[A-Z0-9]{26}$/D', $id)) {
            return '';
        }
        $where = ['status' => 'published'];
        $style = '';
        if ($kind === 'slot') {
            $where['public_id'] = $id;
        } elseif ($kind === 'zone' || $kind === 'container') {
            $row = $this->store->one($kind === 'zone' ? 'zones' : 'containers', ['public_id' => $id, 'status' => 'published']);
            if (!$row) {
                return '';
            }
            $where[$kind === 'zone' ? 'zone_id' : 'container_id'] = $row['id'];
            if ($kind === 'container') {
                $layout = json_decode($row['responsive_json'], true, 16, JSON_THROW_ON_ERROR);
                $style = '--adp-columns:' . (int) $layout['columns'] . ';--adp-tablet-columns:' . (int) $layout['tablet_columns'] . ';--adp-mobile-columns:' . (int) $layout['mobile_columns'] . ';--adp-gap:' . (int) $layout['gap'] . 'px;--adp-ratio:' . esc_attr($layout['aspect_ratio']);
            }
        } else {
            return '';
        }
        $slots = $this->store->many('slots', $where, 5000);
        if (!$slots) {
            return '';
        }
        wp_enqueue_script('adpilot-runtime');
        wp_enqueue_style('adpilot-runtime');
        $html = '<div class="adp-placements" style="' . esc_attr($style) . '" data-adpilot-layout="' . esc_attr($id) . '">';
        foreach ($slots as $slot) {
            $html .= '<div class="adp-slot" id="adp-' . esc_attr(Ids::make()) . '" data-adpilot-slot="' . esc_attr($slot['public_id']) . '" aria-label="' . esc_attr__('Advertising placement', 'adpilot') . '"></div>';
        }
        return $html . '</div>';
    }
}
