<?php

namespace App\Traits;

use Exception;
use GuzzleHttp\Client;

trait ApiRequestHelper
{
    public function internalRequest($method, $requestUrl, $data = [], $headers =[]){
        error_log($this->baseUrl.$requestUrl." - ".$method);
        $requestUrl = $this->baseUrl.$requestUrl;
        $client = new Client(['base_uri' => $this->baseUrl]);

           // create resolve option for curl when it's subdomain of localhost with port when developing
           $curl_resolve = preg_match("/localhost/", $this->baseUrl) ? str_replace("http://", "", $this->baseUrl).":127.0.0.1" : "";
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
           $response = $client->request($method, $requestUrl);
            $contents = $response->getBody()->getContents();
           $decoded_content = json_decode($contents, true);
        // if content after is not json, echo it out with SERVICE_NAME if APP_DEBUG is on
            if (!isset($decoded_content)) {
                if (env("APP_DEBUG")) {
                    echo  ": $contents";
                    die;
                } else {
                    throw new Exception("Unexpected error.");
                }
            }
        return $decoded_content;
    }
}
