<?php

// include the library
require '../Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL();

// since we are comnunicating over HTTPS, we load a CA bundles so we don't get CURLE_SSL_CACERT response from cURL
$curl->ssl(true, 2, __DIR__ . '/cacert.pem');

// get a random file from mozilla's public ftp server at http://ftp.mozilla.org/
$curl->ftp_download('https://ftp.mozilla.org/pub/firefox/releases/65.0/KEY', 'cache');

echo 'File downloaded - look in the "cache" folder!';

?>
