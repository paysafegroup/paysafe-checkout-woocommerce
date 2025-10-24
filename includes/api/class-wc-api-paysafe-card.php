<?php

use Paysafe\PhpSdk\Exceptions\PaysafeApiException;

class PaysafeApiCardPluginConnector extends PaysafeApiBasePluginConnector
{
    /**
     * Paysafe Card Void call
     *
     * @param string $paymentId
     * @param array $data
     *
     * @return array
     *
     * @throws PaysafeException
     */
    public function voidPayment(string $paymentId, array $data): array
    {
        try {
            $paysafeApiResult = $this->connector->voidPayment($paymentId, $data);

            return $paysafeApiResult->getData();
        } catch (PaysafeApiException $e) {
            $this->logError('Paysafe Checkout: Void payment exception ' . $e->getMessage(),
                $e->getAdditionalData());

            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new PaysafeException(
                esc_html(__('Void payment failed', 'paysafe-checkout') . ': ' . $e->getMessage()),
                (int)$e->getCode(),
                null,
                $e->getAdditionalData()
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        } catch (\Exception $e) {
            $this->logError('Paysafe Checkout: Void payment exception ' . $e->getMessage());

            throw new PaysafeException(
                esc_html(__('Void payment failed', 'paysafe-checkout') . ': ' . $e->getMessage())
            );
        }
    }

    /**
     * Paysafe Card Settle call
     *
     * @param string $paymentId
     * @param array $data
     *
     * @return array
     *
     * @throws PaysafeException
     */
    public function settlePayment(string $paymentId, array $data): array
    {
        try {
            $paysafeApiResult = $this->connector->settlePayment($paymentId, $data);

            return $paysafeApiResult->getData();
        } catch (PaysafeApiException $e) {
            $this->logError('Paysafe Checkout: Settle payment exception ' . $e->getMessage(),
                $e->getAdditionalData());

            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new PaysafeException(
                esc_html(__('Settle payment failed', 'paysafe-checkout') . ': ' . $e->getMessage()),
                (int)$e->getCode(),
                null,
                $e->getAdditionalData()
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        } catch (\Exception $e) {
            $this->logError('Paysafe Checkout: Settle payment exception ' . $e->getMessage());

            throw new PaysafeException(
                esc_html(__('Settle payment failed', 'paysafe-checkout') . ': ' . $e->getMessage())
            );
        }
    }

    /**
     * Paysafe Card Refund call
     *
     * @param string $settlementId
     * @param array $data
     *
     * @return array
     *
     * @throws PaysafeException
     */
    public function refundPayment(string $settlementId, array $data): array
    {
        try {
            $paysafeApiResult = $this->connector->refundPayment($settlementId, $data);

            return $paysafeApiResult->getData();
        } catch (PaysafeApiException $e) {
            $this->logError('Paysafe Checkout: Refund payment exception ' . $e->getMessage(),
                $e->getAdditionalData());

            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new PaysafeException(
                esc_html(__('Refund payment failed', 'paysafe-checkout') . ': ' . $e->getMessage()),
                (int)$e->getCode(),
                null,
                $e->getAdditionalData()
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        } catch (\Exception $e) {
            $this->logError('Paysafe Checkout: Refund payment exception ' . $e->getMessage());

            throw new PaysafeException(
                esc_html(__('Refund payment failed', 'paysafe-checkout') . ': ' . $e->getMessage())
            );
        }
    }

    /**
     * Paysafe make payment call
     *
     * @param array $data
     *
     * @return array
     *
     * @throws PaysafeException
     */
    public function handleProcessPayment(array $data): array
    {
        try {
            $paysafeApiResult = $this->connector->handleProcessPayment($data);

            return $paysafeApiResult->getData();
        } catch (PaysafeApiException $e) {
            $this->logError('Paysafe Checkout: Process payment exception ' . $e->getMessage(),
                $e->getAdditionalData());

            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new PaysafeException(
                esc_html(__('Process payment failed', 'paysafe-checkout') . ': ' . $e->getMessage()),
                (int)$e->getCode(),
                null,
                $e->getAdditionalData()
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        } catch (\Exception $e) {
            $this->logError('Paysafe Checkout: Process payment exception ' . $e->getMessage());

            throw new PaysafeException(
                esc_html(__('Process payment failed', 'paysafe-checkout') . ': ' . $e->getMessage())
            );
        }
    }

    /**
     * Paysafe make verify payment call
     *
     * @param array $data
     *
     * @return array
     *
     * @throws PaysafeException
     */
    public function handleVerifyPayment(array $data): array
    {
        try {
            $paysafeApiResult = $this->connector->handleVerifyPayment($data);

            return $paysafeApiResult->getData();
        } catch (PaysafeApiException $e) {
            $this->logError('Paysafe Checkout: Verify payment exception ' . $e->getMessage(),
                $e->getAdditionalData());

            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new PaysafeException(
                esc_html(__('Verify payment failed', 'paysafe-checkout') . ': ' . $e->getMessage()),
                (int)$e->getCode(),
                null,
                $e->getAdditionalData()
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        } catch (\Exception $e) {
            $this->logError('Paysafe Checkout: Verify payment exception ' . $e->getMessage());

            throw new PaysafeException(
                esc_html(__('Verify payment failed', 'paysafe-checkout') . ': ' . $e->getMessage())
            );
        }
    }

    /**
     * Paysafe get customer single-use toke call
     *
     * @param string $paysafeCustomerId
     * @param array $data
     *
     * @return array
     *
     * @throws PaysafeException
     */
    public function getCustomerSingleUseToken(string $paysafeCustomerId, array $data): array
    {
        try {
            $paysafeApiResult = $this->connector->getCustomerSingleUseToken($paysafeCustomerId, $data);

            return $paysafeApiResult->getData();
        } catch (PaysafeApiException $e) {
            $this->logError(
                'Paysafe Checkout: Get customer single use token exception ' . $e->getMessage(),
                $e->getAdditionalData());

            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new PaysafeException(
                esc_html(
                __('Get customer single use token failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                ),
                (int)$e->getCode(),
                null,
                $e->getAdditionalData()
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        } catch (\Exception $e) {
            $this->logError('Paysafe Checkout: Get customer single use token exception ' . $e->getMessage());

            throw new PaysafeException(
                esc_html(
                    __('Get customer single use token failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                )
            );
        }
    }

    /**
     * Paysafe create customer
     *
     * @param array $data
     *
     * @return array
     *
     * @throws PaysafeException
     */
    public function createPaysafeCustomer(array $data): array
    {
        try {
            $paysafeApiResult = $this->connector->createPaysafeCustomer($data);

            return $paysafeApiResult->getData();
        } catch (PaysafeApiException $e) {
            $this->logError('Paysafe Checkout: Create paysafe customer exception ' . $e->getMessage(),
                $e->getAdditionalData());

            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new PaysafeException(
                esc_html(
                    __('Create paysafe customer failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                ),
                (int)$e->getCode(),
                null,
                $e->getAdditionalData()
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        } catch (\Exception $e) {
            $this->logError('Paysafe Checkout: Create paysafe customer exception ' . $e->getMessage());

            throw new PaysafeException(
                esc_html(
                    __('Create paysafe customer failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                )
            );
        }
    }

    /**
     * Paysafe delete customer
     *
     * @param string $customerId
     *
     * @return array
     *
     * @throws PaysafeException
     */
    public function deletePaysafeCustomer(string $customerId): array
    {
        try {
            $paysafeApiResult = $this->connector->deletePaysafeCustomerById($customerId);

            return $paysafeApiResult->getData();
        } catch (PaysafeApiException $e) {
            $this->logError(
                sprintf(
                    /* translators: %s is replaced by the message */
                    __(
                        'Paysafe Checkout: Delete paysafe customer exception %s',
                        'paysafe-checkout'),
                    $e->getMessage()
                ),
                $e->getAdditionalData()
            );

            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new PaysafeException(
                esc_html(
                    __('Delete paysafe customer failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                ),
                (int)$e->getCode(),
                null,
                $e->getAdditionalData()
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        } catch (\Exception $e) {
            $this->logError(
                sprintf(
                /* translators: %s is replaced by the message */
                    __(
                        'Paysafe Checkout: Delete paysafe customer exception %s',
                        'paysafe-checkout'),
                    $e->getMessage()
                )
            );

            throw new PaysafeException(
                esc_html(
                    __('Delete paysafe customer failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                )
            );
        }
    }

    /**
     * Paysafe create customer multi use token
     *
     * @param string $paysafeCustomerId
     * @param array $data
     *
     * @return array
     *
     * @throws PaysafeException
     */
    public function createPaysafeCustomerToken(string $paysafeCustomerId, array $data): array
    {
        try {
            $paysafeApiResult = $this->connector->createPaysafeCustomerToken($paysafeCustomerId, $data);

            return $paysafeApiResult->getData();
        } catch (PaysafeApiException $e) {
            $this->logError(
                'Paysafe Checkout: Create paysafe multi-use token exception ' . $e->getMessage(),
                $e->getAdditionalData());

            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new PaysafeException(
                esc_html(
                    __('Create paysafe customer multi-use token failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                ),
                (int)$e->getCode(),
                null,
                $e->getAdditionalData()
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        } catch (\Exception $e) {
            $this->logError('Paysafe Checkout: Create paysafe multi-use token exception ' . $e->getMessage());

            throw new PaysafeException(
                esc_html(
                    __('Create paysafe multi-use token failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                )
            );
        }
    }

    /**
     * Delete Paysafe token
     *
     * @param string $customerId
     * @param string $paysafeToken
     *
     * @return array
     *
     * @throws PaysafeException
     */
    public function deletePaysafeToken(string $customerId, string $paysafeToken): array
    {
        try {
            $paysafeApiResult = $this->connector->deletePaysafeToken($customerId, $paysafeToken);

            return $paysafeApiResult->getData();
        } catch (PaysafeApiException $e) {
            $this->logError('Paysafe Checkout: Delete paysafe token exception ' . $e->getMessage(),
                $e->getAdditionalData());

            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new PaysafeException(
                esc_html(
                    __('Delete paysafe token failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                ),
                (int)$e->getCode(),
                null,
                $e->getAdditionalData()
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        } catch (\Exception $e) {
            $this->logError('Paysafe Checkout: Delete paysafe token exception ' . $e->getMessage());

            throw new PaysafeException(
                esc_html(
                    __('Delete paysafe token failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                )
            );
        }
    }

    /**
     * Get Paysafe customer data by customer id
     *
     * @param string $customerId
     * @param array $data
     * 
     * @return array
     *
     * @throws PaysafeException
     */
    public function getPaysafeCustomerData(string $customerId, array $data): array
    {
        try {
            $paysafeApiResult = $this->connector->getPaysafeCustomerData($customerId, $data);

            return $paysafeApiResult->getData();
        } catch (PaysafeApiException $e) {
            $this->logError('Paysafe Checkout: Get customer data exception ' . $e->getMessage(),
                $e->getAdditionalData());

            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new PaysafeException(
                esc_html(
                    __('Get customer data failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                ),
                (int)$e->getCode(),
                null,
                $e->getAdditionalData()
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        } catch (\Exception $e) {
            $this->logError('Paysafe Checkout: Get customer data exception ' . $e->getMessage());

            throw new PaysafeException(
                esc_html(
                    __('Get customer data failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                )
            );
        }
    }

    /**
     * Get Paysafe customer data by merchant customer id
     *
     * @param array $data
     *
     * @return array
     *
     * @throws PaysafeException
     */
    public function getPaysafeCustomerDataByMid(array $data): array
    {
        try {
            $paysafeApiResult = $this->connector->getPaysafeCustomerDataByMid($data);

            return $paysafeApiResult->getData();
        } catch (PaysafeApiException $e) {
            $this->logError(
                'Paysafe Checkout: Get customer data by merchant customer id exception ' . $e->getMessage(),
                $e->getAdditionalData());

            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new PaysafeException(
                esc_html(
                    __('Get customer data by merchant customer id failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                ),
                (int)$e->getCode(),
                null,
                $e->getAdditionalData()
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        } catch (\Exception $e) {
            $this->logError(
                'Paysafe Checkout: Get customer data by merchant customer id exception ' . $e->getMessage());

            throw new PaysafeException(
                esc_html(
                    __('Get customer data by merchant customer id failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                )
            );
        }
    }

    /**
     * Get Paysafe payment handle by merchant reference number
     *
     * @param array $data
     *
     * @return array
     *
     * @throws PaysafeException
     */
    public function getPaymentHandleByMerchantReference(array $data): array
    {
        try {
            $paysafeApiResult = $this->connector->getPaymentHandleByMerchantReference($data);

            return $paysafeApiResult->getData();
        } catch (PaysafeApiException $e) {
            $this->logError(
                'Paysafe Checkout: Get payment handle by reference exception ' . $e->getMessage(),
                $e->getAdditionalData());

            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new PaysafeException(
                esc_html(
                    __('Get payment handle by reference failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                ),
                (int)$e->getCode(),
                null,
                $e->getAdditionalData()
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        } catch (\Exception $e) {
            $this->logError('Paysafe Checkout: Get payment handle by reference exception '
                . $e->getMessage());

            throw new PaysafeException(
                esc_html(
                    __('Get payment handle by reference failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                )
            );
        }
    }

    /**
     * Gets the data of a Paysafe payment by settlement ID
     *
     * @param string $settlementId
     *
     * @return array
     *
     * @throws PaysafeException
     */
    public function getPaysafeSettlementData(string $settlementId): array
    {
        try {
            $paysafeApiResult = $this->connector->getPaysafeSettlementData($settlementId);

            return $paysafeApiResult->getData();
        } catch (PaysafeApiException $e) {
            $this->logError(
                'Paysafe Checkout: Get Paysafe settlement data by settlement ID' . $e->getMessage(),
                $e->getAdditionalData());

            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new PaysafeException(
                esc_html(
                    __('Get Paysafe settlement data by settlement ID failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                ),
                (int)$e->getCode(),
                null,
                $e->getAdditionalData()
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        } catch (\Exception $e) {
            $this->logError('Paysafe Checkout: Get Paysafe settlement data by settlement ID exception '
                . $e->getMessage());

            throw new PaysafeException(
                esc_html(
                    __('Get Paysafe settlement data by settlement ID failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                )
            );
        }
    }

	/**
	 * Gets the data of a Paysafe payment by payment ID
	 *
	 * @param string $paymentId
	 *
	 * @return array
	 *
	 * @throws PaysafeException
	 */
	public function getPaysafePaymentData(string $paymentId): array
	{
		try {
			$paysafeApiResult = $this->connector->getPaysafePaymentData($paymentId);

			return $paysafeApiResult->getData();
		} catch (PaysafeApiException $e) {
			$this->logError(
				'Paysafe Checkout: Get Paysafe payment data by payment ID' . $e->getMessage(),
				$e->getAdditionalData());

			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new PaysafeException(
				esc_html(
					__('Get Paysafe payment data by payment ID failed', 'paysafe-checkout')
					. ': ' . $e->getMessage()
				),
				(int)$e->getCode(),
				null,
				$e->getAdditionalData()
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		} catch (\Exception $e) {
			$this->logError('Paysafe Checkout: Get Paysafe payment data by payment ID exception '
			                . $e->getMessage());

			throw new PaysafeException(
				esc_html(
					__('Get Paysafe payment data by payment ID failed', 'paysafe-checkout')
					. ': ' . $e->getMessage()
				)
			);
		}
	}

    /**
     * Cancels a Paysafe settlement
     *
     * @param string $settlementId
     *
     * @return array
     *
     * @throws PaysafeException
     */
    public function cancelPaysafeSettlement(string $settlementId): array
    {
        try {
            $paysafeApiResult = $this->connector->cancelPaysafeSettlement($settlementId);

            return $paysafeApiResult->getData();
        } catch (PaysafeApiException $e) {
            $this->logError(
                'Paysafe Checkout: Cancel Paysafe settlement' . $e->getMessage(),
                $e->getAdditionalData());

            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new PaysafeException(
                esc_html(
                    __('Cancel Paysafe settlement failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                ),
                (int)$e->getCode(),
                null,
                $e->getAdditionalData()
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        } catch (\Exception $e) {
            $this->logError('Paysafe Checkout: Cancel Paysafe settlement exception ' . $e->getMessage());

            throw new PaysafeException(
                esc_html(
                    __('Cancel Paysafe settlement failed', 'paysafe-checkout')
                    . ': ' . $e->getMessage()
                )
            );
        }
    }

	/**
	 * Handle the exchange of the customer's single use payment handle into a multi use payment handle
	 * to save the card locally in woocommerce
	 *
	 * @param string $paysafe_customer_id
	 * @param array $data
	 *
	 * @return array
	 *
	 * @throws PaysafeException
	 */
	public function handleSuph2MuphExchange(string $paysafe_customer_id, array $data): array
	{
		try {
			$paysafeApiResult = $this->connector->handleSuph2MuphExchange($paysafe_customer_id, $data);

			return $paysafeApiResult->getData();
		} catch (PaysafeApiException $e) {
			$this->logError('Paysafe Checkout: exchange single use token to multi use exception ' . $e->getMessage(),
				$e->getAdditionalData());

			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new PaysafeException(
				esc_html(__('Process exchange single use token to multi use failed', 'paysafe-checkout') . ': ' . $e->getMessage()),
				(int)$e->getCode(),
				null,
				$e->getAdditionalData()
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		} catch (\Exception $e) {
			$this->logError('Paysafe Checkout: Process exchange single use token to multi use exception ' . $e->getMessage());

			throw new PaysafeException(
				esc_html(__('Process exchange single use token to multi use failed', 'paysafe-checkout') . ': ' . $e->getMessage())
			);
		}
	}
}
