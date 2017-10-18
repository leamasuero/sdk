<?php

class MU
{

    const VERSION = "1.0.0";
    const AUTH_BASE_URL = "https://auth.prop44.info";
    const API_BASE_URL = "https://api.prop44.info";

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

        $this->token = $this->getAccessToken($username, $password);
    }

    private function getAccessToken($username, $password)
    {
        if (isset($this->token) && !is_null($this->token)) {
            return $this->token;
        }

        $reponse = MURestClient::connect(self::AUTH_BASE_URL)
            ->auth(base64_encode($username . ':' . $password), 'Basic')
            ->post('/session');

        return $reponse->token;
    }

    public function getSession()
    {
        return MURestClient::connect(self::AUTH_BASE_URL)
                ->auth($this->token)
                ->get('/session');
    }

    public function crearPropriedad($datosPropiedad)
    {
        return MURestClient::connect(self::API_BASE_URL)
                ->auth($this->token)
                ->post('/propiedades', $datosPropiedad);
    }
}

/**
 * MercadoUnico cURL RestClient
 */
class MURestClient
{

    const GET = 'GET';
    const PUT = 'PUT';
    const POST = 'POST';
    const DELETE = 'DELETE';

    /**
     * @var string
     */
    private $host;

    /**
     * @var integer
     */
    private $port;

    /**
     * @var array
     */
    private $headers;

    /**
     * @param string $host
     * @param integer $port
     * @return MURestClient
     */
    static public function connect($host, $port = 80)
    {
        if (!extension_loaded("curl")) {
            throw new MUException("cURL extension not found. You need to enable cURL in your php.ini or another configuration you have.");
        }

        return new self($host, $port);
    }

    protected function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
        $this->headers = array(
            "Accept" => "application/json",
            "Content-type" => "application/json"
        );
    }

    public function auth($token, $type = 'Bearer')
    {
        $this->setHeaders(array('Authorization' => "{$type} {$token}"));
        return $this;
    }

    public function setHeaders($headers)
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * 
     * @return array
     */
    private function getHttpHeaders()
    {
        $headers = array();
        foreach ($this->getHeaders() as $k => $v) {
            $headers[] = "{$k}: {$v}";
        }

        return $headers;
    }

    public function get($path, $data = array())
    {
        return $this->exec(self::GET, $path, $data);
    }

    public function post($path, $data = array())
    {
        $jsonData = json_encode($data);

        if (function_exists('json_last_error')) {
            if (json_last_error() != JSON_ERROR_NONE) {
                throw new JsonErrorException(json_last_error(), $data);
            }
        }

        return $this->exec(self::POST, $path, $jsonData);
    }

    private function buildRequest($method, $path, $data = array())
    {
        $connect = curl_init();

        curl_setopt($connect, CURLOPT_USERAGENT, "MercadoUnico PHP SDK v" . MU::VERSION);
        curl_setopt($connect, CURLOPT_URL, "{$this->host}{$path}");
        curl_setopt($connect, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($connect, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($connect, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($connect, CURLOPT_HTTPHEADER, $this->getHttpHeaders());

        switch ($method) {
            case self::POST:
                curl_setopt($connect, CURLOPT_POSTFIELDS, $data);
                break;
        }



        return $connect;
    }

    public function exec($method, $path, $data)
    {
        $connect = $this->buildRequest($method, $path, $data);
        $response = curl_exec($connect);

        if ($response === false) {
            throw new MUException(curl_error($connect));
        }

        $responseStatusCode = curl_getinfo($connect, CURLINFO_HTTP_CODE);

        if ($responseStatusCode >= Response::HTTP_BAD_REQUEST) {
            throw new MUErrorResponseException(json_decode($response, true), $responseStatusCode);
        }

        curl_close($connect);

        return new MUResponse(json_decode($response, true), $responseStatusCode);
    }
}

class MUException extends Exception
{

    public function __construct($message, $code = 500, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class JsonErrorException extends MUException
{

    private $jsonError;

    /**
     * @var array
     */
    private $data;

    public function __construct($jsonError, $data)
    {
        $this->jsonError = $jsonError;
        $this->data = $data;
        parent::__construct("Json error", Response::HTTP_BAD_REQUEST);
    }

    public function getJsonError()
    {
        return $this->jsonError;
    }

    public function getData()
    {
        return $this->data;
    }
}

class MUErrorResponseException extends MUException
{

    /**
     * @var array
     */
    private $errorResponse;

    public function __construct($errorResponse, $httpStatusCode)
    {
        $this->errorResponse = $errorResponse;
        parent::__construct($errorResponse['message'], $httpStatusCode);
    }

    public function getErrorResponse()
    {
        return $this->errorResponse;
    }
}

class MUResponse extends Response
{

    /**
     * @var array 
     */
    private $body;

    /**
     * @var int 
     */
    private $httpStatusCode;

    public function __construct($body, $httpStatusCode)
    {
        $this->body = $body;
        $this->httpStatusCode = $httpStatusCode;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }

    public function __get($name)
    {
        return isset($this->body[$name]) ? $this->body[$name] : null;
    }
}

class Response
{

    const HTTP_CONTINUE = 100;
    const HTTP_SWITCHING_PROTOCOLS = 101;
    const HTTP_PROCESSING = 102;            // RFC2518
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_ACCEPTED = 202;
    const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
    const HTTP_NO_CONTENT = 204;
    const HTTP_RESET_CONTENT = 205;
    const HTTP_PARTIAL_CONTENT = 206;
    const HTTP_MULTI_STATUS = 207;          // RFC4918
    const HTTP_ALREADY_REPORTED = 208;      // RFC5842
    const HTTP_IM_USED = 226;               // RFC3229
    const HTTP_MULTIPLE_CHOICES = 300;
    const HTTP_MOVED_PERMANENTLY = 301;
    const HTTP_FOUND = 302;
    const HTTP_SEE_OTHER = 303;
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_USE_PROXY = 305;
    const HTTP_RESERVED = 306;
    const HTTP_TEMPORARY_REDIRECT = 307;
    const HTTP_PERMANENTLY_REDIRECT = 308;  // RFC7238
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_PAYMENT_REQUIRED = 402;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_NOT_ACCEPTABLE = 406;
    const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    const HTTP_REQUEST_TIMEOUT = 408;
    const HTTP_CONFLICT = 409;
    const HTTP_GONE = 410;
    const HTTP_LENGTH_REQUIRED = 411;
    const HTTP_PRECONDITION_FAILED = 412;
    const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
    const HTTP_REQUEST_URI_TOO_LONG = 414;
    const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const HTTP_EXPECTATION_FAILED = 417;
    const HTTP_I_AM_A_TEAPOT = 418;                                               // RFC2324
    const HTTP_UNPROCESSABLE_ENTITY = 422;                                        // RFC4918
    const HTTP_LOCKED = 423;                                                      // RFC4918
    const HTTP_FAILED_DEPENDENCY = 424;                                           // RFC4918
    const HTTP_RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL = 425;   // RFC2817
    const HTTP_UPGRADE_REQUIRED = 426;                                            // RFC2817
    const HTTP_PRECONDITION_REQUIRED = 428;                                       // RFC6585
    const HTTP_TOO_MANY_REQUESTS = 429;                                           // RFC6585
    const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;                             // RFC6585
    const HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    const HTTP_INTERNAL_SERVER_ERROR = 500;
    const HTTP_NOT_IMPLEMENTED = 501;
    const HTTP_BAD_GATEWAY = 502;
    const HTTP_SERVICE_UNAVAILABLE = 503;
    const HTTP_GATEWAY_TIMEOUT = 504;
    const HTTP_VERSION_NOT_SUPPORTED = 505;
    const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;                        // RFC2295
    const HTTP_INSUFFICIENT_STORAGE = 507;                                        // RFC4918
    const HTTP_LOOP_DETECTED = 508;                                               // RFC5842
    const HTTP_NOT_EXTENDED = 510;                                                // RFC2774
    const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;                             // RFC6585

}
