<?php

namespace Paysafe\PhpSdk\Exceptions;

use Throwable;

class PaysafeApiException extends \Exception {
    const OPTIONS_EMPTY = 1001;
    const API_KEY_MISSING = 1002;

    const API_CALL_FAILED = 2001;
    const API_RESPONSE_INVALID = 2002;
    const API_INVALID_CREDENTIALS = 2003;

    const API_FIELD_VALIDATION_ERROR = 3001;

    private array|null $additionalData = null;

    /**
     * Paysafe custom exception
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param array|null $additionalData
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null,
        array $additionalData = null
    )
    {
        parent::__construct($message, $code, $previous);

        $this->additionalData = $additionalData;
    }

    /**
     * Return our additional data array
     *
     * @return array
     */
    public function getAdditionalData(): array
    {
        return $this->additionalData ?? [];
    }

    /**
     * Set our additional data array
     *
     * @param array $additionalData
     *
     * @return $this
     */
    public function setAdditionalData(array $additionalData): self
    {
        $this->additionalData = $additionalData;

        return $this;
    }
}