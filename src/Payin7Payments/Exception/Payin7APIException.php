<?php
namespace Payin7Payments\Exception;

use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;

class Payin7APIException extends BadResponseException
{
    protected $_server_error_code;
    protected $_server_error_message;
    protected $_server_error_domain;
    protected $_server_error_exception;
    protected $_server_error_trace;

    /**
     * @var ValidationFailure[]
     */
    protected $_validation_failures;

    public static function factory(RequestInterface $request, Response $response)
    {
        $response_json = $response->json();
        $generic_error = null;
        $cls = null;

        $is_lc_error = static::isLCApiError($response_json);

        $err_code = null;
        $err_message = null;
        $err_domain = null;
        $err_exception = null;
        $err_trace = null;
        $validation_failures = array();

        if ($is_lc_error) {
            $err_code = isset($response_json['error']['code']) ? $response_json['error']['code'] : null;
            $err_message = isset($response_json['error']['message']) ? $response_json['error']['message'] : null;
            $err_domain = isset($response_json['error']['domain']) ? $response_json['error']['domain'] : null;
            $err_exception = isset($response_json['error']['exception']) ? $response_json['error']['exception'] : null;
            $err_trace = isset($response_json['error']['trace']) ? $response_json['error']['trace'] : null;

            // parse validation failures
            $failures = isset($response_json['error']['validation_failures']) ? $response_json['error']['validation_failures'] : null;

            if ($failures) {
                foreach ($failures as $failure) {
                    $validation_failures[] = new ValidationFailure(
                        (isset($failure['name']) ? $failure['name'] : null),
                        (isset($failure['message']) ? $failure['message'] : null),
                        (isset($failure['extra_data']) ? $failure['extra_data'] : null)
                    );
                    unset($failure);
                }
            }
        } else {
            $err_message = 'Server communication error';
        }

        if ($is_lc_error) {
            $cls = __NAMESPACE__ . '\\ClientErrorResponseException';
        } elseif ($response->isClientError()) {
            $cls = __NAMESPACE__ . '\\ServerErrorResponseException';
        } else {
            $cls = __CLASS__;
        }

        /** @var Payin7APIException $e */
        $e = new $cls($err_message);
        $e->setResponse($response);
        $e->setRequest($request);
        $e->setServerErrorCode($err_code);
        $e->setServerErrorMessage($err_message);
        $e->setServerErrorDomain($err_domain);
        $e->setServerErrorException($err_exception);
        $e->setServerErrorTrace($err_trace);
        $e->setServerValidationFailures($validation_failures);

        return $e;
    }

    /**
     * Verifies the response bode contains a Lightcast (TM) Error response
     * @param $response_body
     * @return bool
     */
    private static function isLCApiError($response_body)
    {
        return isset($response_body['error']);
    }

    public function getServerValidationFailures()
    {
        return $this->_validation_failures;
    }

    public function setServerValidationFailures($failures = null)
    {
        $this->_validation_failures = $failures;
    }

    public function setServerErrorCode($code)
    {
        $this->_server_error_code = $code;
    }

    public function getServerErrorCode()
    {
        return $this->_server_error_code;
    }

    public function setServerErrorMessage($message)
    {
        $this->_server_error_message = $message;
    }

    public function getServerErrorMessage()
    {
        return $this->_server_error_message;
    }

    public function getFullServerErrorMessage()
    {
        $message = $this->getServerErrorMessage();

        $verrs = $this->getServerValidationFailures();
        $vv = array();

        if ($verrs) {
            foreach ($verrs as $verr) {
                $vv[] = $verr->getMessage();
                unset($verr);
            }
        }

        $message .= ($vv ? "\n\n- " . implode('- ', $vv) : null);

        return $message;
    }

    public function setServerErrorDomain($domain)
    {
        $this->_server_error_domain = $domain;
    }

    public function getServerErrorDomain()
    {
        return $this->_server_error_domain;
    }

    public function setServerErrorException($exception)
    {
        $this->_server_error_exception = $exception;
    }

    public function getServerErrorException()
    {
        return $this->_server_error_exception;
    }

    public function getServerErrorTrace()
    {
        return $this->_server_error_trace;
    }

    public function setServerErrorTrace($trace)
    {
        $this->_server_error_trace = $trace;
    }
}
