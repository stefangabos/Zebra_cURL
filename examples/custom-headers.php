<?php

// make sure error reporting is on
ini_set('display_errors', 1);
error_reporting(E_ALL);
require '../../../../../framework/helpers/common.php';

// make sure cache folder exists and is writable
if (!is_dir('cache') || !is_writable('cache')) trigger_error('the "cache" folder must be present and be writable in the "examples" folder', E_USER_ERROR);

// make sure CA bundle exists
elseif (!file_exists('cacert.pem')) trigger_error('"cacert.pem" file was not found', E_USER_ERROR);

// include the library
require '../Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL;

// since we are communicating over HTTPS, we load the CA bundle from the examples folder,
// so we don't get CURLE_SSL_CACERT response from cURL
// you can always update this bundle from https://curl.haxx.se/docs/caextract.html
$curl->ssl(true, 2, __DIR__ . '/cacert.pem');

// $urls = array(
//     array(
//         'url'   => 'https://c1eba17eab1cd688f5f1b6459cd9a5cf.m.pipedream.net',
//         'data'  => array('foo1' => rand(0, 9999)),
//     ),
//     array(
//         'url'   => 'https://c1eba17eab1cd688f5f1b6459cd9a5cf.m.pipedream.net',
//         'data'  => array('foo2' => rand(0, 9999)),
//     ),
// );


$urls = array(
    // array('https://c1eba17eab1cd688f5f1b6459cd9a5cf.m.pipedream.net' => array('url' => 'https://c1eba17eab1cd688f5f1b6459cd9a5cf.m.pipedream.net', 'data' => array('foo1' => rand(0, 9999)))),
    array('https://c1eba17eab1cd688f5f1b6459cd9a5cf.m.pipedream.net' => array('foo2' => rand(0, 9999))),
    // array('https://c1eba17eab1cd688f5f1b6459cd9a5cf.m.pipedream.net' => array('foo3' => rand(0, 9999))),
    // array('https://c1eba17eab1cd688f5f1b6459cd9a5cf.m.pipedream.net' => array('foo4' => rand(0, 9999))),
    // array('https://c1eba17eab1cd688f5f1b6459cd9a5cf.m.pipedream.net' => array('foo5' => rand(0, 9999))),
    // array('https://c1eba17eab1cd688f5f1b6459cd9a5cf.m.pipedream.net' => array('foo6' => rand(0, 9999))),
    // array('https://c1eba17eab1cd688f5f1b6459cd9a5cf.m.pipedream.net' => array('foo7' => rand(0, 9999))),
    // array('https://c1eba17eab1cd688f5f1b6459cd9a5cf.m.pipedream.net' => array('foo8' => rand(0, 9999))),
    // array('https://c1eba17eab1cd688f5f1b6459cd9a5cf.m.pipedream.net' => array('foo9' => rand(0, 9999))),
);

$curl->post($urls, function($result) {
    echo '<pre>';
    print_r($result);
});
