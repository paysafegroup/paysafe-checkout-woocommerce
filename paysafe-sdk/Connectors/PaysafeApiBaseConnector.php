<?php
namespace Paysafe\PhpSdk\Connectors;

use Paysafe\PhpSdk\Result\PaysafeApiResult;
use Paysafe\PhpSdk\Exceptions\PaysafeApiException;
use Paysafe\PhpSdk\Logging\Interfaces\PaysafeLoggerInterface;

abstract class PaysafeApiBaseConnector
{
    // define AUTH types
    const AUTH_BASIC = 'BASIC';

    // define cUrl methods
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';

    // define validation fields
    const VALIDATION_FIELD_REQUIRED = 'required';
    const VALIDATION_FIELD_OPTIONAL = 'optional';

	private string $authorization;

    private bool $isTestMode;

    private ?PaysafeLoggerInterface $logger = null;

    private string $apiUrl = 'https://api.paysafe.com/';

    private string $apiTestUrl = 'https://api.test.paysafe.com/';

    private int $curlTimeout = 60;

    /**
     * Initiate the api connector
     *
     * @param array $config
     * @param PaysafeLoggerInterface|null $logger
     *
     * @throws PaysafeApiException
     */
    public function __construct(array $config, ?PaysafeLoggerInterface $logger = null)
    {
        $apiKey = $config['api_key'] ?? null;
        if (!$apiKey) {
            throw new PaysafeApiException(
                esc_html("API Key is required"),
                (int)PaysafeApiException::API_KEY_MISSING
            );
        }

        $this->authorization = $apiKey;
        $this->isTestMode = (bool)($config['is_test_mode'] ?? false);

        $this->logger = $logger;
    }

    /**
     * Sets the logger
     * 
     * @param ?PaysafeLoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(?PaysafeLoggerInterface $logger = null): void {
        $this->logger = $logger;
    }

    /**
     * Gets the logger
     * 
     * Beware! This might return null if there is no logger set!
     * 
     * @return ?PaysafeLoggerInterface
     */
    public function getLogger(): ?PaysafeLoggerInterface {
        return $this->logger;
    }

    /**
     * Call Paysafe API with GET method
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function get(string $endpoint, array $data = []): PaysafeApiResult
    {
        $queryString = '';
        foreach ($data as $field => $value) {
            $queryString .= $field . '=' . $value . '&';
        }

        if ($queryString) {
            $endpoint .= '?' . $queryString;
        }

        return $this->callPaysafeApi(
            $endpoint
        );
    }

    /**
     * Call Paysafe API with POST method
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function post(string $endpoint, array $data = []): PaysafeApiResult
    {
        return $this->callPaysafeApi(
            $endpoint,
            $data,
            self::METHOD_POST
        );
    }

    /**
     * Call Paysafe API with PUT method
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function put(string $endpoint, array $data = []): PaysafeApiResult
    {
        return $this->callPaysafeApi(
            $endpoint,
            $data,
            self::METHOD_PUT
        );
    }

    /**
     * Call Paysafe API with DELETE method
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    public function delete(string $endpoint, array $data = []): PaysafeApiResult
    {
        return $this->callPaysafeApi(
            $endpoint,
            $data,
            self::METHOD_DELETE
        );
    }

    /**
     * Call Paysafe API and handle the response
     *
     * @param string $endpoint
     * @param array $data
     * @param string $method
     *
     * @return PaysafeApiResult
     *
     * @throws PaysafeApiException
     */
    private function callPaysafeApi(
        string $endpoint,
        array $data = [],
        string $method = self::METHOD_GET
    ): PaysafeApiResult
    {
        $apiUrl = $this->getApiUrlWithEndpoint($endpoint);

        $args = [
            'user-agent' => 'Paysafe PHP SDK',
            'method' => $method,
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Basic ' . $this->authorization,
            ],
            'timeout' => $this->curlTimeout,
        ];

        if ($method === self::METHOD_POST) {
            $args['body'] = wp_json_encode($data);

            $this->logApiDebug(
                'call_paysafe_api: POST data sent',
                $data
            );
        }

        if ($method === self::METHOD_DELETE && !empty($data)) {
            $args['body'] = wp_json_encode($data);

            $this->logApiDebug(
                'call_paysafe_api: DELETE data sent',
                $data
            );
        }

        $curlResponseRaw = wp_remote_post(
            $apiUrl,
            $args
        );

        $curlHttpCode = wp_remote_retrieve_response_code( $curlResponseRaw );

        $curlResponse = json_decode( wp_remote_retrieve_body( $curlResponseRaw ), true );

        $this->logApiDebug(
            'call_paysafe_api: Response received.',
	        $curlResponse ?? []
        );

        if (! in_array($curlHttpCode, array(200, 201, 409), true)) {
            $error = $this->getPaysafeApiError($curlResponseRaw);
            $errorCode = (int)$curlResponse['error']['code'] ?? 0;
            $errorMessage = $curlResponse['error']['message'] ?? '';

            if (!$errorCode) {
                // smart routing error code
                $errorCode = (int)str_replace('SMARTROUTING-', '', $curlResponse['error']['code']) ?? 0;
            }

            $this->logApiError(
                'Call to Paysafe API failed',
                [
                    'api_url'   => $apiUrl,
                    'http_code' => $curlHttpCode,
                    'error'     => $error,
                ]
            );

            throw new PaysafeApiException(
                esc_html($error),
                in_array($errorCode, [5279], true) ?
                    (int)PaysafeApiException::API_INVALID_CREDENTIALS
                    : (int)PaysafeApiException::API_CALL_FAILED,
                null,
                [
                    'error_code'    => esc_html($errorCode),
                    'error_message' => esc_html($errorMessage),
                    'http_code'     => esc_html($curlHttpCode),
                ]
            );
        }

        if (( empty($curlResponseRaw) || !is_array($curlResponse))) {
            // exception on some special calls, paysafe api response is just an empty string
            $curlResponse = [];
        }

        if (! is_array($curlResponse)) {
            $error = $this->getPaysafeApiError($curlResponseRaw);
            $errorCode = (int)$curlResponse['error']['code'] ?? 0;
            $errorMessage = (int)$curlResponse['error']['message'] ?? '';

            if (!$errorCode) {
                // smart routing error code
                $errorCode = (int)str_replace('SMARTROUTING-', '', $curlResponse['error']['code']) ?? 0;
            }
            
            $this->logApiError(
                'Call to Paysafe returned invalid answer',
                [
                    'api_url'   => $apiUrl,
                    'http_code' => $curlHttpCode,
                    'error'     => $error,
                ]
            );

            throw new PaysafeApiException(
                esc_html($error),
                (int)PaysafeApiException::API_RESPONSE_INVALID,
                null,
                [
                    'error_code'    => esc_html($errorCode),
                    'error_message' => esc_html($errorMessage),
                ]
            );
        }

        return new PaysafeApiResult(
            $curlResponse
        );
    }

    /**
     * Return the Paysafe API url based on whether it is in test mode or not
     *
     * @param string $endpoint
     *
     * @return string
     */
    private function getApiUrlWithEndpoint(string $endpoint): string
    {
        if ($this->isTestMode) {
            return $this->apiTestUrl . $endpoint;
        }

        return $this->apiUrl . $endpoint;
    }

    /**
     * Return the Curl or Paysafe error message
     *
     * @param \WP_Error|array $curlResponseRaw
     *
     * @return string
     */
    private function getPaysafeApiError($curlResponseRaw): string
    {
        $error = $errno = '';
        if ($curlResponseRaw instanceof \WP_Error) {
            $error = $curlResponseRaw->get_error_message();
            $errno = $curlResponseRaw->get_error_code();
        }

        if (!$error && is_array($curlResponseRaw)) {
            $curlResponse = json_decode( wp_remote_retrieve_body( $curlResponseRaw ), true );
            // get error out of response body
            $errno = $curlResponse['error']['code'] ?? '';
            $error = $curlResponse['error']['message'] ?? '';
        }

        return esc_html(trim($errno . ': ' . $error));
    }

    /**
     * Log Paysafe API debug
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    protected function logApiDebug(string $message, array $context = []): void
    {
        if(strtolower(substr($message, 0, 17)) != 'paysafe checkout:') {
            $message = 'Paysafe Checkout: API Log: ' . trim($message);
        }
        try {
            if ($this->logger) {
                $this->logger->debug($message, $context);

                return;
            }
        } catch (\Exception $e) {
            // do nothing
        }
    }

    /**
     * Log Paysafe API errors
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    protected function logApiError(string $message, array $context = []): void
    {
        if(strtolower(substr($message, 0, 17)) != 'paysafe checkout:') {
            $message = 'Paysafe Checkout: API Log: ' . trim($message);
        }
        try {
            if ($this->logger) {
                $this->logger->error($message, $context);

                return;
            }
        } catch (\Exception $e) {
            // do nothing
        }
    }

    /**
     * Validate API params for calls
     *
     * @param array $params
     * @param array $requirements
     *
     * @return array
     *
     * @throws PaysafeApiException
     */
    protected function validateParams(array $params, array $requirements): array
    {
        $validatedParams = [];

        foreach ($requirements as $field => $requirement) {
            if ($requirement === self::VALIDATION_FIELD_REQUIRED && !isset($params[$field])) {
                throw new PaysafeApiException(
                    sprintf(
                        'Validation error, field %s is missing!',
                        esc_html($field)
                    ),
                    (int)PaysafeApiException::API_FIELD_VALIDATION_ERROR
                );
            }

            if (isset($params[$field])) {
                $validatedParams[$field] = $params[$field];
            }
        }

        return $validatedParams;
    }
}
