# json-to-elastic
Search for JSON files on GitHub repository and sync with ElasticSearch cluster.

## Using
```php
include './send-to-elastic.php'; // include class

/** array with options required */

$options = array(
    'githubUser' => 'Fulano', // Github Username
    'githubPass' => 'FulanoStrongPasswd', // Github Passwd
    'repository' => 'Fulano/Repository', // Repository Name
    'repoPath' => 'src', // Repository Path
    'elsType' => 'ElasticType', // Elasticsearch Type
    'elsIndex' => 'ElasticIndex', // Elasticsearch Index
    'elsHost' => 'localhost:9200' // Elasticsearch Host
);

// Create a new instance of class passing the array with options
$el = new SendToElastic($options);

// Call the function 
$el->getJson();

```
