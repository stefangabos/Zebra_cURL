<?php

// make sure error reporting is on
ini_set('display_errors', 1);
error_reporting(E_ALL);

// make sure cache folder exists and is writable
if (!is_dir('cache') || !is_writable('cache')) throw new Exception('the "cache" folder must be present and be writable in the "examples" folder');

// make sure CA bundle exists
elseif (!file_exists('cacert.pem')) throw new Exception('"cacert.pem" file was not found');

// include the library
require '../Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL();

// since we are communicating over HTTPS, we load the CA bundle from the examples folder,
// so we don't get CURLE_SSL_CACERT response from cURL
// you can always update this bundle from https://curl.se/docs/caextract.html
$curl->ssl(true, 2, __DIR__ . '/cacert.pem');

// download this project's logo
$curl->download('https://raw.githubusercontent.com/stefangabos/zebrajs/master/docs/images/logo.png', 'cache');

echo 'File downloaded to the "cache" folder.<br>Click <a href="cache/logo.png">here</a> to open it.';
