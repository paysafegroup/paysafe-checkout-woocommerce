<?php

use Paysafe\PhpSdk\Connectors\PaysafeApiGeneralConnector;
use Paysafe\PhpSdk\Exceptions\PaysafeApiException;

class PaysafeApiGeneralPluginConnector extends PaysafeApiBasePluginConnector
{
    /**
     * Used to test whether saved public and private keys are set correctly
     *
     * @param bool $usePublicKey
     *
     * @return bool
     *
     * @throws PaysafeException
     */
    public function testSaveKey(bool $usePublicKey = false): bool
    {
        $paysafeWcOptions = get_option(PAYSAFE_SETTINGS_KEYWORD, []);
        $testMode = ($paysafeWcOptions['test_mode'] ?? null) === 'yes';

        $isTestMode = !empty($testMode) && (bool)$testMode;
        $testLiveString = $isTestMode ? 'test' : 'live';
        $publicPrivateString = $usePublicKey ? 'public' : 'private';

        $apiKey = base64_encode(
                      $paysafeWcOptions[$testLiveString.'_'.$publicPrivateString.'_api_key'] ?? ''
                  ) ?? null;

        return $this->testKey($apiKey, $isTestMode);
    }

    /**
     * Used to test whether new public and private keys are set correctly
     *
     * @param bool $usePublicKey
     *
     * @return bool
     *
     * @throws PaysafeException
     */
    public function testNewKey(array $data = null, bool $usePublicKey = false): bool
    {
        $testMode = ($data['test_mode'] ?? null) === 'yes';
        $isTestMode = !empty($testMode) && (bool)$testMode;
        $testLiveString = $isTestMode ? 'test' : 'live';
        $publicPrivateString = $usePublicKey ? 'public' : 'private';

        $apiKey = base64_encode(
                      $data[$testLiveString.'_'.$publicPrivateString.'_api_key'] ?? ''
                  ) ?? null;

        $monitorResult =  $this->testKey($apiKey, $isTestMode);

        if (!$monitorResult) {
            return $monitorResult;
        }

        return $this->checkKey($usePublicKey);
    }

    public function checkKey($usePublicKey): bool
    {
        $rand_id = 'woocommerce_check_api_key_with_nonexisting_id_53ca342a-6074-4e84-aec2-e886ba023bf2';
        try {
            $this->connector->getPaysafeCustomer($rand_id);
        } catch (PaysafeApiException $e) {
            $httpCode = $e->getAdditionalData()['http_code'] ?? null;
            if ($usePublicKey){
                return $httpCode == '403';
            } else {
                return $httpCode == '404';
            }
        }

        return false;
    }

    /**
     * Test if a key works
     *
     * @param         $apiKey
     * @param   bool  $isTestMode
     *
     * @return bool
     * @throws PaysafeException
     */
    public function testKey($apiKey, bool $isTestMode): bool
    {
        if (empty($apiKey)) {
            throw new PaysafeException(
                esc_html('password not set'),
                esc_html(PaysafeApiException::API_KEY_MISSING)
            );
        }

        try {
            $this->connector = new PaysafeApiGeneralConnector([
                'api_key' => $apiKey,
                'is_test_mode' => $isTestMode,
            ], $this->logger);
        } catch (PaysafeApiException $e) {
            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new PaysafeException(
                esc_html($e->getMessage()),
                (int)$e->getCode(),
                null,
                $e->getAdditionalData()
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        return $this->monitor();
    }

    /**
     * Paysafe monitor service
     *
     * @return bool
     *
     * @throws PaysafeException
     */
    public function monitor(): bool
    {
        try {
            return $this->connector->monitor();
        } catch (PaysafeApiException $e) {
            $this->logError(
                'Paysafe Checkout: Monitor service failed "' . $e->getMessage() . '"',
                $e->getAdditionalData());

            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new PaysafeException(
                esc_html(__('Auth key invalid', 'paysafe-checkout')),
                (int)$e->getCode(),
                null,
                $e->getAdditionalData()
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        } catch (\Exception $e) {
            $this->logError('Paysafe Checkout: Monitor service failed "' . $e->getMessage() . '"');

            throw new PaysafeException(
                esc_html(__('Auth key invalid', 'paysafe-checkout'))
            );
        }
    }

    /**
     * Paysafe Get Payment Methods call
     *
     * @param string $currencyCode
     *
     * @return array
     *
     * @throws PaysafeException
     */
    public function getPaymentMethods(string $currencyCode): array
    {
        try {
            $paysafeApiResult = $this->connector->getPaymentMethods([
                'currencyCode'  => $currencyCode,
            ]);

            return $paysafeApiResult->getData();
        } catch (PaysafeApiException $e) {
            $this->logError(
                'Paysafe Checkout: Get Payment methods exception "' . $e->getMessage() . '"',
                $e->getAdditionalData());

            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new PaysafeException(
                esc_html(__('Get payment methods failed', 'paysafe-checkout')),
                (int)$e->getCode(),
                null,
                $e->getAdditionalData()
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        } catch (\Exception $e) {
            $this->logError('Paysafe Checkout: Get Payment methods exception "' . $e->getMessage() . '"');

            throw new PaysafeException(
                esc_html(__('Get payment methods failed', 'paysafe-checkout'))
            );
        }
    }
}
