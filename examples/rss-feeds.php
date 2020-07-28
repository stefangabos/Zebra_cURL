<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>Minimalist RSS reader</title>
    <style>
    body {
        font-family: Tahoma;
        font-size: 14px;
        background: #f0f0f0;
    }
    .container {
        width: 580px;
        margin: 0 auto;
        background: #FFF;
        padding: 1.4em;
    }
    h2 {
        margin: 0.2em 0 0.2em;
        font-size: 170%;
        line-height: 1.1;
    }
    h6 {
        color: #C40000;
        margin: 0;
        font-size: 110%;
    }
    h2 a {
        color: #333;
        text-decoration: none;
    }
    p {
        margin: 0 0 1.4em;
    }
    hr {
        height: 1px;
        overflow: hidden;
        border: none;
        background: #dedede;
        margin-bottom: 1.4em;
    }
    hr:last-child {
        display: none;
    }
    </style>
</head>
<body>

    <div class="container">

    <?php

    // make sure error reporting is on
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // make sure cache folder exists and is writable
    if (!is_dir('cache') || !is_writable('cache')) trigger_error('the "cache" folder must be present and be writable in the "examples" folder', E_USER_ERROR);

    // make sure CA bundle exists
    elseif (!file_exists('cacert.pem')) trigger_error('"cacert.pem" file was not found', E_USER_ERROR);

    // include the library
    require '../Zebra_cURL.php';

    // instantiate the Zebra_cURL class
    $curl = new Zebra_cURL();

    // cache results 3600 seconds
    $curl->cache('cache', 3600);

    // since we are communicating over HTTPS, we load the CA bundle from the examples folder,
    // so we don't get CURLE_SSL_CACERT response from cURL
    // you can always update this bundle from https://curl.haxx.se/docs/caextract.html
    $curl->ssl(true, 2, __DIR__ . '/cacert.pem');

    $feeds = array(
        'https://www.smashingmagazine.com/feed/'    =>  'Smashing Magazine',
        'https://code.tutsplus.com/posts.atom'      =>  'TutsPlus',
        'https://alistapart.com/main/feed/'         =>  'A List Apart',
    );

    // get RSS feeds of some popular tech websites
    $curl->get(array_keys($feeds), function($result) use ($feeds) {

        // everything went well at cURL level
        if ($result->response[1] == CURLE_OK) {

            // if server responded with code 200 (meaning that everything went well)
            // see http://httpstatus.es/ for a list of possible response codes
            if ($result->info['http_code'] == 200) {

                // the content is an XML, process it
                $xml = simplexml_load_string($result->body);

                $limit = 0;

                // different types of RSS feeds...
                if (isset($xml->channel->item))

                    // show title and date for each entry
                    // (limit to 5 entries only)
                    foreach ($xml->channel->item as $entry) {
                        if (++$limit > 5) break;
                        echo '<h6>' . $feeds[$result->info['original_url']] . '</h6>';
                        echo '<h2><a href="' . $entry->link . '">' . $entry->title . '</a></h2>';
                        echo '<p><small>' . $entry->pubDate . '</small></p>';
                        echo '<p>' . substr(strip_tags($entry->description), 0, 500) . '</p><hr>';
                    }

                // different types of RSS feeds...
                 else

                    // show title and date for each entry
                    // (limit to 5 entries only)
                    foreach ($xml->entry as $entry) {
                        if (++$limit > 5) break;
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

    ?>

    </div>

</body>
</html>
