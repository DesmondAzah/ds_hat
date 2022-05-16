<?php
namespace App\Traits;

use Exception;
use GuzzleHttp\Client;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;

trait RequestTrait {

    /**
     * Request to ds_auth to verify and get access key
     * @param string $method GET, POST, PUT, etc.
     * @param string $requestUrl The URL to send the request to
     * @param array $data The data to be passed as JSON if it's not GET request
     *                 OR The data to be passed as Query String if it's with the GET request        
     * @param array $headers An array of headers to send with the request
     * @return array
     */
    public function authRequest($method, $requestUrl, $data = [], $headers = []) {
        $client = new Client([
            'base_uri' => $this->baseUrl
        ]);
        error_log("authRequest: ".$this->baseUrl.$requestUrl);
        $jwt = $this->generateToken();
        $headers['Authorization'] = 'Bearer ' . $jwt;
        $headers['GW-NAME'] = SERVICE_NAME;
        // create resolve option for curl when it's subdomain of localhost with port when developing
        $curl_resolve = preg_match("/\.localhost\:/", $this->baseUrl) ? str_replace("http://", "", $this->baseUrl).":127.0.0.1" : "";
        $curl_option = [
            // if this is other than GET request, pass "$data" to "json" option
            'json'      => $method !== 'GET' ? $data : [],
            // if this is GET request, pass "$data" to "query" option
            'query'       => $method === 'GET' ? $data : [],
            'headers'     => $headers,
            // fix issue of "Could not resolve host" for virtual host with port on curl
            'curl' => [
                CURLOPT_RESOLVE => [$curl_resolve]
            ]
        ];
        error_log(print_r($curl_option, true));
        $response = $client->request($method, $requestUrl, $curl_option);
        $contents = $response->getBody()->getContents();
        $decoded_content = json_decode($contents, true);
        // if content after is not json, echo it out with SERVICE_NAME if APP_DEBUG is on
        if (!isset($decoded_content)) {
            if (env("APP_DEBUG")) {
                echo SERVICE_NAME . ": $contents";
                die;
            } else {
                throw new Exception("Unexpected error.");
            }
        }
        // throw exception if response "success" return false
        if (isset($decoded_content['success']) && !$decoded_content['success']) {
            throw new Exception($decoded_content['message'], $response->getStatusCode());
        }
        return $decoded_content;
    }

    /**
     * Request to other external domain services using access code gotten from ds_auth
     * @param string $method GET, POST, PUT, etc.
     * @param string $requestUrl The URL to send the request to
     * @param array $data The data to be passed as JSON if it's not GET request
     *                 OR The data to be passed as Query String if it's with the GET request        
     * @param array $headers An array of headers to send with the request
     * @return array
     */
    public function dsRequest($method, $requestUrl, $data = [], $headers = []) {
        $client = new Client([
            'base_uri' => $this->baseUrl
        ]);
        /* LOGGING SERVICE - START */
        // $logging_service = app()->make("LoggingService");
        // $headers = $logging_service->updateRequestHeaders();
        /* LOGGING SERVICE - END*/
        
        // Header default value setup
        $headers['GW-NAME'] = SERVICE_NAME;
        if (!empty(request()->header('Authorization')))
            $headers['Authorization'] = request()->header('Authorization');

        // create resolve option for curl when it's subdomain of localhost with port when developing
        $curl_resolve = preg_match("/localhost\:/", $this->baseUrl) ? str_replace("http://", "", $this->baseUrl).":127.0.0.1" : "";
        $curl_option = [
            // if this is other than GET request, pass "$data" to "json" option
            'json'      => $method !== 'GET' ? $data : [],
            // if this is GET request, pass "$data" to "query" option
            'query'       => $method === 'GET' ? $data : [],
            'headers'     => $headers,
            // fix issue of "Could not resolve host" for virtual host with port on curl
            'curl' => [
                CURLOPT_RESOLVE => [$curl_resolve]
            ]
        ];
        $response = $client->request($method, $requestUrl, $curl_option);
        $contents = $response->getBody()->getContents();
        $decoded_content = json_decode($contents, true);
        // if content after is not json, echo it out with SERVICE_NAME if APP_DEBUG is on
        if (!isset($decoded_content)) {
            if (env("APP_DEBUG")) {
                echo SERVICE_NAME . ": $contents";
                die;
            } else {
                throw new Exception("Unexpected error.");
            }
        }
        // throw exception if response "success" return false
        if (isset($decoded_content['success']) && !$decoded_content['success']) {
            throw new Exception($decoded_content['message'], $response->getStatusCode());
        }
        return $decoded_content;
    }
    
    /**
     * Generate a new JWT
     * @return string
     */
    private function generateToken() {
        $cachedKeys = Cache::get('s_keys');
        $key = $cachedKeys[SERVICE_NAME]['s_key'];
        $payload = array(
            'gw_name' => SERVICE_NAME,
            'exp' => time() + 60,
        );
        return JWT::encode($payload, $key);
    }
}

?>