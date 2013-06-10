##Zebra_cURL

####A high performance PHP cURL library

**Zebra_cURL** is a high performance PHP library acting as a wrapper to PHP’s <a href="http://www.php.net/manual/en/book.curl.php">libcurl library</a>, which not only allows the running of multiple requests at once asynchronously, in parallel, but also as soon as one thread finishes it can be processed right away without having to wait for the other threads in the queue to finish.

Also, each time a request is completed another one is added to the queue, thus keeping a constant number of threads running at all times and eliminating wasted CPU cycles from busy waiting. This result is a faster and more efficient way of processing large quantities of cURL requests (like fetching thousands of RSS feeds at once), drastically reducing processing time.

This script supports GET (with caching) and POST request, basic downloads as well as downloads from FTP servers, HTTP Authentication, and requests through proxy servers.

For maximum efficiency downloads are streamed (bytes downloaded are directly written to disk) removing the unnecessary strain from the server of having to read files into memory first, and then writing them to disk.

Zebra_cURL requires the <a href="http://www.php.net/manual/en/curl.installation.php">PHP cURL extension</a> to be enabled.

The code is heavily commented and generates no warnings/errors/notices when PHP’s error reporting level is set to <a href="http://www.php.net/manual/en/function.error-reporting.php">E_ALL</a>.

##Features

- supports GET (with caching) and POST request, basic downloads as well as downloads from FTP servers, HTTP Authentication, and requests through proxy servers
- allows the running of multiple requests at once asynchronously, in parallel, but also as soon as one thread finishes it can be processed right away without having to wait for the other threads in the queue to finish
- downloads are streamed (bytes downloaded are directly written to disk) removing the unnecessary strain from the server of having to read files into memory first, and then writing them to disk
- provides a very detailed information about the made requests
- has <a href="http://stefangabos.ro/wp-content/docs/Zebra_cURL/Zebra_cURL/Zebra_cURL.html">comprehensive documentation</a>
- code is heavily commented and generates no warnings/errors/notices when PHP’s error reporting level is set to E_ALL

## Requirements

PHP 5.0.2+ with the cURL extension installed

## How to use

**Fetch RSS feeds**

```php

<?php

function callback($result) {

    // remember, the "body" property of $result is run through
    // "htmlentities()", so you may need to "html_entity_decode" it

    // show everything
    print_r('<pre>');
    print_r($result->info);

}

require 'path/to/Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL();

// cache results 60 seconds
$curl->cache('cache', 60);

// get RSS feeds of some popular tech websites
$curl->get(array(
    'http://rss1.smashingmagazine.com/feed/',
    'http://allthingsd.com/feed/',
    'http://feeds.feedburner.com/nettuts',
    'http://www.webmonkey.com/feed/',
    'http://feeds.feedburner.com/alistapart/main',
), 'callback');

?>

```

**Twitter search**

```php

<?php

function callback($result) {

    // results from twitter is json-encoded;
    // remember, the "body" property of $result is run through
    // "htmlentities()" so we need to "html_entity_decode" it
    $result->body = json_decode(html_entity_decode($result->body));

    // show everything
    print_r('<pre>');
    print_r($result);

}

// include the library
require 'path/to/Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL();

// cache results 60 seconds
$curl->cache('cache', 60);

// search twitter for the "jquery" hashtag
$curl->get('http://search.twitter.com/search.json?q=' . urlencode('#jquery'), 'callback');

?>

```

**Download an image**

```php

<?php

// include the library
require 'path/to/Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL();

// download one of the official twitter image
$curl->download('https://abs.twimg.com/a/1362101114/images/resources/twitter-bird-callout.png', 'cache');

?>

```

Documentation and more information can be found on the **[project's homepage](http://stefangabos.ro/php-libraries/zebra-curl/)**
