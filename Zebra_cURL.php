<?php

/**
 *  Zebra_cURL, a high performance PHP cURL library
 *
 *  Zebra_cURL is a high performance PHP library acting as a wrapper to PHP's {@link http://www.php.net/manual/en/book.curl.php libcurl library},
 *  which not only allows the running of multiple requests at once asynchronously, in parallel, but also as soon as one
 *  thread finishes it can be processed right away without having to wait for the other threads in the queue to finish.
 *
 *  Also, each time a request is completed another one is added to the queue, thus keeping a constant number of threads
 *  running at all times and eliminating wasted CPU cycles from busy waiting. This result is a faster and more efficient
 *  way of processing large quantities of cURL requests (like fetching thousands of RSS feeds at once), drastically reducing
 *  processing time.
 *
 *  This script supports GET and POST request, basic downloads as well as downloads from FTP servers, HTTP Authentication,
 *  and requests through proxy servers.
 *
 *  For maximum efficiency downloads are streamed (bytes downloaded are directly written to disk) removing the unnecessary
 *  strain from the server of having to read files into memory first, and then writing them to disk.
 *
 *  Zebra_cURL requires the {@link http://www.php.net/manual/en/curl.installation.php PHP cURL extension} to be enabled.
 *
 *  The code is heavily commented and generates no warnings/errors/notices when PHP's error reporting level is set to
 *  {@link http://www.php.net/manual/en/function.error-reporting.php E_ALL}.
 *
 *  Visit {@link http://stefangabos.ro/php-libraries/zebra-curl/} for more information.
 *
 *  For more resources visit {@link http://stefangabos.ro/}
 *
 *  @author     Stefan Gabos <contact@stefangabos.ro>
 *  @version    1.0.1 (last revision: May 30, 2013)
 *  @copyright  (c) 2013 Stefan Gabos
 *  @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE
 *  @package    Zebra_cURL
 */

class Zebra_cURL {

    /**
     *  The number of parallel, asynchronous, requests to be processed by the library at once.
     *
     *  <code>
     *  // allow execution of 30 simultaneous threads
     *  $curl->threads = 30;
     *  </code>
     *
     *  Note that the library will keep this number of parallel threads running at all times (unless, of course, there
     *  are less remaining URLs to process); it's doing this by starting a new thread as soon as another one finishes,
     *  instead of waiting for each batch to finish, and so on, until there are no more URLs to process, and thus
     *  greatly decreasing execution time.
     *
     *  Default is 10.
     *
     *  @var integer
     */
    public $threads;

    /**
     * Default value is true, can be changed by giving the constractor parameter value false.
     *
     * Used by the {@link _process()} to determine if we run response body through PHP's htmlentities function.
     *
     * @access private
     *
     */
    private $_htmlentities;

    /**
     *  An associative array linked with all the resources, used to store original URL and file pointer resources, used
     *  for streaming downloads.
     *
     *  @var array
     *
     *  @access private
     */
    private $_info;

    /**
     *  Used by the {@link _process()} method to keep track of URLs that need to be processed.
     *
     *  @access private
     */
    private $_queue;

    /**
     *  The cURL multi handle
     *
     *  @var resource
     *
     *  @access private
     */
    private $_multi_handle;

    /**
     *  Possible values of the "result" attribute in the object passed to the callback function.
     *
     *  @var array
     *
     *  @access private
     */
    private $_response_messages = array(
        0   =>  'CURLE_OK',
        1   =>  'CURLE_UNSUPPORTED_PROTOCOL',
        2   =>  'CURLE_FAILED_INIT',
        3   =>  'CURLE_URL_MALFORMAT',
        4   =>  'CURLE_URL_MALFORMAT_USER',
        5   =>  'CURLE_COULDNT_RESOLVE_PROXY',
        6   =>  'CURLE_COULDNT_RESOLVE_HOST',
        7   =>  'CURLE_COULDNT_CONNECT',
        8   =>  'CURLE_FTP_WEIRD_SERVER_REPLY',
        9   =>  'CURLE_REMOTE_ACCESS_DENIED',
        11  =>  'CURLE_FTP_WEIRD_PASS_REPLY',
        13  =>  'CURLE_FTP_WEIRD_PASV_REPLY',
        14  =>  'CURLE_FTP_WEIRD_227_FORMAT',
        15  =>  'CURLE_FTP_CANT_GET_HOST',
        17  =>  'CURLE_FTP_COULDNT_SET_TYPE',
        18  =>  'CURLE_PARTIAL_FILE',
        19  =>  'CURLE_FTP_COULDNT_RETR_FILE',
        21  =>  'CURLE_QUOTE_ERROR',
        22  =>  'CURLE_HTTP_RETURNED_ERROR',
        23  =>  'CURLE_WRITE_ERROR',
        25  =>  'CURLE_UPLOAD_FAILED',
        26  =>  'CURLE_READ_ERROR',
        27  =>  'CURLE_OUT_OF_MEMORY',
        28  =>  'CURLE_OPERATION_TIMEDOUT',
        30  =>  'CURLE_FTP_PORT_FAILED',
        31  =>  'CURLE_FTP_COULDNT_USE_REST',
        33  =>  'CURLE_RANGE_ERROR',
        34  =>  'CURLE_HTTP_POST_ERROR',
        35  =>  'CURLE_SSL_CONNECT_ERROR',
        36  =>  'CURLE_BAD_DOWNLOAD_RESUME',
        37  =>  'CURLE_FILE_COULDNT_READ_FILE',
        38  =>  'CURLE_LDAP_CANNOT_BIND',
        39  =>  'CURLE_LDAP_SEARCH_FAILED',
        41  =>  'CURLE_FUNCTION_NOT_FOUND',
        42  =>  'CURLE_ABORTED_BY_CALLBACK',
        43  =>  'CURLE_BAD_FUNCTION_ARGUMENT',
        45  =>  'CURLE_INTERFACE_FAILED',
        47  =>  'CURLE_TOO_MANY_REDIRECTS',
        48  =>  'CURLE_UNKNOWN_TELNET_OPTION',
        49  =>  'CURLE_TELNET_OPTION_SYNTAX',
        51  =>  'CURLE_PEER_FAILED_VERIFICATION',
        52  =>  'CURLE_GOT_NOTHING',
        53  =>  'CURLE_SSL_ENGINE_NOTFOUND',
        54  =>  'CURLE_SSL_ENGINE_SETFAILED',
        55  =>  'CURLE_SEND_ERROR',
        56  =>  'CURLE_RECV_ERROR',
        58  =>  'CURLE_SSL_CERTPROBLEM',
        59  =>  'CURLE_SSL_CIPHER',
        60  =>  'CURLE_SSL_CACERT',
        61  =>  'CURLE_BAD_CONTENT_ENCODING',
        62  =>  'CURLE_LDAP_INVALID_URL',
        63  =>  'CURLE_FILESIZE_EXCEEDED',
        64  =>  'CURLE_USE_SSL_FAILED',
        65  =>  'CURLE_SEND_FAIL_REWIND',
        66  =>  'CURLE_SSL_ENGINE_INITFAILED',
        67  =>  'CURLE_LOGIN_DENIED',
        68  =>  'CURLE_TFTP_NOTFOUND',
        69  =>  'CURLE_TFTP_PERM',
        70  =>  'CURLE_REMOTE_DISK_FULL',
        71  =>  'CURLE_TFTP_ILLEGAL',
        72  =>  'CURLE_TFTP_UNKNOWNID',
        73  =>  'CURLE_REMOTE_FILE_EXISTS',
        74  =>  'CURLE_TFTP_NOSUCHUSER',
        75  =>  'CURLE_CONV_FAILED',
        76  =>  'CURLE_CONV_REQD',
        77  =>  'CURLE_SSL_CACERT_BADFILE',
        78  =>  'CURLE_REMOTE_FILE_NOT_FOUND',
        79  =>  'CURLE_SSH',
        80  =>  'CURLE_SSL_SHUTDOWN_FAILED',
        81  =>  'CURLE_AGAIN',
        82  =>  'CURLE_SSL_CRL_BADFILE',
        83  =>  'CURLE_SSL_ISSUER_ERROR',
        84  =>  'CURLE_FTP_PRET_FAILED',
        84  =>  'CURLE_FTP_PRET_FAILED',
        85  =>  'CURLE_RTSP_CSEQ_ERROR',
        86  =>  'CURLE_RTSP_SESSION_ERROR',
        87  =>  'CURLE_FTP_BAD_FILE_LIST',
        88  =>  'CURLE_CHUNK_FAILED',
    );

    /**
     *  Constructor of the class.
     *
     *  Below is the list of default options set for each request, unless these options are specifically changed by one
     *  of the methods or via the {@link option()} method:
     *
     *  -   <b>CURLINFO_HEADER_OUT</b>      -   <b>TRUE</b>; get the last request header; if set to FALSE the "last_request"
     *                                          entry of the "headers" attribute of the object given as argument to the
     *                                          callback function, will be an empty string; <i>you should leave this
     *                                          unaltered!</i>;
     *
     *  -   <b>CURLOPT_AUTOREFERER</b>      -   <b>TRUE</b>; automatically set the <i>Referer:</i> field in requests
     *                                          where it follows a <i>Location:</i> redirect;
     *
     *  -   <b>CURLOPT_COOKIEFILE</b>       -   <b>empty string</b>; no cookies are loaded, but cookie handling is still
     *                                          enabled
     *
     *  -   <b>CURLOPT_CONNECTTIMEOUT</b>   -   <b>10</b>; the number of seconds to wait while trying to connect. use 0
     *                                          to wait indefinitely;
     *
     *  -   <b>CURLOPT_FOLLOWLOCATION</b>   -   <b>TRUE</b>; automatically follow any <i>Location:</i> header that the
     *                                          server sends as part of the HTTP header (note this is recursive, PHP will
     *                                          follow as many <i>Location:</i> headers as specified by the value of
     *                                          CURLOPT_MAXREDIRS - see below);
     *
     *  -   <b>CURLOPT_HEADER</b>           -   <b>TRUE</b>; get the response header(s); if set to FALSE the "responses"
     *                                          entry of the "headers" attribute of the object given as argument to the
     *                                          callback function, will be an empty string; <i>you should leave this
     *                                          unaltered!</i>;
     *
     *  -   <b>CURLOPT_MAXREDIRS</b>        -   <b>50</b>; the maximum amount of HTTP redirections to follow; used
     *                                          together with CURLOPT_FOLLOWLOCATION;
     *
     *  -   <b>CURLOPT_RETURNTRANSFER</b>   -   <b>TRUE</b>; return the transfer's body as a string instead of outputting
     *                                          it directly; if set to FALSE the "body" attribute of the object given as
     *                                          argument to a callback function will be an empty string; <b>this will
     *                                          always be TRUE and cannot be changed!</b>;
     *
     *  -   <b>CURLOPT_SSL_VERIFYHOST</b>   -   <b>2</b>; check the existence of a common name in the SSL peer certificate
     *                                          (for when connecting to HTTPS), and that it matches with the provided
     *                                          hostname; see also {@link ssl()};
     *
     *  -   <b>CURLOPT_SSL_VERIFYPEER</b>   -   <b>FALSE</b>; stop cURL from verifying the peer's certificate (which
     *                                          would most likely cause the request to fail). see also {@link ssl()};
     *
     *  -   <b>CURLOPT_TIMEOUT</b>          -   <b>10</b>; the maximum number of seconds to allow cURL functions to
     *                                          execute;
     *
     *  -   <b>CURLOPT_USERAGENT</b>        -   A (slightly) random user agent (Internet Explorer 9 or 10, on Windows
     *                                          Vista, 7 or 8, with other extra strings). Some web services will not
     *                                          respond unless a valid user-agent string is provided;
     *
     *  @return void
     */
    function __construct($htmlentities = true)
    {

        // if the cURL extension is not available, trigger an error and stop execution
        if (!extension_loaded('curl')) trigger_error('php_curl extension is not loaded!', E_USER_ERROR);

        // set defaults for accessing HTTPS servers
        $this->ssl();

        // initialize some internal variables
        $this->_multi_handle = false;
        $this->_info = array();

        // caching is disabled by default
        $this->cache(false);

        // the default number of parallel, asynchronous, requests to be processed by the library at once.
        $this->threads = 10;

        // by default process runs response body through htmlentities
        $this->_htmlentities = $htmlentities;

    }

    /**
     *  Use this method to enable caching for {@link get() get} and {@link header() header} requests.
     *
     *  <i>Caching is only used for {@link get() get} and {@link header() header} requests, and will be ignored for other
     *  request types even if it is enabled!</i>
     *
     *  <i>Caching is disabled by default!</i>
     *
     *  <code>
     *  // the callback function to be executed for each and every
     *  // request, as soon as a request finishes
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  function mycallback($result) {
     *
     *      // everything went well
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // see all the returned data
     *          print_r('<pre>');
     *          print_r($result);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else die('An error occurred: ' . $result->response[1]);
     *
     *  }
     *
     *  // include the Zebra_cURL library
     *  require 'path/to/Zebra_cURL';
     *
     *  // instantiate the Zebra_cURL object
     *  $curl = new Zebra_cURL();
     *
     *  // cache results in the "cache" folder and for 86400 seconds (24 hours)
     *  $curl->cache('cache', 86400);
     *
     *  // let's fetch the RSS feeds of some popular websites
     *  // execute the "mycallback" function for each request, as soon as it finishes
     *  $curl->get(array(
     *      'http://feeds.feedburner.com/alistapart/main',
     *      'http://feeds.feedburner.com/TechCrunch',
     *      'http://feeds.mashable.com/mashable',
     *  ), 'mycallback')
     *  </code>
     *
     *  @param  string      $path       The path where the cache files to be stored.
     *
     *                                  Setting this to FALSE will disable caching.
     *
     *                                  <i>Unless set to FALSE this must point to a writable directory or an error will
     *                                  be triggered!</i>
     *
     *  @param  integer     $lifetime   (Optional) The number of seconds after which cache will be considered as expired.
     *
     *                                  Default is 3600.
     *
     *  @param  boolean     $compress   (Optional) If set to TRUE, cache files will be
     *                                  {@link http://php.net/manual/ro/function.gzcompress.php gzcompress}-ed  so that
     *                                  they occupy less disk space.
     *
     *                                  Default is TRUE.
     *
     *  @param  octal       $chmod      (Optional) The file system permissions to be set for newly created cache files.
     *
     *                                  I suggest using the value "0755" (without the quotes) but, if you know what you
     *                                  are doing, here is how you can calculate the permission levels:
     *
     *                                  - 400 Owner Read
     *                                  - 200 Owner Write
     *                                  - 100 Owner Execute
     *                                  - 40 Group Read
     *                                  - 20 Group Write
     *                                  - 10 Group Execute
     *                                  - 4 Global Read
     *                                  - 2 Global Write
     *                                  - 1 Global Execute
     *
     *                                  Default is "0755" (without the quotes).
     *
     *  @return null
     */
    public function cache($path, $lifetime = 3600, $compress = true, $chmod = 0755)
    {

        // if we have to enable caching
        if ($path != false)

            // store cache-related properties
            $this->cache = array(
                'path'      =>  $path,
                'lifetime'  =>  $lifetime,
                'chmod'     =>  $chomd,
                'compress'  =>  $compress,
            );

        // if we have to disable caching, disable it
        else $this->cache = false;

    }

    /**
     *  Sets the path and name of the file to save to / retrieve cookies from, for each accessed URL. (cookie name/data
     *  will be stored in this file on a per-domain basis). Important when cookies need to stored/restored to maintain
     *  status/session of the request(s) made to the same domain(s).
     *
     *  This method will automatically set the <b>CURLOPT_COOKIEJAR</b> and <b>CURLOPT_COOKIEFILE</b> options.
     *
     *  @param  string      $path   The path to a file to save to / retrieve cookies from, for each accessed URL.
     *
     *                              If file does not exist the library will attempt to create it and if it is unable to
     *                              create it will trigger an error.
     *
     *  @param  boolean     $keep   (Optional)  By default, the file to save to / retrieve cookies from is deleted when
     *                              script execution finishes. If you want the file to be preserved, set this argument to
     *                              TRUE.
     *
     *                              Default is FALSE.
     *
     *  @return null
     */
    public function cookies($path, $keep = false)
    {
        // file does not exist
        if (!is_file($path)) {

            // attempt to create it
            if (!($handle = fopen($path, 'a')))

                // if file could not be created, trigger an error
                trigger_error('File "' . $path . '" for storing cookies could not be found nor could it automatically be created! Make sure either that the path to the file points to a writable directory, or create the file yourself and make it writable.', E_USER_ERROR);

            // if file could be create, release handle
            fclose($handle);

        }

        // set these options
        $this->option(array(
            CURLOPT_COOKIEJAR   =>  $path,
            CURLOPT_COOKIEFILE  =>  $path,
        ));

    }

    /**
     *  Downloads one or more files from one or more URLs specified by the <i>$url</i> argument, saves the downloaded
     *  files (with their original name) to the path specified by the <i>$destination_path</i>, and executes the callback
     *  function specified by the <i>$callback</i> argument for each and every request, as soon as each request finishes.
     *
     *  Downloads are streamed (bytes downloaded are directly written to disk) removing the unnecessary strain from your
     *  server of reading files into memory first, and then writing them to disk.
     *
     *  This method will automatically set the <b>CURLOPT_BINARYTRANSFER</b> option to TRUE, so you might want to change
     *  this back to FALSE/0 or "unset" it using the {@link option()} method, before making a {@link get()}, {@link header()}
     *  or {@link post()} request.
     *
     *  <i>Files are downloaded preserving their name so you may run into trouble when trying to download more images
     *  having the same name (either from the same, or different servers)!</i>
     *
     *  <i>Multiple requests are made asynchronously, in parallel, and the callback function is called for each and every
     *  request, as soon as each request finishes. The number of parallel requests to be made at once can be set through
     *  the {@link threads} property.</i>
     *
     *  <i>Note that in case of multiple URLs, requests may not finish in the same order as initiated!</i>
     *
     *  <code>
     *  // the callback function to be executed for each and every
     *  // request, as soon as a request finishes
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  function mycallback($result) {
     *
     *      // everything went well
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // see all the returned data
     *          print_r('<pre>');
     *          print_r($result);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else die('An error occured: ' . $result->response[1]);
     *
     *  }
     *
     *  // include the Zebra_cURL library
     *  require 'path/to/Zebra_cURL';
     *
     *  // instantiate the Zebra_cURL object
     *  $curl = new Zebra_cURL();
     *
     *  // download 2 images from 2 different websites, and
     *  // execute the "mycallback" function for each request, as soon as it finishes
     *  $curl->download(array(
     *      'http://www.somewebsite.com/images/alpha.jpg',
     *      'http://www.otherwebsite.com/images/omega.jpg',
     *  ), 'destination/path/', 'mycallback');
     *  </code>
     *
     *  @param  mixed   $url                A single or an array of URLs to process.
     *
     *  @param  string  $destination_path   The path to where to save the file(s) to.
     *
     *                                      If path is not pointing to a directory or is not writable, the library will
     *                                      trigger an error.
     *
     *  @param  mixed   $callback           (Optional) Callback function to be called as soon as a request finishes.
     *
     *                                      May be given as a string representing a name of an existing function, as an
     *                                      anonymous function created on the fly via {@link http://www.php.net/manual/ro/function.create-function.php
     *                                      create_function} or, as of PHP 5.3.0, via a {@link http://www.php.net/manual/ro/function.create-function.php
     *                                      closure}.
     *
     *                                      The callback function receives <b>an object</b> as argument with <b>4 properties</b>:
     *
     *                                      -   <b>info</b>     -   an associative array containing information about the
     *                                                              request that just finished, as returned by PHP's
     *                                                              {@link http://php.net/manual/en/function.curl-getinfo.php curl_getinfo()}
     *                                                              function; there's also an extra entry called <i>original_url</i>
     *                                                              because, as curl_getinfo() only returns information
     *                                                              about the <b>last</b> request, the original URL may
     *                                                              be lost otherwise.
     *
     *                                      -   <b>headers</b>  -   an associative array with 2 items:
     *
     *                                                              <b>- last_request</b> an array with a single entry
     *                                                              containing the request headers generated by <i>the
     *                                                              last request</i>; so, remember, if there are redirects
     *                                                              involved, there will be more requests made, but only
     *                                                              information from the last one will be available; if
     *                                                              explicitly disabled via the {@link option()} method
     *                                                              by setting <b>CURLINFO_HEADER_OUT</b> to 0 or FALSE,
     *                                                              this will be an empty string;
     *
     *                                                              <b>- responses</b> an array with one or more entries
     *                                                              (if there are redirects involved) with the response
     *                                                              headers of all the requests made; if explicitly disabled
     *                                                              via the {@link option()} method by setting
     *                                                              <b>CURLOPT_HEADER</b> to 0 or FALSE, this will be an
     *                                                              empty string;
     *
     *                                                              <i>Unless disabled, each entry in the headers' array
     *                                                              is an associative array in the form of property =>
     *                                                              value</i>
     *
     *                                      -   <b>body</b>     -   the response of the request (the content of the page
     *                                                               at the URL) with all applicable characters converted
     *                                                               to HTML entities via PHP's {@link http://php.net/manual/en/function.htmlentities.php htmlentities()}
     *                                                              function, so remember to use PHP's {@link http://www.php.net/manual/en/function.html-entity-decode.php html_entity_decode()}
     *                                                              function to do reverse this, if it's the case; if
     *                                                              explicitly disabled via the {@link option()} method
     *                                                              by setting <b>CURLOPT_NOBODY</b> to 0 or FALSE, this
     *                                                              will be an empty string;
     *
     *                                      -   <b>response</b> -   the response given by the cURL library as an array
     *                                                              with 2 entries: the first entry represents the result's
     *                                                              code, while the second is the textual representation
     *                                                              of the code; if the request was successful, these
     *                                                              values will be <i>array(0, CURLE_OK);</i> consult
     *                                                              {@link http://www.php.net/manual/en/function.curl-errno.php#103128
     *                                                              this list} to see the possible values of this property;
     *
     *  @return null
     */
    public function download($url, $destination_path, $callback = '')
    {

        // if destination path is not a directory or is not writable, trigger an error message
        if (!is_dir($destination_path) || !is_writable($destination_path)) trigger_error('"' . $destination_path . '" is not a valid path or is not writable!', E_USER_ERROR);

        // set download path
        $this->download_path = rtrim($destination_path, '/\\') . '/';

        // instruct the cURL library that it has to do a binary transfer
        $this->option(CURLOPT_BINARYTRANSFER, 1);

        // process request(s)
        $this->_process($url, $callback);

    }

    /**
     *  Works exactly like the {@link download()} method only that downloads are made from an FTP server.
     *
     *  Downloads from an FTP server to which the connection is made using the given <i>$username</i> and <i>$password</i>
     *  arguments, one or more files specified by the <i>$url</i> argument, saves the downloaded files (with their original
     *  name) to the path specified by the <i>$destination_path</i>, and executes the callback function specified by the
     *  <i>$callback</i> argument for each and every request, as soon as each request finishes.
     *
     *  Downloads are streamed (bytes downloaded are directly written to disk) removing the unnecessary strain from your
     *  server of reading files into memory first, and then writing them to disk.
     *
     *  This method will automatically set the <b>CURLOPT_BINARYTRANSFER</b> option to TRUE, so you might want to change
     *  this back to FALSE/0 or "unset" it using the {@link option()} method, before making a {@link get()}, {@link header()}
     *  or {@link post()} request.
     *
     *  <i>Files are downloaded preserving their name so you may run into trouble when trying to download more images
     *  having the same name (either from the same, or different servers)!</i>
     *
     *  <i>Multiple requests are made asynchronously, in parallel, and the callback function is called for each and every
     *  request, as soon as each request finishes. The number of parallel requests to be made at once can be set through
     *  the {@link threads} property.</i>
     *
     *  <i>Note that in case of multiple URLs, requests may not finish in the same order as initiated!</i>
     *
     *  <code>
     *  // the callback function to be executed for each and every
     *  // request, as soon as a request finishes
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  function mycallback($result) {
     *
     *      // everything went well
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // see all the returned data
     *          print_r('<pre>');
     *          print_r($result);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else die('An error occured: ' . $result->response[1]);
     *
     *  }
     *
     *  // include the Zebra_cURL library
     *  require 'path/to/Zebra_cURL';
     *
     *  // instantiate the Zebra_cURL object
     *  $curl = new Zebra_cURL();
     *
     *  // connect to the FTP server using the given credential, download a file to a given location and
     *  // execute the "mycallback" function for each request, as soon as it finishes
     *  $curl->download('ftp://somefile.ext', 'destination/path/', 'username', 'password', 'mycallback');
     *  </code>
     *
     *  @param  mixed   $url                A single or an array of URLs to process.
     *
     *  @param  string  $destination_path   The path to where to save the file(s) to.
     *
     *                                      If path is not pointing to a directory or is not writable, the library will
     *                                      trigger an error.
     *
     *  @param  string  $username           (Optional) The username to be used to connect to the FTP server (if required).
     *
     *  @param  string  $password           (Optional) The password to be used to connect to the FTP server (if required).
     *
     *  @param  mixed   $callback           (Optional) Callback function to be called as soon as a request finishes.
     *
     *                                      May be given as a string representing a name of an existing function, as an
     *                                      anonymous function created on the fly via {@link http://www.php.net/manual/ro/function.create-function.php
     *                                      create_function} or, as of PHP 5.3.0, via a {@link http://www.php.net/manual/ro/function.create-function.php
     *                                      closure}.
     *
     *                                      The callback function receives <b>an object</b> as argument with <b>4 properties</b>:
     *
     *                                      -   <b>info</b>     -   an associative array containing information about the
     *                                                              request that just finished, as returned by PHP's
     *                                                              {@link http://php.net/manual/en/function.curl-getinfo.php curl_getinfo()}
     *                                                              function;
     *
     *                                      -   <b>headers</b>  -   an associative array with 2 items:
     *
     *                                                              <b>- last_request</b> an array with a single entry
     *                                                              containing the request headers generated by <i>the
     *                                                              last request</i>; so, remember, if there are redirects
     *                                                              involved, there will be more requests made, but only
     *                                                              information from the last one will be available; if
     *                                                              explicitly disabled via the {@link option()} method
     *                                                              by setting <b>CURLINFO_HEADER_OUT</b> to 0 or FALSE,
     *                                                              this will be an empty string;
     *
     *                                                              <b>- responses</b> an array with one or more entries
     *                                                              (if there are redirects involved) with the response
     *                                                              headers of all the requests made; if explicitly disabled
     *                                                              via the {@link option()} method by setting
     *                                                              <b>CURLOPT_HEADER</b> to 0 or FALSE, this will be an
     *                                                              empty string;
     *
     *                                                              <i>Unless disabled, each entry in the headers' array
     *                                                              is an associative array in the form of property =>
     *                                                              value</i>
     *
     *                                      -   <b>body</b>     -   the response of the request (the content of the page
     *                                                               at the URL) with all applicable characters converted
     *                                                               to HTML entities via PHP's {@link http://php.net/manual/en/function.htmlentities.php htmlentities()}
     *                                                              function, so remember to use PHP's {@link http://www.php.net/manual/en/function.html-entity-decode.php html_entity_decode()}
     *                                                              function to do reverse this, if it's the case; if
     *                                                              explicitly disabled via the {@link option()} method
     *                                                              by setting <b>CURLOPT_NOBODY</b> to 0 or FALSE, this
     *                                                              will be an empty string;
     *
     *                                      -   <b>response</b> -   the response given by the cURL library as an array
     *                                                              with 2 entries: the first entry represents the result's
     *                                                              code, while the second is the textual representation
     *                                                              of the code; if the request was successful, these
     *                                                              values will be <i>array(0, CURLE_OK);</i> consult
     *                                                              {@link http://www.php.net/manual/en/function.curl-errno.php#103128
     *                                                              this list} to see the possible values of this property;
     *
     *  @return null
     */
    public function ftp_download($url, $destination_path, $username = '', $password = '', $callback = '')
    {

        // if he have at least an username, set username/password
        if ($username != '') $this->option(CURLOPT_USERPWD, $username . ':' . $password);

        // call the download method
        $this->download($url, $destination_path, $callback);

    }

    /**
     *  Performs an HTTP <b>GET</b> request to one or more URLs specified by the <i>$url</i> argument and executes the
     *  callback function specified by the <i>$callback</i> argument for each and every request, as soon as each request
     *  finishes.
     *
     *  <i>Multiple requests are made asynchronously, in parallel, and the callback function is called for each and every
     *  request, as soon as each request finishes. The number of parallel requests to be made at once can be set through
     *  the {@link threads} property.</i>
     *
     *  <i>Note that in case of multiple URLs, requests may not finish in the same order as initiated!</i>
     *
     *  <code>
     *  // the callback function to be executed for each and every
     *  // request, as soon as a request finishes
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  function mycallback($result) {
     *
     *      // everything went well
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // see all the returned data
     *          print_r('<pre>');
     *          print_r($result);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else die('An error occured: ' . $result->response[1]);
     *
     *  }
     *
     *  // include the Zebra_cURL library
     *  require 'path/to/Zebra_cURL';
     *
     *  // instantiate the Zebra_cURL object
     *  $curl = new Zebra_cURL();
     *
     *  // cache results in the "cache" folder and for 3600 seconds (one hour)
     *  $curl->cache('cache', 3600);
     *
     *  // let's fetch the RSS feeds of some popular websites
     *  // execute the "mycallback" function for each request, as soon as it finishes
     *  $curl->get(array(
     *      'http://feeds.feedburner.com/alistapart/main',
     *      'http://feeds.feedburner.com/TechCrunch',
     *      'http://feeds.mashable.com/mashable',
     *  ), 'mycallback')
     *  </code>
     *
     *  @param  mixed   $url        A single or an array of URLs to process.
     *
     *  @param  mixed   $callback   (Optional) Callback function to be called as soon as a request finishes.
     *
     *                              May be given as a string representing a name of an existing function, as an anonymous
     *                              function created on the fly via {@link http://www.php.net/manual/ro/function.create-function.php
     *                              create_function} or, as of PHP 5.3.0, via a {@link http://www.php.net/manual/ro/function.create-function.php
     *                              closure}.
     *
     *                              The callback function receives <b>an object</b> as argument with <b>4 properties</b>:
     *
     *                              -   <b>info</b>     -   an associative array containing information about the request
     *                                                      that just finished, as returned by PHP's
     *                                                      {@link http://php.net/manual/en/function.curl-getinfo.php curl_getinfo()}
     *                                                      function;
     *
     *                              -   <b>headers</b>  -   an associative array with 2 items:
     *
     *                                                      <b>- last_request</b> an array with a single entry containing
     *                                                      the request headers generated by <i>the last request</i>; so,
     *                                                      remember, if there are redirects involved, there will be more
     *                                                      requests made, but only information from the last one will be
     *                                                      available; if explicitly disabled via the {@link option()}
     *                                                      method by setting <b>CURLINFO_HEADER_OUT</b> to 0 or FALSE,
     *                                                      this will be an empty string;
     *
     *                                                      <b>- responses</b> an array with one or more entries (if there
     *                                                      are redirects involved) with the response headers of all the
     *                                                      requests made; if explicitly disabled via the {@link option()}
     *                                                      method by setting <b>CURLOPT_HEADER</b> to 0 or FALSE, this
     *                                                      will be an empty string;
     *
     *                                                      <i>Unless disabled, each entry in the headers' array is an
     *                                                      associative array in the form of property => value</i>
     *
     *                              -   <b>body</b>     -   the response of the request (the content of the page at the URL)
     *                                                      with all applicable characters converted to HTML entities via
     *                                                      PHP's {@link http://php.net/manual/en/function.htmlentities.php htmlentities()}
     *                                                      function, so remember to use PHP's {@link http://www.php.net/manual/en/function.html-entity-decode.php html_entity_decode()}
     *                                                      function to do reverse this, if it's the case. if explicitly
     *                                                      disabled via the {@link option()} method by setting
     *                                                      <b>CURLOPT_NOBODY</b> to 0 or FALSE, this will be an empty
     *                                                      string;
     *
     *                              -   <b>response</b> -   the response given by the cURL library as an array with 2
     *                                                      entries: the first entry represents the result's code, while
     *                                                      the second is the textual representation of the code; if the
     *                                                      request was successful, these values will be <i>array(0,
     *                                                      CURLE_OK);</i> consult {@link http://www.php.net/manual/en/function.curl-errno.php#103128
     *                                                      this list} to see the possible values of this property;
     *
     *  @return null
     */
    public function get($url, $callback = '')
    {

        // make sure we perform a GET request
		$this->option(CURLOPT_HTTPGET, 1);

        // process request(s)
        return $this->_process($url, $callback);

    }

    /**
     *  Works exactly like the {@link get()} method, the only difference being that this method will automatically set
     *  the <b>CURLOPT_NOBODY</b> option to FALSE and thus the <i>body</i> property of the result will be an empty string.
     *  Also, <b>CURLINFO_HEADER_OUT</b> and <b>CURLOPT_HEADER</b> will be set to TRUE and therefore header information
     *  will be available.
     *
     *  <i>Multiple requests are made asynchronously, in parallel, and the callback function is called for each and every
     *  request, as soon as each request finishes. The number of parallel requests to be made at once can be set through
     *  the {@link threads} property.</i>
     *
     *  <i>Note that in case of multiple URLs, requests may not finish in the same order as initiated!</i>
     *
     *  <code>
     *  // the callback function to be executed for each and every
     *  // request, as soon as a request finishes
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  function mycallback($result) {
     *
     *      // everything went well
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // see all the returned data
     *          print_r('<pre>');
     *          print_r($result);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else die('An error occured: ' . $result->response[1]);
     *
     *  }
     *
     *  // include the Zebra_cURL library
     *  require 'path/to/Zebra_cURL';
     *
     *  // instantiate the Zebra_cURL object
     *  $curl = new Zebra_cURL();
     *
     *  // process given URLs execute the "mycallback" function for each
     *  // request, as soon as it finishes
     *  $curl->header('http://www.somewebsite.com', 'mycallback');
     *  </code>
     *
     *  @param  mixed   $url        A single or an array of URLs to process.
     *
     *  @param  mixed   $callback   (Optional) Callback function to be called as soon as a request finishes.
     *
     *                              May be given as a string representing a name of an existing function, as an anonymous
     *                              function created on the fly via {@link http://www.php.net/manual/ro/function.create-function.php
     *                              create_function} or, as of PHP 5.3.0, via a {@link http://www.php.net/manual/ro/function.create-function.php
     *                              closure}.
     *
     *                              The callback function receives <b>an object</b> as argument with <b>4 properties</b>:
     *
     *                              -   <b>info</b>     -   an associative array containing information about the request
     *                                                      that just finished, as returned by PHP's
     *                                                      {@link http://php.net/manual/en/function.curl-getinfo.php curl_getinfo()}
     *                                                      function;
     *
     *                              -   <b>headers</b>  -   an associative array with 2 items:
     *
     *                                                      <b>- last_request</b> an array with a single entry containing
     *                                                      the request headers generated by <i>the last request</i>; so,
     *                                                      remember, if there are redirects involved, there will be more
     *                                                      requests made, but only information from the last one will be
     *                                                      available;
     *
     *                                                      <b>- responses</b> an array with one or more entries (if there
     *                                                      are redirects involved) with the response headers of all the
     *                                                      requests made;
     *
     *                                                      <i>Each entry in the headers' array is an associative array
     *                                                      in the form of property => value</i>
     *
     *                              -   <b>body</b>     -   an empty string
     *
     *                              -   <b>response</b> -   the response given by the cURL library as an array with 2
     *                                                      entries: the first entry represents the result's code, while
     *                                                      the second is the textual representation of the code; if the
     *                                                      request was successful, these values will be <i>array(0,
     *                                                      CURLE_OK);</i> consult {@link http://www.php.net/manual/en/function.curl-errno.php#103128
     *                                                      this list} to see the possible values of this property;
     *
     *  @return null
     */
    public function header($url, $callback = '')
    {

        // no "body" for header requests but make sure we have the headers
        $this->option(array(
            CURLINFO_HEADER_OUT     =>  1,
            CURLOPT_HEADER          =>  1,
            CURLOPT_HTTPGET         =>  1,
            CURLOPT_NOBODY          =>  1,
        ));

        // execute request(s)
        $this->_process($url, $callback);

    }

    /**
     *  Use this method to make requests to pages that requires prior HTTP authentication.
     *
     *  <code>
     *  // the callback function to be executed for each and every
     *  // request, as soon as a request finishes
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  function mycallback($result) {
     *
     *      // everything went well
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // see all the returned data
     *          print_r('<pre>');
     *          print_r($result);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else die('An error occured: ' . $result->response[1]);
     *
     *  }
     *
     *  // include the Zebra_cURL library
     *  require 'path/to/Zebra_cURL';
     *
     *  // instantiate the Zebra_cURL object
     *  $curl = new Zebra_cURL();
     *
     *  // prepare user name and password
     *  $curl->http_authentication('username', 'password');
     *
     *  // get content from a page that requires prior HTTP authentication
     *  $curl->get('http://www.some-page-requiring-prior-http-authentication.com', 'mycallback');
     *  </code>
     *
     *  @param  string      $username       User name to be used for authentication.
     *
     *  @param  string      $password       Password to be used for authentication.
     *
     *  @param  string      $type           (Optional) The HTTP authentication method(s) to use. The options are:
     *
     *                                      -   <b>CURLAUTH_BASIC</b>
     *                                      -   <b>CURLAUTH_DIGEST</b>
     *                                      -   <b>CURLAUTH_GSSNEGOTIATE</b>
     *                                      -   <b>CURLAUTH_NTLM</b>
     *                                      -   <b>CURLAUTH_ANY</b>
     *                                      -   CU<b>RLAUTH_ANYSAFE</b>
     *
     *                                      The bitwise | (or) operator can be used to combine more than one method. If
     *                                      this is done, cURL will poll the server to see what methods it supports and
     *                                      pick the best one.
     *
     *                                      <b>CURLAUTH_ANY</b> is an alias for <b>CURLAUTH_BASIC</b> | <b>CURLAUTH_DIGEST</b> |
     *                                      <b>CURLAUTH_GSSNEGOTIATE</b> | <b>CURLAUTH_NTLM</b>.
     *
     *                                      <b>CURLAUTH_ANYSAFE</b> is an alias for <b>CURLAUTH_DIGEST</b> | <b>CURLAUTH_GSSNEGOTIATE</b> |
     *                                      <b>CURLAUTH_NTLM</b>.
     *
     *                                      Default is <b>CURLAUTH_ANY</b>.
     *
     *  @return null
     */
    public function http_authentication($username, $password, $type = CURLAUTH_ANY)
    {

        // set the required options
		$this->option(array(
            CURLOPT_HTTPAUTH    =>  $type,
            CURLOPT_USERPWD     =>  $username . ':' . $password,
        ));

    }

    /**
     *  Allows you to set one or more {@link http://php.net/manual/en/function.curl-setopt.php cURL options}.
     *
     *  <code>
     *  // include the Zebra_cURL library
     *  require 'path/to/Zebra_cURL';
     *
     *  // instantiate the Zebra_cURL object
     *  $curl = new Zebra_cURL();
     *
     *  // setting a single option
     *  $curl->option(CURLOPT_CONNECTTIMEOUT, 10);
     *
     *  // setting multiple options at once
     *  $curl->option(array(
     *      CURLOPT_TIMEOUT         =>  10,
     *      CURLOPT_CONNECTTIMEOUT  =>  10,
     *  ));
     *
     *  // make a request here...
     *  </code>
     *
     *  @param  mixed   $option     A single option for which to set a value, or an associative array in the form of
     *                              <i>option</i> => <i>value</i> (in case of an array, the <i>$value</i> argument will
     *                              be disregarded).
     *
     *                              <i>Setting a value to</i> <b>null</b> <i>will "unset" that option.</i>
     *
     *  @param  mixed   $value      (Optional) If the <i>$option</i> argument is not an array, then this argument represents
     *                              the value to be set for the respective option. If the <i>$option</i> argument is an
     *                              array, then the value of this argument will be ignored.
     *
     *                              <i>Setting a value to</i> <b>null</b> <i>will "unset" that option.</i>
     *
     *  @return null
     *
     */
    public function option($option, $value = '')
    {

        // if $options is given as an array
        if (is_array($option))

            // iterate through each of the values
            foreach ($option as $name => $value)

                // if we need to "unset" an option, unset it
                if (is_null($value)) unset($this->options[$name]);

                // set the value for the option otherwise
                else $this->options[$name] = $value;

        // if option is not given as an array,
        // if we need to "unset" an option, unset it
        elseif (is_null($value)) unset($this->options[$option]);

        // set the value for the option otherwise
        else $this->options[$option] = $value;

    }

    /**
     *  Performs an HTTP <b>POST</b> to one or more URLs specified by the <i>$url</i> argument, using the values specified
     *  by the <i>$values</i> argument, and executes the callback function specified by the <i>$callback</i> argument for
     *  each and every request, as soon as each request finishes.
     *
     *  <i>Multiple requests are made asynchronously, in parallel, and the callback function is called for each and every
     *  request, as soon as each request finishes. The number of parallel requests to be made at once can be set through
     *  the {@link threads} property.</i>
     *
     *  <i>Note that in case of multiple URLs, requests may not finish in the same order as initiated!</i>
     *
     *  <code>
     *  // the callback function to be executed for each and every
     *  // request, as soon as a request finishes
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  function mycallback($result) {
     *
     *      // everything went well
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // see all the returned data
     *          print_r('<pre>');
     *          print_r($result);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else die('An error occured: ' . $result->response[1]);
     *
     *  }
     *
     *  // include the Zebra_cURL library
     *  require 'path/to/Zebra_cURL';
     *
     *  // instantiate the Zebra_cURL object
     *  $curl = new Zebra_cURL();
     *
     *  // do a POST and execute the "mycallback" function for each
     *  // request, as soon as it finishes
     *  $curl->post('http://www.somewebsite.com', array(
     *      'field_1'   =>  'value 1',
     *      'field_2'   =>  'value 2',
     *  ), 'mycallback');
     *
     *  // do a POST and execute the "mycallback" function for each
     *  // request, as soon as it finishes
     *  // note that we're also uploading a file this time
     *  // and note that we're prefixing the file name with @
     *  $curl->post('http://www.somewebsite.com', array(
     *      'field_1'   =>  'value 1',
     *      'field_2'   =>  'value 2',
     *      'upload'    =>  '@absolute/path/to/file.ext',
     *  ), 'mycallback');
     *  </code>
     *
     *  @param  mixed   $url        A single or an array of URLs to which to POST to.
     *
     *  @param  array   $values     An associative array in the form of <i>element => value</i> representing the data to
     *                              post in the HTTP "POST" operation.
     *
     *                              To post a file, prepend the filename with @ and use the full path. The filetype can
     *                              be explicitly specified by following the filename with the type in the format <b>';type=mimetype'.</b>
     *                              You should always specify the mime type as most of the times cURL will send the wrong
     *                              mime type...
     *
     *                              The <i>Content-Type</i> header will be set to <b>multipart/form-data.</b>
     *
     *  @param  mixed   $callback   (Optional) Callback function to be called as soon as a request finishes.
     *
     *                              May be given as a string representing a name of an existing function, as an anonymous
     *                              function created on the fly via {@link http://www.php.net/manual/ro/function.create-function.php
     *                              create_function} or, as of PHP 5.3.0, via a {@link http://www.php.net/manual/ro/function.create-function.php
     *                              closure}.
     *
     *                              The callback function receives <b>an object</b> as argument with <b>4 properties</b>:
     *
     *                              -   <b>info</b>     -   an associative array containing information about the request
     *                                                      that just finished, as returned by PHP's
     *                                                      {@link http://php.net/manual/en/function.curl-getinfo.php curl_getinfo()}
     *                                                      function;
     *
     *                              -   <b>headers</b>  -   an associative array with 2 items:
     *
     *                                                      <b>- last_request</b> an array with a single entry containing
     *                                                      the request headers generated by <i>the last request</i>; so,
     *                                                      remember, if there are redirects involved, there will be more
     *                                                      requests made, but only information from the last one will be
     *                                                      available; if explicitly disabled via the {@link option()}
     *                                                      method by setting <b>CURLINFO_HEADER_OUT</b> to 0 or FALSE,
     *                                                      this will be an empty string;
     *
     *                                                      <b>- responses</b> an array with one or more entries (if there
     *                                                      are redirects involved) with the response headers of all the
     *                                                      requests made; if explicitly disabled via the {@link option()}
     *                                                      method by setting <b>CURLOPT_HEADER</b> to 0 or FALSE, this
     *                                                      will be an empty string;
     *
     *                                                      <i>Unless disabled, each entry in the headers' array is an
     *                                                      associative array in the form of property => value</i>
     *
     *                              -   <b>body</b>     -   the response of the request (the content of the page at the URL)
     *                                                      with all applicable characters converted to HTML entities via
     *                                                      PHP's {@link http://php.net/manual/en/function.htmlentities.php htmlentities()}
     *                                                      function, so remember to use PHP's {@link http://www.php.net/manual/en/function.html-entity-decode.php html_entity_decode()}
     *                                                      function to do reverse this, if it's the case. if explicitly
     *                                                      disabled via the {@link option()} method by setting
     *                                                      <b>CURLOPT_NOBODY</b> to 0 or FALSE, this will be an empty
     *                                                      string;
     *
     *                              -   <b>response</b> -   the response given by the cURL library as an array with 2
     *                                                      entries: the first entry represents the result's code, while
     *                                                      the second is the textual representation of the code; if the
     *                                                      request was successful, these values will be <i>array(0,
     *                                                      CURLE_OK);</i> consult {@link http://www.php.net/manual/en/function.curl-errno.php#103128
     *                                                      this list} to see the possible values of this property;
     *
     *  @return null
     */
    public function post($url, $values, $callback = '')
    {

        // if second argument is not an array, trigger an error
        if (!is_array($values)) trigger_error('Second argument to method "post" must be an array!', E_USER_ERROR);

        // prepare cURL for making a POST
        $this->option(array(
            CURLOPT_POST        =>  1,
            CURLOPT_POSTFIELDS  =>  http_build_query($values, NULL, '&'),
        ));

        // execute request(s)
        $this->_process($url, $callback);

    }

    /**
     *  Instruct the library to tunnel all requests through a proxy server.
     *
     *  <code>
     *  // the callback function to be executed for each and every
     *  // request, as soon as a request finishes
     *  function mycallback($result) {
     *
     *      // everything went well
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // see all the returned data
     *          print_r('<pre>');
     *          print_r($result);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else die('An error occured: ' . $result->response[1]);
     *
     *  }
     *
     *  // include the Zebra_cURL library
     *  require 'path/to/Zebra_cURL';
     *
     *  // instantiate the class
     *  $curl = new Zebra_cURL();
     *
     *  // connect to a proxy server
     *  // (that's a random one i got from http://www.hidemyass.com/proxy-list/)
     *  $curl->proxy('187.63.32.250', '3128');
     *
     *  // fetch a page
     *  $curl->get('http://www.somewebsite.com/', 'mycallback');
     *  </code>
     *
     *  @param  string      $proxy      The HTTP proxy to tunnel requests through.
     *
     *                                  Can be an URL or an IP address.
     *
     *                                  <i>This option can also be set using the {@link option()} method and setting </i>
     *                                  <b>CURLOPT_PROXY</b> <i> option to the desired value</i>.
     *
     *                                  Setting this argument to FALSE will "unset" all the proxy-related options.
     *
     *  @param  string      $port       (Optional) The port number of the proxy to connect to.
     *
     *                                  Default is 80.
     *
     *                                  <i>This option can also be set using the {@link option()} method and setting </i>
     *                                  <b>CURLOPT_PROXYPORT</b> <i> option to the desired value</i>.
     *
     *  @param  string      $username   (Optional) The username to be used for the connection to the proxy (if required
     *                                  by the proxy)
     *
     *                                  Default is "" (an empty string)
     *
     *                                  <i>The username and the password can also be set using the {@link option()} method
     *                                  and setting </i> <b>CURLOPT_PROXYUSERPWD</b> <i> option to the desired value
     *                                  formatted like </i> <b>[username]:[password]</b>.     .
     *
     *  @param  string      $password   (Optional) The password to be used for the connection to the proxy (if required
     *                                  by the proxy)
     *
     *                                  Default is "" (an empty string)
     *
     *                                  <i>The username and the password can also be set using the {@link option()} method
     *                                  and setting </i> <b>CURLOPT_PROXYUSERPWD</b> <i> option to the desired value
     *                                  formatted like </i> <b>[username]:[password]</b>.     .
     *
     *  @return null
     */
    public function proxy($proxy, $port = 80, $username = '', $password = '')
    {

        // if not disabled
        if ($proxy) {

            // set the required options
            $this->option(array(
                CURLOPT_HTTPPROXYTUNNEL     =>  1,
                CURLOPT_PROXY               =>  $proxy,
                CURLOPT_PROXYPORT           =>  $port,
            ));

            // if a username is also specified
            if ($username != '')

                // set authentication values
                $this->option(CURLOPT_PROXYUSERPWD, $username . ':' . $password);

        // if disabled
        } else

            // unset proxy-related options
            $this->option(array(
                CURLOPT_HTTPPROXYTUNNEL     =>  null,
                CURLOPT_PROXY               =>  null,
                CURLOPT_PROXYPORT           =>  null,
            ));

    }

    /**
     *  Requests made to HTTPS servers sometimes require additional configuration, depending on the server. Most of the
     *  times {@link __construct() the defaults} set by the library will get you through, but if defaults are not working,
     *  you can set specific options using this method.
     *
     *  <code>
     *  // include the Zebra_cURL library
     *  require 'path/to/Zebra_cURL';
     *
     *  // instantiate the class
     *  $curl = new Zebra_cURL();
     *
     *  // instruct the library to verify peer's SSL certificate
     *  // (ignored if request is not made through HTTPS)
     *  $curl->ssl(true);
     *
     *  // fetch a page
     *  $curl->get('https://www.somewebsite.com/', create_function('$result', 'print_r("<pre>"); print_r($result);'));
     *  </code>
     *
     *  @param  boolean     $verify_peer        (Optional) Should the peer's certificate be verified by cURL?
     *
     *                                          Default is FALSE.
     *
     *                                          <i>This option can also be set using the {@link option()} method and
     *                                          setting </i> <b>CURLOPT_SSL_VERIFYPEER</b> <i> option to the desired value</i>.
     *
     *  @param  integer     $verify_host        (Optional) Specifies whether or not to check the existence of a common
     *                                          name in the SSL peer certificate and that it matches with the provided
     *                                          hostname.
     *
     *                                          -   1   to check the existence of a common name in the SSL peer certificate;
     *                                          -   2   to check the existence of a common name and also verify that it
     *                                                  matches the hostname provided; in production environments the value
     *                                                  of this option should be kept at 2;
     *
     *                                          Default is 2
     *
     *                                          <samp>Support for value 1 removed in cURL 7.28.1</samp>
     *
     *                                          <i>This option can also be set using the {@link option()} method and
     *                                          setting </i> <b>CURLOPT_SSL_VERIFYHOST</b> <i> option to the desired value</i>.
     *
     *  @param  mixed       $file               (Optional) An absolute path to a file holding one or more certificates to
     *                                          verify the peer with. This only makes sense if <b>CURLOPT_SSL_VERIFYPEER</b>
     *                                          is set to TRUE.
     *
     *                                          Default is FALSE.
     *
     *                                          <i>This option can also be set using the {@link option()} method and
     *                                          setting </i> <b>CURLOPT_CAINFO</b> <i> option to the desired value</i>.
     *
     *  @param  mixed       $path               (Optional) An absolute path to a directory that holds multiple CA
     *                                          certificates. This only makes sense if <b>CURLOPT_SSL_VERIFYPEER</b> is
     *                                          set to TRUE.
     *
     *                                          Default is FALSE.
     *
     *                                          <i>This option can also be set using the {@link option()} method and
     *                                          setting </i> <b>CURLOPT_CAPATH</b> <i> option to the desired value</i>.
     *
     *  @return null
     */
    public function ssl($verify_peer = false, $verify_host = 2, $file = false, $path = false)
    {

        // set default options
        $this->option(array(
        	CURLOPT_SSL_VERIFYPEER => $verify_peer,
        	CURLOPT_SSL_VERIFYHOST => $verify_host,
        ));

        // if a path to a file holding one or more certificates to verify the peer with was given
        if ($file !== false)

            // if file could be found, use it
            if (is_file($file)) $this->option(CURLOPT_CAINFO, $file);

            // if file was not found, trigger an error
            else trigger_error('File "' . $file . '", holding one or more certificates to verify the peer with, was not found!', E_USER_ERROR);

        // if a directory holding multiple CA certificates was given
        if ($path !== false)

            // if folder could be found, use it
            if (is_dir($path)) $this->option(CURLOPT_CAPATH, $path);

            // if folder was not found, trigger an error
            else trigger_error('Directory "' . $path . '", holding one or more CA certificates to verify the peer with, was not found!', E_USER_ERROR);

    }

    /**
     *  Returns the set options in "human-readable" format.
     *
     *  @return string  Returns the set options in "human-readable" format.
     *
     *  @access private
     */
    private function _debug()
    {

        $result = '';

        // iterate through the defined constants
        foreach(get_defined_constants() as $name => $number)

            // iterate through the set options
            foreach ($this->options as $index => $value)

                // if this is a curl-related constant and it is one of the options that are set, add it to the result
                if (substr($name, 0, 7) == 'CURLOPT' && $number == $index) $result .= $name . ' => ' . $value . '<br>';

        // return the result
        return $result;

    }

    /**
     *  A helper method used by the {@link _process()} method to process request and response headers. It parses a string
     *  containing one or more HTTP headers and returns an array of headers where each entry also contains an associative
     *  array of <i>name</i> => <i>value</i> for each row of data in the respective header.
     *
     *  @param  string  $headers    A string containing one or more HTTP headers, where multiple headers are separated by
     *                              a blank line.
     *
     *  @return mixed               Returns an array of headers where each entry also contains an associative array of
     *                              <i>name</i> => <i>value</i> for each row of data in the respective header.
     *
     *                              If CURLOPT_HEADER is set to FALSE or 0, this method will return an empty string.
     *
     *  @access private
     */
    private function _parse_headers($headers)
    {

        $result = array();

        // if we have nothing to work with
        if ($headers != '') {

            // split multiple headers by blank lines
            $headers = preg_split('/^\s*$/m', trim($headers));

            // iterate through the headers
            foreach($headers as $index => $header) {

                $arguments_count = func_num_args();

                // get all the lines in the header
                // lines in headers look like [name] : [value]
                // also, the first line, the status, does not have a name, so we add the name now
                preg_match_all('/^(.*?)\:\s(.*)$/m', ($arguments_count == 2 ? 'Request Method: ' : 'Status: ') . trim($header), $matches);

                // save results
                foreach ($matches[0] as $key => $value)

                    $result[$index][$matches[1][$key]] = trim($matches[2][$key]);

            }

        }

        // return headers as an array
        return $result;

    }

    /**
     *  Does the actual work.
     *
     *  @return null
     *
     *  @access private
     */
    private function _process($urls, $callback = '')
    {

        // if caching is enabled but path doesn't exist or is not writable
        if ($this->cache !== false && (!is_dir($this->cache['path']) || !is_writable($this->cache['path'])))

            // trigger an error and stop execution
            trigger_error('Cache path does not exists or is not writable!', E_USER_ERROR);

        // if callback function doesn't exists
        if ($callback != '' && !is_callable($callback))

            // trigger an error and stop execution
            trigger_error('Callback function "' . $callback . '" does not exist!', E_USER_ERROR);

        $urls = !is_array($urls) ? (array)$urls : $urls;

        // only if we're making a GET request, and caching is enabled
        if (isset($this->options[CURLOPT_HTTPGET]) && $this->options[CURLOPT_HTTPGET] == 1 && $this->cache !== false) {

            // iterate through the URLs
            foreach ($urls as $url) {

                // get the path to the cache file associated with the URL
                $cache_path = rtrim($this->cache['path'], '/') . '/' . md5($url);

                // if cache file exists and is not expired
                if (file_exists($cache_path) && filemtime($cache_path) + $this->cache['lifetime'] > time()) {

                    // if we have a callback, return the result from the cache file, and feed it as argument to the callback function
                    if ($callback != '') call_user_func($callback, unserialize($this->cache['compress'] ? gzuncompress(file_get_contents($cache_path)) : file_get_contents($cache_path)));

                // if no cache file, or cache file is expired
                } else $this->_queue[] = $url;

            }

        // if we're not making a GET request or caching is disabled, we don't bother with cache: we need to process all the URLs
        } else $this->_queue = $urls;

        // if there are any URLs to process
        if (!empty($this->_queue)) {

            // initialize the multi handle
            // this will allow us to process multiple cURL handles in parallel
            $this->_multi_handle = curl_multi_init();

            // queue the first batch of URLs
            // (as many as defined by the "threads" property or less if there aren't as many URLs)
            $this->_queue_requests();

            $running = null;

            // loop
            do {

                // get status update
                while (($status = curl_multi_exec($this->_multi_handle, $running)) == CURLM_CALL_MULTI_PERFORM);

                // if no request has finished yet, keep looping
                if ($status != CURLM_OK) break;

                // if a request was just completed, we'll have to find out which one
                while ($info = curl_multi_info_read($this->_multi_handle)) {

                    // get handle of the completed request
                    $handle = $info['handle'];

                    // get content associated with the handle
                    $content = curl_multi_getcontent($handle);

                    // get the handle's ID
                    $resource_number = preg_replace('/Resource id #/', '', $handle);

                    // create a new object in which we will store all the data associated with the handle,
                    // as properties of this object
                    $result = new stdClass();

                    // get information about the request
                    $result->info = curl_getinfo($handle);

                    // extend the "info" property with the original URL
                    $result->info = array('original_url' => $this->_info['fh' . $resource_number]['original_url']) + $result->info;

                    // last request headers
                    $result->headers['last_request'] =

                        (

                            // if CURLINFO_HEADER_OUT is set
                            isset($this->options[CURLINFO_HEADER_OUT]) &&

                            // if CURLINFO_HEADER_OUT is TRUE
                            $this->options[CURLINFO_HEADER_OUT] == 1 &&

                            // if we actually have this information
                            isset($result->info['request_header'])

                        // extract request headers
                        ) ? $this->_parse_headers($result->info['request_header'], true) : '';

                    // remove request headers information from its previous location
                    unset($result->info['request_header']);

                    // get headers (unless we were explicitly told not to)
                    $result->headers['responses'] = (isset($this->options[CURLOPT_HEADER]) && $this->options[CURLOPT_HEADER] == 1) ?

                        $this->_parse_headers(substr($content, 0, $result->info['header_size'])) :

                        '';

                    // get output (unless we were explicitly told not to)
                    $result->body = (!isset($this->options[CURLOPT_NOBODY]) || (isset($this->options[CURLOPT_NOBODY]) && $this->options[CURLOPT_NOBODY] == 0)) ?

                        ((isset($this->options[CURLOPT_HEADER]) && $this->options[CURLOPT_HEADER] == 1) ?

                        substr($content, $result->info['header_size']) :

                        $content) :

                        '';

                    // run htmlentities if it is set and body is set
                    if ($this->_htmlentities && !empty($result->body)) htmlentities($result->body);

                    // get CURLs response code and associated message
                    $result->response = array($this->_response_messages[$info['result']], $info['result']);

                    // if caching is enabled and we're making a GET request
                    if ($this->cache !== false && isset($this->options[CURLOPT_HTTPGET]) && $this->options[CURLOPT_HTTPGET] == 1) {

                        // get the path to the cache file associated with the URL
                        $cache_path = rtrim($this->cache['path'], '/') . '/' . md5($result->info['original_url']);

                        // cache the result
                        file_put_contents($cache_path, $this->cache['compress'] ? gzcompress(serialize($result)) : serialize($result));

                        // set rights on the file
                        chmod($cache_path, intval($this->cache['chmod'], 8));

                    }

                    // call the attached callback function sending our object as argument
                    if ($callback != '') call_user_func($callback, $result);

                    // if there are more URLs to process, queue the next one
                    if (!empty($this->_queue)) $this->_queue_requests();

                    // remove the handle that we finished processing
                    // this needs to be done *after* we've already queued a new URL for processing
                    curl_multi_remove_handle($this->_multi_handle, $handle);

                    // make sure the handle gets closed
                    curl_close($handle);

                    // if we're downloading something
                    if (isset($this->options[CURLOPT_BINARYTRANSFER]) && $this->options[CURLOPT_BINARYTRANSFER])

                        // close the associated file pointer
                        fclose($this->_info['fh' . $resource_number]['file_handler']);

                    // remove information associated with this resource
                    unset($this->_info['fh' . $resource_number]);

                }

                // waits until curl_multi_exec() returns CURLM_CALL_MULTI_PERFORM or until the timeout, whatever happens first
                if ($running) curl_multi_select($this->_multi_handle, 1);

            // as long as there are threads running
            } while ($running);

            // close the multi curl handle
            curl_multi_close($this->_multi_handle);

        }

    }

    /**
     *  A helper method used by the {@link _process()} method, which takes care of keeping a constant number of requests
     *  queued, so that as soon as one request finishes another one will instantly take its place, thus making sure that
     *  the maximum allowed number of parallel threads are running all the time.
     *
     *  @return null
     *
     *  @access private
     */
    private function _queue_requests()
    {

        // get the queue's length
        $queue_length = count($this->_queue);

        // iterate through the items in the queue
        for ($i = 0; $i < ($queue_length < $this->threads ? $queue_length : $this->threads); $i++) {

            // remove first URL from the queue
            $url = array_shift($this->_queue);

            // initialize individual cURL handle with the URL
            $handle = curl_init($url);

            // make sure defaults are set
            $this->_set_defaults();

            // get the handle's ID
            $resource_number = preg_replace('/Resource id #/', '', $handle);

            // save the original URL
            // (because there may be redirects, and because "curl_getinfo" returns information only about the last
            // request, this can be lost otherwise)
            $this->_info['fh' . $resource_number]['original_url'] = $url;

            // if we're downloading something
            if (isset($this->options[CURLOPT_BINARYTRANSFER]) && $this->options[CURLOPT_BINARYTRANSFER]) {

                // open a file and save the file pointer
                $this->_info['fh' . $resource_number]['file_handler'] = fopen($this->download_path . basename($url), 'w');

                // no headers
                $this->option(CURLOPT_HEADER, 0);

                // tell cURL to use the file for streaming the download
                $this->option(CURLOPT_FILE, $this->_info['fh' . $resource_number]['file_handler']);

            }

            // set options for the handle
            curl_setopt_array($handle, $this->options);

            // add the normal handle to the multi handle
            curl_multi_add_handle($this->_multi_handle, $handle);

        }

    }

    /**
     *  A helper method used by the {@link _process()} method, which sets the default cURL options for each request.
     *
     *  @return null
     *
     *  @access private
     */
    private function _set_defaults()
    {

        // if "CURLOPT_AUTOREFERER" has not been explicitly set, make it TRUE
        // (automatically set the "Referer:" field where it follows a "Location:" redirect)
        if (!isset($this->options[CURLOPT_AUTOREFERER])) $this->option(CURLOPT_AUTOREFERER, 1);

        // if "CURLOPT_COOKIEFILE" has not been explicitly set, set it to the default value
        // (name of the file containing the cookie data; if the name is an empty string, no cookies are
        // loaded, but cookie handling is still enabled)
        if (!isset($this->options[CURLOPT_COOKIEFILE])) $this->option(CURLOPT_COOKIEFILE, '');

        // if "CURLOPT_CONNECTTIMEOUT" has not been explicitly set, set it to the default value
        // (the number of seconds to wait while trying to connect)
        if (!isset($this->options[CURLOPT_CONNECTTIMEOUT])) $this->option(CURLOPT_CONNECTTIMEOUT, 10);

        // if "CURLOPT_FOLLOWLOCATION" has not been explicitly set, make it TRUE
        // (follow any "Location:" header that the server sends as part of the HTTP header - note this is recursive
        // and that PHP will follow as many "Location:" headers as specified by CURLOPT_MAXREDIRS)
        if (!isset($this->options[CURLOPT_FOLLOWLOCATION])) $this->option(CURLOPT_FOLLOWLOCATION, 1);

        // if "CURLOPT_HEADER" has not been explicitly set, make it TRUE
        // (include the response header(s) as a property of the object given as argument to the callback)
        if (!isset($this->options[CURLOPT_HEADER])) $this->option(CURLOPT_HEADER, 1);

        // if "CURLINFO_HEADER_OUT" has not been explicitly set, make it TRUE
        // (include the last request headers as a property of the object given as argument to the callback)
        if (!isset($this->options[CURLINFO_HEADER_OUT])) $this->option(CURLINFO_HEADER_OUT, 1);

        // if "CURLOPT_MAXREDIRS" has not been explicitly set, set it to the default value
        // (the maximum amount of HTTP redirections to follow; used together with CURLOPT_FOLLOWLOCATION)
        if (!isset($this->options[CURLOPT_MAXREDIRS])) $this->option(CURLOPT_MAXREDIRS, 50);

        // if "CURLOPT_TIMEOUT" has not been explicitly set, set it to the default value
        // (the maximum number of seconds to allow cURL functions to execute)
        if (!isset($this->options[CURLOPT_TIMEOUT])) $this->option(CURLOPT_TIMEOUT, 30);

        // if "CURLOPT_USERAGENT" has not been explicitly set, use a random user agent
        // (some services/websites will block the request if there's no/invalid user agent)
        // note that the user agent will change whenever you run the script!
        if (!isset($this->options[CURLOPT_USERAGENT])) $this->option(CURLOPT_USERAGENT, $this->_user_agent());

        // if "CURLOPT_RETURNTRANSFER" is always TRUE
        // (return the transfer as a string of instead of outputting it out directly)
        $this->option(CURLOPT_RETURNTRANSFER, 1);

    }

    /**
     *  Generates a (slightly) random user agent (Internet Explorer 9 or 10, on Windows Vista, 7 or 8, with other extra
     *  strings)
     *
     *  Some web services will not respond unless a valid user-agent string is provided.
     *
     *  @return null
     *
     *  @access private
     */
    private function _user_agent()
    {

        // browser version: 9 or 10
        $version = rand(9, 10);

        // windows version; here are the meanings:
        // Windows NT 6.2   ->  Windows 8                                       //  can have IE10
        // Windows NT 6.1   ->  Windows 7                                       //  can have IE9 or IE10
        // Windows NT 6.0   ->  Windows Vista                                   //  can have IE9
        $major_version = 6;

        $minor_version =

            // for IE9 Windows can have "0", "1" or "2" as minor version number
            $version == 8 || $version == 9 ? rand(0, 2) :

            // for IE10 Windows will have "2" as major version number
            2;

        // add some extra information
        $extras = rand(0, 3);

        // return the random user agent string
        return 'Mozilla/5.0 (compatible; MSIE ' . $version . '.0; Windows NT ' . $major_version . '.' . $minor_version . ($extras == 1 ? '; WOW64' : ($extras == 2 ? '; Win64; IA64' : ($extras == 3 ? '; Win64; x64' : ''))) . ')';

    }

}

?>