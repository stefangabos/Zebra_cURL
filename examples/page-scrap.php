<?php

// include the library
require '../Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL();

// cache results 3600 seconds
$curl->cache('cache', 3600);

// since we are communicating over HTTPS, we load the CA bundle from the examples folder,
// so we don't get CURLE_SSL_CACERT response from cURL
// you can always update this bundle from https://curl.haxx.se/docs/caextract.html
$curl->ssl(true, 2, __DIR__ . '/cacert.pem');

// a simple way of scrapping a page
// (you can do more with the "get" method and callback functions)
echo $curl->scrap('https://github.com/', true);

?>