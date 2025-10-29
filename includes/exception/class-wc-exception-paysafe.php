<?php

class PaysafeException extends \Exception {
    const REFUND_CALL_UNKNOWN_STATUS = 90010;
    const VOID_CALL_UNKNOWN_STATUS = 90011;
    const SETTLEMENT_CALL_UNKNOWN_STATUS = 90012;
    const PROCESS_PAYMENT_CALL_UNKNOWN_STATUS = 90013;

    const CUSTOMER_SUT_CALL_UNKNOWN_STATUS = 90014;
    const CUSTOMER_NOT_CREATED = 90015;
    const CUSTOMER_CREATED_WITHOUT_MULTI_TOKEN = 90016;
    const CUSTOMER_HANDLE_CREATE_FAILED = 90017;

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