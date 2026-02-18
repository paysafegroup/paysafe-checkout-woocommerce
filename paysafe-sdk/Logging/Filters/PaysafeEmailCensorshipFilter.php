<?php
namespace Paysafe\PhpSdk\Logging\Filters;

use \Paysafe\PhpSdk\Logging\Interfaces\PaysafeLogCensorshipFilterInterface;

class PaysafeEmailCensorshipFilter implements PaysafeLogCensorshipFilterInterface {
    /**
     * The replacement character
     */
    private string $replacement = '*';

    /**
     * Should we also censor domain?
     */
    private bool $censorDomain = false;

    /**
     * Will censor an individual email address
     * 
     * @param string $email Input email to censor
     * @return string Censored email
     */
    private function censorIndividual(string $email): string
    {
        $emailCensored = $email;

        $usernamePart = substr($email, 0, stripos($email, '@'));
        $emailCensored = str_replace($usernamePart, str_pad('', strlen($usernamePart), $this->replacement)
            , $emailCensored);

        if ($this->censorDomain) {
            $domainPart = substr($email, stripos($email, '@') + 1);
            $emailCensored = str_replace($domainPart, str_pad('', strlen($domainPart), $this->replacement)
                , $emailCensored);
        }

        return $emailCensored;
    }

    /**
     * Will censor any email addresses found in $message
     * 
     * @param string $message The log message we want to censor
     * @return string
     */
    public function censor(string $message): string 
    {
        $regex = "/(?:[a-z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+=?^_`{|}~-]+)*"
            . "|\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\")"
            . "@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]"
            . "*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}"
            . "(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:"
            . "(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/";

        $matches = [];
        preg_match_all($regex, $message, $matches);

        // If no emails are found
        if (!isset($matches[0]) || empty($matches[0])) {
            return $message;
        }

        foreach ($matches as $matchGroup) {
            foreach ($matchGroup as $match) {
                $message = str_replace($match, $this->censorIndividual($match), $message);
            }
        }

        return $message;
    }
}