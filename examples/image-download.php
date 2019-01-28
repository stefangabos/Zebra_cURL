<?php

// include the library
require '../Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL();

// since we are communicating over HTTPS, we load a CA bundles so we don't get CURLE_SSL_CACERT response from cURL
$curl->ssl(true, 2, __DIR__ . '/cacert.pem');

// download one of the official twitter image
$curl->download('https://abs.twimg.com/a/1362101114/images/resources/twitter-bird-callout.png', 'cache');

echo 'Image downloaded - look in the "cache" folder!';

?>