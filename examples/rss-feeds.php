<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>Minimalist RSS reader</title>
    <style type="text/css">
    h2 {
        font-family: Tahoma;
        font-size: 18px;
        margin: 1em 0 0;
    }
    h2 span {
        color: #C40000;
    }
    h2 a {
        color: #333;
        text-decoration: none;
    }
    p {
        font-family: Tahoma;
        font-size: 12px;
        margin: 0 0 1.4em;
    }
    </style>
</head>
<body>

<?php

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
require '../Zebra_cURL.php';

// instantiate the Zebra_cURL class
$curl = new Zebra_cURL();

// cache results 3600 seconds
$curl->cache('cache', 3600);

$feeds = array(
    'http://rss1.smashingmagazine.com/feed/'        =>  'Smashing Magazine',
    'http://feeds.feedburner.com/nettuts'           =>  'TutsPlus',
    'http://feeds.feedburner.com/alistapart/main'   =>  'A List Apart',
);

// get RSS feeds of some popular tech websites
$curl->get(array_keys($feeds), 'callback', $feeds);

?>

</body
</html>
