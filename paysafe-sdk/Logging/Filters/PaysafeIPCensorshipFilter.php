<?php
namespace Paysafe\PhpSdk\Logging\Filters;

use \Paysafe\PhpSdk\Logging\Interfaces\PaysafeLogCensorshipFilterInterface;

class PaysafeIPCensorshipFilter implements PaysafeLogCensorshipFilterInterface {
    /**
     * The replacement character
     */
    private string $replacement = '*';

    /**
     * Function to expand IPv6 address into the full format
     * 
     * @param string $input The compact/shortened IPv6 address
     * @return string The expanded IPv6 address
     */
    private function expandIPv6Address(string $ip): string
    {
        $hex = unpack("H*hex", inet_pton($ip));         
        $ip = substr(preg_replace("/([A-f0-9]{4})/", "$1:", $hex['hex']), 0, -1);
    
        return $ip;
    }

    /**
     * Method that finds and censors IPv4 addresses in string
     * 
     * @param string $message The input string
     * @return string Censored string
     */
    private function censorIPv4Addresses(string $message): string
    {
        $regex = '/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/';

        $matches = [];
        preg_match_all($regex, $message, $matches);

        // No IPv4 found in string
        if (!isset($matches[0]) || empty($matches[0])) {
            return $message;
        }

        foreach ($matches as $match) {
            if(isset($match[0])) {
                $message = str_replace($match[0], preg_replace('/(?!\d{1,3}\.\d{1,3}\.)\d/',
                    $this->replacement, $match[0]), $message);
            }
        }

        return $message;
    }

    /**
     * Method that censors one single IPv6 address to first 3 octets
     * 
     * @param string $ip The original IPv6 address
     * @return string The censored IPv6 address
     */
    private function censorIPv6Address(string $ip): string
    {
        $first_segment = substr($ip, 0, 14);
        $rest_segments = implode(':', str_split(str_repeat($this->replacement, 20), 4));
        return $first_segment . ':' . $rest_segments;
    }

    /**
     * Method that finds, expands, and censors IPv6 addresses in string
     * 
     * @param string $message The input string
     * @return string Censored string
     */
    private function censorIPv6Addresses(string $message): string
    {
        $regex = '(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]'
            . '{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]'
            . '{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|'
            . '([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:'
            . '((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:)'
            . '{0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|'
            . '([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}'
            . '[0-9]){0,1}[0-9]))';

        $matches = [];
        preg_match_all($regex, $message, $matches);

        // No IPv6 found in string
        if (!isset($matches[0]) || empty($matches[0])) {
            return $message;
        }

        foreach ($matches as $match) {
            if(isset($match[0])) {
                $ip = $match[0];
                $expandedIP = $this->expandIPv6Address($ip);
                $censoredIP = $this->censorIPv6Address($expandedIP);
                $message = str_replace($ip, $censoredIP, $message);
            }
        }

        return $message;
    }

    /**
     * Will censor any IPv4 and IPv6 addresses in the $message
     * For IPv4 will return censored last two octets, like this: 192.168.***.***
     * For IPv6 will return censored 
     * 
     * @param string $message The log message we want to censor
     * @return string Censored log message
     */
    public function censor(string $message): string
    {
        // censor IPv4 address (last 2 octets)
        $message = $this->censorIPv4Addresses($message);

        // censor IPv6 addresses
        $message = $this->censorIPv6Addresses($message);

        return $message;
    }
}