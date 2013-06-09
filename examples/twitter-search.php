<?php

function callback($result) {

    // results from twitter is json-encoded;
    // remember, the "body" property of $result is run through "htmlentities()" so we need to "html_entity_decode" it
    $result->body = json_decode(html_entity_decode($result->body));

    // show everything
    print_r('<pre>');
    print_r($result->info);

}

// include the library
require '../Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL();

// cache results 60 seconds
$curl->cache('cache', 60);

// search twitter for the "jquery" hashtag
$curl->get('http://search.twitter.com/search.json?q=' . urlencode('#jquery'), 'callback');

?>