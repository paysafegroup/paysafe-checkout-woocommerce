<?php

namespace Paysafe\PhpSdk\Connectors;

use Paysafe\PhpSdk\Exceptions\PaysafeApiException;
use Paysafe\PhpSdk\Result\PaysafeApiResult;
use Paysafe\PhpSdk\Logging\Interfaces\PaysafeLoggerInterface;

class PaysafeApiCardConnector extends PaysafeApiBaseConnector
{
    // define CARD specific endpoints
    const ENDPOINT_VOID_AUTHORIZATION = 'paymenthub/v1/payments/{paymentId}/voidauths';
    const ENDPOINT_SETTLEMENT_PROCESS = 'paymenthub/v1/payments/{paymentId}/settlements';
    const ENDPOINT_REFUND_PROCESS = 'paymenthub/v1/settlements/{settlementId}/refunds';
    const ENDPOINT_GET_SETTLEMENT_DATA = 'paymenthub/v1/settlements/{settlementId}';
    const ENDPOINT_GET_PAYMENT_DATA = 'paymenthub/v1/payments/{paymentId}';
    const ENDPOINT_CANCEL_SETTLEMENT = 'paymenthub/v1/settlements/{settlementId}';
    const ENDPOINT_PROCESS_PAYMENT = 'paymenthub/v1/payments';
    const ENDPOINT_VERIFY_PAYMENT = 'paymenthub/v1/verifications';
    const ENDPOINT_PROCESS_PAYMENT_HANDLES = 'paymenthub/v1/paymenthandles';
    const ENDPOINT_CREATE_CUSTOMER = 'paymenthub/v1/customers';
    const ENDPOINT_GET_CUSTOMER_PROFILE_BY_MID = 'paymenthub/v1/customers';
    const ENDPOINT_GET_CUSTOMER_PROFILE = 'paymenthub/v1/customers/{customerId}';
    const ENDPOINT_CUSTOMER_SINGLE_USE_TOKEN = 'paymenthub/v1/customers/{customerId}/singleusecustomertokens';
    const ENDPOINT_CUSTOMER_PAYMENT_HANDLES = 'paymenthub/v1/customers/{customerId}/paymenthandles';
    const ENDPOINT_DELETE_PAYSAFE_TOKEN = 'paymenthub/v1/customers/{customerId}/paymenthandles/{tokenId}';

    const SETTLEMENT_URL_VARIABLE = '{settlementId}';
    const PAYMENT_URL_VARIABLE = '{paymentId}';
    const CUSTOMER_URL_VARIABLE = '{customerId}';

    /**
     * Initiate the CARD payment gateway api connector
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
     * Paysafe CARD gateway VOID payment call
     *
     * @param string $paymentId
     * @param array $params
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function voidPayment(string $paymentId, array $params = []): PaysafeApiResult
    {
        $this->logApiDebug("Card Connector: Void Payment $paymentId", $params ?? []);


        return $this->post(
            str_replace('{paymentId}', $paymentId, self::ENDPOINT_VOID_AUTHORIZATION),
            $this->validateVoidParams($params)
        );
    }

    /**
     * Paysafe CARD gateway SETTLE payment call
     *
     * @param string $paymentId
     * @param array $params
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function settlePayment(string $paymentId, array $params = []): PaysafeApiResult
    {
        $this->logApiDebug("Card Connector: Settle Payment $paymentId", $params ?? []);


        return $this->post(
            str_replace('{paymentId}', $paymentId, self::ENDPOINT_SETTLEMENT_PROCESS),
            $this->validateSettleParams($params)
        );
    }

    /**
     * Paysafe CARD gateway REFUND payment call
     *
     * @param string $settlementId
     * @param array $params
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function refundPayment(string $settlementId, array $params = []): PaysafeApiResult
    {
        $this->logApiDebug("Card Connector: Refund Payment $settlementId", $params ?? []);


        return $this->post(
            str_replace(self::SETTLEMENT_URL_VARIABLE, $settlementId, self::ENDPOINT_REFUND_PROCESS),
            $this->validateRefundParams($params)
        );
    }

    /**
     * Handle paysafe register payment call
     *
     * @param array $params
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function handleProcessPayment(array $params = []): PaysafeApiResult
    {
        $this->logApiDebug("Card Connector: Handle process payment", $params ?? []);

        return $this->post(
             self::ENDPOINT_PROCESS_PAYMENT,
            $this->validatePaymentParams($params)
        );
    }

    /**
     * Handle paysafe verify payment call
     *
     * @param array $params
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function handleVerifyPayment(array $params = []): PaysafeApiResult
    {
        $this->logApiDebug("Card Connector: Handle verify payment", $params ?? []);

        return $this->post(
             self::ENDPOINT_VERIFY_PAYMENT,
            $this->validateVerificationParams($params)
        );
    }

    /**
     * Get customer single use token based on paysafe customer id
     *
     * @param string $paysafeCustomerId
     * @param array $params
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function getCustomerSingleUseToken(string $paysafeCustomerId, array $params = []): PaysafeApiResult
    {
        $this->logApiDebug("Card Connector: Handle get customer single use token", $params ?? []);

        return $this->post(
            str_replace(
                self::CUSTOMER_URL_VARIABLE,
                $paysafeCustomerId,
                self::ENDPOINT_CUSTOMER_SINGLE_USE_TOKEN
            ),
            $this->validateCustomerSutParams($params)
        );
    }

    /**
     * Create paysafe customer
     *
     * @param array $params
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function createPaysafeCustomer(array $params = []): PaysafeApiResult
    {
        $this->logApiDebug("Card Connector: Handle create customer", $params ?? []);


        return $this->post(
            self::ENDPOINT_CREATE_CUSTOMER,
            $this->validateCreateCustomerParams($params)
        );
    }

    /**
     * Delete paysafe customer
     *
     * @param string $customerId
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function deletePaysafeCustomerById(string $customerId): PaysafeApiResult
    {
        $this->logApiDebug("Card Connector: Handle delete customer");

        return $this->delete(
            str_replace(
                self::CUSTOMER_URL_VARIABLE,
                $customerId,
                self::ENDPOINT_GET_CUSTOMER_PROFILE
            )
        );
    }

    /**
     * Create customer multi use token based on paysafe customer id
     *
     * @param string $paysafeCustomerId
     * @param array $params
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function createPaysafeCustomerToken(string $paysafeCustomerId, array $params = []): PaysafeApiResult
    {
        $this->logApiDebug("Card Connector: Handle create customer multi use token", $params ?? []);


        return $this->post(
            str_replace(
                self::CUSTOMER_URL_VARIABLE,
                $paysafeCustomerId,
                self::ENDPOINT_CUSTOMER_PAYMENT_HANDLES),
            $this->validateCustomerPaymentHandlesParams($params)
        );
    }

	/**
	 * Handle the exchange of the customer's single use payment handle into a multi use payment handle
	 *
	 * @param string $paysafeCustomerId
	 * @param array $params
	 *
	 * @return PaysafeApiResult
	 *
	 * @throws PaysafeApiException
	 */
	public function handleSuph2MuphExchange(string $paysafeCustomerId, array $params = []): PaysafeApiResult
	{
		$this->logApiDebug(
			"Card Connector: Handle exchange single use token into a multi use token",
			$params ?? []
		);

		return $this->post(
			str_replace(
				self::CUSTOMER_URL_VARIABLE,
				$paysafeCustomerId,
				self::ENDPOINT_CUSTOMER_PAYMENT_HANDLES),
			$this->validateExchangeSuph2MuphParams($params)
		);
	}

    /**
     * Validate Paysafe CARD VOID parameters
     *
     * @param array $params
     *
     * @return array
     *
     * @throws PaysafeApiException
     */
    private function validateVoidParams(array $params): array
    {
        return $this->validateParams(
            $params,
            [
                'merchantRefNum'    => self::VALIDATION_FIELD_REQUIRED,
                'amount'            => self::VALIDATION_FIELD_REQUIRED,
            ]
        );
    }

    /**
     * Validate Paysafe CARD SETTLE parameters
     *
     * @param array $params
     *
     * @return array
     *
     * @throws PaysafeApiException
     */
    private function validateSettleParams(array $params): array
    {
        return $this->validateParams(
            $params,
            [
                'merchantRefNum'    => self::VALIDATION_FIELD_REQUIRED,
                'amount'            => self::VALIDATION_FIELD_OPTIONAL,
                'dupCheck'          => self::VALIDATION_FIELD_OPTIONAL,
            ]
        );
    }

    /**
     * Validate Paysafe CARD REFUND parameters
     *
     * @param array $params
     *
     * @return array
     *
     * @throws PaysafeApiException
     */
    private function validateRefundParams(array $params): array
    {
        return $this->validateParams(
            $params,
            [
                'merchantRefNum'    => self::VALIDATION_FIELD_REQUIRED,
                'amount'            => self::VALIDATION_FIELD_OPTIONAL,
                'dupCheck'          => self::VALIDATION_FIELD_OPTIONAL,
            ]
        );
    }

    /**
     * Validate Paysafe process payment parameters
     *
     * @param array $params
     *
     * @return array
     *
     * @throws PaysafeApiException
     */
    private function validatePaymentParams(array $params): array
    {
        return $this->validateParams(
            $params,
            [
                'merchantRefNum'     => self::VALIDATION_FIELD_REQUIRED,
                'amount'             => self::VALIDATION_FIELD_REQUIRED,
                'currencyCode'       => self::VALIDATION_FIELD_REQUIRED,
                'settleWithAuth'     => self::VALIDATION_FIELD_OPTIONAL,
                'merchantCustomerId' => self::VALIDATION_FIELD_OPTIONAL,
                'paymentHandleToken' => self::VALIDATION_FIELD_REQUIRED,
                'storedCredential' => self::VALIDATION_FIELD_OPTIONAL,
            ]
        );
    }

    /**
     * Validate Paysafe verify payment parameters
     *
     * @param array $params
     *
     * @return array
     *
     * @throws PaysafeApiException
     */
    private function validateVerificationParams(array $params): array
    {
        return $this->validateParams(
            $params,
            [
                'merchantRefNum'     => self::VALIDATION_FIELD_REQUIRED,
                'paymentHandleToken' => self::VALIDATION_FIELD_REQUIRED,
                'customerIp'         => self::VALIDATION_FIELD_OPTIONAL,
                'dupCheck'           => self::VALIDATION_FIELD_OPTIONAL,
                'description'        => self::VALIDATION_FIELD_OPTIONAL,
            ]
        );
    }

    /**
     * Validate customer single use token parameters
     *
     * @param array $params
     *
     * @return array
     *
     * @throws PaysafeApiException
     */
    private function validateCustomerSutParams(array $params): array
    {
        return $this->validateParams(
            $params,
            [
                'merchantRefNum'     => self::VALIDATION_FIELD_REQUIRED,
                'paymentType'        => self::VALIDATION_FIELD_REQUIRED,
            ]
        );
    }

    /**
     * Validate create customer parameters
     *
     * @param array $params
     *
     * @return array
     *
     * @throws PaysafeApiException
     */
    private function validateCreateCustomerParams(array $params): array
    {
        return $this->validateParams(
            $params,
            [
                'merchantRefNum'            => self::VALIDATION_FIELD_OPTIONAL,
                'merchantCustomerId'        => self::VALIDATION_FIELD_REQUIRED,
                'locale'                    => self::VALIDATION_FIELD_REQUIRED,
                'paymentType'               => self::VALIDATION_FIELD_OPTIONAL,
                'paymentHandleTokenFrom'    => self::VALIDATION_FIELD_OPTIONAL,
            ]
        );
    }

    /**
     * Validate customer payment handles parameters
     *
     * @param array $params
     *
     * @return array
     *
     * @throws PaysafeApiException
     */
    private function validateCustomerPaymentHandlesParams(array $params): array
    {
        return $this->validateParams(
            $params,
            [
                'merchantRefNum'            => self::VALIDATION_FIELD_REQUIRED,
                'paymentHandleTokenFrom'    => self::VALIDATION_FIELD_OPTIONAL,
            ]
        );
    }

    /**
     * Validate get customer data by merchant customer id parameters
     *
     * @param array $params
     *
     * @return array
     *
     * @throws PaysafeApiException
     */
    private function validateCustomerDataByMidParams(array $params): array
    {
        return $this->validateParams(
            $params,
            [
                'merchantCustomerId'        => self::VALIDATION_FIELD_REQUIRED,
                'fields'                    => self::VALIDATION_FIELD_OPTIONAL,
            ]
        );
    }

    /**
     * Validate get paysafe payment handle data by merchant reference parameters
     *
     * @param array $params
     *
     * @return array
     *
     * @throws PaysafeApiException
     */
    private function validatePaymentHandleByMerchantReferenceParams(array $params): array
    {
        return $this->validateParams(
            $params,
            [
                'merchantRefNum'            => self::VALIDATION_FIELD_REQUIRED,
                'startDate'                 => self::VALIDATION_FIELD_OPTIONAL,
                'endDate'                   => self::VALIDATION_FIELD_OPTIONAL,
                'limit'                     => self::VALIDATION_FIELD_OPTIONAL,
                'offset'                    => self::VALIDATION_FIELD_OPTIONAL,
            ]
        );
    }

	/**
	 * Validate Paysafe exchange single use token to multi use token parameters
	 *
	 * @param array $params
	 *
	 * @return array
	 *
	 * @throws PaysafeApiException
	 */
	private function validateExchangeSuph2MuphParams(array $params): array
	{
		return $this->validateParams(
			$params,
			[
				'paymentHandleTokenFrom' => self::VALIDATION_FIELD_REQUIRED,
			]
		);
	}

	/**
     * Delete paysafe token from Paysafe API
     *
     * @param string $customerId
     * @param string $paysafeToken
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function deletePaysafeToken(string $customerId, string $paysafeToken): PaysafeApiResult
    {
        $this->logApiDebug("Card Connector: Delete paysafe token $paysafeToken, customer ID: $customerId");

        return $this->delete(
            str_replace(
                [self::CUSTOMER_URL_VARIABLE, '{tokenId}'],
                [$customerId, $paysafeToken],
                self::ENDPOINT_DELETE_PAYSAFE_TOKEN
            ),
            []
        );
    }

    /**
     * Get paysafe customer data by customer id
     *
     * @param string $customerId
     * @param array $data
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function getPaysafeCustomerData(string $customerId, array $data): PaysafeApiResult
    {
        $this->logApiDebug("Card Connector: get customer data for customer ID: $customerId");

        return $this->get(
            str_replace(
                [self::CUSTOMER_URL_VARIABLE],
                [$customerId],
                self::ENDPOINT_GET_CUSTOMER_PROFILE
            ),
            $data
        );
    }

    /**
     * Get paysafe customer data by merchant customer id
     *
     * @param array $data
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function getPaysafeCustomerDataByMid(array $data): PaysafeApiResult
    {
        $this->logApiDebug("Card Connector: get customer data", $data);

        return $this->get(
            self::ENDPOINT_GET_CUSTOMER_PROFILE_BY_MID,
            $this->validateCustomerDataByMidParams($data)
        );
    }

    /**
     * Get paysafe payment handle by merchant reference number
     *
     * @param array $data
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function getPaymentHandleByMerchantReference(array $data): PaysafeApiResult
    {
        $this->logApiDebug("Card Connector: get paysafe payment handle data by merchant reference", $data);

        return $this->get(
            self::ENDPOINT_PROCESS_PAYMENT_HANDLES,
            $this->validatePaymentHandleByMerchantReferenceParams($data)
        );
    }

    /**
     *  Get paysafe settlement data
     *
     * @param string $settlementId
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function getPaysafeSettlementData(string $settlementId): PaysafeApiResult
    {
        $this->logApiDebug("Card Connector: Handle get settlement status.");

        return $this->get(
            str_replace(
                self::SETTLEMENT_URL_VARIABLE,
                $settlementId,
                self::ENDPOINT_GET_SETTLEMENT_DATA
            )
        );
    }

	/**
	 *  Get paysafe payment data
	 *
	 * @param string $paymentId
	 *
	 * @return PaysafeApiResult
	 *
	 * @throws PaysafeApiException
	 */
	public function getPaysafePaymentData(string $paymentId): PaysafeApiResult
	{
		$this->logApiDebug("Card Connector: Handle get payment status.");

		return $this->get(
			str_replace(
				self::PAYMENT_URL_VARIABLE,
				$paymentId,
				self::ENDPOINT_GET_PAYMENT_DATA
			)
		);
	}

    /**
     *  Cancel Paysafe settlement
     *
     * @param string $settlementId
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function cancelPaysafeSettlement(string $settlementId): PaysafeApiResult
    {
        $this->logApiDebug("Card Connector: Handle cancel settlement.");

        return $this->put(
            str_replace(
                self::SETTLEMENT_URL_VARIABLE,
                $settlementId,
                self::ENDPOINT_CANCEL_SETTLEMENT
            ),
            ["status" => "CANCELLED"]
        );
    }
}
