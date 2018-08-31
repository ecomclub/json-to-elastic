<?php

class SendToElastic
{
    /** Elastic Search Host */
    private $elsHost;
    /** Elastic Search Index */
    private $elsIndex;
    /** Elastic Search Type */
    private $elsType;
    /** BasicAuth github */
    private $githubBasicAuth;
    /** Github repository */
    private $repository;
    /** Github repository path */
    private $repoPath;
    /** Object with github response */
    private $data;
    /** Id to send in elastic request */
    private $id;
    
    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        if (empty($options)) {
            throw new Exception('Error Processing Request');
        }

        $this->repository = $options['repository'];
        $this->repoPath = $options['repoPath'];
        $this->elsType = $options['elsType'];
        $this->elsIndex = $options['elsIndex'];
        $this->elsHost = isset($options['elsHost']) ? $options['elsHost'] : 'localhost:9200';
        $this->githubBasicAuth = $options['githubUser'] . ':' . $options['githubPass'];
    }

    /**
     * Make curl request, if $data is null the request is a GET
     * if has value will be a POST
     *
     * @param [string] $url
     * @param [any] $data
     * @param [array] $headers
     * @return void
     */
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
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
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

    /**
     * get json files on repository
     *
     * @return void
     */
    public function getJson()
    {
        $url = 'https://api.github.com/repos/' .
          $this->repository . '/contents/' . $this->repoPath . '?ref=master'; // make url with repository and path name
        $resp = (object)json_decode($this->request($url)); // request github api
        foreach ($resp as $repo) {
            switch ($repo->type) { // verify type of return
                case 'dir': // if is a dir
                    $dir = json_decode($this->request($repo->url)); // request the content on dir
                    foreach ($dir as $value) { // runs the repository looking for files
                        if ($this->isJson($value->name)) { // if file is a json file
                            $json = json_decode($this->request($value->url)); // request the json content
                            $this->data = base64_decode($json->content); // decode the content
                            $this->make(); // call the make(); function to send json to elastic search api
                        }
                    }
                    break;
                case 'file': // if is a file
                    if ($this->isJson($repo->name)) { // verify if it's is a valid json file
                        $json = json_decode($this->request($repo->url)); // request the json content
                        $this->data = base64_decode($json->content); // decode the content
                        $this->make(); // call the make(); function to send json to elastic search api
                    }
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * verify if file is a json by name
     *
     * @param [string] $j
     * @return boolean
     */
    public function isJson($j)
    {
        return (substr($j, 1) != '.' && substr($j, -5) === '.json') ? true : false;
    }

    /**
     * Send json file to elastic search endpoint
     *
     * @return void
     */
    public function make()
    {
        $ret = json_decode($this->data); // decode $this->data object
        $this->id = str_replace('/', '_', $ret->repo . $ret->path); // concat $reto->repo with $ret->path and repleace '/' to '_' to use like a unique id
        $url = $this->elsHost . '/' . $this->elsIndex . '/' . $this->elsType . '/' . $this->id; // make url to ES api request
        $this->request($url, $this->data); // make post request on $url path with $this->data content
    }
}
/*
$o = array(
    'githubUser' => '', // Github username
    'githubPass' => '', // Github passwd
    'repository' => '', // Repository name
    'repoPath' => '', // Repository Path
    'elsType' => '', //
    'elsIndex' => '', //
    'elsHost' => '' // Elastic search host - default value set localhost:9200
);
$a = new SendToElastic($o); // new instance of classe
$a->getJson(); // call the function
*/
