<?php

class SendToElastic
{
    private $index;
    private $host;
    private $path;
    private $type;
    private $data;

    public function __construct($path, $type, $index, $host)
    {
        $this->path = $path;
        $this->type = $type;
        $this->index = $index;
        $this->host = $host;
    }

    public function request($url, $data=null, $headers = null)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, dirname(__FILE__) . DIRECTORY_SEPARATOR);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
    
        $response = curl_exec($ch);
    
        if (curl_error($ch)) {
            trigger_error('Curl Error:' . curl_error($ch));
        }
    
        curl_close($ch);
        return $response;
    }

    public function getJson()
    {
        $url = 'https://api.github.com/repos/ecomclub/' . $this->path . '/contents/docs?ref=master';
        $resp = (object)json_decode($this->request($url));
        foreach ($resp as $repo) {
            if ($repo->type == "dir") {
                $dir = json_decode($this->request($repo->url));
                foreach ($dir as $value) {
                    if ($this->isJson($value->name)) {
                        $json = json_decode($this->request($value->url));
                        $this->data = base64_decode($json->content);
                        print_r($this->data);
                        $this->make();
                    }
                }
            }
        }
    }

    public function isJson($j)
    {
        return substr($j, -5) === ".json" ? true : false;
    }

    public function make()
    {
        $url = $this->host . '/' . $this->type . '/' . $this->index;
        $this->request($url, $this->data);
    }
}

$a = new SendToElastic();
$a->getJson();
