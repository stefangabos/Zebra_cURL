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

// the number of URLs to process at once
$threads = 3;

$queue = array(
    'https://postman-echo.com/get?foo=bar1',
    'https://postman-echo.com/get?foo=bar2',
    'https://postman-echo.com/get?foo=bar3',
    'https://postman-echo.com/get?foo=bar4',
    'https://postman-echo.com/get?foo=bar5',
    'https://postman-echo.com/get?foo=bar6',
    'https://postman-echo.com/get?foo=bar7',
    'https://postman-echo.com/get?foo=bar8',
    'https://postman-echo.com/get?foo=bar9',

);

// we're using this so we can output stuff as we go
ob_start();

// as long as there are URL in the queue
while (!empty($queue)) {

    // remove a bunch of URLs from the queue
    $urls = array_splice($queue, 0, $threads);

    // one call for the whole bunch and send results to a callback
    $curl->get($urls, function($result) use (&$queue) {

        // we use these so we can output stuff as we go
        echo str_pad('<pre>done processing URL ' . $result->info['original_url'], 1024);
        ob_flush();
        flush();

        // if not yet done
        if (strpos($result->info['original_url'], '-1') === false) {

            // we use these so we can output stuff as we go
            echo str_pad("<pre>\t -> \tqueueing request " . $result->info['original_url'] . "-1", 1024);
            ob_flush();
            flush();

            // queue an extra request
            $queue[] = $result->info['original_url'] . '-1';

        }

    });

}
