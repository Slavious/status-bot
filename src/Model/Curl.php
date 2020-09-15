<?php

namespace App\Model;

class Curl
{
    private $status;

    private $result;

    private $info;

    private $url;

    public function __construct($url)
    {
        $this->url = $url;
        $this->init();
    }

    public function init()
    {
        $curl = curl_init($this->url);
        curl_setopt_array($curl, self::getOptions());
        $this->result = curl_exec($curl);
        $this->info = curl_getinfo($curl);
        curl_close($curl);

        return $this->result;
    }

    public function getStatus()
    {
        return $this->info['http_code'];
    }

    /**
     * @return array
     */
    private static function getOptions(): array
    {
        return [
            CURLOPT_RETURNTRANSFER => true,   // return web page
            CURLOPT_HEADER => false,  // don't return headers
            CURLOPT_FOLLOWLOCATION => true,   // follow redirects
            CURLOPT_MAXREDIRS => 10,     // stop after 10 redirects
            CURLOPT_ENCODING => "",     // handle compressed
            CURLOPT_AUTOREFERER => true,   // set referrer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,    // time-out on connect
            CURLOPT_TIMEOUT => 120,    // time-out on response
        ];
    }

}