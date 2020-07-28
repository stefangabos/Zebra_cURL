<img src="https://github.com/stefangabos/zebrajs/blob/master/docs/images/logo.png" alt="zebra-curl-logo" align="right" width="90">

# Zebra cURL

*A high performance cURL PHP library allowing the running of multiple requests at once, asynchronously*

[![Latest Stable Version](https://poser.pugx.org/stefangabos/zebra_curl/v/stable)](https://packagist.org/packages/stefangabos/zebra_curl) [![Total Downloads](https://poser.pugx.org/stefangabos/zebra_curl/downloads)](https://packagist.org/packages/stefangabos/zebra_curl) [![Monthly Downloads](https://poser.pugx.org/stefangabos/zebra_curl/d/monthly)](https://packagist.org/packages/stefangabos/zebra_curl) [![Daily Downloads](https://poser.pugx.org/stefangabos/zebra_curl/d/daily)](https://packagist.org/packages/stefangabos/zebra_curl) [![License](https://poser.pugx.org/stefangabos/zebra_curl/license)](https://packagist.org/packages/stefangabos/zebra_curl)

**Zebra cURL** is a high performance cURL PHP library which not only allows the running of multiple asynchronous requests at once, but also finished threads can be processed right away without having to wait for the other threads in the queue to finish.

Also, each time a request is completed another one is added to the queue, thus keeping a constant number of threads running at all times and eliminating wasted CPU cycles from busy waiting. This result is a faster and more efficient way of processing large quantities of cURL requests (like fetching thousands of RSS feeds at once), drastically reducing processing time.

This script supports GET (with caching), POST, HEADER, PUT, DELETE requests, basic downloads as well as downloads from FTP servers, HTTP Authentication, and requests made through proxy servers.

For maximum efficiency downloads are streamed (bytes downloaded are directly written to disk) removing the unnecessary strain from the server of having to read files into memory first, and then writing them to disk.

The code is heavily commented and generates no warnings/errors/notices when PHP's error reporting level is set to [E_ALL](https://www.php.net/manual/en/function.error-reporting.php).

> :books: Check out the [awesome documentation](https://stefangabos.github.io/Zebra_cURL/Zebra_cURL/Zebra_cURL.html)!

## Support the development of this library

If you like this project please star it on GitHub.<br>
If you are feeling very generous, you can also buy me a coffee through PayPal.<br>
Thank you!

[<img src="https://img.shields.io/github/stars/stefangabos/zebra_curl?color=green&label=star%20it%20on%20GitHub" width="132" height="20" alt="Star it on GitHub">](https://github.com/stefangabos/Zebra_cURL) [![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W6MCFT65DRN64)

## Features

- supports GET (with caching), POST, HEADER, PUT, DELETE requests, basic downloads as well as downloads from FTP servers, HTTP Authentication, and requests through proxy servers
- allows the running of multiple requests at once, asynchronously, and as soon as one thread finishes it can be processed right away without having to wait for the other threads in the queue to finish
- downloads are streamed (bytes downloaded are directly written to disk) removing the unnecessary strain from the server of having to read files into memory first, and then writing them to disk
- provides detailed information about the made requests
- has [awesome documentation](https://stefangabos.github.io/Zebra_cURL/Zebra_cURL/Zebra_cURL.html)
- code is heavily commented and generates no warnings/errors/notices when PHP's error reporting level is set to [E_ALL](https://www.php.net/manual/en/function.error-reporting.php)


## Requirements

PHP 5.3.0+ with the [cURL extension](https://www.php.net/manual/en/curl.installation.php) enabled.

## Installation

You can install via [Composer](https://packagist.org/packages/stefangabos/zebra_curl)

```bash
# get the latest stable release
composer require stefangabos/zebra_curl

# get the latest commit
composer require stefangabos/zebra_curl:dev-master
```

Or you can install it manually by downloading the latest version, unpacking it, and then including it in your project

```php
<?php

require_once 'path/to/Zebra_cURL.php';
```

## How to use

**Scrap a page**

```php
<?php

// include the library
// (you don't need this if you installed the library via Composer)
require 'path/to/Zebra_cURL.php';

// instantiate the Zebra cURL class
$curl = new Zebra_cURL();

// cache results 3600 seconds
$curl->cache('path/to/cache', 3600);

// since we are communicating over HTTPS, we load the CA bundle from the examples folder,
// so we don't get CURLE_SSL_CACERT response from cURL
// you can always update this bundle from https://curl.haxx.se/docs/caextract.html
$curl->ssl(true, 2, __DIR__ . '/cacert.pem');

// a simple way of scrapping a page
// (you can do more with the "get" method and callback functions)
echo $curl->scrap('https://github.com/', true);
```

**Fetch RSS feeds**

```php
<?php

// include the library
// (you don't need this if you installed the library via Composer)
require 'path/to/Zebra_cURL.php';

// instantiate the Zebra cURL class
$curl = new Zebra_cURL();

// cache results 3600 seconds
$curl->cache('path/to/cache', 3600);

// since we are communicating over HTTPS, we load the CA bundle from the examples folder,
// so we don't get CURLE_SSL_CACERT response from cURL
// you can always update this bundle from https://curl.haxx.se/docs/caextract.html
$curl->ssl(true, 2, __DIR__ . '/cacert.pem');

$feeds = array(
    'https://rss1.smashingmagazine.com/feed/'       =>  'Smashing Magazine',
    'https://feeds.feedburner.com/nettuts'          =>  'TutsPlus',
    'http://feeds.feedburner.com/alistapart/main'   =>  'A List Apart',
);

// get RSS feeds of some popular tech websites
$curl->get(array_keys($feeds), function($result) use ($feeds) {

    // everything went well at cURL level
    if ($result->response[1] == CURLE_OK) {

        // if server responded with code 200 (meaning that everything went well)
        // see https://httpstatus.es/ for a list of possible response codes
        if ($result->info['http_code'] == 200) {

            // the content is an XML, process it
            $xml = simplexml_load_string($result->body);

            // different types of RSS feeds...
            if (isset($xml->channel->item))

                // show title and date for each entry
                foreach ($xml->channel->item as $entry) {
                    echo '<h6>' . $feeds[$result->info['original_url']] . '</h6>';
                    echo '<h2><a href="' . $entry->link . '">' . $entry->title . '</a></h2>';
                    echo '<p><small>' . $entry->pubDate . '</small></p>';
                    echo '<p>' . substr(strip_tags($entry->description), 0, 500) . '</p><hr>';
                }

            // different types of RSS feeds...
            else

                // show title and date for each entry
                foreach ($xml->entry as $entry) {
                    echo '<h6>' . $feeds[$result->info['original_url']] . '</h6>';
                    echo '<h2><a href="' . $entry->link['href'] . '">' . $entry->title . '</a></h2>';
                    echo '<p><small>' . $entry->updated . '</small></p>';
                    echo '<p>' . substr(strip_tags($entry->content), 0, 500) . '</p><hr>';
                }

        // show the server's response code
        } else trigger_error('Server responded with code ' . $result->info['http_code'], E_USER_ERROR);

    // something went wrong
    // ($result still contains all data that could be gathered)
    } else trigger_error('cURL responded with: ' . $result->response[0], E_USER_ERROR);

});
```

**Use custom HTTP headers**

```php
// include the library
// (you don't need this if you installed the library via Composer)
require 'path/to/Zebra_cURL.php';

// instantiate the Zebra cURL class
$curl = new Zebra_cURL;

// set custom HTTP headers
$curl->option(CURLOPT_HTTPHEADER, [
    'accept: application/json',
    'X-Token-Foo-Bar: ABC123'   // Pass keys to APIs, for example
]);

echo $curl->scrap('https://httpbin.org/get') . PHP_EOL;
```

**Download an image**

```php
<?php

// include the library
// (you don't need this if you installed the library via Composer)
require 'path/to/Zebra_cURL.php';

// instantiate the Zebra cURL class
$curl = new Zebra_cURL();

// since we are communicating over HTTPS, we load the CA bundle from the examples folder,
// so we don't get CURLE_SSL_CACERT response from cURL
// you can always update this bundle from https://curl.haxx.se/docs/caextract.html
$curl->ssl(true, 2, __DIR__ . '/cacert.pem');

// download one of the official twitter image
$curl->download('https://abs.twimg.com/a/1362101114/images/resources/twitter-bird-callout.png', 'cache');
```


:books: Check out the [awesome documentation](https://stefangabos.github.io/Zebra_cURL/Zebra_cURL/Zebra_cURL.html)!
