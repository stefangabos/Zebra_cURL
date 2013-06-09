<?php

// include the library
require '../Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL();

// download one of the official twitter image
$curl->download('https://abs.twimg.com/a/1362101114/images/resources/twitter-bird-callout.png', 'cache');

echo 'Image downloaded!';

?>