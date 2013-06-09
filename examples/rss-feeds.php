<?php

function callback($result) {

    // remember, the "body" property of $result is run through "htmlentities()", so you may need to "html_entity_decode" it

    // show everything
    print_r('<pre>');
    print_r($result->info);

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
    'http://www.webmonkey.com/feed/',
    'http://feeds.feedburner.com/alistapart/main',
), 'callback');

?>