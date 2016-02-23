# Zebra_cURL

####A high performance PHP cURL library

----

[Packagist](https://packagist.org/) stats

[![Latest Stable Version](https://poser.pugx.org/stefangabos/zebra_curl/v/stable)](https://packagist.org/packages/stefangabos/zebra_curl) [![Total Downloads](https://poser.pugx.org/stefangabos/zebra_curl/downloads)](https://packagist.org/packages/stefangabos/zebra_curl) [![Monthly Downloads](https://poser.pugx.org/stefangabos/zebra_curl/d/monthly)](https://packagist.org/packages/stefangabos/zebra_curl) [![Daily Downloads](https://poser.pugx.org/stefangabos/zebra_curl/d/daily)](https://packagist.org/packages/stefangabos/zebra_curl) [![License](https://poser.pugx.org/stefangabos/zebra_curl/license)](https://packagist.org/packages/stefangabos/zebra_curl)

**Zebra_cURL** is a high performance PHP library acting as a wrapper to PHP’s <a href="http://www.php.net/manual/en/book.curl.php">libcurl library</a>, which not only allows the running of multiple requests at once asynchronously, in parallel, but also as soon as one thread finishes it can be processed right away without having to wait for the other threads in the queue to finish.

Also, each time a request is completed another one is added to the queue, thus keeping a constant number of threads running at all times and eliminating wasted CPU cycles from busy waiting. This result is a faster and more efficient way of processing large quantities of cURL requests (like fetching thousands of RSS feeds at once), drastically reducing processing time.

This script supports GET (with caching), POST, HEADER, PUT, DELETE requests, basic downloads as well as downloads from FTP servers, HTTP Authentication, and requests made through proxy servers.

For maximum efficiency downloads are streamed (bytes downloaded are directly written to disk) removing the unnecessary strain from the server of having to read files into memory first, and then writing them to disk.

Zebra_cURL requires the <a href="http://www.php.net/manual/en/curl.installation.php">PHP cURL extension</a> to be enabled.

The code is heavily commented and generates no warnings/errors/notices when PHP’s error reporting level is set to <a href="http://www.php.net/manual/en/function.error-reporting.php">E_ALL</a>.

##Features

- supports GET (with caching), POST, HEADER, PUT, DELETE requests, basic downloads as well as downloads from FTP servers, HTTP Authentication, and requests through proxy servers
- allows the running of multiple requests at once asynchronously, in parallel, but also as soon as one thread finishes it can be processed right away without having to wait for the other threads in the queue to finish
- downloads are streamed (bytes downloaded are directly written to disk) removing the unnecessary strain from the server of having to read files into memory first, and then writing them to disk
- provides a very detailed information about the made requests
- has <a href="http://stefangabos.ro/wp-content/docs/Zebra_cURL/Zebra_cURL/Zebra_cURL.html">comprehensive documentation</a>
- code is heavily commented and generates no warnings/errors/notices when PHP’s error reporting level is set to E_ALL

## Requirements

PHP 5.3.0+ with the cURL extension installed

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
                    echo '<h2><span>' . $feeds[$result->info['original_url']] . '</span> <a href="' . $entry->link . '">' . $entry->title . '</a></h2>';
                    echo '<p>' . $entry->pubDate . '</p><hr>';
                }

            // different types of RSS feeds...
            else

                // show title and date for each entry
                foreach ($xml->entry as $entry) {
                    echo '<h2><span>' . $feeds[$result->info['original_url']] . '</span> <a href="' . $entry->link['href'] . '">' . $entry->title . '</a></h2>';
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

Documentation and more information can be found on the **[project's homepage](http://stefangabos.ro/php-libraries/zebra-curl/)**
