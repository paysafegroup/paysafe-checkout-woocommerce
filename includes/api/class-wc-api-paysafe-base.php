<?php

use Paysafe\PhpSdk\Exceptions\PaysafeApiException;
use Paysafe\PhpSdk\Logging\Interfaces\PaysafeLoggerInterface;
use Paysafe\PhpSdk\Logging\PaysafeLoggerProvider;

abstract class PaysafeApiBasePluginConnector
{
    /** @var \Paysafe\PhpSdk\Connectors\PaysafeApiBaseConnector  */
    protected $connector;

    protected ?PaysafeLoggerInterface $logger;

    /**
     * Woocommerce adaptation to the Paysafe PHP SDK api connectors
     *
     * @throws PaysafeApiException
     */
    public function __construct(array $data = null)
    {
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/paysafe-sdk/Logging/Interfaces/PaysafeLoggerInterface.php';
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/paysafe-sdk/Logging/PaysafeLoggerProvider.php';

        require_once PAYSAFE_WOO_PLUGIN_PATH . '/paysafe-sdk/Exceptions/PaysafeApiException.php';
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/paysafe-sdk/Result/PaysafeApiResult.php';
        require_once PAYSAFE_WOO_PLUGIN_PATH . '/paysafe-sdk/Connectors/PaysafeApiBaseConnector.php';

        require_once PAYSAFE_WOO_PLUGIN_PATH . '/includes/exception/class-wc-exception-paysafe.php';

        if ($data) {
            $settingsToUse = $data;
        } else {
            $paysafeWcOptions = get_option(PAYSAFE_SETTINGS_KEYWORD, []);

            if (empty($paysafeWcOptions)) {
                throw new PaysafeApiException(
                    esc_html('options unreachable'),
                    esc_html(PaysafeApiException::OPTIONS_EMPTY)
                );
            }

            $settingsToUse = $paysafeWcOptions;
        }


        if (($settingsToUse['debug_log_enabled'] ?? null) === 'yes') {
            $this->logger = (new PaysafeLoggerProvider())->logger;
        } else {
            $this->logger = null;
        }

        $testMode = ($settingsToUse['test_mode'] ?? null) === 'yes';

        $isTestMode = !empty($testMode) && (bool)$testMode;

        if ($isTestMode) {
            $apiKey = base64_encode($settingsToUse['test_private_api_key'] ?? '') ?? null;
        } else {
            $apiKey = base64_encode($settingsToUse['live_private_api_key'] ?? '') ?? null;
        }

        if ($this instanceof PaysafeApiGeneralPluginConnector) {
            require_once PAYSAFE_WOO_PLUGIN_PATH . '/paysafe-sdk/Connectors/PaysafeApiGeneralConnector.php';
            $this->connector = new \Paysafe\PhpSdk\Connectors\PaysafeApiGeneralConnector([
                'api_key'       => $apiKey,
                'is_test_mode'  => $isTestMode,
            ], $this->logger);
        }

        if ($this instanceof PaysafeApiCardPluginConnector) {
            if (empty($apiKey)) {
                throw new PaysafeApiException(
                    esc_html('password not set'),
                    esc_html(PaysafeApiException::API_KEY_MISSING)
                );
            }
            require_once PAYSAFE_WOO_PLUGIN_PATH.'/paysafe-sdk/Connectors/PaysafeApiCardConnector.php';
            $this->connector = new \Paysafe\PhpSdk\Connectors\PaysafeApiCardConnector([
                'api_key'      => $apiKey,
                'is_test_mode' => $isTestMode,
            ], $this->logger);
        }
    }

    /**
     * Log errors with our logger
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        if ($this->logger !== null) {
            $this->logger->error($message, $context);
        }
    }
}
