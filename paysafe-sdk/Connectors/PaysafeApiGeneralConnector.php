<?php

namespace Paysafe\PhpSdk\Connectors;

use Paysafe\PhpSdk\Exceptions\PaysafeApiException;
use Paysafe\PhpSdk\Result\PaysafeApiResult;
use Paysafe\PhpSdk\Logging\Interfaces\PaysafeLoggerInterface;

class PaysafeApiGeneralConnector extends PaysafeApiBaseConnector
{
    // define general purpose endpoints
    const ENDPOINT_MONITOR = 'paymenthub/v1/monitor';
    const ENDPOINT_GET_PAYMENT_METHODS = 'paymenthub/v1/paymentmethods';
    const CUSTOMER_URL_VARIABLE = '{customerId}';
    const ENDPOINT_GET_CUSTOMER_PROFILE = 'paymenthub/v1/customers/{customerId}';

    /**
     * Initiate the general payment gateway api connector
     *
     * @param array $config
     * @param PaysafeLoggerInterface|null $logger
     *
     * @throws PaysafeApiException
     */
    public function __construct(array $config, ?PaysafeLoggerInterface $logger = null)
    {
        parent::__construct($config, $logger);
    }

    /**
     * Test paysafe api status (is it working or not)
     *
     * @return bool
     *
     * @throws PaysafeApiException
     */
    public function monitor(): bool
    {
        $this->logApiDebug("General Connector: Monitor");

        $response = $this->get(
            self::ENDPOINT_MONITOR
        );

        return $response->getStatus() === 'READY';
    }

    /**
     * Get paysafe customer by customer id
     *
     * @param string $customerId
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function getPaysafeCustomer(string $customerId): PaysafeApiResult
    {
        $this->logApiDebug("General Connector: get customer for customer ID: $customerId");

        return $this->get(
            str_replace(
                [self::CUSTOMER_URL_VARIABLE],
                [$customerId],
                self::ENDPOINT_GET_CUSTOMER_PROFILE
            )
        );
    }

    /**
     * Get Paysafe API payment methods for a currency
     *
     * @param array $params
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function getPaymentMethods(array $params = []): PaysafeApiResult
    {
        $this->logApiDebug("General Connector: Get Payment Methods");

        return $this->get(
            self::ENDPOINT_GET_PAYMENT_METHODS,
            $this->validateGetPaymentMethodsParams($params)
        );
    }

    /**
     * Validate Paysafe API Get Payment Methods parameters
     *
     * @param array $params
     *
     * @return array
     *
     * @throws PaysafeApiException
     */
    public function validateGetPaymentMethodsParams(array $params): array
    {
        return $this->validateParams($params, [
            'currencyCode'      => self::VALIDATION_FIELD_REQUIRED,
        ]);
    }

}
