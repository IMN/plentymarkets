<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 23/03/20
 * Time: 16:11
 */

namespace IMN\Services\Api;

class HttpClient
{

    private $headers = array(
        'Content-Type' => 'application/json',
    );
    private $serverUri = 'https://api.imn.io';

    public $info = array();

    public $rawResponse = "";

    public function addHeader($name, $value) {
        $this->headers[$name] = $value;
    }

    public function setBearer($bearer) {
        $this->headers['Authorization'] = 'Bearer '.$bearer;
    }

    public function setApiKey($apiKey) {
        $this->headers['Ocp-Apim-Subscription-Key'] = $apiKey;
    }

    private function getHeaders() {
        $headers = array();
        foreach($this->headers as $key => $header) {
            $headers[] = $key.": ".$header;
        }
        return $headers;
    }

    public function clearHeader($key) {
        if(isset($this->headers[$key])) {
            unset($this->headers[$key]);
        }
    }

    public function get($path) {
        $this->info = array();
        $this->rawResponse = "";
        $url = $this->serverUri . $path;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $this->info = curl_getinfo($ch);
        curl_close($ch);
        $this->rawResponse = $result;
        return \json_decode($result, true);
    }

    public function post($path, $body) {
        $this->info = array();
        $this->rawResponse = "";
        $url = $this->serverUri . $path;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $this->info = curl_getinfo($ch);
        curl_close($ch);
        $this->rawResponse = $result;
        return \json_decode($result, true);
    }


}