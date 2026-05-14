<?php
namespace Paysafe\PhpSdk\Logging;
use Paysafe\PhpSdk\Exceptions\PaysafeApiException;
use Paysafe\PhpSdk\Logging\Interfaces\PaysafeLoggerInterface;
use Paysafe\PhpSdk\Logging\PaysafeLoggerAdapter;
use Paysafe\PhpSdk\Logging\Filters\PaysafePANCensorshipFilter;
use Paysafe\PhpSdk\Logging\Filters\PaysafeEmailCensorshipFilter;
use Paysafe\PhpSdk\Logging\Filters\PaysafeIPCensorshipFilter;

class PaysafeLoggerProvider {
    public ?PaysafeLoggerInterface $logger = null;

    /**
     * Performs setup of Paysafe Logging
     * 
     * @return PaysafeLoggerInterface
     */
    public function __construct() {
        if($this->logger === null) {
            require_once PAYSAFE_WOO_PLUGIN_PATH . '/paysafe-sdk/Logging/Interfaces/PaysafeLoggerInterface.php';
            require_once PAYSAFE_WOO_PLUGIN_PATH . '/paysafe-sdk/Logging/PaysafeLogger.php';
            require_once PAYSAFE_WOO_PLUGIN_PATH . '/paysafe-sdk/Logging/PaysafeLogCensorship.php';
            require_once PAYSAFE_WOO_PLUGIN_PATH . '/paysafe-sdk/Logging/PaysafeLoggerAdapter.php';
            require_once PAYSAFE_WOO_PLUGIN_PATH .
                '/paysafe-sdk/Logging/Interfaces/PaysafeLogCensorshipFilterInterface.php';
            require_once PAYSAFE_WOO_PLUGIN_PATH . '/paysafe-sdk/Logging/Filters/PaysafePANCensorshipFilter.php';
            require_once PAYSAFE_WOO_PLUGIN_PATH . '/paysafe-sdk/Logging/Filters/PaysafeEmailCensorshipFilter.php';
            require_once PAYSAFE_WOO_PLUGIN_PATH . '/paysafe-sdk/Logging/Filters/PaysafeIPCensorshipFilter.php';

            require_once PAYSAFE_WOO_PLUGIN_PATH . '/paysafe-sdk/Exceptions/PaysafeApiException.php';
            require_once PAYSAFE_WOO_PLUGIN_PATH . '/paysafe-sdk/Connectors/PaysafeApiBaseConnector.php';

            $paysafeWcOptions = get_option(PAYSAFE_SETTINGS_KEYWORD, []);

            $censorEnabled = ($paysafeWcOptions['mask_user_data'] ?? 'yes') === 'yes';

            $this->logger = new PaysafeLoggerAdapter();
            if ($censorEnabled) {
                $this->logger->addFilter(new PaysafePANCensorshipFilter());
                $this->logger->addFilter(new PaysafeEmailCensorshipFilter());
                $this->logger->addFilter(new PaysafeIPCensorshipFilter());
            }
        }

        return $this->logger;
    }
}
