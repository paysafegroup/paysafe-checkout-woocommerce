<?php

class Paysafe_Admin_REST_Controller
{
    public static function test_connection(\WP_REST_Request $request): \WP_REST_Response
    {
        $data = $request->get_json_params();
        if (!is_array($data)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Invalid request payload.', 'paysafe-checkout'),
            ], 400);
        }

        $gateway = new WC_Gateway_Paysafe();
        $result  = $gateway->test_connection_credentials($data);

        if ($result['success']) {
            $env       = (!empty($data['test_mode']) && $data['test_mode'] === 'yes') ? 'sandbox' : 'live';
            $envSuffix = ($env === 'live') ? '_live' : '_sandbox';
            $currency  = get_woocommerce_currency();

            // Start from gateway newSettings, then overlay the JUST-entered keys
            $ns = is_array($result['newSettings'] ?? null) ? $result['newSettings'] : [];
            if ($env === 'sandbox') {
                if (array_key_exists('test_public_api_key',  $data)) $ns['test_public_api_key']  = (string)$data['test_public_api_key'];
                if (array_key_exists('test_private_api_key', $data)) $ns['test_private_api_key'] = (string)$data['test_private_api_key'];
            } else {
                if (array_key_exists('live_public_api_key',  $data)) $ns['live_public_api_key']  = (string)$data['live_public_api_key'];
                if (array_key_exists('live_private_api_key', $data)) $ns['live_private_api_key'] = (string)$data['live_private_api_key'];
            }

            // Build a canonical, env-scoped PM tree from the fresh paymentMethods list
            $treeFromApi = self::pm_list_to_tree(($result['paymentMethods'] ?? []), $currency);

            // Write it under payment_methods_{env} (do NOT keep the legacy unsuffixed tree)
            if (!isset($ns["payment_methods_{$env}"])) {
                $ns["payment_methods_{$env}"] = [];
            }
            $ns["payment_methods_{$env}"][$currency] = $treeFromApi;

            // Normalize away legacy/empty PM keys so defaults can flow
            $ns = self::normalize_pm_values($ns);

            // Flatten using the in-memory ns (no DB reads)
            $all_fields = self::get_settings($ns, true);

            // Hard-reset the ACTIVE env’s PM + modal values to defaults (so UI shows fresh defaults)
            $all_fields_defaults = array_map(function ($f) use ($envSuffix) {
                $key   = (string)($f['key'] ?? '');
                $group = strtolower($f['group'] ?? '');
                $isPM         = str_starts_with($key, 'payment_method_') || str_starts_with($key, 'account_id_');
                $isModalGroup = in_array($group, ['card','applepay','googlepay','neteller'], true);
                $matchesEnv   = (!preg_match('/_(sandbox|live)$/i', $key)) || str_ends_with($key, $envSuffix);

                if (($isPM || $isModalGroup) && $matchesEnv) {
                    if (!array_key_exists('default', $f) || $f['default'] === '' || $f['default'] === null) {
                        $f['default'] = self::synthesize_default_for_field($f);
                    }
                    $f['value'] = $f['default'];
                }
                return $f;
            }, $all_fields);

            // Compact list for visible rows in the active env
            $payment_method_fields = array_values(array_filter($all_fields_defaults, function ($f) use ($env) {
                $key   = $f['key'] ?? '';
                $group = strtolower($f['group'] ?? '');

                $isPmOrAccount = str_starts_with($key, 'payment_method_') || str_starts_with($key, 'account_id_');
                $isWebhook     = str_starts_with($key, 'webhook_');
                $inPmGroups    = in_array($group, ['card','applepay','googlepay','neteller'], true);

                // env-gated keys (…_sandbox/_live) must match current env; unsuffixed pass for BC
                $envOk = true;
                if (preg_match('/_(sandbox|live)$/i', $key, $m)) {
                    $envOk = strtolower($m[1]) === $env;
                }

                return ($isPmOrAccount && $envOk) || ($inPmGroups && $envOk) || $isWebhook;
            }));


            // Return corrected structures
            $result['newSettings']           = $ns;
            $result['payment_method_fields'] = $payment_method_fields;
            $result['full_fields_defaults']  = $all_fields_defaults;
        }

        return new \WP_REST_Response($result, $result['success'] ? 200 : 400);
    }

    /**
     * Build canonical tree from gateway `paymentMethods` for one Woo currency.
     * Output shape matches PaysafeSettings::build_payment_fields_for_env expectations.
     */
    private static function pm_list_to_tree(array $pmList, string $currency): array
    {
        $want = strtoupper($currency);
        $tree = [];

        $ensure = static function (array &$t, string $method) {
            if (!isset($t[$method])) {
                $t[$method] = [
                    'name'      => $method,
                    'available' => true,
                    'accounts'  => [],
                ];
            }
        };

        foreach ($pmList as $row) {
            $cur = strtoupper((string)($row['currencyCode'] ?? $row['currency'] ?? ''));
            if ($cur !== $want) {
                continue;
            }

            $method    = strtoupper((string)($row['paymentMethod'] ?? ''));
            $accountId = (string)($row['accountId'] ?? '');
            if ($method === '' || $accountId === '') {
                continue;
            }

            // Common account info
            $acc = [
                'account_id'      => $accountId,
                'processor'       => (string)($row['processorCode'] ?? ''),
                'mcc'             => (string)($row['mcc'] ?? ''),
                'mcc_description' => (string)($row['mccDescription'] ?? ''),
            ];

            // Always add the raw method (CARD will be here)
            $ensure($tree, $method);
            $tree[$method]['accounts'][$accountId] = $acc;

            // Fan-out Apple Pay / Google Pay based on CARD account flags
            // (Paysafe exposes these as booleans per CARD account)
            $cfg         = is_array($row['accountConfiguration'] ?? null) ? $row['accountConfiguration'] : [];
            $isApplePay  = !empty($cfg['isApplePay']);
            $isGooglePay = !empty($cfg['isGooglePay']);

            if ($method === 'CARD') {
                if ($isApplePay) {
                    $ensure($tree, 'APPLEPAY');
                    $tree['APPLEPAY']['accounts'][$accountId] = $acc;
                }
                if ($isGooglePay) {
                    $ensure($tree, 'GOOGLEPAY');
                    $tree['GOOGLEPAY']['accounts'][$accountId] = $acc;
                }
            }
        }

        // Mark methods with no accounts as unavailable (so UI disables them)
        foreach ($tree as $m => &$node) {
            if (empty($node['accounts'])) {
                $node['available'] = false;
            }
        }

        return $tree;
    }

    private static function normalize_pm_values(array $settings): array
    {
        foreach (array_keys($settings) as $k) {
            // drop legacy unsuffixed PM/account keys
            if (preg_match('/^(payment_method|account_id)_[A-Z]{3}_[A-Z0-9]+$/', $k)) {
                unset($settings[$k]);
                continue;
            }
            // drop empty env-suffixed PM/account values so defaults take over
            if (preg_match('/^(payment_method|account_id)_[A-Z]{3}_[A-Z0-9_]+_(sandbox|live)$/', $k)) {
                if ($settings[$k] === '' || $settings[$k] === null) unset($settings[$k]);
            }
        }
        // drop legacy PM tree if present
        unset($settings['payment_methods']);
        return $settings;
    }

    private static function synthesize_default_for_field(array $f): string
    {
        $type = (string)($f['type'] ?? '');
        if ($type === 'paysafe_select') {
            $opts = $f['options'] ?? [];
            if (is_array($opts) && !empty($opts)) {
                $first = array_key_first($opts);
                return (string)($first ?? '');
            }
            return '';
        }
        if ($type === 'checkbox') return 'no';
        return '';
    }

    public static function handle_settings(WP_REST_Request $request)
    {
        $method = $request->get_method();

        if ($method === 'GET') {
            return self::get_settings();
        }
        if ($method === 'POST') {
            return self::save_settings($request->get_json_params());
        }

        return new WP_Error('invalid_method', 'Method not allowed', ['status' => 405]);
    }

    private static function get_settings(array $settings = null, bool $useProvidedValues = false): array
    {
        $form_fields = PaysafeSettings::get_paysafe_settings($settings);

        $values = ($useProvidedValues && is_array($settings))
            ? $settings
            : get_option(PAYSAFE_SETTINGS_KEYWORD, []);

        $flattened = [];

        foreach ($form_fields as $key => $field) {
            $flattened[] = [
                'key'               => $key,
                'type'              => $field['type'] ?? 'text',
                'title'             => $field['title'] ?? '',
                'label'             => $field['label'] ?? '',
                'description'       => $field['description'] ?? '',
                'value'             => array_key_exists($key, $values) ? $values[$key] : ($field['default'] ?? ''),
                'default'           => $field['default'] ?? null,
                'options'           => $field['options'] ?? [],
                'custom_attributes' => $field['custom_attributes'] ?? [],
                'group'             => $field['group'] ?? '',
            ];
        }

        return $flattened;
    }

    private static function save_settings(array $data): array {
        $settings    = get_option(PAYSAFE_SETTINGS_KEYWORD, []);
        $form_fields = PaysafeSettings::get_paysafe_settings($data);

        foreach ($data as $key => $value) {
            if ($key === 'payment_methods_sandbox' || $key === 'payment_methods_live') {
                $settings[$key] = $value; // store full trees per env
                continue;
            }

            $type           = $form_fields[$key]['type'] ?? 'text';
            $settings[$key] = $type === 'checkbox'
                ? ($value === 'yes' ? 'yes' : 'no')
                : (is_array($value) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value));
        }

        update_option(PAYSAFE_SETTINGS_KEYWORD, $settings);
        return ['success' => true];
    }
}
