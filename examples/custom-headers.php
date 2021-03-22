<?php

// make sure error reporting is on
ini_set('display_errors', 1);
error_reporting(E_ALL);

// make sure cache folder exists and is writable
if (!is_dir('cache') || !is_writable('cache')) trigger_error('the "cache" folder must be present and be writable in the "examples" folder', E_USER_ERROR);

// make sure CA bundle exists
elseif (!file_exists('cacert.pem')) trigger_error('"cacert.pem" file was not found', E_USER_ERROR);

// include the library
require '../Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL();

// since we are communicating over HTTPS, we load the CA bundle from the examples folder,
// so we don't get CURLE_SSL_CACERT response from cURL
// you can always update this bundle from https://curl.haxx.se/docs/caextract.html
$curl->ssl(true, 2, __DIR__ . '/cacert.pem');

$curl->get('https://postman-echo.com/get?foo=bar1', function() use (&$curl) {
    echo "Call 1 finished.\n";
    // $curl->get('https://postman-echo.com/get?foo=bar2', function () {
    //     echo "Call 2 finished.\n";
    // });
});