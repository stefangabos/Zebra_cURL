<?php

// include the library
require '../Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL();

// since we are communicating over HTTPS, we load the CA bundle from the examples folder,
// so we don't get CURLE_SSL_CACERT response from cURL
// you can always update this bundle from https://curl.haxx.se/docs/caextract.html
$curl->ssl(true, 2, __DIR__ . '/cacert.pem');

// get a random file from mozilla's public ftp server at http://ftp.mozilla.org/
$curl->ftp_download('https://ftp.mozilla.org/pub/firefox/releases/65.0/KEY', 'cache');

echo 'File downloaded - look in the "cache" folder!';

?>
