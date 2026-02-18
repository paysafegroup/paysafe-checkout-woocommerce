<?php
namespace Paysafe\PhpSdk\Logging;
use \Psr\Log\LoggerInterface;
use \WC_Logger;
use \Paysafe\PhpSdk\Logging\Interfaces\PaysafeLoggerInterface;

class PaysafeLoggerAdapter extends PaysafeLogger implements PaysafeLoggerInterface
{
    private ?WC_Logger $logger = null;

    protected string $logSourceTag = "paysafe-checkout";

    /**
     * Constructor
     * 
     * @param WC_Logger|null $logger
     *
     * @return void
     */
    public function __construct(WC_Logger $logger = null)
    {
        if($logger === null) {
            $logger = \wc_get_logger();
        }

        $this->logger = $logger;
    }

    /**
     * Log a message of severity EMERGENCY
     *
     * @param \Stringable|string $message
     * @param array $context
     *
     * @return void
     */
    public function emergency(\Stringable|string $message, array $context = []): void {
        $message = $this->censor($message);
        $context['source'] = $this->logSourceTag;
        $this->logger->emergency($message, $context);
    }

    /**
     * Log a message of severity ALERT
     *
     * @param \Stringable|string $message
     * @param array $context
     *
     * @return void
     */
    public function alert(\Stringable|string $message, array $context = []): void {
        $message = $this->censor($message);
        $context['source'] = $this->logSourceTag;
        $this->logger->alert($message, $context);
    }

    /**
     * Log a message of severity CRITICAL
     *
     * @param \Stringable|string $message
     * @param array $context
     *
     * @return void
     */
    public function critical(\Stringable|string $message, array $context = []): void {
        $message = $this->censor($message);
        $context['source'] = $this->logSourceTag;
        $this->logger->critical($message, $context);
    }

    /**
     * Log a message of severity ERROR
     *
     * @param \Stringable|string $message
     * @param array $context
     *
     * @return void
     */
    public function error(\Stringable|string $message, array $context = []): void {
        $message = $this->censor($message);
        $context['source'] = $this->logSourceTag;
        $this->logger->error($message, $context);
    }

    /**
     * Log a message of severity WARNING
     *
     * @param \Stringable|string $message
     * @param array $context
     *
     * @return void
     */
    public function warning(\Stringable|string $message, array $context = []): void {
        $message = $this->censor($message);
        $context['source'] = $this->logSourceTag;
        $this->logger->warning($message, $context);
    }

    /**
     * Log a message of severity NOTICE
     *
     * @param \Stringable|string $message
     * @param array $context
     *
     * @return void
     */
    public function notice(\Stringable|string $message, array $context = []): void {
        $message = $this->censor($message);
        $context['source'] = $this->logSourceTag;
        $this->logger->notice($message, $context);
    }

    /**
     * Log a message of severity INFO
     *
     * @param \Stringable|string $message
     * @param array $context
     *
     * @return void
     */
    public function info(\Stringable|string $message, array $context = []): void {
        $message = $this->censor($message);
        $context['source'] = $this->logSourceTag;
        $this->logger->info($message, $context);
    }

    /**
     * Log a message of severity DEBUG
     *
     * @param \Stringable|string $message
     * @param array $context
     *
     * @return void
     */
    public function debug(\Stringable|string $message, array $context = []): void {
        $message = $this->censor($message);
        $context['source'] = $this->logSourceTag;
        $this->logger->debug($message, $context);
    }

    /**
     * Log a message with custom severity
     * 
     * Note: it is advisable to use the separate methods per severity instead of the log() method
     * as per the PSR-3 recommendations
     *
     * @param mixed $level
     * @param \Stringable|string $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, $message, array $context = []): void {
        $message = $this->censor($message);
        $context['source'] = $this->logSourceTag;
        $this->logger->log($level, $message, $context);
    }
}