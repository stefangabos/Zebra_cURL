<?php

/**
 *  A high performance PHP cURL library allowing the running of multiple requests at once, asynchronously, in parallel
 *
 *  Read more {@link https://github.com/stefangabos/Zebra_cURL/ here}
 *
 *  @author     Stefan Gabos <contact@stefangabos.ro>
 *  @version    1.3.5 (last revision: November 01, 2017)
 *  @copyright  (c) 2013 - 2017 Stefan Gabos
 *  @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE
 *  @package    Zebra_cURL
 */

class Zebra_cURL {

    /**
     *  The number of seconds to wait between processing batches of requests.
     *
     *  If the value of this property is greater than 0, the library will process as many requests as defined by the
     *  {@link threads} property, and then wait for {@link pause_interval pause_interval} seconds before processing the
     *  next batch of requests.
     *
     *  Default is 0 (the library will keep as many parallel threads as defined by {@link threads} <b>running at all
     *  times</b>, until there are no more requests to process).
     *
     *  @since 1.3.0
     *
     *  @var integer
     */
    public $pause_interval;

    /**
     *  The number of parallel, asynchronous, requests to be processed by the library, at all times.
     *
     *  <code>
     *  // process 30 simultaneous requests, at all times
     *  $curl->threads = 30;
     *  </code>
     *
     *  Note that, unless {@link pause_interval} is set to a value greater than 0, the library will process a constant
     *  number of requests, <b>at all times</b>; it's doing this by processing a new request as soon as another one
     *  finishes, instead of waiting for each batch to finish, and so on, until there are no more requests to process,
     *  and thus greatly decreasing execution time.
     *
     *  If {@link pause_interval} is set to a value greater than 0, the library will process as many requests as set by
     *  the {@link threads} property and then will wait for {@link pause_interval} seconds before processing the next
     *  batch of requests.
     *
     *  Default is 10.
     *
     *  @var integer
     */
    public $threads;

    /**
     * Used by the {@link _process} method to determine whether to run processed requests' bodies through PHP's
     * {@link http://php.net/manual/en/function.htmlentities.php htmlentities} function.
     *
     * Default is TRUE. Can be changed by instantiating the library with the FALSE argument.
     *
     * @access private
     *
     */
    private $_htmlentities;

    /**
     *  Used to tell the library whether to queue requests or to process them right away
     *
     *  @var resource
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
     *  Used to keep track of all the requests that need to be processed.
     *
     *  @access private
     */
    private $_requests;

    /**
     *  An associative array linked with all the resources, used to store original URL and file pointer resources, used
     *  for streaming downloads.
     *
     *  @var array
     *
     *  @access private
     */
    private $_running;

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
        85  =>  'CURLE_RTSP_CSEQ_ERROR',
        86  =>  'CURLE_RTSP_SESSION_ERROR',
        87  =>  'CURLE_FTP_BAD_FILE_LIST',
        88  =>  'CURLE_CHUNK_FAILED',
    );

    /**
     *  Stores the result of a call to the {@link scrap} method
     *
     *  @var mixed
     *
     *  @access private
     */
    private $_scrap_result;

    /**
     *  Constructor of the class.
     *
     *  Below is the list of default options set by the library when instantiated:
     *
     *  -   <b>CURLINFO_HEADER_OUT</b>      -   <b>TRUE</b>; get the last request header; if set to FALSE the "last_request"
     *                                          entry of the "headers" attribute of the object given as argument to the
     *                                          callback function, will be an empty string;
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
     *  -   <b>CURLOPT_ENCODING</b>         -   <b>gzip,deflate</b>; the contents of the "Accept-Encoding:" header; it
     *                                          enables decoding of the response
     *
     *  -   <b>CURLOPT_FOLLOWLOCATION</b>   -   <b>TRUE</b>; automatically follow any <i>Location:</i> header that the
     *                                          server sends as part of the HTTP header (note this is recursive, PHP will
     *                                          follow as many <i>Location:</i> headers as specified by the value of
     *                                          CURLOPT_MAXREDIRS - see below);
     *
     *  -   <b>CURLOPT_HEADER</b>           -   <b>TRUE</b>; get the response header(s); if set to FALSE the "responses"
     *                                          entry of the "headers" attribute of the object given as argument to the
     *                                          callback function, will be an empty string;
     *
     *  -   <b>CURLOPT_MAXREDIRS</b>        -   <b>50</b>; the maximum amount of HTTP redirects to follow; used together
     *                                          with CURLOPT_FOLLOWLOCATION;
     *
     *  -   <b>CURLOPT_RETURNTRANSFER</b>   -   <b>TRUE</b>; return the transfer's body as a string instead of outputting
     *                                          it directly; if set to FALSE the "body" attribute of the object given as
     *                                          argument to a callback function will be an empty string;
     *
     *  -   <b>CURLOPT_SSL_VERIFYHOST</b>   -   <b>2</b>; check the existence of a common name in the SSL peer certificate
     *                                          (for when connecting to HTTPS), and that it matches with the provided
     *                                          hostname; see also the {@link ssl} method;
     *
     *  -   <b>CURLOPT_SSL_VERIFYPEER</b>   -   <b>FALSE</b>; stop cURL from verifying the peer's certificate (which
     *                                          would most likely cause the request to fail). see also the {@link ssl}
     *                                          method;
     *
     *  -   <b>CURLOPT_TIMEOUT</b>          -   <b>10</b>; the maximum number of seconds to allow cURL functions to
     *                                          execute;
     *
     *  -   <b>CURLOPT_USERAGENT</b>        -   A (slightly) random user agent (Internet Explorer 9 or 10, on Windows
     *                                          Vista, 7 or 8, with other extra strings). Some web services will not
     *                                          respond unless a valid user-agent string is provided
     *
     *  @param  boolean $htmlentities           Instructs the script whether the response body returned by the {@link get}
     *                                          and {@link post} methods should be run through PHP's
     *                                          {@link http://php.net/manual/en/function.htmlentities.php htmlentities}
     *                                          function.
     *
     *  @return void
     */
    public function __construct($htmlentities = true) {

        // if the cURL extension is not available, trigger an error and stop execution
        if (!extension_loaded('curl')) trigger_error('php_curl extension is not loaded', E_USER_ERROR);

        // initialize some private properties
        $this->_multi_handle = $this->_queue = false;
        $this->_running = $this->_requests = array();

        // the default number of seconds to wait between processing batches of requests
        // 0 means no waiting, process all requests at once
        $this->pause_interval = 0;

        // the default number of parallel, asynchronous, requests to be processed by the library at all times
        // (unless the "pause_interval" property is greater than 0, case in which it refers to the number of requests
        // to be processed before pausing)
        $this->threads = 10;

        // set the user's preference on whether to run htmlentities() on the response body or not
        $this->_htmlentities = $htmlentities;

        // set defaults for libcurl
        // set defaults
        $this->option(array(

            // include the last request headers as a property of the object given as argument to the callback
            CURLINFO_HEADER_OUT         =>  1,

            // automatically set the "Referer:" field where it follows a "Location:" redirect
            CURLOPT_AUTOREFERER         =>  1,

            // the name of the file containing the cookie data; if the name is an empty string, no cookies are
            // loaded, but cookie handling is still enabled
            CURLOPT_COOKIEFILE          =>  '',

            // the number of seconds to wait while trying to connect
            CURLOPT_CONNECTTIMEOUT      =>  10,

            // the contents of the "Accept-Encoding:" header; it enables decoding of the response
            CURLOPT_ENCODING            =>  'gzip,deflate',

            // follow any "Location:" header that the server sends as part of the HTTP header - note this is recursive
            // and that PHP will follow as many "Location:" headers as specified by CURLOPT_MAXREDIRS
            CURLOPT_FOLLOWLOCATION      =>  1,

            // include the response header(s) as a property of the object given as argument to the callback
            CURLOPT_HEADER              =>  1,

            // the maximum amount of HTTP redirects to follow; used together with CURLOPT_FOLLOWLOCATION
            CURLOPT_MAXREDIRS           =>  50,

            // the maximum number of seconds to allow cURL functions to execute before timing out
            CURLOPT_TIMEOUT             =>  30,

            // most services/websites will block requests with no/invalid user agents
            // note that the user agent string is random and will change whenever the library is instantiated!
            CURLOPT_USERAGENT           =>  $this->_user_agent(),

            // return the transfer as a string of instead of outputting it to the screen
            CURLOPT_RETURNTRANSFER      =>  1,

        ));

        // if PHP version is at least 5.5
        if (version_compare(PHP_VERSION, '5.5') >= 0)

            // disable usage of @ in POST arguments
            // see https://wiki.php.net/rfc/curl-file-upload
            $this->option(CURLOPT_SAFE_UPLOAD, true);

        // set defaults for accessing HTTPS servers
        $this->ssl();

        // caching is disabled by default
        $this->cache(false);

    }

    /**
     *  Use this method to enable caching of requests.
     *
     *  <i>Note that only the actual request is cached and not associated downloads, if any!</i>
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
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see http://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *          // show the server's response code
     *          } else die('Server responded with code ' . $result->info['http_code']);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else die('cURL responded with: ' . $result->response[0]);
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
     *  // let's fetch the RSS feeds of some popular tech-related websites
     *  // execute the "mycallback" function for each request, as soon as it finishes
     *  $curl->get(array(
     *      'http://feeds.feedburner.com/alistapart/main',
     *      'http://feeds.feedburner.com/TechCrunch',
     *      'http://feeds.mashable.com/mashable',
     *  ), 'mycallback')
     *  </code>
     *
     *  @param  string      $path       Path where cache files to be stored.
     *
     *                                  Setting this to FALSE will disable caching.
     *
     *                                  <i>If set to a non-existing path, the library will try to create the folder
     *                                  and will trigger an error if, for whatever reasons, it is unable to do so. If the
     *                                  folder can be created, its permissions will be set to the value of $chmod</i>
     *
     *  @param  integer     $lifetime   (Optional) The number of seconds after which cache will be considered expired.
     *
     *                                  Default is 3600 (one hour).
     *
     *  @param  boolean     $compress   (Optional) If set to TRUE, cache files will be
     *                                  {@link http://php.net/manual/en/function.gzcompress.php gzcompress}-ed  so that
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
     *  @return void
     */
    public function cache($path, $lifetime = 3600, $compress = true, $chmod = 0755) {

        // if caching is not explicitly disabled
        if ($path !== false) {

            // if path doesn't exist, attempt to create it
            if (!is_dir($path)) @mkdir($path, $chmod, true);

            // save cache-related properties
            $this->cache = array(
                'path'      =>  $path,
                'lifetime'  =>  $lifetime,
                'chmod'     =>  $chmod,
                'compress'  =>  $compress,
            );

        // if caching is explicitly disabled, set this property to FALSE
        } else $this->cache = false;

    }

    /**
     *  Sets the path and name of the file to save to / retrieve cookies from. All cookie data will be stored in this
     *  file on a per-domain basis. Important when cookies need to stored/restored to maintain status/session of requests
     *  made to the same domains.
     *
     *  This method will automatically set the <b>CURLOPT_COOKIEJAR</b> and <b>CURLOPT_COOKIEFILE</b> options.
     *
     *  @param  string      $path   The path to a file to save to / retrieve cookies from.
     *
     *                              If file does not exist the library will attempt to create it, and if it is unable to
     *                              create it will trigger an error.
     *
     *  @return void
     */
    public function cookies($path) {

        // file does not exist
        if (!is_file($path)) {

            // attempt to create it
            if (!($handle = fopen($path, 'a')))

                // if file could not be created, trigger an error
                trigger_error('File "' . $path . '" for storing cookies could not be found nor could it automatically be created! Make sure either that the path to the file points to a writable directory, or create the file yourself and make it writable', E_USER_ERROR);

            // if file could be create, release handle
            fclose($handle);

        }

        // set these options
        $this->option(array(
            CURLOPT_COOKIEJAR   =>  $path,  //  for writing
            CURLOPT_COOKIEFILE  =>  $path,  //  for reading
        ));

    }

    /**
     *  Performs an HTTP <b>DELETE</b> request to one or more URLs with the POST data as specified by the <i>$urls</i> argument,
     *  and executes the callback function specified by the <i>$callback</i> argument for each and every request, as soon
     *  as a request finishes.
     *
     *  This method will automatically set the following options:
     *
     *  - <b>CURLINFO_HEADER_OUT</b> - TRUE
     *  - <b>CURLOPT_CUSTOMREQUEST</b> - "DELETE"
     *  - <b>CURLOPT_HEADER</b> - TRUE
     *  - <b>CURLOPT_NOBODY</b> - FALSE
     *  - <b>CURLOPT_POST</b> - FALSE
     *  - <b>CURLOPT_POSTFIELDS</b> - the POST data
     *
     *  ...and will unset the following options:
     *
     *  - <b>CURLOPT_BINARYTRANSFER</b>
     *  - <b>CURLOPT_HTTPGET</b> - TRUE
     *  - <b>CURLOPT_FILE</b>
     *
     *  Multiple requests are processed asynchronously, in parallel, and the callback function is called for each and every
     *  request, as soon as a request finishes. The number of parallel requests to be constantly processed, at all times,
     *  can be set through the {@link threads} property. See also the {@link pause_interval} property.
     *
     *  <i>Note that requests may not finish in the same order as initiated!</i>
     *
     *  <code>
     *  // the callback function to be executed for each and every
     *  // request, as soon as a request finishes
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  function mycallback($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see http://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *          // show the server's response code
     *          } else die('Server responded with code ' . $result->info['http_code']);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else die('cURL responded with: ' . $result->response[0]);
     *
     *  }
     *
     *  // include the Zebra_cURL library
     *  require 'path/to/Zebra_cURL';
     *
     *  // instantiate the Zebra_cURL object
     *  $curl = new Zebra_cURL();
     *
     *  // do a PUT and execute the "mycallback" function for each
     *  // request, as soon as it finishes
     *  $curl->delete(array(
     *      'http://www.somewebsite.com'  =>  array(
     *          'data_1'  =>  'value 1',
     *          'data_2'  =>  'value 2',
     *      ),
     *  ), 'mycallback');
     *  </code>
     *
     *  @param  mixed   $urls       An associative array in the form of <i>url => delete-data</i>, where "delete-data" is an
     *                              associative array in the form of <i>name => value</i>.
     *
     *                              "delete-data" can also be an arbitrary string - useful if you want to send raw data (like a JSON)
     *
     *                              The <i>Content-Type</i> header will be set to <b>multipart/form-data.</b>
     *
     *  @param  mixed   $callback   (Optional) Callback function to be called as soon as a request finishes.
     *
     *                              May be given as a string representing a name of an existing function, as an anonymous
     *                              function created on the fly via {@link http://www.php.net/manual/en/function.create-function.php
     *                              create_function} or via a {@link http://www.php.net/manual/en/function.create-function.php
     *                              closure}.
     *
     *                              The callback function receives as first argument <b>an object</b> with <b>4 properties</b>
     *                              as described below, while any further arguments passed to the {@link delete} method
     *                              will be passed as extra arguments to the callback function:
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
     *                                                      available; if explicitly disabled via the {@link option}
     *                                                      method by setting <b>CURLINFO_HEADER_OUT</b> to 0 or FALSE,
     *                                                      this will be an empty string;
     *
     *                                                      <b>- responses</b> an array with one or more entries (if there
     *                                                      are redirects involved) with the response headers of all the
     *                                                      requests made; if explicitly disabled via the {@link option}
     *                                                      method by setting <b>CURLOPT_HEADER</b> to 0 or FALSE, this
     *                                                      will be an empty string;
     *
     *                                                      <i>Unless disabled, each entry in the headers' array is an
     *                                                      associative array in the form of property => value</i>
     *
     *                              -   <b>body</b> -       the response of the request (the content of the page at the
     *                                                      URL).
     *
     *                                                      Unless disabled via the {@link __construct() constructor}, all
     *                                                      applicable characters will be converted to HTML entities via
     *                                                      PHP's {@link http://php.net/manual/en/function.htmlentities.php htmlentities}
     *                                                      function, so remember to use PHP's {@link http://www.php.net/manual/en/function.html-entity-decode.php html_entity_decode}
     *                                                      function to do reverse this, if it's the case;
     *
     *                                                      If "body" is explicitly disabled via the {@link option}
     *                                                      method by setting <b>CURLOPT_NOBODY</b> to 0 or FALSE, this
     *                                                      will be an empty string;
     *
     *                              -   <b>response</b> -   the response given by the cURL library as an array with 2
     *                                                      entries: the first entry is the textual representation of the
     *                                                      result's code, while second is the result's code itself; if
     *                                                      the request was successful, these values will be
     *                                                      <i>array(CURLE_OK, 0);</i> consult
     *                                                      {@link http://www.php.net/manual/en/function.curl-errno.php#103128 this list}
     *                                                      to see the possible values of this property;
     *
     *  <samp>If the callback function returns FALSE  while {@link cache} is enabled, the library will not cache the
     *  respective request, making it easy to retry failed requests without having to clear all cache.</samp>
     *
     *  @since 1.3.3
     *
     *  @return void
     */
    public function delete($urls, $callback = '') {

        // if "urls" argument is not an array, trigger an error
        if (!is_array($urls)) trigger_error('First argument to "delete" method must be an array!', E_USER_ERROR);

        // iterate through the list of URLs to process
        foreach ((array)$urls as $url => $values)

            // add each URL and associated properties to the "_requests" property
            $this->_requests[] = array(
                'url'               =>  $url,
                'options'           =>  array(
                    CURLINFO_HEADER_OUT     =>  1,
                    CURLOPT_CUSTOMREQUEST   =>  'DELETE',
                    CURLOPT_HEADER          =>  1,
                    CURLOPT_NOBODY          =>  0,
                    CURLOPT_POST            =>  0,
                    CURLOPT_POSTFIELDS      =>  is_array($values) ? http_build_query($values, NULL, '&') : $values,
                    CURLOPT_BINARYTRANSFER  =>  null,
                    CURLOPT_HTTPGET         =>  null,
                    CURLOPT_FILE            =>  null,
                ),
                'callback'          =>  $callback,

                // additional arguments to pass to the callback function, if any
                'arguments'         =>  array_slice(func_get_args(), 2),

            );

        // if we're just queuing requests for now, do not execute the next lines
        if ($this->_queue) return;

        // if we have to pause between batches of requests, process them sequentially, in batches
        if ($this->pause_interval > 0) $this->_process_paused();

        // if we don't have to pause between batches of requests, process them all at once
        else $this->_process();

    }

    /**
     *  Downloads one or more files from one or more URLs specified by the <i>$urls</i> argument, saves the downloaded
     *  files to the path specified by the <i>$path</i> argument, and executes the callback function specified by the
     *  <i>$callback</i> argument for each and every request, as soon as a request finishes.
     *
     *  <samp>If the path you are downloading from refers to a file, then the file's original name will be preserved but,
     *  if you are downloading a file generated by a script (i.e. http://foo.com/bar.php?w=1200&h=800), the downloaded
     *  file's name will be random generated. Refer to the downloaded file's name in the result's "info" attribute, in
     *  the "downloaded_filename" section - see the example below.</samp>
     *
     *  Downloads are streamed (bytes downloaded are directly written to disk) removing the unnecessary strain from your
     *  server of reading files into memory first, and then writing them to disk.
     *
     *  This method will automatically set the following options:
     *
     *  - <b>CURLINFO_HEADER_OUT</b> - TRUE
     *  - <b>CURLOPT_BINARYTRANSFER</b> - TRUE
     *  - <b>CURLOPT_HEADER</b> - TRUE
     *  - <b>CURLOPT_FILE</b>
     *
     *  ...and will unset the following options:
     *
     *  - <b>CURLOPT_CUSTOMREQUEST</b>
     *  - <b>CURLOPT_HTTPGET</b>
     *  - <b>CURLOPT_NOBODY</b>
     *  - <b>CURLOPT_POST</b>
     *  - <b>CURLOPT_POSTFIELDS</b>
     *
     *  Files are downloaded preserving their original names, so you may want to check that if you are downloading more
     *  files having the same name!
     *
     *  Multiple requests are processed asynchronously, in parallel, and the callback function is called for each and every
     *  request, as soon as a request finishes. The number of parallel requests to be constantly processed, at all times,
     *  can be set through the {@link threads} property. See also the {@link pause_interval} property.
     *
     *  <i>Note that requests may not finish in the same order as initiated!</i>
     *
     *  <code>
     *  // the callback function to be executed for each and every
     *  // request, as soon as a request finishes
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  function mycallback($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see http://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *              // get the downloaded file's path
     *              $result->info['downloaded_filename'];
     *
     *          // show the server's response code
     *          } else die('Server responded with code ' . $result->info['http_code']);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else die('cURL responded with: ' . $result->response[0]);
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
     *  @param  mixed   $urls               A single URL or an array of URLs to process.
     *
     *  @param  string  $path               The path to where to save the file(s) to.
     *
     *                                      If path is not pointing to a directory or is not writable, the library will
     *                                      trigger an error.
     *
     *  @param  mixed   $callback           (Optional) Callback function to be called as soon as a request finishes.
     *
     *                                      May be given as a string representing a name of an existing function, as an
     *                                      anonymous function created on the fly via {@link http://www.php.net/manual/en/function.create-function.php
     *                                      create_function} or via a {@link http://www.php.net/manual/en/function.create-function.php
     *                                      closure}.
     *
     *                                      The callback function receives as first argument <b>an object</b> with <b>4
     *                                      properties</b> as described below, while any further arguments passed to the
     *                                      {@link download} method will be passed as extra arguments to the callback function:
     *
     *                                      -   <b>info</b>     -   an associative array containing information about the
     *                                                              request that just finished, as returned by PHP's
     *                                                              {@link http://php.net/manual/en/function.curl-getinfo.php curl_getinfo}
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
     *                                                              explicitly disabled via the {@link option} method
     *                                                              by setting <b>CURLINFO_HEADER_OUT</b> to 0 or FALSE,
     *                                                              this will be an empty string;
     *
     *                                                              <b>- responses</b> an empty string as it is not
     *                                                              available for this method;
     *
     *                                                              <i>Unless disabled, each entry in the headers' array
     *                                                              is an associative array in the form of property =>
     *                                                              value</i>
     *
     *                                      -   <b>body</b>     -   an empty string as it is not available for this method;
     *
     *                                      -   <b>response</b> -   the response given by the cURL library as an array with
     *                                                              2 entries: the first entry is the textual representation
     *                                                              of the result's code, while second is the result's code
     *                                                              itself; if the request was successful, these values will
     *                                                              be <i>array(CURLE_OK, 0);</i> consult
     *                                                              {@link http://www.php.net/manual/en/function.curl-errno.php#103128 this list}
     *                                                              to see the possible values of this property;
     *
     *  <samp>If the callback function returns FALSE  while {@link cache} is enabled, the library will not cache the
     *  respective request, making it easy to retry failed requests without having to clear all cache.</samp>
     *
     *  @return void
     */
    public function download($urls, $path, $callback = '') {

        // if destination path is not a directory or is not writable, trigger an error message
        if (!is_dir($path) || !is_writable($path)) trigger_error('"' . $path . '" is not a valid path or is not writable', E_USER_ERROR);

        // iterate through the list of URLs to process
        foreach ((array)$urls as $url)

            // add each URL and associated properties to the "_requests" property
            $this->_requests[] = array(
                'url'               =>  $url,
                'path'              =>  rtrim($path, '/\\') . '/',
                'options'           =>  array(
                    CURLINFO_HEADER_OUT     =>  1,
                    CURLOPT_BINARYTRANSFER  =>  1,
                    CURLOPT_HEADER          =>  0,
                    CURLOPT_CUSTOMREQUEST   =>  null,
                    CURLOPT_HTTPGET         =>  null,
                    CURLOPT_NOBODY          =>  null,
                    CURLOPT_POST            =>  null,
                    CURLOPT_POSTFIELDS      =>  null,
                ),
                'callback'          =>  $callback,

                // additional arguments to pass to the callback function, if any
                'arguments'         =>  array_slice(func_get_args(), 3),

            );

        // if we're just queuing requests for now, do not execute the next lines
        if ($this->_queue) return;

        // if we have to pause between batches of requests, process them sequentially, in batches
        if ($this->pause_interval > 0) $this->_process_paused();

        // if we don't have to pause between batches of requests, process them all at once
        else $this->_process();

    }

    /**
     *  Works exactly like the {@link download} method only that downloads are made from an FTP server.
     *
     *  Downloads from an FTP server to which the connection is made using the given <i>$username</i> and <i>$password</i>
     *  arguments, one or more files specified by the <i>$urls</i> argument, saves the downloaded files (with their original
     *  name) to the path specified by the <i>$path</i> argument, and executes the callback function specified by the
     *  <i>$callback</i> argument for each and every request, as soon as a request finishes.
     *
     *  Downloads are streamed (bytes downloaded are directly written to disk) removing the unnecessary strain from your
     *  server of reading files into memory first, and then writing them to disk.
     *
     *  This method will automatically set the following options:
     *
     *  - <b>CURLINFO_HEADER_OUT</b> - TRUE
     *  - <b>CURLOPT_BINARYTRANSFER</b> - TRUE
     *  - <b>CURLOPT_HEADER</b> - TRUE
     *  - <b>CURLOPT_FILE</b>
     *
     *  ...and will unset the following options:
     *
     *  - <b>CURLOPT_CUSTOMREQUEST</b>
     *  - <b>CURLOPT_HTTPGET</b>
     *  - <b>CURLOPT_NOBODY</b>
     *  - <b>CURLOPT_POST</b>
     *  - <b>CURLOPT_POSTFIELDS</b>
     *
     *  Files are downloaded preserving their name so you may want to check that, if you are downloading more files
     *  having the same name (either from the same, or from different servers)!
     *
     *  Multiple requests are processed asynchronously, in parallel, and the callback function is called for each and every
     *  request, as soon as a request finishes. The number of parallel requests to be constantly processed, at all times,
     *  can be set through the {@link threads} property. See also the {@link pause_interval} property.
     *
     *  <i>Note that requests may not finish in the same order as initiated!</i>
     *
     *  <code>
     *  // the callback function to be executed for each and every
     *  // request, as soon as a request finishes
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  function mycallback($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see http://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *          // show the server's response code
     *          } else die('Server responded with code ' . $result->info['http_code']);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else die('cURL responded with: ' . $result->response[0]);
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
     *  $curl->ftp_download('ftp://somefile.ext', 'destination/path', 'username', 'password', 'mycallback');
     *  </code>
     *
     *  @param  mixed   $urls               A single URL or an array of URLs to process.
     *
     *  @param  string  $path               The path to where to save the file(s) to.
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
     *                                      anonymous function created on the fly via {@link http://www.php.net/manual/en/function.create-function.php
     *                                      create_function} or via a {@link http://www.php.net/manual/en/function.create-function.php
     *                                      closure}.
     *
     *                                      The callback function receives as first argument <b>an object</b> with <b>4
     *                                      properties</b> as described below, while any further arguments passed to the
     *                                      {@link ftp_download} method will be passed as extra arguments to the callback function:
     *
     *                                      -   <b>info</b>     -   an associative array containing information about the
     *                                                              request that just finished, as returned by PHP's
     *                                                              {@link http://php.net/manual/en/function.curl-getinfo.php curl_getinfo}
     *                                                              function;
     *
     *                                      -   <b>headers</b>  -   an associative array with 2 items:
     *
     *                                                              <b>- last_request</b> an array with a single entry
     *                                                              containing the request headers generated by <i>the
     *                                                              last request</i>; so, remember, if there are redirects
     *                                                              involved, there will be more requests made, but only
     *                                                              information from the last one will be available; if
     *                                                              explicitly disabled via the {@link option} method
     *                                                              by setting <b>CURLINFO_HEADER_OUT</b> to 0 or FALSE,
     *                                                              this will be an empty string;
     *
     *                                                              <b>- responses</b> an empty string as it is not
     *                                                              available for this method;
     *
     *                                                              <i>Unless disabled, each entry in the headers' array
     *                                                              is an associative array in the form of property =>
     *                                                              value</i>
     *
     *                                      -   <b>body</b>     -   an empty string as it is not available for this method;
     *
     *                                      -   <b>response</b> -   the response given by the cURL library as an array with
     *                                                              2 entries: the first entry is the textual representation
     *                                                              of the result's code, while second is the result's code
     *                                                              itself; if the request was successful, these values will
     *                                                              be <i>array(CURLE_OK, 0);</i> consult
     *                                                              {@link http://www.php.net/manual/en/function.curl-errno.php#103128 this list}
     *                                                              to see the possible values of this property;
     *
     *  <samp>If the callback function returns FALSE  while {@link cache} is enabled, the library will not cache the
     *  respective request, making it easy to retry failed requests without having to clear all cache.</samp>
     *
     *  @return void
     */
    public function ftp_download($urls, $path, $username = '', $password = '', $callback = '') {

        // if destination path is not a directory or is not writable, trigger an error message
        if (!is_dir($path) || !is_writable($path)) trigger_error('"' . $path . '" is not a valid path or is not writable', E_USER_ERROR);

        // iterate through the list of URLs to process
        foreach ((array)$urls as $url)

            // add each URL and associated properties to the "_requests" property
            $this->_requests[] = array(
                'url'               =>  $url,
                'path'              =>  rtrim($path, '/\\') . '/',
                'options'           =>  array(
                    CURLINFO_HEADER_OUT     =>  1,
                    CURLOPT_BINARYTRANSFER  =>  1,
                    CURLOPT_HEADER          =>  0,
                    CURLOPT_USERPWD         =>  $username != '' ? $username . ':' . $password : null,
                    CURLOPT_CUSTOMREQUEST   =>  null,
                    CURLOPT_HTTPGET         =>  null,
                    CURLOPT_NOBODY          =>  null,
                    CURLOPT_POST            =>  null,
                    CURLOPT_POSTFIELDS      =>  null,
                ),
                'callback'          =>  $callback,

                // additional arguments to pass to the callback function, if any
                'arguments'         =>  array_slice(func_get_args(), 5),

            );

        // if we're just queuing requests for now, do not execute the next lines
        if ($this->_queue) return;

        // if we have to pause between batches of requests, process them sequentially, in batches
        if ($this->pause_interval > 0) $this->_process_paused();

        // if we don't have to pause between batches of requests, process them all at once
        else $this->_process();

    }

    /**
     *  Performs an HTTP <b>GET</b> request to one or more URLs specified by the <i>$urls</i> argument and executes the
     *  callback function specified by the <i>$callback</i> argument for each and every request, as soon as a request
     *  finishes.
     *
     *  This method will automatically set the following options:
     *
     *  - <b>CURLINFO_HEADER_OUT</b> - TRUE
     *  - <b>CURLOPT_HEADER</b> - TRUE
     *  - <b>CURLOPT_HTTPGET</b> - TRUE
     *  - <b>CURLOPT_NOBODY</b> - FALSE
     *
     *  ...and will unset the following options:
     *
     *  - <b>CURLOPT_BINARYTRANSFER</b>
     *  - <b>CURLOPT_CUSTOMREQUEST</b>
     *  - <b>CURLOPT_FILE</b>
     *  - <b>CURLOPT_POST</b>
     *  - <b>CURLOPT_POSTFIELDS</b>
     *
     *  Multiple requests are processed asynchronously, in parallel, and the callback function is called for each and every
     *  request, as soon as a request finishes. The number of parallel requests to be constantly processed, at all times,
     *  can be set through the {@link threads} property. See also the {@link pause_interval} property.
     *
     *  <i>Note that requests may not finish in the same order as initiated!</i>
     *
     *  <code>
     *  // the callback function to be executed for each and every
     *  // request, as soon as a request finishes
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  function mycallback($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see http://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *          // show the server's response code
     *          } else die('Server responded with code ' . $result->info['http_code']);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else die('cURL responded with: ' . $result->response[0]);
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
     *  @param  mixed   $urls       A single URL or an array of URLs to process.
     *
     *  @param  mixed   $callback   (Optional) Callback function to be called as soon as a request finishes.
     *
     *                              May be given as a string representing a name of an existing function, as an anonymous
     *                              function created on the fly via {@link http://www.php.net/manual/en/function.create-function.php
     *                              create_function} or via a {@link http://www.php.net/manual/en/function.create-function.php
     *                              closure}.
     *
     *                              The callback function receives as first argument <b>an object</b> with <b>4 properties</b>
     *                              as described below, while any further arguments passed to the {@link get} method will
     *                              be passed as extra arguments to the callback function:
     *
     *                              -   <b>info</b>     -   an associative array containing information about the request
     *                                                      that just finished, as returned by PHP's
     *                                                      {@link http://php.net/manual/en/function.curl-getinfo.php curl_getinfo}
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
     *                              -   <b>body</b> -       the response of the request (the content of the page at the
     *                                                      URL).
     *
     *                                                      Unless disabled via the {@link __construct() constructor}, all
     *                                                      applicable characters will be converted to HTML entities via
     *                                                      PHP's {@link http://php.net/manual/en/function.htmlentities.php htmlentities}
     *                                                      function, so remember to use PHP's {@link http://www.php.net/manual/en/function.html-entity-decode.php html_entity_decode}
     *                                                      function to do reverse this, if it's the case;
     *
     *                              -   <b>response</b> -   the response given by the cURL library as an array with 2
     *                                                      entries: the first entry is the textual representation of the
     *                                                      result's code, while second is the result's code itself; if
     *                                                      the request was successful, these values will be
     *                                                      <i>array(CURLE_OK, 0);</i> consult
     *                                                      {@link http://www.php.net/manual/en/function.curl-errno.php#103128 this list}
     *                                                      to see the possible values of this property;
     *
     *  <samp>If the callback function returns FALSE  while {@link cache} is enabled, the library will not cache the
     *  respective request, making it easy to retry failed requests without having to clear all cache.</samp>
     *
     *  @return void
     */
    public function get($urls, $callback = '') {

        // iterate through the list of URLs to process
        foreach ((array)$urls as $url)

            // add each URL and associated properties to the "_requests" property
            $this->_requests[] = array(
                'url'               =>  $url,
                'options'           =>  array(
                    CURLINFO_HEADER_OUT     =>  1,
                    CURLOPT_HEADER          =>  1,
                    CURLOPT_HTTPGET         =>  1,
                    CURLOPT_NOBODY          =>  0,
                    CURLOPT_BINARYTRANSFER  =>  null,
                    CURLOPT_CUSTOMREQUEST   =>  null,
                    CURLOPT_FILE            =>  null,
                    CURLOPT_POST            =>  null,
                    CURLOPT_POSTFIELDS      =>  null,
                ),
                'callback'          =>  $callback,

                // additional arguments to pass to the callback function, if any
                'arguments'         =>  array_slice(func_get_args(), 2),

            );

        // if we're just queuing requests for now, do not execute the next lines
        if ($this->_queue) return;

        // if we have to pause between batches of requests, process them sequentially, in batches
        if ($this->pause_interval > 0) $this->_process_paused();

        // if we don't have to pause between batches of requests, process them all at once
        else $this->_process();

    }

    /**
     *  Works exactly like the {@link get} method, the only difference being that this method will only return the
     *  headers, without body.
     *
     *  This method will automatically set the following options:
     *
     *  - <b>CURLINFO_HEADER_OUT</b> - TRUE
     *  - <b>CURLOPT_HEADER</b> - TRUE
     *  - <b>CURLOPT_HTTPGET</b> - TRUE
     *  - <b>CURLOPT_NOBODY</b> - TRUE
     *
     *  ...and will unset the following options:
     *
     *  - <b>CURLOPT_BINARYTRANSFER</b>
     *  - <b>CURLOPT_CUSTOMREQUEST</b>
     *  - <b>CURLOPT_FILE</b>
     *  - <b>CURLOPT_POST</b>
     *  - <b>CURLOPT_POSTFIELDS</b>
     *
     *  Multiple requests are processed asynchronously, in parallel, and the callback function is called for each and every
     *  request, as soon as a request finishes. The number of parallel requests to be constantly processed, at all times,
     *  can be set through the {@link threads} property. See also the {@link pause_interval} property.
     *
     *  <i>Note that requests may not finish in the same order as initiated!</i>
     *
     *  <code>
     *  // the callback function to be executed for each and every
     *  // request, as soon as a request finishes
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  function mycallback($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see http://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *          // show the server's response code
     *          } else die('Server responded with code ' . $result->info['http_code']);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else die('cURL responded with: ' . $result->response[0]);
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
     *  @param  mixed   $urls       A single URL or an array of URLs to process.
     *
     *  @param  mixed   $callback   (Optional) Callback function to be called as soon as a request finishes.
     *
     *                              May be given as a string representing a name of an existing function, as an anonymous
     *                              function created on the fly via {@link http://www.php.net/manual/en/function.create-function.php
     *                              create_function} or via a {@link http://www.php.net/manual/en/function.create-function.php
     *                              closure}.
     *
     *                              The callback function receives as first argument <b>an object</b> with <b>4 properties</b>
     *                              as described below, while any further arguments passed to the {@link header} method
     *                              will be passed as extra arguments to the callback function:
     *
     *                              -   <b>info</b>     -   an associative array containing information about the request
     *                                                      that just finished, as returned by PHP's
     *                                                      {@link http://php.net/manual/en/function.curl-getinfo.php curl_getinfo}
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
     *                              -   <b>body</b>     -   an empty string as it is not available for this method;
     *
     *                              -   <b>response</b> -   the response given by the cURL library as an array with 2
     *                                                      entries: the first entry is the textual representation of the
     *                                                      result's code, while second is the result's code itself; if
     *                                                      the request was successful, these values will be
     *                                                      <i>array(CURLE_OK, 0);</i> consult
     *                                                      {@link http://www.php.net/manual/en/function.curl-errno.php#103128 this list}
     *                                                      to see the possible values of this property;
     *
     *  <samp>If the callback function returns FALSE  while {@link cache} is enabled, the library will not cache the
     *  respective request, making it easy to retry failed requests without having to clear all cache.</samp>
     *
     *  @return void
     */
    public function header($urls, $callback = '') {

        // iterate through the list of URLs to process
        foreach ((array)$urls as $url)

            // add each URL and associated properties to the "_requests" property
            $this->_requests[] = array(
                'url'               =>  $url,
                'options'           =>  array(
                    CURLINFO_HEADER_OUT     =>  1,
                    CURLOPT_HEADER          =>  1,
                    CURLOPT_HTTPGET         =>  1,
                    CURLOPT_NOBODY          =>  1,
                    CURLOPT_BINARYTRANSFER  =>  null,
                    CURLOPT_CUSTOMREQUEST   =>  null,
                    CURLOPT_FILE            =>  null,
                    CURLOPT_POST            =>  null,
                    CURLOPT_POSTFIELDS      =>  null,
                ),
                'callback'          =>  $callback,

                // additional arguments to pass to the callback function, if any
                'arguments'         =>  array_slice(func_get_args(), 2),

            );

        // if we're just queuing requests for now, do not execute the next lines
        if ($this->_queue) return;

        // if we have to pause between batches of requests, process them sequentially, in batches
        if ($this->pause_interval > 0) $this->_process_paused();

        // if we don't have to pause between batches of requests, process them all at once
        else $this->_process();

    }

    /**
     *  Use this method to make requests to pages that require prior HTTP authentication.
     *
     *  <code>
     *  // the callback function to be executed for each and every
     *  // request, as soon as a request finishes
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  function mycallback($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see http://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *          // show the server's response code
     *          } else die('Server responded with code ' . $result->info['http_code']);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else die('cURL responded with: ' . $result->response[0]);
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
     *  If you have to unset previously set values use
     *  <code>
     *  $curl->http_authentication();
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
     *  @return void
     */
    public function http_authentication($username = '', $password = '', $type = CURLAUTH_ANY) {

        // set the required options
		$this->option(array(
            CURLOPT_HTTPAUTH    =>  ($username == '' && $password == '' ? null : $type),
            CURLOPT_USERPWD     =>  ($username == '' && $password == '' ? null : ($username . ':' . $password)),
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
     *  @return void
     *
     */
    public function option($option, $value = '') {

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
     *  Performs an HTTP <b>POST</b> request to one or more URLs with the POST data as specified by the <i>$urls</i> argument,
     *  and executes the callback function specified by the <i>$callback</i> argument for each and every request, as soon
     *  as a request finishes.
     *
     *  This method will automatically set the following options:
     *
     *  - <b>CURLINFO_HEADER_OUT</b> - TRUE
     *  - <b>CURLOPT_HEADER</b> - TRUE
     *  - <b>CURLOPT_NOBODY</b> - FALSE
     *  - <b>CURLOPT_POST</b> - TRUE
     *  - <b>CURLOPT_POSTFIELDS</b> - the POST data
     *
     *  ...and will unset the following options:
     *
     *  - <b>CURLOPT_BINARYTRANSFER</b>
     *  - <b>CURLOPT_CUSTOMREQUEST</b>
     *  - <b>CURLOPT_HTTPGET</b> - TRUE
     *  - <b>CURLOPT_FILE</b>
     *
     *  Multiple requests are processed asynchronously, in parallel, and the callback function is called for each and every
     *  request, as soon as a request finishes. The number of parallel requests to be constantly processed, at all times,
     *  can be set through the {@link threads} property. See also the {@link pause_interval} property.
     *
     *  <i>Note that requests may not finish in the same order as initiated!</i>
     *
     *  <code>
     *  // the callback function to be executed for each and every
     *  // request, as soon as a request finishes
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  function mycallback($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see http://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *          // show the server's response code
     *          } else die('Server responded with code ' . $result->info['http_code']);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else die('cURL responded with: ' . $result->response[0]);
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
     *  $curl->post(array(
     *      'http://www.somewebsite.com'  =>  array(
     *          'data_1'  =>  'value 1',
     *          'data_2'  =>  'value 2',
     *      ),
     *  ), 'mycallback');
     *
     *  // note that we're also uploading a file this time
     *  // and note that we're prefixing the file name with @
     *  $curl->post(array(
     *      'http://www.somewebsite.com'  =>  array(
     *          'data_1'  =>  'value 1',
     *          'data_2'  =>  'value 2',
     *          'data_3'  =>  '@absolute/path/to/file.ext',
     *  ), 'mycallback');
     *  </code>
     *
     *  @param  mixed   $urls       An associative array in the form of <i>url => post-data</i>, where "post-data" is an
     *                              associative array in the form of <i>name => value</i>.
     *
     *                              "post-data" can also be an arbitrary string - useful if you want to send raw data (like a JSON)
     *
     *                              To post a file, prepend the filename with @ and use the full server path.
     *
     *                              For PHP 5.5+ files are uploaded using {@link http://php.net/manual/ro/class.curlfile.php CURLFile}
     *                              and {@link https://wiki.php.net/rfc/curl-file-upload CURLOPT_SAFE_UPLOAD} will be set to TRUE.
     *
     *                              For lower PHP versions, files will be uploaded the "old" way and the file's mime type
     *                              should be explicitly specified by following the filename with the type in the format
     *                              <b>';type=mimetype'.</b> as most of the times cURL will send the wrong mime type...
     *
     *                              The <i>Content-Type</i> header will be set to <b>multipart/form-data.</b>
     *
     *  @param  mixed   $callback   (Optional) Callback function to be called as soon as a request finishes.
     *
     *                              May be given as a string representing a name of an existing function, as an anonymous
     *                              function created on the fly via {@link http://www.php.net/manual/en/function.create-function.php
     *                              create_function} or via a {@link http://www.php.net/manual/en/function.create-function.php
     *                              closure}.
     *
     *                              The callback function receives as first argument <b>an object</b> with <b>4 properties</b>
     *                              as described below, while any further arguments passed to the {@link post} method
     *                              will be passed as extra arguments to the callback function:
     *
     *                              -   <b>info</b>     -   an associative array containing information about the request
     *                                                      that just finished, as returned by PHP's
     *                                                      {@link http://php.net/manual/en/function.curl-getinfo.php curl_getinfo}
     *                                                      function;
     *
     *                              -   <b>headers</b>  -   an associative array with 2 items:
     *
     *                                                      <b>- last_request</b> an array with a single entry containing
     *                                                      the request headers generated by <i>the last request</i>; so,
     *                                                      remember, if there are redirects involved, there will be more
     *                                                      requests made, but only information from the last one will be
     *                                                      available; if explicitly disabled via the {@link option}
     *                                                      method by setting <b>CURLINFO_HEADER_OUT</b> to 0 or FALSE,
     *                                                      this will be an empty string;
     *
     *                                                      <b>- responses</b> an array with one or more entries (if there
     *                                                      are redirects involved) with the response headers of all the
     *                                                      requests made; if explicitly disabled via the {@link option}
     *                                                      method by setting <b>CURLOPT_HEADER</b> to 0 or FALSE, this
     *                                                      will be an empty string;
     *
     *                                                      <i>Unless disabled, each entry in the headers' array is an
     *                                                      associative array in the form of property => value</i>
     *
     *                              -   <b>body</b> -       the response of the request (the content of the page at the
     *                                                      URL).
     *
     *                                                      Unless disabled via the {@link __construct() constructor}, all
     *                                                      applicable characters will be converted to HTML entities via
     *                                                      PHP's {@link http://php.net/manual/en/function.htmlentities.php htmlentities}
     *                                                      function, so remember to use PHP's {@link http://www.php.net/manual/en/function.html-entity-decode.php html_entity_decode}
     *                                                      function to do reverse this, if it's the case;
     *
     *                                                      If "body" is explicitly disabled via the {@link option}
     *                                                      method by setting <b>CURLOPT_NOBODY</b> to 0 or FALSE, this
     *                                                      will be an empty string;
     *
     *                              -   <b>response</b> -   the response given by the cURL library as an array with 2
     *                                                      entries: the first entry is the textual representation of the
     *                                                      result's code, while second is the result's code itself; if
     *                                                      the request was successful, these values will be
     *                                                      <i>array(CURLE_OK, 0);</i> consult
     *                                                      {@link http://www.php.net/manual/en/function.curl-errno.php#103128 this list}
     *                                                      to see the possible values of this property;
     *
     *  <samp>If the callback function returns FALSE  while {@link cache} is enabled, the library will not cache the
     *  respective request, making it easy to retry failed requests without having to clear all cache.</samp>
     *
     *  @return void
     */
    public function post($urls, $callback = '') {

        // if "urls" argument is not an array, trigger an error
        if (!is_array($urls)) trigger_error('First argument to "post" method must be an array!', E_USER_ERROR);

        // iterate through the list of URLs to process
        foreach ((array)$urls as $url => $values) {

            // if $values is an array
            if (is_array($values))

                // walk recursively through the array
                array_walk_recursive($values, function(&$value) {

                    // if we have to upload a file
                    if (strpos($value, '@') === 0)

                        // if PHP version is 5.5+
                        if (version_compare(PHP_VERSION, '5.5') >= 0) {

                            // remove the @ from the name
                            $file = substr($value, 1);

                            // use CURLFile to prepare the file
                            $value = new CURLFile($file);

                        }

                });

            // add each URL and associated properties to the "_requests" property
            $this->_requests[] = array(
                'url'               =>  $url,
                'options'           =>  array(
                    CURLINFO_HEADER_OUT     =>  1,
                    CURLOPT_HEADER          =>  1,
                    CURLOPT_NOBODY          =>  0,
                    CURLOPT_POST            =>  1,
                    CURLOPT_POSTFIELDS      =>  $values,
                    CURLOPT_BINARYTRANSFER  =>  null,
                    CURLOPT_CUSTOMREQUEST   =>  null,
                    CURLOPT_HTTPGET         =>  null,
                    CURLOPT_FILE            =>  null,
                ),
                'callback'          =>  $callback,

                // additional arguments to pass to the callback function, if any
                'arguments'         =>  array_slice(func_get_args(), 2),

            );

        }

        // if we're just queuing requests for now, do not execute the next lines
        if ($this->_queue) return;

        // if we have to pause between batches of requests, process them sequentially, in batches
        if ($this->pause_interval > 0) $this->_process_paused();

        // if we don't have to pause between batches of requests, process them all at once
        else $this->_process();

    }

    /**
     *  Instruct the library to tunnel all requests through a proxy server.
     *
     *  <code>
     *  // the callback function to be executed for each and every
     *  // request, as soon as a request finishes
     *  function mycallback($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see http://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *          // show the server's response code
     *          } else die('Server responded with code ' . $result->info['http_code']);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else die('cURL responded with: ' . $result->response[0]);
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
     *                                  <i>This option can also be set using the {@link option} method and setting </i>
     *                                  <b>CURLOPT_PROXY</b> <i> option to the desired value</i>.
     *
     *                                  Setting this argument to FALSE will "unset" all the proxy-related options.
     *
     *  @param  string      $port       (Optional) The port number of the proxy to connect to.
     *
     *                                  Default is 80.
     *
     *                                  <i>This option can also be set using the {@link option} method and setting </i>
     *                                  <b>CURLOPT_PROXYPORT</b> <i> option to the desired value</i>.
     *
     *  @param  string      $username   (Optional) The username to be used for the connection to the proxy (if required
     *                                  by the proxy)
     *
     *                                  Default is "" (an empty string)
     *
     *                                  <i>The username and the password can also be set using the {@link option} method
     *                                  and setting </i> <b>CURLOPT_PROXYUSERPWD</b> <i> option to the desired value
     *                                  formatted like </i> <b>[username]:[password]</b>.     .
     *
     *  @param  string      $password   (Optional) The password to be used for the connection to the proxy (if required
     *                                  by the proxy)
     *
     *                                  Default is "" (an empty string)
     *
     *                                  <i>The username and the password can also be set using the {@link option} method
     *                                  and setting </i> <b>CURLOPT_PROXYUSERPWD</b> <i> option to the desired value
     *                                  formatted like </i> <b>[username]:[password]</b>.     .
     *
     *  @return void
     */
    public function proxy($proxy, $port = 80, $username = '', $password = '') {

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
     *  Performs an HTTP <b>PUT</b> request to one or more URLs with the POST data as specified by the <i>$urls</i> argument,
     *  and executes the callback function specified by the <i>$callback</i> argument for each and every request, as soon
     *  as a request finishes.
     *
     *  This method will automatically set the following options:
     *
     *  - <b>CURLINFO_HEADER_OUT</b> - TRUE
     *  - <b>CURLOPT_CUSTOMREQUEST</b> - "PUT"
     *  - <b>CURLOPT_HEADER</b> - TRUE
     *  - <b>CURLOPT_NOBODY</b> - FALSE
     *  - <b>CURLOPT_POST</b> - FALSE
     *  - <b>CURLOPT_POSTFIELDS</b> - the POST data
     *
     *  ...and will unset the following options:
     *
     *  - <b>CURLOPT_BINARYTRANSFER</b>
     *  - <b>CURLOPT_HTTPGET</b> - TRUE
     *  - <b>CURLOPT_FILE</b>
     *
     *  Multiple requests are processed asynchronously, in parallel, and the callback function is called for each and every
     *  request, as soon as a request finishes. The number of parallel requests to be constantly processed, at all times,
     *  can be set through the {@link threads} property. See also the {@link pause_interval} property.
     *
     *  <i>Note that requests may not finish in the same order as initiated!</i>
     *
     *  <code>
     *  // the callback function to be executed for each and every
     *  // request, as soon as a request finishes
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  function mycallback($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see http://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *          // show the server's response code
     *          } else die('Server responded with code ' . $result->info['http_code']);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else die('cURL responded with: ' . $result->response[0]);
     *
     *  }
     *
     *  // include the Zebra_cURL library
     *  require 'path/to/Zebra_cURL';
     *
     *  // instantiate the Zebra_cURL object
     *  $curl = new Zebra_cURL();
     *
     *  // do a PUT and execute the "mycallback" function for each
     *  // request, as soon as it finishes
     *  $curl->put(array(
     *      'http://www.somewebsite.com'  =>  array(
     *          'data_1'  =>  'value 1',
     *          'data_2'  =>  'value 2',
     *      ),
     *  ), 'mycallback');
     *  </code>
     *
     *  @param  mixed   $urls       An associative array in the form of <i>url => put-data</i>, where "put-data" is an
     *                              associative array in the form of <i>name => value</i>.
     *
     *                              "put-data" can also be an arbitrary string - useful if you want to send raw data (like a JSON)
     *
     *                              To put a file, prepend the filename with @ and use the full server path.
     *
     *                              For PHP 5.5+ files are uploaded using {@link http://php.net/manual/ro/class.curlfile.php CURLFile}
     *                              and {@link https://wiki.php.net/rfc/curl-file-upload CURLOPT_SAFE_UPLOAD} will be set to TRUE.
     *
     *                              For lower PHP versions, files will be uploaded the "old" way and the file's mime type
     *                              should be explicitly specified by following the filename with the type in the format
     *                              <b>';type=mimetype'.</b> as most of the times cURL will send the wrong mime type...
     *
     *                              The <i>Content-Type</i> header will be set to <b>multipart/form-data.</b>
     *
     *  @param  mixed   $callback   (Optional) Callback function to be called as soon as a request finishes.
     *
     *                              May be given as a string representing a name of an existing function, as an anonymous
     *                              function created on the fly via {@link http://www.php.net/manual/en/function.create-function.php
     *                              create_function} or via a {@link http://www.php.net/manual/en/function.create-function.php
     *                              closure}.
     *
     *                              The callback function receives as first argument <b>an object</b> with <b>4 properties</b>
     *                              as described below, while any further arguments passed to the {@link put} method
     *                              will be passed as extra arguments to the callback function:
     *
     *                              -   <b>info</b>     -   an associative array containing information about the request
     *                                                      that just finished, as returned by PHP's
     *                                                      {@link http://php.net/manual/en/function.curl-getinfo.php curl_getinfo}
     *                                                      function;
     *
     *                              -   <b>headers</b>  -   an associative array with 2 items:
     *
     *                                                      <b>- last_request</b> an array with a single entry containing
     *                                                      the request headers generated by <i>the last request</i>; so,
     *                                                      remember, if there are redirects involved, there will be more
     *                                                      requests made, but only information from the last one will be
     *                                                      available; if explicitly disabled via the {@link option}
     *                                                      method by setting <b>CURLINFO_HEADER_OUT</b> to 0 or FALSE,
     *                                                      this will be an empty string;
     *
     *                                                      <b>- responses</b> an array with one or more entries (if there
     *                                                      are redirects involved) with the response headers of all the
     *                                                      requests made; if explicitly disabled via the {@link option}
     *                                                      method by setting <b>CURLOPT_HEADER</b> to 0 or FALSE, this
     *                                                      will be an empty string;
     *
     *                                                      <i>Unless disabled, each entry in the headers' array is an
     *                                                      associative array in the form of property => value</i>
     *
     *                              -   <b>body</b> -       the response of the request (the content of the page at the
     *                                                      URL).
     *
     *                                                      Unless disabled via the {@link __construct() constructor}, all
     *                                                      applicable characters will be converted to HTML entities via
     *                                                      PHP's {@link http://php.net/manual/en/function.htmlentities.php htmlentities}
     *                                                      function, so remember to use PHP's {@link http://www.php.net/manual/en/function.html-entity-decode.php html_entity_decode}
     *                                                      function to do reverse this, if it's the case;
     *
     *                                                      If "body" is explicitly disabled via the {@link option}
     *                                                      method by setting <b>CURLOPT_NOBODY</b> to 0 or FALSE, this
     *                                                      will be an empty string;
     *
     *                              -   <b>response</b> -   the response given by the cURL library as an array with 2
     *                                                      entries: the first entry is the textual representation of the
     *                                                      result's code, while second is the result's code itself; if
     *                                                      the request was successful, these values will be
     *                                                      <i>array(CURLE_OK, 0);</i> consult
     *                                                      {@link http://www.php.net/manual/en/function.curl-errno.php#103128 this list}
     *                                                      to see the possible values of this property;
     *
     *  <samp>If the callback function returns FALSE  while {@link cache} is enabled, the library will not cache the
     *  respective request, making it easy to retry failed requests without having to clear all cache.</samp>
     *
     *  @since 1.3.3
     *
     *  @return void
     */
    public function put($urls, $callback = '') {

        // if "urls" argument is not an array, trigger an error
        if (!is_array($urls)) trigger_error('First argument to "put" method must be an array!', E_USER_ERROR);

        // iterate through the list of URLs to process
        foreach ((array)$urls as $url => $values)

            // if $values is an array
            if (is_array($values))

                // walk recursively through the array
                array_walk_recursive($values, function(&$value) {

                    // if we have to upload a file
                    if (strpos($value, '@') === 0)

                        // if PHP version is 5.5+
                        if (version_compare(PHP_VERSION, '5.5') >= 0) {

                            // remove the @ from the name
                            $file = substr($value, 1);

                            // use CURLFile to prepare the file
                            $value = new CURLFile($file);

                        }

                });

            // add each URL and associated properties to the "_requests" property
            $this->_requests[] = array(
                'url'               =>  $url,
                'options'           =>  array(
                    CURLINFO_HEADER_OUT     =>  1,
                    CURLOPT_CUSTOMREQUEST   =>  'PUT',
                    CURLOPT_HEADER          =>  1,
                    CURLOPT_NOBODY          =>  0,
                    CURLOPT_POST            =>  0,
                    CURLOPT_POSTFIELDS      =>  $values,
                    CURLOPT_BINARYTRANSFER  =>  null,
                    CURLOPT_HTTPGET         =>  null,
                    CURLOPT_FILE            =>  null,
                ),
                'callback'          =>  $callback,

                // additional arguments to pass to the callback function, if any
                'arguments'         =>  array_slice(func_get_args(), 2),

            );

        // if we're just queuing requests for now, do not execute the next lines
        if ($this->_queue) return;

        // if we have to pause between batches of requests, process them sequentially, in batches
        if ($this->pause_interval > 0) $this->_process_paused();

        // if we don't have to pause between batches of requests, process them all at once
        else $this->_process();

    }

    /**
     *  Instructs the library to queue requests rather than processing them right away. Useful for grouping different
     *  types of requests and treat them as a single request.
     *
     *  Until {@link start} method is called, all calls to {@link delete}, {@link download}, {@link ftp_download},
     *  {@link get}, {@link header}, {@link post} and {@link put} methods will queue up rather than being executed right
     *  away. Once the {@link start} method is called, all queued requests will be processed while values of
     *  {@link threads} and {@link pause_interval} properties will still apply.
     *
     *  <code>
     *  // the callback function to be executed for each and every
     *  // request, as soon as a request finishes
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  function mycallback($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see http://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *          // show the server's response code
     *          } else die('Server responded with code ' . $result->info['http_code']);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else die('cURL responded with: ' . $result->response[0]);
     *
     *  }
     *
     *  // include the Zebra_cURL library
     *  require 'path/to/Zebra_cURL';
     *
     *  // instantiate the Zebra_cURL object
     *  $curl = new Zebra_cURL();
     *
     *  // queue requests - useful for grouping different types of requests
     *  // in this example, when the "start" method is called, we'll execute
     *  // the "get" and the "post" requests simultaneously as if it was a
     *  // single request
     *  $curl->queue();
     *
     *  // do a POST and execute the "mycallback" function for each
     *  // request, as soon as it finishes
     *  $curl->post(array(
     *      'http://www.somewebsite.com'  =>  array(
     *          'data_1'  =>  'value 1',
     *          'data_2'  =>  'value 2',
     *      ),
     *  ), 'mycallback');
     *
     *  // let's fetch the RSS feeds of some popular websites
     *  // execute the "mycallback" function for each request, as soon as it finishes
     *  $curl->get(array(
     *      'http://feeds.feedburner.com/alistapart/main',
     *      'http://feeds.feedburner.com/TechCrunch',
     *      'http://feeds.mashable.com/mashable',
     *  ), 'mycallback')
     *
     *  // execute queued requests
     *  $curl->start();
     *  </code>
     *
     *  @since 1.3.0
     *
     *  @return void
     */
    public function queue() {

        // set a flag indicating the library to queue requests rather than executing them right away
        $this->_queue = true;

    }

    /**
     *  A shorthand for making a single {@link get} request without the need of a callback function
     *
     *  <code>
     *  // include the Zebra_cURL library
     *  require 'path/to/Zebra_cURL';
     *
     *  // instantiate the class
     *  $curl = new Zebra_cURL();
     *
     *  // get page's content only
     *  $content = $curl->scrap('https://www.somewebsite.com/');
     *
     *  // print that to screen
     *  echo $content;
     *
     *  // get everything we can about the page
     *  $content = $curl->scrap('https://www.somewebsite.com/', false);
     *
     *  // print that to screen
     *  print_r('<pre>');
     *  print_r($content);
     *  </code>
     *
     *  @param  string      $url        An URL to fetch
     *
     *  @param  boolean     $body_only  (Optional) When set to TRUE, will instruct the method to return <i>only</i>
     *                                  the page's content, without info, headers, responses, etc.
     *
     *                                  When set to FALSE, will instruct the method to return everything it can about the
     *                                  scrapped page, as an object with properties as described for the <i>$callback</i>
     *                                  argument of the {@link get} method.
     *
     *                                  Default is TRUE.
     *
     *  @since 1.3.3
     *
     *  @return mixed   Returns the scrapped page's content, when <i>$body_only</i> is set to TRUE, or an object with
     *                  properties as described for the <i>$callback</i> argument of the {@link get} method.
     */
    public function scrap($url, $body_only = true) {

        // this method requires the $url argument to be a string
        if (is_array($url)) trigger_error('URL must be a string', E_USER_ERROR);

        // make the request
        $this->get($url, function($result) {

            // store result in this private property of the library
            $this->_scrap_result = $result;

        });

        // return result
        return $body_only ? $this->_scrap_result->body : $this->_scrap_result;

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
     *                                          Default is TRUE.
     *
     *                                          <i>This option can also be set using the {@link option} method and
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
     *                                          <i>This option can also be set using the {@link option} method and
     *                                          setting </i> <b>CURLOPT_SSL_VERIFYHOST</b> <i> option to the desired value</i>.
     *
     *  @param  mixed       $file               (Optional) An absolute path to a file holding one or more certificates to
     *                                          verify the peer with. This only makes sense if <b>CURLOPT_SSL_VERIFYPEER</b>
     *                                          is set to TRUE.
     *
     *                                          Default is FALSE.
     *
     *                                          <i>This option can also be set using the {@link option} method and
     *                                          setting </i> <b>CURLOPT_CAINFO</b> <i> option to the desired value</i>.
     *
     *  @param  mixed       $path               (Optional) An absolute path to a directory that holds multiple CA
     *                                          certificates. This only makes sense if <b>CURLOPT_SSL_VERIFYPEER</b> is
     *                                          set to TRUE.
     *
     *                                          Default is FALSE.
     *
     *                                          <i>This option can also be set using the {@link option} method and
     *                                          setting </i> <b>CURLOPT_CAPATH</b> <i> option to the desired value</i>.
     *
     *  @return void
     */
    public function ssl($verify_peer = true, $verify_host = 2, $file = false, $path = false) {

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
            else trigger_error('File "' . $file . '", holding one or more certificates to verify the peer with, was not found', E_USER_ERROR);

        // if a directory holding multiple CA certificates was given
        if ($path !== false)

            // if folder could be found, use it
            if (is_dir($path)) $this->option(CURLOPT_CAPATH, $path);

            // if folder was not found, trigger an error
            else trigger_error('Directory "' . $path . '", holding one or more CA certificates to verify the peer with, was not found', E_USER_ERROR);

    }

    /**
     *  Executes queued requests.
     *
     *  See {@link queue} method for more information.
     *
     *  @since 1.3.0
     *
     *  @return void
     */
    public function start() {

        // indicate the library that it should execute queued requests
        $this->_queue = false;

        // if we have to pause between batches of requests, process them sequentially, in batches
        if ($this->pause_interval > 0) $this->_process_paused();

        // if we don't have to pause between batches of requests, process them all at once
        else $this->_process();

    }

    /**
     *  Returns the currently set options in "human-readable" format.
     *
     *  @return string  Returns the set options in "human-readable" format.
     *
     *  @access private
     */
    private function _debug() {

        $result = '';

        // iterate through the defined constants
        foreach(get_defined_constants() as $name => $number)

            // iterate through the set options
            foreach ($this->options as $index => $value)

                // if this is a curl-related constant and it is one of the options that are set, add it to the result
                if (substr($name, 0, 7) == 'CURLOPT' && $number == $index) $result .= str_pad($index, 5, ' ', STR_PAD_LEFT) . ' ' . $name . ' => ' . var_export($value, true) . '<br>';

        // return the result
        return $result;

    }

    /**
     *  Returns the cache file name associated with a specific request.
     *
     *  @return string  Returns the set options in "human-readable" format.
     *
     *  @access private
     */
    private function _get_cache_file_name($request) {

        // iterate through the options associated with the request
        foreach ($request['options'] as $key => $value)

            // ...and remove null or empty values
            if (is_null($value) || $value == '') unset($request['options'][$key]);

        // remove some entries associated with the request
        // callback, arguments and the associated file handler (where it is the case) are not needed
        $request = array_diff_key($request, array('callback' => '', 'arguments' => '', 'file_handler' => ''));

        // return the path and name of the file name associated with the request
        return rtrim($this->cache['path'], '/') . '/' . md5(serialize($request));

    }

    /**
     *  Parse response headers.
     *
     *  It parses a string containing one or more HTTP headers and returns an array of headers where each entry also
     *  contains an associative array of <i>name</i> => <i>value</i> for each row of data in the respective header.
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
    private function _parse_headers($headers) {

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
     *  @return void
     *
     *  @access private
     */
    private function _process() {

        // if caching is enabled but path doesn't exist, or is not writable
        if ($this->cache !== false && (!is_dir($this->cache['path']) || !is_writable($this->cache['path'])))

            // trigger an error and stop execution
            trigger_error('Cache path does not exists or is not writable', E_USER_ERROR);

        // iterate through the requests to process
        foreach ($this->_requests as $index => $request) {

            // if callback function is defined but it doesn't exists
            if ($request['callback'] != '' && !is_callable($request['callback']))

                // trigger an error and stop execution
                // the check is for when callback functions are defined as methods of a class
                trigger_error('Callback function "' . (is_array($request['callback']) ? array_pop($request['callback']) : $request['callback']) . '" does not exist', E_USER_ERROR);

            // if caching is enabled
            if ($this->cache !== false) {

                // get the name to be used for the cache file associated with the request
                $cache_file = $this->_get_cache_file_name($request);

                // if cache file exists and is not expired
                if (file_exists($cache_file) && filemtime($cache_file) + $this->cache['lifetime'] > time()) {

                    // if we have a callback
                    if ($request['callback'] != '') {

                        // prepare the arguments to pass to the callback function
                        $arguments = array_merge(

                            // made of the result from the cache file...
                            array(unserialize($this->cache['compress'] ? gzuncompress(file_get_contents($cache_file)) : file_get_contents($cache_file))),

                            // ...and any additional arguments (minus the first 2)
                            (array)$request['arguments']

                        );

                        // feed them as arguments to the callback function
                        call_user_func_array($request['callback'], $arguments);

                        // remove this request from the list so it doesn't get processed
                        unset($this->_requests[$index]);

                    }

                }

            }

        }

        // if there are any requests to process
        if (!empty($this->_requests)) {

            // initialize the multi handle
            // this will allow us to process multiple handles in parallel
            $this->_multi_handle = curl_multi_init();

            // queue the first batch of requests
            // (as many as defined by the "threads" property, or less if there aren't as many requests)
            $this->_queue_requests();

            // a flag telling the library if there are any requests currently processing
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

                    // get the information associated with the request
                    $request = $this->_running['fh' . $resource_number];

                    // create a new object in which we will store all the data associated with the handle,
                    // as properties of this object
                    $result = new stdClass();

                    // get information about the request
                    $result->info = curl_getinfo($handle);

                    // extend the "info" property with the original URL
                    $result->info = array('original_url' => $request['url']) + $result->info;

                    // if request was a POST
                    if (isset($request['options'][CURLOPT_POSTFIELDS]) && $request['options'][CURLOPT_POSTFIELDS])

                        // put POST parameters in the response
                        $result->post = $request['options'][CURLOPT_POSTFIELDS];

                    // last request headers
                    $result->headers['last_request'] =

                        (

                            // if CURLINFO_HEADER_OUT is set
                            isset($request['options'][CURLINFO_HEADER_OUT]) &&

                            // if CURLINFO_HEADER_OUT is TRUE
                            $request['options'][CURLINFO_HEADER_OUT] == 1 &&

                            // if we actually have this information
                            isset($result->info['request_header'])

                        // extract request headers
                        ) ? $this->_parse_headers($result->info['request_header'], true) : '';

                    // remove request headers information from its previous location
                    unset($result->info['request_header']);

                    // get headers (unless we were explicitly told not to)
                    $result->headers['responses'] = (isset($request['options'][CURLOPT_HEADER]) && $request['options'][CURLOPT_HEADER] == 1) ?

                        $this->_parse_headers(substr($content, 0, $result->info['header_size'])) :

                        '';

                    // get output (unless we were explicitly told not to)
                    $result->body = !isset($request['options'][CURLOPT_NOBODY]) || $request['options'][CURLOPT_NOBODY] == 0 ?

                        (isset($request['options'][CURLOPT_HEADER]) && $request['options'][CURLOPT_HEADER] == 1 ?

                        substr($content, $result->info['header_size']) :

                        $content) :

                        '';

                    // if _htmlentities is set to TRUE, we're not doing a binary transfer and we have a body, run htmlentities() on it
                    if ($this->_htmlentities && !isset($request['options'][CURLOPT_BINARYTRANSFER]) && $result->body != '') {

                        // since PHP 5.3.0, htmlentities will return an empty string if the input string contains an
                        // invalid code unit sequence within the given encoding (utf-8 in our case)
                        // so take care of that
                        if (defined(ENT_IGNORE)) $result->body = htmlentities($result->body, ENT_IGNORE, 'utf-8');

                        // for PHP versions lower than 5.3.0
                        else htmlentities($result->body);

                    }

                    // get CURLs response code and associated message
                    $result->response = array($this->_response_messages[$info['result']], $info['result']);

                    // if we have a callback
                    if (isset($request['callback']) && $request['callback'] != '') {

                        // prepare the arguments to pass to the callback function
                        $arguments = array_merge(

                            // made of the "result" object...
                            array($result),

                            // ...and any additional arguments
                            $request['arguments']

                        );

                        // if downloaded a file
                        if (isset($request['options'][CURLOPT_BINARYTRANSFER]) && $request['options'][CURLOPT_BINARYTRANSFER]) {

                            // we make a dummy array with the first first 2 elements (which we also remove from the $arguments[0]->info array)
                            $tmp_array = array_splice($arguments[0]->info, 0, 2);

                            // make available the name we saved the file with
                            // (we need to merge the first 2 elements, our new array and the rest of the elements)
                            $arguments[0]->info = array_merge($tmp_array, array('downloaded_filename' => $this->_running['fh' . $resource_number]['file_name']), $arguments[0]->info);

                        }

                        // feed them as arguments to the callback function
                        // and save the callback's response, if any
                        $callback_response = call_user_func_array($request['callback'], $arguments);

                    // if no callback function, we assume the response is TRUE
                    } else $callback_response = true;

                    // if caching is enabled and the callback function did not return FALSE
                    if ($this->cache !== false && $callback_response !== false) {

                        // get the name of the cache file associated with the request
                        $cache_file = $this->_get_cache_file_name($request);

                        // cache the result
                        file_put_contents($cache_file, $this->cache['compress'] ? gzcompress(serialize($result)) : serialize($result));

                        // set rights on the file
                        chmod($cache_file, intval($this->cache['chmod'], 8));

                    }

                    // if there are more URLs to process and we're don't pause between batches of requests, queue the next one(s)
                    if (!empty($this->_requests) && !$this->pause_interval) $this->_queue_requests();

                    // remove the handle that we finished processing
                    // this needs to be done *after* we've already queued a new URL for processing
                    curl_multi_remove_handle($this->_multi_handle, $handle);

                    // make sure the handle gets closed
                    curl_close($handle);

                    // if we downloaded a file
                    if (isset($request['options'][CURLOPT_BINARYTRANSFER]) && $request['options'][CURLOPT_BINARYTRANSFER])

                        // close the associated file pointer
                        fclose($this->_running['fh' . $resource_number]['file_handler']);

                    // we don't need the information associated with this request anymore
                    unset($this->_running['fh' . $resource_number]);

                }

                // waits until curl_multi_exec() returns CURLM_CALL_MULTI_PERFORM or until the timeout, whatever happens first
                // call usleep() if a select returns -1 - workaround for PHP bug: https://bugs.php.net/bug.php?id=61141
                if ($running && curl_multi_select($this->_multi_handle) === -1) usleep(100);

            // as long as there are threads running or requests waiting in the queue
            } while ($running || !empty($this->_running));

            // close the multi curl handle
            curl_multi_close($this->_multi_handle);

        }

    }

    /**
     *  A wrapper for the {@link _process} method used when we need to pause between batches of requests to
     *  process.
     *
     *  @return void
     *
     *  @access private
     */
    private function _process_paused() {

        // copy all requests to another variable
        $urls = $this->_requests;

        // while there are URLs to process
        while (!empty($urls)) {

            // get from the entire list of requests as many as specified by the "threads" property
            $this->_requests = array_splice($urls, 0, $this->threads, array());

            // process those requests
            $this->_process();

            // wait for as many seconds as specified by the "pause_interval" property
            if (!empty($urls)) sleep($this->pause_interval);

        }

    }

    /**
     *  A helper method used by the {@link _process} method, taking care of keeping a constant number of requests
     *  queued, so that as soon as one request finishes another one will instantly take its place, thus making sure that
     *  the maximum allowed number of parallel threads are running all the time.
     *
     *  @return void
     *
     *  @access private
     */
    private function _queue_requests() {

        // get the number of remaining urls
        $requests_count = count($this->_requests);

        // iterate through the items in the queue
        for ($i = 0; $i < ($requests_count < $this->threads ? $requests_count : $this->threads); $i++) {

            // remove the first request from the queue
            $request = array_shift($this->_requests);

            // initialize individual cURL handle with the URL
            $handle = curl_init($request['url']);

            // get the handle's ID
            $resource_number = preg_replace('/Resource id #/', '', $handle);

            // if we're downloading something
            if (isset($request['options'][CURLOPT_BINARYTRANSFER]) && $request['options'][CURLOPT_BINARYTRANSFER]) {

                // use parse_url to analyze the string
                // we use this so we won't have hashtags and/or query string in the file's name later on
                $parsed = parse_url($request['url']);

                // the name to save the file by
                // if the downloaded path refers to something with a query string (i.e. download.php?foo=bar&w=1000&h=1000)
                // the downloaded file's name would be "download.php" and, if you are downloading multiple files, each one
                // would overwrite the previous one; therefore we use an md5 of the query string in this case
                $request['file_name'] = $request['path'] . (isset($parsed['query']) && $parsed['query'] != '' ? md5($parsed['query']) : basename($parsed['path']));

                // open a file and save the file pointer
                $request['file_handler'] = fopen($request['file_name'], 'w+');

                // tell libcurl to use the file for streaming the download
                $this->option(CURLOPT_FILE, $request['file_handler']);

            }

            // set request's options
            foreach ($request['options'] as $key => $value) $this->option($key, $value);

            // in some cases, CURLOPT_HTTPAUTH and CURLOPT_USERPWD need to be set as last options in order to work
            $options_to_be_set_last = array(10005, 107);

            // iterate through all the options
            foreach ($this->options as $key => $value)

                // if this option is one of those to be set at the end
                if (in_array($key, $options_to_be_set_last)) {

                    // remove the option from where it is
                    unset($this->options[$key]);

                    // add option at the end
                    $this->options[$key] = $value;

                }

            // set options for the handle
            curl_setopt_array($handle, $this->options);

            // add the normal handle to the multi handle
            curl_multi_add_handle($this->_multi_handle, $handle);

            // add request to the list of running requests
            $this->_running['fh' . $resource_number] = $request;

        }

    }

    /**
     *  Generates a (slightly) random user agent (Internet Explorer 9 or 10, on Windows Vista, 7 or 8, with other extra
     *  strings)
     *
     *  Some web services will not respond unless a valid user-agent string is provided.
     *
     *  @return void
     *
     *  @access private
     */
    private function _user_agent() {

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
