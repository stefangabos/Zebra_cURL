    // the callback function to be executed for each and every
    // request, as soon as a request finishes
    function mycallback($result) {
     
        // everything went well at cURL level
        if ($result->response[1] == CURLE_OK) {
     
            // if server responded with code 200 (meaning that everything went well)
            // see http://httpstatus.es/ for a list of possible response codes
            if ($result->info['http_code'] == 200) {
     
                // see all the returned data
                print_r('<pre>');
                print_r($result);
     
            // show the server's response code
            } else die('Server responded with code ' . $result->info['http_code']);
     
        // something went wrong
        // ($result still contains all data that could be gathered)
        } else die('cURL responded with: ' . $result->response[0]);
     
    }
     
    // include the Zebra_cURL library
    require 'path/to/Zebra_cURL';
     
    // instantiate the class
    $curl = new Zebra_cURL();
     
    // connect to a proxy server
    // (that's a random one i got from http://www.hidemyass.com/proxy-list/)
    //$curl->proxy('187.63.32.250', '3128');
     
    // fetch a page
    $curl->get('http://www.google.com/', 'mycallback');
