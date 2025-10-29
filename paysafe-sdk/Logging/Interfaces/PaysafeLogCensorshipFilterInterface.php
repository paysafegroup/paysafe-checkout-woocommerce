<?php
namespace Paysafe\PhpSdk\Logging\Interfaces;

interface PaysafeLogCensorshipFilterInterface
{
    /**
     * Will apply filter
     * 
     * @param string $message The log message we want to censor
     * @return string
     */
    public function censor(string $message): string;
}