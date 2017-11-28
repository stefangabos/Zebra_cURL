<img src="https://github.com/stefangabos/zebrajs/blob/master/docs/images/logo.png" alt="zebrajs" align="right">

# Zebra_cURL

*A high performance PHP cURL library allowing the running of multiple requests at once, asynchronously*

[![Latest Stable Version](https://poser.pugx.org/stefangabos/zebra_curl/v/stable)](https://packagist.org/packages/stefangabos/zebra_curl) [![Total Downloads](https://poser.pugx.org/stefangabos/zebra_curl/downloads)](https://packagist.org/packages/stefangabos/zebra_curl) [![Monthly Downloads](https://poser.pugx.org/stefangabos/zebra_curl/d/monthly)](https://packagist.org/packages/stefangabos/zebra_curl) [![Daily Downloads](https://poser.pugx.org/stefangabos/zebra_curl/d/daily)](https://packagist.org/packages/stefangabos/zebra_curl) [![License](https://poser.pugx.org/stefangabos/zebra_curl/license)](https://packagist.org/packages/stefangabos/zebra_curl)

**Zebra_cURL** is a high performance PHP cURL which not only allows the running of multiple requests at once asynchronously but also finished threads can be processed right away without having to wait for the other threads in the queue to finish.

Also, each time a request is completed another one is added to the queue, thus keeping a constant number of threads running at all times and eliminating wasted CPU cycles from busy waiting. This result is a faster and more efficient way of processing large quantities of cURL requests (like fetching thousands of RSS feeds at once), drastically reducing processing time.

This script supports GET (with caching), POST, HEADER, PUT, DELETE requests, basic downloads as well as downloads from FTP servers, HTTP Authentication, and requests made through proxy servers.

For maximum efficiency downloads are streamed (bytes downloaded are directly written to disk) removing the unnecessary strain from the server of having to read files into memory first, and then writing them to disk.

The code is heavily commented and generates no warnings/errors/notices when PHP's error reporting level is set to [E_ALL](http://www.php.net/manual/en/function.error-reporting.php).

## Support the development of this library

[![Donate](https://img.shields.io/badge/Be%20kind%20%7C%20Donate%20$3%20with%20-%20PayPal%20-brightgreen.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W6MCFT65DRN64)

## Features

- supports GET (with caching), POST, HEADER, PUT, DELETE requests, basic downloads as well as downloads from FTP servers, HTTP Authentication, and requests through proxy servers
- allows the running of multiple requests at once, asynchronously, and as soon as one thread finishes it can be processed right away without having to wait for the other threads in the queue to finish
- downloads are streamed (bytes downloaded are directly written to disk) removing the unnecessary strain from the server of having to read files into memory first, and then writing them to disk
- provides a very detailed information about the made requests
- has [awesome documentation](https://stefangabos.github.io/Zebra_cURL/Zebra_cURL/Zebra_cURL.html)
- code is heavily commented and generates no warnings/errors/notices when PHP's error reporting level is set to [E_ALL](http://www.php.net/manual/en/function.error-reporting.php)

## Requirements

PHP 5.3.0+ with the [cURL extension](http://www.php.net/manual/en/curl.installation.php) enabled.

## Installation

Download the latest version, unpack it, and load it in your project

```php
require_once 'Zebra_cURL.php';
```

## Installation with Composer

You can install Zebra_cURL via [Composer](https://packagist.org/packages/stefangabos/zebra_curl)

```
composer require stefangabos/zebra_curl
```

## How to use

**Scrap a page**

```php
// include the library
require 'path/to/Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL();

// cache results 3600 seconds
$curl->cache('path/to/cache', 3600);

// a simple way of scrapping a page
// (you can do more with the "get" method and callback functions)
echo $curl->scrap('https://google.com', true);
```

**Fetch RSS feeds**

```php
function callback($result, $feeds) {

    // everything went well at cURL level
    if ($result->response[1] == CURLE_OK) {

        // if server responded with code 200 (meaning that everything went well)
        // see http://httpstatus.es/ for a list of possible response codes
        if ($result->info['http_code'] == 200) {

            // the content is an XML, process it
            $xml = simplexml_load_string($result->body);

            // different types of RSS feeds...
            if (isset($xml->channel->item))

                // show title and date for each entry
                foreach ($xml->channel->item as $entry) {
                    echo '<h2><span>' . $feeds[$result->info['original_url']] . '</span> [' . $entry->title . '](' . $entry->link . ')</h2>';
                    echo '<p>' . $entry->pubDate . '</p><hr>';
                }

            // different types of RSS feeds...
            else

                // show title and date for each entry
                foreach ($xml->entry as $entry) {
                    echo '<h2><span>' . $feeds[$result->info['original_url']] . '</span> [' . $entry->title . '](' . $entry->link['href'] . ')</h2>';
                    echo '<p>' . $entry->updated . '</p><hr>';
                }

        // show the server's response code
        } else die('Server responded with code ' . $result->info['http_code']);

    // something went wrong
    // ($result still contains all data that could be gathered)
    } else die('cURL responded with: ' . $result->response[0]);

}

// include the library
require 'path/to/Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL();

// cache results 3600 seconds
$curl->cache('path/to/cache', 3600);

$feeds = array(
    'http://rss1.smashingmagazine.com/feed/'        =>  'Smashing Magazine',
    'http://feeds.feedburner.com/nettuts'           =>  'TutsPlus',
    'http://feeds.feedburner.com/alistapart/main'   =>  'A List Apart',
);

// get RSS feeds of some popular tech websites
$curl->get(array_keys($feeds), 'callback', $feeds);
```

**Download an image**

```php
// include the library
require 'path/to/Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL();

// download one of the official twitter image
$curl->download('https://abs.twimg.com/a/1362101114/images/resources/twitter-bird-callout.png', 'cache');
```

:books: Checkout the [awesome documentation](https://stefangabos.github.io/Zebra_cURL/Zebra_cURL/Zebra_cURL.html)!