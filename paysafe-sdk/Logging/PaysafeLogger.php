<?php
namespace Paysafe\PhpSdk\Logging;
use \Paysafe\PhpSdk\Logging\Interfaces\PaysafeLogCensorshipFilterInterface;
use \Paysafe\PhpSdk\Logging\Interfaces\PaysafeLoggerInterface;

abstract class PaysafeLogger implements PaysafeLoggerInterface
{
    private ?PaysafeLogCensorship $censorshipEngine = null;
    private array $filters = [];

    /**
     * Applies censorship to log message
     * 
     * @param string $message Input message
     * @return string Censored message
     */
    public function censor(string $message): string {
        if($this->censorshipEngine === null) {
            $this->censorshipEngine = new PaysafeLogCensorship();
        }
        return $this->censorshipEngine->applyCensorship($this->filters, $message);
    }

    /**
     * Adds a censorship filter
     * 
     * @param PaysafeLogCensorshipFilterInterface $filter
     * @return void
     */
    public function addFilter(PaysafeLogCensorshipFilterInterface $filter): void
    {
        $this->filters[] = $filter;
    }

}