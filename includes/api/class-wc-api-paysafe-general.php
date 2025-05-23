<?php

use Paysafe\PhpSdk\Connectors\PaysafeApiGeneralConnector;
use Paysafe\PhpSdk\Exceptions\PaysafeApiException;

class PaysafeApiGeneralPluginConnector extends PaysafeApiBasePluginConnector
{
    /**
     * Used to test whether public and private keys are set correctly
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
            $paysafeWcOptions[$testLiveString . '_' . $publicPrivateString . '_api_key'] ?? ''
        ) ?? null;
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
