<?php
namespace Paysafe\PhpSdk\Logging\Filters;

use \Paysafe\PhpSdk\Logging\Interfaces\PaysafeLogCensorshipFilterInterface;

class PaysafePANCensorshipFilter implements PaysafeLogCensorshipFilterInterface {
    /**
     * The replacement character
     */
    private string $replacement = '*';

    /**
     * Performs Luhn validation of input
     * 
     * @see https://en.wikipedia.org/wiki/Luhn_algorithm
     * 
     * @param mixed $input
     * @return bool
     */
    private function luhnValidate(mixed $input): bool
    {
        if (!is_numeric($input)) {
            return false;
        }

        $string = (string) preg_replace('/\D/', '', $input);
        $sum = 0;
        $digits = strlen($string) - 1;
        $parity = $digits % 2;
        for ($i = $digits; $i >= 0; $i--) {
            $digit = substr($string, $i, 1);
            if (!$parity == ($i % 2)) {
                $digit <<= 1;
            }
            $digit = ($digit > 9) ? ($digit - 9) : $digit;
            $sum += $digit;
        }

        return (0 == ($sum % 10));
    }

    /**
     * Will censor any PAN (Personal Account Number, i.e. Credit/Debit card number) 
     * in the input string and return it in format ************NNNN where N is number
     * 
     * @param string $message The log message we want to censor
     * @return string
     */
    public function censor(string $message): string 
    {
        $regex = '/(?:\d[ \t-]*?){13,19}/m';
        $matches = [];
        preg_match_all($regex, $message, $matches);

        // No PAN found in string
        if (!isset($matches[0]) || empty($matches[0])) {
            return $message;
        }

        foreach ($matches as $matchGroup) {
            foreach ($matchGroup as $match) {
                $numericMatch = preg_replace('/[^\d]/', '', $match);

                // If not a valid PAN, skip this match
                if ($this->luhnValidate($numericMatch) === false) {
                    continue;
                }

                $replacement = str_pad('', strlen($numericMatch) - 4,
                        $this->replacement) . substr($numericMatch, -4);
                $message = str_replace($match, $replacement, $message);
            }
        }

        return $message;
    }
}