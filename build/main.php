<?php

class SendToElastic
{
    private $elsHost;
    private $elsIndex;
    private $elsType;
    private $githubBasicAuth;
    private $repository;
    private $repoPath;
    private $data;
    private $id;

    public function __construct(array $options)
    {
        if (empty($options)) {
            throw new Exception('Error Processing Request');
        }

        $this->repository = $options['repository'];
        $this->repoPath = $options['repoPath'];
        $this->elsType = $options['elsType'];
        $this->elsIndex = $options['elsIndex'];
        $this->elsHost = $options['elsHost'];
        $this->githubBasicAuth = $options['githubUser'] . ':' . $options['githubPass'];
    }

    public function request($url, $data = null, $headers = null)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, dirname(__FILE__) . DIRECTORY_SEPARATOR);

        if (empty($data)) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $this->githubBasicAuth);
        }

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
    
        $response = curl_exec($ch);
    
        if (curl_error($ch)) {
            trigger_error('cURL error:' . curl_error($ch));
        }
    
        curl_close($ch);
        return $response;
    }

    public function getJson()
    {
        $url = 'https://api.github.com/repos/' .
          $this->repository . '/contents/' . $this->repoPath . '?ref=master';
        $resp = (object)json_decode($this->request($url));
        foreach ($resp as $repo) {
            if ($repo->type === 'dir') {
                $dir = json_decode($this->request($repo->url));
                foreach ($dir as $value) {
                    if ($this->isJson($value->name)) {
                        $json = json_decode($this->request($value->url));
                        $this->data = base64_decode($json->content);
                        $this->make();
                    }
                }
            }elseif($this->isJson($repo->name)){
                $json = json_decode($this->request($repo->url));
                $this->data = base64_decode($json->content);
                $this->make();
            }
        }
    }

    public function isJson($j)
    {
        return (substr($j, 1) != '.' && substr($j, -5) === '.json') ? true : false;
    }

    public function make()
    {
        $ret = json_decode($this->data);
        $this->id = str_replace('/', '_', $ret->repo . $ret->path);
        $url = $this->elsHost . '/' . $this->elsIndex . '/' . $this->elsType . '/' . $this->id;
        $this->request($url, $this->data);
    }
}
