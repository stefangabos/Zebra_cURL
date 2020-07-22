<?php

// report all error messages
ini_set('display_errors', 1);
error_reporting(E_ALL);

// instantiate Zebra_cURL
require '../Zebra_cURL.php';
$curl = new Zebra_cURL();

// one or more URLs
$queue = array(
    'https://url1.com',
    'https://url2.com',
    'https://url3.com',
    'https://url4.com',
);

// a list of proxies
$proxies = array(
    array('ip' => '1.1.1.1', 'port' => '3128'),
    array('ip' => '1.1.1.2', 'port' => '3128'),
    array('ip' => '1.1.1.3', 'port' => '3128'),
    array('ip' => '1.1.1.4', 'port' => '3128'),
);

// the number of URLs to process at once
$threads = 10;

$current_proxy = 0;

// as long as there are URL in the queue
while (!empty($queue)) {

    // remove a bunch of URLs from the queue
    $urls = array_splice($queue, 0, $threads);

    // connect to a proxy server
    $curl->proxy($proxies[$current_proxy]['ip'], $proxies[$current_proxy]['port']);

    // one call for the whole bunch and a generic callback
    $curl->get($urls, function($result) use (&$queue, $proxies, &$current_proxy) {

        // everything went well and content was returned
        if ($result->response[1] == CURLE_OK && $result->info['http_code'] == 200 && $result->body !== '') {

            // do your thing

        // if something's wrong
        } else {

            // if there are more proxy servers to test
            if ($current_proxy < count($proxies) - 1) {

                // use the next available proxy
                $current_proxy++;

                // add URL back to queue
                $queue[] = $result->info['original_url'];

            }

        }

    });

}