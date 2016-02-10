<?php

// include the library
require '../Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL();

// cache results 3600 seconds
$curl->cache('cache', 3600);

// a simple way of scrapping a page
// (you can do more with the "get" method and callback functions)
echo $curl->scrap('https://google.com', true);

?>