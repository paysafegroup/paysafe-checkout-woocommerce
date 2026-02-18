<?php
namespace Paysafe\PhpSdk\Logging;
use \Paysafe\PhpSdk\Logging\Interfaces\PaysafeLogCensorshipFilterInterface;

class PaysafeLogCensorship {
    /**
     * Applies censorship based on the supplied filters
     *
     * @param array $filters The filters to apply
     * @param string $message Message to censor
     *
     * @return string Censored message
     *
     * @throws \PaysafeException
     */
    public function applyCensorship(array $filters, string $message): string {
        foreach($filters as $filter) {
            if(!$filter instanceof PaysafeLogCensorshipFilterInterface) {
                throw new \PaysafeException("Supplied filter does not implement PaysafeLogCensorshipFilterInterface");
            }
            $message = $filter->censor($message);
        }
        return $message;
    }
}