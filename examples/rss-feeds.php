<?php

function callback($result) {

    // everything went well at cURL level
    if ($result->response[1] == CURLE_OK) {

        // if server responded with code 200 (meaning that everything went well)
        // see http://httpstatus.es/ for a list of possible response codes
        if ($result->info['http_code'] == 200) {

            // see all the returned data
            // remember, that the "body" property of $result, unless specifically disabled in the library's constructor,
            // is run through "htmlentities()", so you may want to "html_entity_decode" it
            print_r('<pre>');
            print_r($result->info);

        // show the server's response code
        } else die('Server responded with code ' . $result->info['http_code']);

    // something went wrong
    // ($result still contains all data that could be gathered)
    } else die('cURL responded with: ' . $result->response[0]);

}

// include the library
require '../Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL();

// cache results 3600 seconds
$curl->cache('cache', 3600);

// get RSS feeds of some popular tech websites
$curl->get(array(
    'http://rss1.smashingmagazine.com/feed/',
    'http://allthingsd.com/feed/',
    'http://feeds.feedburner.com/nettuts',
    'http://feeds.feedburner.com/alistapart/main',
), 'callback');

?>