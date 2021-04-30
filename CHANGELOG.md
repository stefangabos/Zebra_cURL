## version 1.5.1 (May 01, 2021)

- fixed compatibility with PHP 8
## version 1.5.0 (September 29, 2020)

- the `get` method now allows passing of query strings in a nicer way
- fixed bug where when passing an associative array as an extra argument to any of the main methods, it would be incorrectly passed forward to the callback function
- fixed bug where not all the formats for URLs described in the documentation were actually supported
- documentation overhaul

## version 1.4.0 (May 8, 2019)

- custom options can now be set for each individual request when processing multiple requests at once; see [this issue](https://github.com/stefangabos/Zebra_cURL/issues/32) for the initial request and see the documentation for the _$urls_ argument in the documentation for the [get()](https://stefangabos.github.io/Zebra_cURL/Zebra_cURL/Zebra_cURL.html#methodget) method (or any other method where multiple URLs can be specified)
- removed from source code comments and documentation all references to the deprecated [create_function](http://php.net/en/create_function) function
- improved documentation for the [ssl()](https://stefangabos.github.io/Zebra_cURL/Zebra_cURL/Zebra_cURL.html#methodssl) method and how to easily get a CA certificates bundle to enable communication over HTTPS
- all examples were updated and were made functional again

## version 1.3.4 (May 22, 2017)

- fixed bug when having pauses between batches of requests
- fixed bug with script stopping after first request if the "threads" property was set to 1; thanks to @sbosshardt
- fixed broken file uploads when making POST and PUT requests
- fixed warning that would be shown when POST-ing/PUT-ing raw data; thanks **Sebastian Popa**
- fixed bug where the [header](https://stefangabos.github.io/Zebra_cURL/Zebra_cURL/Zebra_cURL.html#methodheader) method would only accept an array of URLs or would trigger an error otherwise
- minor source code tweaks
- unnecessary files are no more included when downloading from GitHub or via Composer
- documentation is now available in the repository and on GitHub
- the home of the library is now exclusively on GitHub

## version 1.3.3 (February 11, 2016)

- minimum required PHP version is now 5.3.0 instead of 5.0.3
- fixed a bug where the library would not download files having query strings or hashtags, and triggered warnings instead; thanks **Fshamri**
- fixed a bug which broke the [ftp_download](https://stefangabos.github.io/Zebra_cURL/Zebra_cURL/Zebra_cURL.html#methodftp_download) method
- fixed an issue where SSL certificate checking (CURLOPT_SSL_VERIFYPEER) was disabled by default; now it is enabled by default; thanks **Daniel Stenberg**
- added [delete](https://stefangabos.github.io/Zebra_cURL/Zebra_cURL/Zebra_cURL.html#methoddelete) and [put](https://stefangabos.github.io/Zebra_cURL/Zebra_cURL/Zebra_cURL.html#methodput) methods for making DELETE and PUT requests
- added [scrap](https://stefangabos.github.io/Zebra_cURL/Zebra_cURL/Zebra_cURL.html#methodscrap) method for quickly making a single [get](https://stefangabos.github.io/Zebra_cURL/Zebra_cURL/Zebra_cURL.html#methodget) request without the need of a callback function; thanks **Alexey Dorokhov**
- if caching is enabled but the cache folder doesn't exist, the library will now try and create it before triggering an error; thanks **Alexey Dorokhov**
- removed unused argument for the cookies method; thanks **Székely Dániel**
- updated existing examples and added a new one for scrapping a page
- losts of cleanup in the documentation

## version 1.3.2 (January 12, 2016)

- fixed a bug with http_authentication method not working
- fixed handling of edge case HTTP authentication
- fixed a warning message when setting the callback function as a method of a class, but the method was not available
- fixed bug with additional arguments not being passed to the get method
- updated the "post" method so that now arbitrary strings can also be POST-ed (instead of key => value pair only); useful for POST-ing JSON; thanks **Julian Zel**
- added possibility to unset previously set credentials for HTTP authentication
- POST parameters are now in the response as an additional "post" entry in the response array, both as string and as an array (only for POST requests)
- improved debug messages

## version 1.3.1 (February 03, 2015)

- Fixed a bug where setting any value to the "pause_interval" property would result in no requests being processed

## version 1.3.0 (January 31, 2015)

-  changed how the "post" method receives its arguments; **this breaks compatibility with previous versions!**
-  fixed a bug where making mixed calls to the library's method would get you into trouble as options set by one method were not being unset by another
-  all types of requests can now be cached, not just "get" and "header" request
-  URLs can now also be processed in batches with [pause in between](https://stefangabos.github.io/Zebra_cURL/Zebra_cURL/Zebra_cURL.html#var$pause_interval); helpful for relieving stress on servers, if you are processing hundreds or more requests
-  any type of requests can now be processed at once, using the newly added [queue](https://stefangabos.github.io/Zebra_cURL/Zebra_cURL/Zebra_cURL.html#methodqueue) method
-  the library now also sets default value for the CURLOPT_ENCODING option to "gzip,deflate"
-  improved documentation

## version 1.2.1 (November 12, 2014)

- fixed an issue that appeared since PHP 5.3.0 where, because of how [htmlentities](http://php.net/manual/en/function.htmlentities.php) has changed since that version, the body of a fetched page would be an empty string the output would contain invalid code unit sequences within the given encoding (utf-8 in our case)
- fixed an issues in composer.json due to which the class was not registered for autoloading after installation, and the library now explicitly requires lib-curl; thanks to **Igor Denisenko**
- fixed some documentation issues; thanks to **Igor Denisenko**

## version 1.1.0 (June 26, 2014)

- fixed a bug where the "post" method was not working with callback functions
- added a workaround for PHP bug: [https://bugs.php.net/bug.php?id=61141](https://bugs.php.net/bug.php?id=61141); thanks to **Syed I.R**
- custom arguments can now pe passed to the callback functions
- callback functions may now return FALSE instructing the library to not cache the respective request; this makes it easy to retry failed requests without having to clear all cache
- added an example for FTP download

## version 1.0.2 (August 29, 2013)

- fixed a bug where the "type" argument of the "http_authentication" method could not be changed; thanks **apmolsa**
- fixed a bug where the "chmod" argument of the "cache" method could not be changed; thanks **apmolsa**
- fixed a bug where PHP's htmlentities() function was accidentally being run on the response body of downloads
- the constructor now takes one argument specifying whether the response body should be run through PHP's htmlentities() function

## version 1.0.1 (May 30, 2013)

- fixed a bug where in PHP 5.2.7+ the library was triggering fatal error because I was using func_num_args() as an argument to another function
- the project is now also available on [GitHub](https://github.com/stefangabos/Zebra_cURL) and as a [package for Composer](https://packagist.org/packages/stefangabos/zebra_curl)

## version 1.0 (March 02, 2013)

- initial release
