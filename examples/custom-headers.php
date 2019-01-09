<?php

// include the library
require __DIR__ . '/../Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL;

// set custom HTTP headers
$curl->option(CURLOPT_HTTPHEADER, [
    'accept: application/json',
    'X-Token-Foo-Bar: ABC123'   // Pass keys to APIs, for example
]);

echo $curl->scrap('http://httpbin.org/get') . PHP_EOL;
