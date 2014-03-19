<?php

// include the library
require '../Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL();

// get a random file from mozilla's public ftp server at http://ftp.mozilla.org/
$curl->ftp_download('http://ftp.mozilla.org/pub/mozilla.org/webtools/bugzilla-4.0-to-4.0.5-nodocs.diff.gz', 'cache');

echo 'File downloaded!';

?>
