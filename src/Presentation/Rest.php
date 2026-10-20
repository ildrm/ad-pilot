<?php
namespace AdPilot\Presentation;

use AdPilot\Core\{Input, Problem};
use AdPilot\Platform\Settings;
use AdPilot\Inventory\Inventory;
use AdPilot\Layout\Layouts;
use AdPilot\Identity\Organizations;
use AdPilot\Creatives\Creatives;
use AdPilot\Pricing\Pricing;
use AdPilot\Commerce\Commerce;

final class Rest
{
    public function __construct(private readonly array $s)
    {
    }

    private function route(string $method, string $path, string $permission, callable $handler): void
    {
        register_rest_route('adpilot/v1', $path, ['methods' => $method,
            'permission_callback' => static function () use ($permission): bool|\WP_Error {
                $allowed = $permission === 'public' || ($permission === 'authenticated' ? is_user_logged_in() : current_user_can($permission));
                return $allowed ? true : new \WP_Error('adpilot_forbidden', __('You do not have permission for this operation.', 'adpilot'), ['status' => 403]);
            },
            'callback' => static function (\WP_REST_Request $request) use ($handler): \WP_REST_Response|\WP_Error {
                try {
                    if (strlen((string) $request->get_body()) > 65536) {
                        throw new Problem('payload_too_large', 'Request body exceeds 64 KiB.', 413);
                    }
                    $response = rest_ensure_response($handler($request));
                    $response->header('Cache-Control', 'private, no-store');
                    return $response;
                } catch (Problem $error) {
                    return new \WP_Error('adpilot_' . $error->key, $error->getMessage(), ['status' => $error->status]);
                } catch (\Throwable $error) {
                    // No SQL, paths, secrets or request contents in public errors.
                    return new \WP_Error('adpilot_operation_failed', __('The operation could not complete. Retry or check diagnostics.', 'adpilot'), ['status' => 503]);
                }
            }]);
    }

    private static function body(\WP_REST_Request $request): array
    {
        $input = $request->get_json_params();
        if ($input !== null && !is_array($input)) {
            throw new Problem('invalid_json', 'A JSON object is required.');
        }
        return $input ?? [];
    }

    private function page(\WP_REST_Request $request, string $table, array $where, callable $view): array
    {
        if ($request->get_param('search') && in_array($table, ['zones', 'containers', 'slots', 'campaigns', 'organizations', 'creative_versions', 'layout_drafts'], true)) {
            $where['_search'] = Input::text(['search' => $request->get_param('search')], 'search', 80);
        }
        $page = Input::integer(['page' => $request->get_param('page') ?? 1], 'page', 1, 10000);
        $size = Input::integer(['per_page' => $request->get_param('per_page') ?? 50], 'per_page', 1, 100);
        return ['items' => array_map($view, $this->s['store']->many($table, $where, $size, ($page - 1) * $size)),
            'total' => $this->s['store']->count($table, $where), 'page' => $page, 'per_page' => $size];
    }

    private static function envelope(array $items): array
    {
        return ['items' => $items, 'total' => count($items), 'page' => 1, 'per_page' => count($items)];
    }

    public function register(): void
    {
        $s = $this->s;
        $id = '[A-Z0-9]{26}';
        $this->route('GET', '/me', 'authenticated', static fn() => ['id' => get_current_user_id(), 'display_name' => wp_get_current_user()->display_name,
            'site_id' => get_current_blog_id(), 'capabilities' => array_values(array_filter(\AdPilot\Platform\Plugin::CAPS, 'current_user_can'))]);
        $this->route('GET', '/my-organizations', 'authenticated', fn() => self::envelope($s['organizations']->mine(get_current_user_id())));
        $this->route('POST', '/organizations', 'authenticated', static function ($r) use ($s) {
            if (!Settings::read()['settings']['self_service_signup'] && !current_user_can('adpilot_manage_organization_status')) {
                throw new Problem('signup_unavailable', 'Ask the publisher to provision your advertiser organization.', 403);
            }
            return $s['organizations']->create(get_current_user_id(), self::body($r));
        });
        $this->route('GET', '/admin/organizations', 'adpilot_manage_organization_status', fn($r) => $this->page($r, 'organizations', [], [Organizations::class, 'view']));
        $this->route('GET', "/organizations/(?P<org_id>{$id})", 'authenticated', static fn($r) => Organizations::view($s['organizations']->authorized(get_current_user_id(), $r['org_id'])));
        foreach (['verify' => 'verified', 'suspend' => 'suspended'] as $action => $status) {
            $this->route('POST', "/admin/organizations/(?P<org_id>{$id})/actions/{$action}", 'adpilot_manage_organization_status', static fn($r) =>
                $s['organizations']->setStatus(get_current_user_id(), $r['org_id'], $status, Input::text(self::body($r), 'reason', 1000)));
        }
        $this->route('GET', "/organizations/(?P<org_id>{$id})/members", 'authenticated', function ($r) use ($s) {
            $org = $s['organizations']->authorized(get_current_user_id(), $r['org_id'], 'members');
            return $this->page($r, 'organization_memberships', ['organization_id' => $org['id']], static fn($row) => ['id' => $row['public_id'], 'role_key' => $row['role_key'], 'status' => $row['status'], 'row_version' => (int) $row['row_version']]);
        });
        foreach (['change-role' => false, 'revoke' => true] as $action => $revoke) {
            $this->route('POST', "/organizations/(?P<org_id>{$id})/members/(?P<member_id>{$id})/actions/{$action}", 'authenticated', static function ($r) use ($s, $revoke) {
                $org = $s['organizations']->authorized(get_current_user_id(), $r['org_id'], 'members');
                $member = $s['store']->one('organization_memberships', ['public_id' => $r['member_id'], 'organization_id' => $org['id']]) ?? throw new Problem('not_found', 'Membership unavailable.', 404);
                return $s['organizations']->changeMember(get_current_user_id(), $r['org_id'], (int) $member['user_id'], self::body($r)['role_key'] ?? $member['role_key'], $revoke);
            });
        }

        $this->route('GET', '/admin/zones', 'adpilot_view_inventory', fn($r) => $this->page($r, 'zones', [], [Inventory::class, 'zoneView']));
        $this->route('POST', '/admin/zones', 'adpilot_manage_inventory', static fn($r) => $s['inventory']->createZone(get_current_user_id(), self::body($r)));
        $this->route('GET', "/admin/zones/(?P<zone_id>{$id})", 'adpilot_view_inventory', static fn($r) => Inventory::zoneView($s['inventory']->zone($r['zone_id'])));
        $this->route('PATCH', "/admin/zones/(?P<zone_id>{$id})", 'adpilot_manage_inventory', static fn($r) => $s['inventory']->updateZone(get_current_user_id(), $r['zone_id'], self::body($r)));
        $this->route('GET', '/admin/containers', 'adpilot_view_inventory', function ($r) use ($s) {
            $where = [];
            if ($r->get_param('zone_id')) { $where['zone_id'] = $s['inventory']->zone((string) $r['zone_id'])['id']; }
            return $this->page($r, 'containers', $where, [$s['inventory'], 'containerView']);
        });
        $this->route('POST', '/admin/containers', 'adpilot_manage_inventory', static fn($r) => $s['inventory']->createContainer(get_current_user_id(), self::body($r)));
        $this->route('PATCH', "/admin/containers/(?P<container_id>{$id})", 'adpilot_manage_inventory', static fn($r) => $s['inventory']->updateContainer(get_current_user_id(), $r['container_id'], self::body($r)));
        $this->route('GET', '/admin/slots', 'adpilot_view_inventory', function ($r) use ($s) {
            $where = [];
            foreach (['zone_id' => 'zones', 'container_id' => 'containers'] as $key => $table) {
                if ($r->get_param($key)) {
                    $row = $s['store']->one($table, ['public_id' => (string) $r[$key]]) ?? throw new Problem('not_found', 'Inventory unavailable.', 404);
                    $where[$key] = $row['id'];
                }
            }
            return $this->page($r, 'slots', $where, [$s['inventory'], 'slotView']);
        });
        $this->route('GET', '/catalog/placements', 'public', fn($r) => $this->page($r, 'slots', ['status' => 'published'], [$s['inventory'], 'slotView']));
        $this->route('GET', '/catalog/formats', 'public', static fn() => self::envelope([['id' => 'native', 'name' => 'Native text', 'safe_text_only' => true]]));
        $rateView = static fn(array $row): array => Pricing::rateView($row) + ['slot_id' => ($s['store']->one('slots', ['id' => $row['slot_id']])['public_id'] ?? null)];
        $this->route('GET', '/catalog/packages', 'public', function ($r) use ($s, $rateView) {
            $where = ['_catalog' => true];
            if ($r->get_param('slot_id')) {
                $slot = $s['inventory']->slot((string) $r['slot_id']);
                if ($slot['status'] !== 'published') { throw new Problem('not_found', 'Published placement unavailable.', 404); }
                $where['slot_id'] = $slot['id'];
            }
            return $this->page($r, 'rate_card_versions', $where, $rateView);
        });
        $this->route('GET', '/admin/rate-cards', 'adpilot_manage_rates', fn($r) => $this->page($r, 'rate_card_versions', [], $rateView));
        $this->route('POST', '/admin/rate-cards', 'adpilot_manage_rates', static fn($r) => $s['pricing']->publish(get_current_user_id(), self::body($r)));
        $this->route('POST', "/organizations/(?P<org_id>{$id})/quotes", 'authenticated', static fn($r) => $s['pricing']->quote(get_current_user_id(), $r['org_id'], self::body($r)));
        $this->route('GET', "/organizations/(?P<org_id>{$id})/quotes/(?P<quote_id>{$id})", 'authenticated', static function ($r) use ($s) {
            $org = $s['organizations']->authorized(get_current_user_id(), $r['org_id'], 'purchase');
            return Pricing::quoteView($s['store']->one('quotes', ['public_id' => $r['quote_id'], 'organization_id' => $org['id']]) ?? throw new Problem('not_found', 'Quote unavailable.', 404));
        });
        $holdView = static fn(array $h): array => ['id' => $h['public_id'], 'status' => $h['status'] === 'active' && $h['expires_at'] <= gmdate('Y-m-d H:i:s') ? 'expired' : $h['status'], 'expires_at' => $h['expires_at'], 'start_at' => $h['starts_at'], 'end_at' => $h['ends_at'], 'row_version' => (int) $h['row_version']];
        $this->route('POST', "/organizations/(?P<org_id>{$id})/holds", 'authenticated', static function ($r) use ($s, $holdView) {
            $org = $s['organizations']->authorized(get_current_user_id(), $r['org_id'], 'purchase');
            $quote = $s['store']->one('quotes', ['public_id' => Input::text(self::body($r), 'quote_id', 26), 'organization_id' => $org['id']]) ?? throw new Problem('not_found', 'Quote unavailable.', 404);
            return $holdView($s['reservations']->hold(get_current_user_id(), (int) $org['id'], $quote, (string) $r->get_header('Idempotency-Key')));
        });
        $this->route('GET', "/organizations/(?P<org_id>{$id})/holds/(?P<hold_id>{$id})", 'authenticated', static function ($r) use ($s, $holdView) {
            $org = $s['organizations']->authorized(get_current_user_id(), $r['org_id'], 'purchase');
            return $holdView($s['store']->one('inventory_holds', ['public_id' => $r['hold_id'], 'organization_id' => $org['id']]) ?? throw new Problem('not_found', 'Hold unavailable.', 404));
        });
        $this->route('POST', "/organizations/(?P<org_id>{$id})/holds/(?P<hold_id>{$id})/actions/release", 'authenticated', static function ($r) use ($s, $holdView) {
            $org = $s['organizations']->authorized(get_current_user_id(), $r['org_id'], 'purchase');
            return $s['store']->transaction(function () use ($r, $s, $org, $holdView) {
                $hold = $s['store']->one('inventory_holds', ['public_id' => $r['hold_id'], 'organization_id' => $org['id']], true) ?? throw new Problem('not_found', 'Hold unavailable.', 404);
                if ($hold['status'] === 'active') {
                    $hold = $s['store']->update('inventory_holds', $hold, ['status' => 'released']);
                    $s['audit']->record(get_current_user_id(), 'hold.released', 'hold', $hold['public_id']);
                }
                return $holdView($hold);
            });
        });
        $this->route('POST', "/organizations/(?P<org_id>{$id})/orders", 'authenticated', static function ($r) use ($s) {
            $org = $s['organizations']->authorized(get_current_user_id(), $r['org_id'], 'purchase');
            $hold = $s['store']->one('inventory_holds', ['public_id' => Input::text(self::body($r), 'hold_id', 26), 'organization_id' => $org['id']]) ?? throw new Problem('not_found', 'Hold unavailable.', 404);
            return Commerce::view($s['commerce']->createOrder(get_current_user_id(), (int) $org['id'], $hold));
        });
        $this->route('GET', "/organizations/(?P<org_id>{$id})/orders", 'authenticated', function ($r) use ($s) {
            $org = $s['organizations']->authorized(get_current_user_id(), $r['org_id'], 'finance');
            return $this->page($r, 'advertising_orders', ['organization_id' => $org['id']], [Commerce::class, 'view']);
        });
        $this->route('GET', "/organizations/(?P<org_id>{$id})/orders/(?P<order_id>{$id})", 'authenticated', static function ($r) use ($s) {
            $org = $s['organizations']->authorized(get_current_user_id(), $r['org_id'], 'finance');
            return Commerce::view($s['store']->one('advertising_orders', ['public_id' => $r['order_id'], 'organization_id' => $org['id']]) ?? throw new Problem('not_found', 'Order unavailable.', 404));
        });
        $this->route('POST', "/organizations/(?P<org_id>{$id})/orders/(?P<order_id>{$id})/actions/start-checkout", 'authenticated', static function ($r) use ($s) {
            $org = $s['organizations']->authorized(get_current_user_id(), $r['org_id'], 'purchase');
            $order = $s['store']->one('advertising_orders', ['public_id' => $r['order_id'], 'organization_id' => $org['id']]) ?? throw new Problem('not_found', 'Order unavailable.', 404);
            return $s['woo']->start($order, get_current_user_id());
        });
        $this->route('GET', '/admin/orders', 'adpilot_view_orders', fn($r) => $this->page($r, 'advertising_orders', [], [Commerce::class, 'view']));
        $this->route('GET', '/admin/ledger', 'adpilot_view_finance', fn($r) => $this->page($r, 'financial_ledger_entries', [], static fn($row) => ['id' => $row['public_id'], 'currency' => $row['currency'], 'amount_minor' => (string) $row['amount_minor'], 'entry_type' => $row['entry_type'], 'posted_at' => $row['posted_at']]));
        $this->route('GET', '/public/feature-capabilities', 'public', static fn() => Settings::availability());

        $this->route('GET', '/admin/layouts', 'adpilot_view_inventory', fn($r) => $this->page($r, 'layout_drafts', [], [Layouts::class, 'view']));
        $this->route('POST', '/admin/layouts/drafts', 'adpilot_manage_inventory', static fn($r) => $s['layouts']->draft(get_current_user_id(), self::body($r)));
        $this->route('GET', "/admin/layouts/drafts/(?P<draft_id>{$id})", 'adpilot_view_inventory', static fn($r) => Layouts::view($s['store']->one('layout_drafts', ['public_id' => $r['draft_id']]) ?? throw new Problem('not_found', 'Draft unavailable.', 404)));
        $this->route('PATCH', "/admin/layouts/drafts/(?P<draft_id>{$id})", 'adpilot_manage_inventory', static fn($r) => $s['layouts']->save(get_current_user_id(), $r['draft_id'], self::body($r)));
        $this->route('POST', "/admin/layouts/drafts/(?P<draft_id>{$id})/actions/validate", 'adpilot_manage_inventory', static fn($r) => $s['layouts']->validate($r['draft_id']));
        $this->route('POST', "/admin/layouts/drafts/(?P<draft_id>{$id})/actions/publish", 'adpilot_publish_layout', static fn($r) => $s['layouts']->publish(get_current_user_id(), $r['draft_id'], Input::integer(self::body($r), 'row_version', 1, PHP_INT_MAX)));
        $this->route('GET', "/admin/layouts/(?P<layout_id>{$id})/revisions", 'adpilot_view_inventory', fn($r) => $this->page($r, 'layout_revisions', ['scope_key' => $r['layout_id']], static fn($row) => ['id' => $row['public_id'], 'revision' => (int) $row['revision_number'], 'published_at' => $row['published_at']]));

        $this->route('GET', '/admin/campaigns', 'adpilot_view_campaigns', fn($r) => $this->page($r, 'campaigns', [], [$s['campaigns'], 'view']));
        $this->route('GET', "/organizations/(?P<org_id>{$id})/campaigns", 'authenticated', function ($r) use ($s) {
            $org = $s['organizations']->authorized(get_current_user_id(), $r['org_id']);
            return $this->page($r, 'campaigns', ['organization_id' => $org['id']], [$s['campaigns'], 'view']);
        });
        $this->route('POST', "/organizations/(?P<org_id>{$id})/campaigns", 'authenticated', static fn($r) => $s['campaigns']->create(get_current_user_id(), $r['org_id'], self::body($r)));
        foreach (['pause', 'resume', 'emergency-stop'] as $action) {
            $this->route('POST', "/admin/campaigns/(?P<campaign_id>{$id})/actions/{$action}", $action === 'emergency-stop' ? 'adpilot_emergency_stop' : 'adpilot_manage_campaigns', static fn($r) =>
                $s['campaigns']->action(get_current_user_id(), $r['campaign_id'], $action, Input::text(self::body($r), 'reason', 1000)));
        }
        $this->route('POST', "/admin/campaigns/(?P<campaign_id>{$id})/actions/schedule", 'adpilot_manage_campaigns', static function ($r) use ($s) {
            $settings = Settings::read()['settings'];
            if (!empty(self::body($r)['order_id'])) {
                return $s['campaigns']->allocatePaid(get_current_user_id(), $r['campaign_id'], Input::text(self::body($r), 'order_id', 26), Input::text(self::body($r), 'reason', 1000));
            }
            return $s['campaigns']->complimentary(get_current_user_id(), $r['campaign_id'], Input::text(self::body($r), 'reason', 1000), $settings['manual_entitlements_enabled'] && $settings['manual_policy_ref'] !== '');
        });

        $this->route('GET', "/organizations/(?P<org_id>{$id})/creatives", 'authenticated', function ($r) use ($s) {
            $org = $s['organizations']->authorized(get_current_user_id(), $r['org_id']);
            return $this->page($r, 'creative_versions', ['organization_id' => $org['id']], [Creatives::class, 'view']);
        });
        $this->route('POST', "/organizations/(?P<org_id>{$id})/creatives", 'authenticated', static fn($r) => $s['creatives']->create(get_current_user_id(), $r['org_id'], self::body($r)));
        $this->route('POST', "/organizations/(?P<org_id>{$id})/creatives/(?P<creative_id>{$id})/versions", 'authenticated', static fn($r) => $s['creatives']->revise(get_current_user_id(), $r['org_id'], $r['creative_id'], self::body($r)));
        $this->route('POST', "/organizations/(?P<org_id>{$id})/creatives/(?P<creative_id>{$id})/actions/submit-review", 'authenticated', static fn($r) => $s['creatives']->submit(get_current_user_id(), $r['org_id'], $r['creative_id']));
        $this->route('GET', '/admin/creative-reviews', 'adpilot_moderate_creatives', fn($r) => $this->page($r, 'creative_versions', ['status' => 'submitted'], [Creatives::class, 'view']));
        foreach (['approve' => 'approved', 'reject' => 'rejected', 'request-changes' => 'changes_requested'] as $action => $verdict) {
            $this->route('POST', "/admin/creative-reviews/(?P<review_id>{$id})/actions/{$action}", 'adpilot_moderate_creatives', static fn($r) => $s['creatives']->review(get_current_user_id(), $r['review_id'], $verdict, Input::text(self::body($r), 'reason', 1000)));
        }

        $this->route('POST', '/delivery/decisions', 'public', static function ($r) use ($s) {
            $input = self::body($r);
            if (!isset($input['opportunities']) || !is_array($input['opportunities'])) { throw new Problem('invalid_batch', 'Render opportunities are required.'); }
            return $s['delivery']->decide($input['opportunities'], Settings::measurementAllowed());
        });
        $this->route('POST', '/delivery/click', 'public', static fn($r) => $s['delivery']->signal(Input::text(self::body($r), 'decision_ref', 4096), 'click', Settings::measurementAllowed()));
        $this->route('POST', '/measurement/events', 'public', static function ($r) use ($s) {
            $events = self::body($r)['events'] ?? null;
            if (!is_array($events) || count($events) > 20) { throw new Problem('batch_limit', 'At most 20 event claims per batch.'); }
            $results = [];
            foreach ($events as $event) {
                if (!is_array($event)) { throw new Problem('invalid_event', 'Every event must be a JSON object.'); }
                $results[] = $s['delivery']->signal(Input::text($event, 'decision_ref', 4096), Input::text($event, 'type', 20), Settings::measurementAllowed());
            }
            return ['items' => $results, 'billable' => false];
        });
        $this->route('GET', '/measurement/client-config', 'public', static fn() => ['enabled' => Settings::measurementAllowed(), 'supported_events' => ['rendered', 'click'], 'billable' => false]);
        $this->route('GET', '/admin/reports/overview', 'adpilot_view_aggregate_analytics', static fn() => [
            'zones' => $s['store']->count('zones'), 'slots' => $s['store']->count('slots'), 'campaigns' => $s['store']->count('campaigns'),
            'pending_reviews' => $s['store']->count('creative_versions', ['status' => 'submitted']), 'served' => $s['store']->count('delivery_events', ['event_type' => 'served']),
            'rendered' => $s['store']->count('delivery_events', ['event_type' => 'rendered_claim']), 'clicks' => $s['store']->count('delivery_events', ['event_type' => 'click']),
            'revenue_minor' => null, 'currency' => null, 'updated_at' => gmdate('c'), 'rendered_definition' => 'Untrusted browser render claims; never billable.']);
        $this->route('GET', "/organizations/(?P<org_id>{$id})/reports/campaigns", 'authenticated', function ($r) use ($s) {
            $org = $s['organizations']->authorized(get_current_user_id(), $r['org_id']);
            return $this->page($r, 'campaigns', ['organization_id' => $org['id']], function ($c) use ($s) {
                return $s['campaigns']->view($c) + ['served' => $s['store']->count('delivery_events', ['campaign_id' => $c['id'], 'event_type' => 'served']),
                    'rendered_claims' => $s['store']->count('delivery_events', ['campaign_id' => $c['id'], 'event_type' => 'rendered_claim'])];
            });
        });
        $this->route('GET', '/admin/settings', 'adpilot_manage_settings', static fn() => Settings::read());
        $this->route('PATCH', '/admin/settings', 'adpilot_manage_settings', static function ($r) use ($s) {
            $body = self::body($r);
            if (array_intersect(array_keys($body['settings'] ?? []), ['measurement_enabled', 'measurement_policy_ref', 'event_retention_days']) && !current_user_can('adpilot_manage_privacy')) {
                throw new Problem('privacy_permission_required', 'Privacy configuration requires its dedicated capability.', 403);
            }
            if (array_key_exists('emergency_stop', $body['settings'] ?? []) && !current_user_can('adpilot_emergency_stop')) {
                throw new Problem('stop_permission_required', 'Emergency controls require the dedicated safety capability.', 403);
            }
            $settings = $s['store']->transaction(function () use ($s, $body): array {
                $result = Settings::save($body);
                $s['audit']->record(get_current_user_id(), 'settings.updated', 'settings', 'site');
                return $result;
            });
            wp_cache_delete('adpilot_configuration', 'options');
            wp_cache_delete('alloptions', 'options');
            return $settings;
        });
        $this->route('GET', '/admin/feature-availability', 'adpilot_manage_settings', static fn() => Settings::availability());
        $this->route('GET', '/admin/diagnostics', 'adpilot_view_diagnostics', static fn() => ['version' => ADPILOT_VERSION,
            'schema_version' => (int) get_option('adpilot_schema_version'), 'transactional_storage' => $s['store']->transactional(),
            'woocommerce' => class_exists('WooCommerce'), 'environment' => wp_get_environment_type(), 'pending_outbox' => $s['store']->count('outbox_messages', ['status' => 'pending']),
            'scheduler_next_at' => wp_next_scheduled('adpilot_maintenance') ?: null, 'features' => Settings::availability()]);
        $this->route('GET', '/admin/audit-log', 'adpilot_view_audit_log', fn($r) => $this->page($r, 'audit_log', [], static fn($row) => ['id' => $row['public_id'], 'action' => $row['action'], 'object_type' => $row['object_type'], 'object_id' => $row['object_public_id'], 'created_at' => $row['created_at']]));
        $this->route('POST', "/admin/jobs/(?P<job_id>{$id})/actions/retry", 'adpilot_retry_jobs', static fn($r) => $s['outbox']->retry($r['job_id']));

        $editorPermission = 'adpilot_insert_blocks';
        $this->route('GET', '/editor/zones', $editorPermission, fn($r) => $this->page($r, 'zones', ['status' => 'published'], [Inventory::class, 'zoneView']));
        $this->route('GET', "/editor/zones/(?P<zone_id>{$id})/preview", $editorPermission, static fn($r) => ['html' => \AdPilot\Platform\Plugin::safeRender('zone', $r['zone_id'], true), 'billable' => false]);
        $this->route('POST', '/editor/placement-config/validate', $editorPermission, static function ($r) use ($s) {
            $body = self::body($r); $attrs = $body['attributes'] ?? [];
            if (!in_array($body['block'] ?? '', ['adpilot/ad-slot', 'adpilot/ad-zone', 'adpilot/ad-repeater', 'adpilot/ad-showcase'], true) || !is_array($attrs)) { throw new Problem('invalid_block', 'Unsupported placement block.'); }
            if (($body['block'] ?? '') === 'adpilot/ad-slot') {
                $slot = $s['inventory']->slot(Input::text($attrs, 'slotId', 26));
                if ($slot['status'] !== 'published') { throw new Problem('unpublished_slot', 'Choose a published slot.'); }
            } elseif (!empty($attrs['containerId'])) {
                $container = $s['store']->one('containers', ['public_id' => Input::text($attrs, 'containerId', 26), 'status' => 'published']);
                if (!$container) { throw new Problem('unpublished_container', 'Choose a published container.'); }
            } else {
                $zone = $s['inventory']->zone(Input::text($attrs, 'zoneId', 26));
                if ($zone['status'] !== 'published') { throw new Problem('unpublished_zone', 'Choose a published zone.'); }
            }
            if (($body['block'] ?? '') === 'adpilot/ad-repeater') { Input::integer($attrs, 'interval', 1, 100, 5); }
            return ['valid' => true, 'billable' => false];
        });
    }
}
