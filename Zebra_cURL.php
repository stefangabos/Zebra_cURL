<?php

/**
 *  A high performance cURL PHP library allowing the running of multiple requests at once, asynchronously.
 *
 *  Read more {@link https://github.com/stefangabos/Zebra_cURL/ here}.
 *
 *  @author     Stefan Gabos <contact@stefangabos.ro>
 *  @version    1.5.1 (last revision: May 01, 2021)
 *  @copyright  © 2013 - 2021 Stefan Gabos
 *  @license    https://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE
 *  @package    Zebra_cURL
 */

class Zebra_cURL {

    /**
     *  The number of seconds to wait between processing batches of requests.
     *
     *  If the value of this property is greater than `0`, the library will process as many requests as defined by the
     *  {@link threads} property and then wait for {@link pause_interval} seconds before processing the next batch of
     *  requests.
     *
     *  Default is `0` (the library will keep as many parallel threads as defined by {@link threads} running at all times
     *  until there are no more requests to process).
     *
     *  @since 1.3.0
     *
     *  @var integer
     */
    public $pause_interval;

    /**
     *  The number of parallel, asynchronous requests to be processed by the library, at once.
     *
     *  <code>
     *  // process 30 simultaneous requests at once
     *  $curl->threads = 30;
     *  </code>
     *
     *  Note that unless {@link pause_interval} is set to a value greater than `0`, the library will process a constant
     *  number of requests, at all times; it is doing this by starting a new request as soon as another one finishes.
     *
     *  If {@link pause_interval} is set to a value greater than `0`, the library will process as many requests as set
     *  by the {@link threads} property and then wait for {@link pause_interval} seconds before processing the next
     *  batch of requests.
     *
     *  Default is `10`
     *
     *  @var integer
     */
    public $threads;

    /**
     * Used by the {@link _process} method to determine whether to run processed requests' bodies through PHP's
     * {@link https://php.net/manual/en/function.htmlentities.php htmlentities} function.
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
     *  As of PHP 8 we use an extra map as a helper.
     *
     *  @var array
     *
     *  @access private
     */
    private $_running_map;

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
     *  Below is the list of default options set by the library when instantiated. Various methods of the library may
     *  overwrite some of these options when called (see {@link delete}, {@link download}, {@link ftp_download}, {@link get},
     *  {@link header}, {@link post}, {@link put}). The value of any of these options may also be changed with the
     *  {@link option} method. For a full list of available options and their description, consult the
     *  {@link https://www.php.net/manual/en/function.curl-setopt.php PHP documentation}.
     *
     *  -   `CURLINFO_HEADER_OUT`       -   the last request string sent<br>
     *                                      default: `TRUE`
     *
     *  -   `CURLOPT_AUTOREFERER`       -   TRUE to automatically set the *"Referer:"* field in requests where it follows
     *                                      a *"Location:"* redirect<br>
     *                                      default: `TRUE`
     *
     *
     *  -   `CURLOPT_COOKIEFILE`        -   the name of the file containing the cookie data. the cookie file can be in
     *                                      Netscape format, or just plain HTTP-style headers dumped into a file. if the
     *                                      name is an empty string, no cookies are loaded, but cookie handling is still
     *                                      enabled<br>
     *                                      default: `an empty string`
     *
     *  -   `CURLOPT_CONNECTTIMEOUT`    -   the number of seconds to wait while trying to connect<br>
     *                                      default: `10` (use `0` to wait indefinitely)
     *
     *  -   `CURLOPT_ENCODING`          -   the contents of the "Accept-Encoding: " header. this enables decoding of the
     *                                      response. supported encodings are *identity*, *deflate*, and *gzip*. if an
     *                                      empty string is set, a header containing all supported encoding types is sent<br>
     *                                      default: `gzip,deflate`
     *
     *  -   `CURLOPT_FOLLOWLOCATION`    -   TRUE to follow any *"Location:"* header that the server sends as part of the
     *                                      HTTP header (note this is recursive, PHP will follow as many *"Location:"*
     *                                      headers that it is sent, unless `CURLOPT_MAXREDIRS` is set - see below)<br>
     *                                      default: `TRUE`
     *
     *  -   `CURLOPT_HEADER`            -   TRUE to include the header in the output<br>
     *                                      default: `TRUE`
     *
     *  -   `CURLOPT_MAXREDIRS`         -   the maximum amount of HTTP redirections to follow. use this option alongside
     *                                      `CURLOPT_FOLLOWLOCATION` - see above<br>
     *                                      default: `50`
     *
     *  -   `CURLOPT_RETURNTRANSFER`    -   TRUE to return the transfer's body as a string instead of outputting it
     *                                      directly<br>
     *                                      default: `TRUE`
     *
     *  -   `CURLOPT_SSL_VERIFYHOST`    -   1 to check the existence of a common name in the SSL peer certificate. 2 to
     *                                      check the existence of a common name and also verify that it matches the
     *                                      hostname provided. 0 to not check the names<br>
     *                                      see the {@link ssl} method for more info<br>
     *                                      default: `TRUE`
     *
     *  -   `CURLOPT_SSL_VERIFYPEER`    -   FALSE to stop cURL from verifying the peer's certificate<br>
     *                                      see the {@link ssl} method for more info<br>
     *                                      default: `TRUE`
     *
     *  -   `CURLOPT_TIMEOUT`           -   the maximum number of seconds to allow cURL functions to execute<br>
     *                                      default: `10`
     *
     *  -   `CURLOPT_USERAGENT`         -   a (slightly) random user agent (Internet Explorer 9 or 10, on Windows Vista,
     *                                      7 or 8, with other extra strings). Some web services will not respond unless
     *                                      a valid user-agent string is provided
     *
     *  @param  boolean $htmlentities       (Optional) Instructs the script whether the response body returned by the
     *                                      {@link get} and {@link post} methods should be run through PHP's
     *                                      {@link https://php.net/manual/en/function.htmlentities.php htmlentities}
     *                                      function.
     *
     *                                      Default is `TRUE`
     *
     *  @return void
     */
    public function __construct($htmlentities = true) {

        // if the cURL extension is not available, trigger an error and stop execution
        if (!extension_loaded('curl')) trigger_error('php_curl extension is not loaded', E_USER_ERROR);

        // initialize some private properties
        $this->_multi_handle = $this->_queue = false;
        $this->_running = $this->_running_map = $this->_requests = array();

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
     *  Enables caching of request results.
     *
     *  >   Note that in case of downloads, only the actual request is cached and not the associated downloads
     *
     *  <code>
     *  // instantiate the class
     *  $curl = new Zebra_cURL();
     *
     *  // if making requests over HTTPS we need to load a CA bundle
     *  // so we don't get CURLE_SSL_CACERT response from cURL
     *  // you can get this bundle from https://curl.haxx.se/docs/caextract.html
     *  $curl->ssl(true, 2, 'path/to/cacert.pem');
     *
     *  // cache results in the "cache" folder and for 86400 seconds (24 hours)
     *  $curl->cache('cache', 86400);
     *
     *  // fetch the RSS feeds of some popular tech-related websites
     *  // and execute a callback function for each request, as soon as it finishes
     *  $curl->get(array(
     *
     *      'https://alistapart.com/main/feed/',
     *      'https://www.smashingmagazine.com/feed/',
     *      'https://code.tutsplus.com/posts.atom',
     *
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  ), function($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see https://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *          // show the server's response code
     *          } else trigger_error('Server responded with code ' . $result->info['http_code'], E_USER_ERROR);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else trigger_error('cURL responded with: ' . $result->response[0], E_USER_ERROR);
     *
     *  });
     *  </code>
     *
     *  @param  string      $path       Path where cache files to be stored.
     *
     *                                  Setting this to `FALSE` will disable caching.
     *
     *                                  *If set to a non-existing path, the library will try to create the folder
     *                                  and will trigger an error if, for whatever reasons, it is unable to do so. If the
     *                                  folder can be created, its permissions will be set to the value of the $chmod
     *                                  argument.*
     *
     *  @param  integer     $lifetime   (Optional) The number of seconds after which cache will be considered expired.
     *
     *                                  Default is `3600` (one hour).
     *
     *  @param  boolean     $compress   (Optional) If set to `TRUE`, cache files will be
     *                                  {@link https://php.net/manual/en/function.gzcompress.php gzcompress}-ed so that
     *                                  they occupy less disk space.
     *
     *                                  Default is `TRUE`.
     *
     *  @param  octal       $chmod      (Optional) The file system permissions to be set for newly created cache files.
     *
     *                                  I suggest using the value `0755` but, if you know what you are doing, here is how
     *                                  you can calculate the permission levels:
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
     *                                  Default is `0755`.
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
     *  Sets the path and name of the file to save cookie to / retrieve cookies from. All cookie data will be stored in
     *  this file on a per-domain basis. Important when cookies need to stored/restored to maintain status/session of
     *  requests made to the same domains.
     *
     *  This method will automatically set the `CURLOPT_COOKIEJAR` and `CURLOPT_COOKIEFILE` options.
     *
     *  @param  string      $path   The path to a file to save cookies to / retrieve cookies from.
     *
     *                              *If file does not exist the library will attempt to create it and, if it is unable to
     *                              do so, it will trigger an error.*
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
     *  Performs an HTTP `DELETE` request to one or more URLs with optional POST data, and executes the callback
     *  function specified by the *$callback* argument for each and every request, as soon as the request finishes.
     *
     *  This method will automatically set the following options:
     *
     *  - `CURLINFO_HEADER_OUT` = `TRUE`
     *  - `CURLOPT_CUSTOMREQUEST` = `DELETE`
     *  - `CURLOPT_HEADER` = `TRUE`
     *  - `CURLOPT_NOBODY` = `FALSE`
     *  - `CURLOPT_POST` = `FALSE`
     *  - `CURLOPT_POSTFIELDS` = the POST data
     *
     *  ...and will unset the following options:
     *
     *  - `CURLOPT_BINARYTRANSFER`
     *  - `CURLOPT_HTTPGET`
     *  - `CURLOPT_FILE`
     *
     *  Multiple requests are processed asynchronously, in parallel, and the callback function is called for each and every
     *  request as soon as the request finishes. The number of parallel requests to be constantly processed, at all times,
     *  is set through the {@link threads} property. See also {@link pause_interval}.
     *
     *  >   Because requests are done asynchronously, when initiating multiple requests at once, these may not finish in
     *      the order in which they were initiated!
     *
     *  <code>
     *  // instantiate the class
     *  $curl = new Zebra_cURL();
     *
     *  // if making requests over HTTPS we need to load a CA bundle
     *  // so we don't get CURLE_SSL_CACERT response from cURL
     *  // you can get this bundle from https://curl.haxx.se/docs/caextract.html
     *  $curl->ssl(true, 2, 'path/to/cacert.pem');
     *
     *  // do a DELETE request
     *  // and execute a callback function for each request, as soon as it finishes
     *  $curl->delete(array(
     *
     *      'https://www.somewebsite.com'   =>  array(
     *          'data_1'  =>  'value 1',
     *          'data_2'  =>  'value 2',
     *      ),
     *
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  ), function($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see https://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *          // show the server's response code
     *          } else trigger_error('Server responded with code ' . $result->info['http_code'], E_USER_ERROR);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else trigger_error('cURL responded with: ' . $result->response[0], E_USER_ERROR);
     *
     *  });
     *  </code>
     *
     *  @param  mixed       $urls           URL(s) to send the request(s) to.
     *
     *                                      Read full description of the argument at the {@link post} method.
     *
     *  @param  callable    $callback       (Optional) Callback function to be called as soon as the request finishes.
     *
     *                                      Read full description of the argument at the {@link get} method.
     *
     *  @since 1.3.3
     *
     *  @return void
     */
    public function delete($urls, $callback = '') {

        // normalize URLs
        // (transforms every allowed combination to the same type of array)
        $urls = $this->_prepare_urls($urls);

        // iterate through the list of URLs to process
        foreach ($urls as $values)

            // add each URL and associated properties to the "_requests" property
            $this->_requests[] = array(

                'url'               =>  $values['url'],

                // merge any custom options with the default ones
                'options'           =>
                    (isset($values['options']) ? $values['options'] : array()) +
                    array(
                        CURLINFO_HEADER_OUT     =>  1,
                        CURLOPT_CUSTOMREQUEST   =>  'DELETE',
                        CURLOPT_HEADER          =>  1,
                        CURLOPT_NOBODY          =>  0,
                        CURLOPT_POST            =>  0,
                        CURLOPT_POSTFIELDS      =>  isset($values['data']) ? (is_array($values['data']) ? http_build_query($values['data'], null, '&') : $values['data']) : '',
                        CURLOPT_BINARYTRANSFER  =>  null,
                        CURLOPT_HTTPGET         =>  null,
                        CURLOPT_FILE            =>  null,
                    ),

                'callback'          =>  $callback,

                // additional arguments to pass to the callback function, if any
                'arguments'         =>  array_slice(func_get_args(), 2, null, true),

            );

        // if we're just queuing requests for now, do not execute the next lines
        if ($this->_queue) return;

        // if we have to pause between batches of requests, process them sequentially, in batches
        if ($this->pause_interval > 0) $this->_process_paused();

        // if we don't have to pause between batches of requests, process them all at once
        else $this->_process();

    }

    /**
     *  Downloads one or more files from one or more URLs, saves the downloaded files to the path specified by the *$path*
     *  argument, and executes the callback function specified by the *$callback* argument for each and every request,
     *  as soon as the request finishes.
     *
     *  >   If the path you are downloading from refers to a file, the file's original name will be preserved but, if
     *      you are downloading a file generated by a script (i.e. https://foo.com/bar.php?w=1200&h=800), the downloaded
     *      file's name will be random generated. Refer to the downloaded file's name in the result's `info` attribute,
     *      in the `downloaded_filename` section - see the example below.
     *
     *  >   If you are downloading multiple files with the same name the later ones will overwrite the previous ones.
     *
     *  Downloads are streamed (bytes downloaded are directly written to disk) removing the unnecessary strain from your
     *  server of reading files into memory first, and then writing them to disk.
     *
     *  This method will automatically set the following options:
     *
     *  - `CURLINFO_HEADER_OUT` = `TRUE`
     *  - `CURLOPT_BINARYTRANSFER` = `TRUE`
     *  - `CURLOPT_HEADER` = `TRUE`
     *  - `CURLOPT_FILE`
     *
     *  ...and will unset the following options:
     *
     *  - `CURLOPT_CUSTOMREQUEST`
     *  - `CURLOPT_HTTPGET`
     *  - `CURLOPT_NOBODY`
     *  - `CURLOPT_POST`
     *  - `CURLOPT_POSTFIELDS`
     *
     *  Multiple requests are processed asynchronously, in parallel, and the callback function is called for each and every
     *  request as soon as the request finishes. The number of parallel requests to be constantly processed, at all times,
     *  is set through the {@link threads} property. See also {@link pause_interval}.
     *
     *  >   Because requests are done asynchronously, when initiating multiple requests at once, these may not finish in
     *      the order in which they were initiated!
     *
     *  <code>
     *  // instantiate the class
     *  $curl = new Zebra_cURL();
     *
     *  // if making requests over HTTPS we need to load a CA bundle
     *  // so we don't get CURLE_SSL_CACERT response from cURL
     *  // you can get this bundle from https://curl.haxx.se/docs/caextract.html
     *  $curl->ssl(true, 2, 'path/to/cacert.pem');
     *
     *  // download 2 images from 2 different websites
     *  // and execute a callback function for each request, as soon as it finishes
     *  $curl->download(array(
     *
     *      'https://www.somewebsite.com/images/alpha.jpg',
     *      'https://www.otherwebsite.com/images/omega.jpg',
     *
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  ), 'destination/path/', function($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see https://httpstatus.es/ for a list of possible response codes
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
     *          } else trigger_error('Server responded with code ' . $result->info['http_code'], E_USER_ERROR);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else trigger_error('cURL responded with: ' . $result->response[0], E_USER_ERROR);
     *
     *  });
     *  </code>
     *
     *  @param  mixed       $urls           URL(s) to send the request(s) to.
     *
     *                                      Can be any of the following:
     *
     *                                      <code>
     *                                      // a string
     *                                      $curl->download('https://address.com/file.foo', 'path', 'callback');
     *
     *                                      // an array, for multiple requests
     *                                      $curl->download(array(
     *                                          'https://address1.com/file1.foo',
     *                                          'https://address2.com/file2.bar',
     *                                      ), 'path', 'callback');
     *                                      </code>
     *
     *                                      If {@link option() custom options} need to be set for each request, use the
     *                                      following format:
     *
     *                                      <code>
     *                                      // this can also be an array of arrays, for multiple requests
     *                                      $curl->download(array(
     *
     *                                          // mandatory!
     *                                          'url'       =>  'https://address.com/file.foo',
     *
     *                                          // optional, used to set any cURL option
     *                                          // in the same way you would set with the options() method
     *                                          'options'   =>  array(
     *                                                              CURLOPT_USERAGENT   =>  'Dummy scrapper 1.0',
     *                                                          ),
     *
     *                                      ), 'path', 'callback');
     *                                      </code>
     *
     *  @param  string      $path           The path to where to save the file(s) to.
     *
     *                                      *If path is not pointing to a directory or the directory is not writable, the
     *                                      library will trigger an error.*
     *
     *  @param  callable    $callback       (Optional) Callback function to be called as soon as the request finishes.
     *
     *                                      Read full description of the argument at the {@link get} method.
     *
     *  @return void
     */
    public function download($urls, $path, $callback = '') {

        // if destination path is not a directory or is not writable, trigger an error message
        if (!is_dir($path) || !is_writable($path)) trigger_error('"' . $path . '" is not a valid path or is not writable', E_USER_ERROR);

        // normalize URLs
        // (transforms every allowed combination to the same type of array)
        $urls = $this->_prepare_urls($urls);

        // iterate through the list of URLs to process
        foreach ($urls as $values)

            // add each URL and associated properties to the "_requests" property
            $this->_requests[] = array(

                'url'               =>  $values['url'],

                'path'              =>  rtrim($path, '/\\') . '/',

                // merge any custom options with the default ones
                'options'           =>
                    (isset($values['options']) ? $values['options'] : array()) +
                    array(
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
                'arguments'         =>  array_slice(func_get_args(), 3, null, true),

            );

        // if we're just queuing requests for now, do not execute the next lines
        if ($this->_queue) return;

        // if we have to pause between batches of requests, process them sequentially, in batches
        if ($this->pause_interval > 0) $this->_process_paused();

        // if we don't have to pause between batches of requests, process them all at once
        else $this->_process();

    }

    /**
     *  Works exactly like the {@link download} method but downloads are made from an FTP server.
     *
     *  Downloads one or more files from an FTP server, to which the connection is made using the given *$username* and
     *  *$password* arguments, saves the downloaded files (with their original name) to the path specified by the *$path*
     *  argument, and executes the callback function specified by the *$callback* argument for each and every request,
     *  as soon as the request finishes.
     *
     *  Downloads are streamed (bytes downloaded are directly written to disk) removing the unnecessary strain from your
     *  server of reading files into memory first, and then writing them to disk.
     *
     *  This method will automatically set the following options:
     *
     *  - `CURLINFO_HEADER_OUT` = `TRUE`
     *  - `CURLOPT_BINARYTRANSFER` = `TRUE`
     *  - `CURLOPT_HEADER` = `TRUE`
     *  - `CURLOPT_FILE`
     *
     *  ...and will unset the following options:
     *
     *  - `CURLOPT_CUSTOMREQUEST`
     *  - `CURLOPT_HTTPGET`
     *  - `CURLOPT_NOBODY`
     *  - `CURLOPT_POST`
     *  - `CURLOPT_POSTFIELDS`
     *
     *  >   If you are downloading multiple files with the same name the later ones will overwrite the previous ones.
     *
     *  Multiple requests are processed asynchronously, in parallel, and the callback function is called for each and every
     *  request as soon as the request finishes. The number of parallel requests to be constantly processed, at all times,
     *  is set through the {@link threads} property. See also {@link pause_interval}.
     *
     *  >   Because requests are done asynchronously, when initiating multiple requests at once, these may not finish in
     *      the order in which they were initiated!
     *
     *  <code>
     *  // instantiate the class
     *  $curl = new Zebra_cURL();
     *
     *  // if making requests over HTTPS we need to load a CA bundle
     *  // so we don't get CURLE_SSL_CACERT response from cURL
     *  // you can get this bundle from https://curl.haxx.se/docs/caextract.html
     *  $curl->ssl(true, 2, 'path/to/cacert.pem');
     *
     *  // connect to the FTP server using the given credential, download a file to a given location
     *  // and execute a callback function for each request, as soon as it finishes
     *  $curl->ftp_download(
     *
     *      'ftp://somefile.ext',
     *      'destination/path',
     *      'username',
     *      'password',
     *
     *      // the callback function receives as argument an object with 4 properties
     *      // (info, header, body and response)
     *      function($result) {
     *
     *          // everything went well at cURL level
     *          if ($result->response[1] == CURLE_OK) {
     *
     *              // if server responded with code 200 (meaning that everything went well)
     *              // see https://httpstatus.es/ for a list of possible response codes
     *              if ($result->info['http_code'] == 200) {
     *
     *                  // see all the returned data
     *                  print_r('<pre>');
     *                  print_r($result);
     *
     *              // show the server's response code
     *              } else trigger_error('Server responded with code ' . $result->info['http_code'], E_USER_ERROR);
     *
     *          // something went wrong
     *          // ($result still contains all data that could be gathered)
     *          } else trigger_error('cURL responded with: ' . $result->response[0], E_USER_ERROR);
     *
     *      }
     *
     *  );
     *  </code>
     *
     *  @param  mixed       $urls           URL(s) to send the request(s) to.
     *
     *                                      Can be any of the following:
     *
     *                                      <code>
     *                                      // a string
     *                                      $curl->ftp_download(
     *                                          'ftp://address.com/file.foo',
     *                                          'destination/path',
     *                                          'username',
     *                                          'password',
     *                                          'callback'
     *                                      );
     *
     *                                      // an array, for multiple requests
     *                                      $curl->ftp_download(array(
     *                                          'ftp://address1.com/file1.foo',
     *                                          'ftp://address2.com/file2.bar',
     *                                      ), 'destination/path', 'username', 'password', 'callback');
     *                                      </code>
     *
     *                                      If {@link option() custom options} need to be set for each request, use the
     *                                      following format:
     *
     *                                      <code>
     *                                      // this can also be an array of arrays, for multiple requests
     *                                      $curl->ftp_download(array(
     *
     *                                          // mandatory!
     *                                          'url'       =>  'ftp://address.com/file.foo',
     *
     *                                          // optional, used to set any cURL option
     *                                          // in the same way you would set with the options() method
     *                                          'options'   =>  array(
     *                                                              CURLOPT_USERAGENT   =>  'Dummy scrapper 1.0',
     *                                                          ),
     *
     *                                      ), 'destination/path', 'username', 'password', 'callback');
     *                                      </code>
     *
     *                                      Note that in all the examples above, you are downloading files from a single
     *                                      FTP server. To make requests to multiple FTP servers, set the `CURLOPT_USERPWD`
     *                                      option yourself. The *$username* and *$password* arguments will be overwritten
     *                                      by the values set like this.
     *
     *                                      <code>
     *                                      $curl->ftp_download(array(
     *                                          array(
     *                                              'url'       =>  'ftp://address1.com/file1.foo',
     *                                              'options'   =>  array(
     *                                                                  CURLOPT_USERPWD =>  'username1:password1',
     *                                                              ),
     *                                          ),
     *                                          array(
     *                                              'url'       =>  'ftp://address2.com/file2.foo',
     *                                              'options'   =>  array(
     *                                                                  CURLOPT_USERPWD =>  'username2:password2',
     *                                                              ),
     *                                          ),
     *                                      ), 'destination/path', '', '', 'callback');
     *                                      </code>
     *
     *  @param  string      $path           The path to where to save the file(s) to.
     *
     *                                      *If path is not pointing to a directory or is not writable, the library will
     *                                      trigger an error.*
     *
     *  @param  string      $username       (Optional) The username to be used to connect to the FTP server (if required).
     *
     *  @param  string      $password       (Optional) The password to be used to connect to the FTP server (if required).
     *
     *  @param  callable    $callback       (Optional) Callback function to be called as soon as the request finishes.
     *
     *                                      Read full description of the argument at the {@link get} method.
     *
     *  @return void
     */
    public function ftp_download($urls, $path, $username = '', $password = '', $callback = '') {

        // if destination path is not a directory or is not writable, trigger an error message
        if (!is_dir($path) || !is_writable($path)) trigger_error('"' . $path . '" is not a valid path or is not writable', E_USER_ERROR);

        // normalize URLs
        // (transforms every allowed combination to the same type of array)
        $urls = $this->_prepare_urls($urls);

        // iterate through the list of URLs to process
        foreach ($urls as $values)

            // add each URL and associated properties to the "_requests" property
            $this->_requests[] = array(

                'url'               =>  $values['url'],

                'path'              =>  rtrim($path, '/\\') . '/',

                // merge any custom options with the default ones
                'options'           =>
                    (isset($values['options']) ? $values['options'] : array()) +
                    array(
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
                'arguments'         =>  array_slice(func_get_args(), 5, null, true),

            );

        // if we're just queuing requests for now, do not execute the next lines
        if ($this->_queue) return;

        // if we have to pause between batches of requests, process them sequentially, in batches
        if ($this->pause_interval > 0) $this->_process_paused();

        // if we don't have to pause between batches of requests, process them all at once
        else $this->_process();

    }

    /**
     *  Performs an HTTP `GET` request to one or more URLs and executes the callback function specified by the *$callback*
     *  argument for each and every request, as soon as the request finishes.
     *
     *  This method will automatically set the following options:
     *
     *  - `CURLINFO_HEADER_OUT` = `TRUE`
     *  - `CURLOPT_HEADER` = `TRUE`
     *  - `CURLOPT_HTTPGET` = `TRUE`
     *  - `CURLOPT_NOBODY` = `FALSE`
     *
     *  ...and will unset the following options:
     *
     *  - `CURLOPT_BINARYTRANSFER`
     *  - `CURLOPT_CUSTOMREQUEST`
     *  - `CURLOPT_FILE`
     *  - `CURLOPT_POST`
     *  - `CURLOPT_POSTFIELDS`
     *
     *  Multiple requests are processed asynchronously, in parallel, and the callback function is called for each and every
     *  request as soon as the request finishes. The number of parallel requests to be constantly processed, at all times,
     *  is set through the {@link threads} property. See also {@link pause_interval}.
     *
     *  >   Because requests are done asynchronously, when initiating multiple requests at once, these may not finish in
     *      the order in which they were initiated!
     *
     *  <code>
     *  // instantiate the class
     *  $curl = new Zebra_cURL();
     *
     *  // if making requests over HTTPS we need to load a CA bundle
     *  // so we don't get CURLE_SSL_CACERT response from cURL
     *  // you can get this bundle from https://curl.haxx.se/docs/caextract.html
     *  $curl->ssl(true, 2, 'path/to/cacert.pem');
     *
     *  // cache results in the "cache" folder and for 3600 seconds (one hour)
     *  $curl->cache('cache', 3600);
     *
     *  // let's fetch the RSS feeds of some popular websites
     *  // execute the callback function for each request, as soon as it finishes
     *  $curl->get(array(
     *
     *      'https://alistapart.com/main/feed/',
     *      'https://www.smashingmagazine.com/feed/',
     *      'https://code.tutsplus.com/posts.atom',
     *
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  ), function($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see https://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *          // show the server's response code
     *          } else trigger_error('Server responded with code ' . $result->info['http_code'], E_USER_ERROR);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else trigger_error('cURL responded with: ' . $result->response[0], E_USER_ERROR);
     *
     *  });
     *  </code>
     *
     *  @param  mixed       $urls           URL(s) to send the request(s) to.
     *
     *                                      Can be any of the following:
     *
     *                                      <code>
     *                                      // a string
     *                                      $curl->get('https://address.com/', 'callback');
     *
     *                                      // an array, for multiple requests
     *                                      $curl->get(array(
     *                                          'https://address1.com/',
     *                                          'https://address2.com/',
     *                                      ), 'callback');
     *                                      </code>
     *
     *                                      If {@link option() custom options} need to be set for each request, use the
     *                                      following format:
     *
     *                                      <code>
     *                                      // this can also be an array of arrays, for multiple requests
     *                                      $curl->get(array(
     *
     *                                          // mandatory!
     *                                          'url'       =>  'https://address.com/',
     *
     *                                          // optional, used to set any cURL option
     *                                          // in the same way you would set with the options() method
     *                                          'options'   =>  array(
     *                                                              CURLOPT_USERAGENT   =>  'Dummy scrapper 1.0',
     *                                                          ),
     *
     *                                          // optional, you can pass arguments this way also
     *                                          'data'      =>  array(
     *                                                              'data_1'  =>  'value 1',
     *                                                              'data_2'  =>  'value 2',
     *                                                          ),
     *
     *                                      ), 'callback');
     *                                      </code>
     *
     *  @param  callable    $callback       (Optional) Callback function to be called as soon as the request finishes.
     *
     *                                      May be given as a string representing the name of an existing function, or
     *                                      as an {@link https://php.net/manual/en/functions.anonymous.php anonymous function}.
     *
     *                                      The callback function receives as first argument **an object** with **4 properties**
     *                                      as described below. Any extra arguments passed to the {@link download} method
     *                                      will be passed as extra arguments to the callback function:
     *
     *                                      -   `info`      -   an associative array containing information about the request
     *                                                          that just finished, as returned by PHP's {@link https://php.net/manual/en/function.curl-getinfo.php curl_getinfo()}
     *                                                          function
     *
     *                                      -   `headers`   -   an associative array with 2 items:
     *
     *                                                          <ul><li><ul><li>
     *                                                          `last_request` - an array with a single entry containing
     *                                                          the request headers generated by *the last request*<br>
     *                                                          therefore, when redirects are involved, only information
     *                                                          from the *last request* will be available<br>
     *                                                          if explicitly disabled by setting `CURLINFO_HEADER_OUT`
     *                                                          to `0` or `FALSE` through the {@link option} method, this
     *                                                          will be an empty string
     *                                                          </li></ul></li></ul>
     *
     *                                                          <ul><li><ul><li>
     *                                                          `responses` an empty string as it is not available for
     *                                                          this method
     *                                                          </li></ul></li></ul>
     *
     *                                      -   `body`      -   the response of the request (the content of the page at
     *                                                          the URL).<br><br>
     *                                                          >   Unless disabled via the {@link __construct() constructor},
     *                                                          all applicable characters will be converted to HTML entities
     *                                                          via PHP's {@link https://php.net/manual/en/function.htmlentities.php htmlentities}
     *                                                          function, so remember to use PHP's
     *                                                          {@link https://www.php.net/manual/en/function.html-entity-decode.php html_entity_decode}
     *                                                          function in case you need the decoded values<br>
     *                                                          if explicitly disabled by setting `CURLOPT_NOBODY` to `0`
     *                                                          or `FALSE` through the {@link option} method, this will
     *                                                          be an empty string
     *
     *                                      -   `response`  -   the {@link https://www.php.net/manual/en/function.curl-errno.php#103128 response}
     *                                                          given by the cURL library as an array with 2 items:<br>
     *
     *                                                          <ul><li><ul><li>
     *                                                          the textual representation of the result's code (i.e. `CURLE_OK`)
     *                                                          </li></ul></li></ul>
     *                                                          <ul><li><ul><li>
     *                                                          the result's code (i.e. `0`)
     *                                                          </li></ul></li></ul>
     *
     *  >   If the callback function returns FALSE  while {@link cache caching} is enabled, the library will not cache
     *  the respective request, making it easy to retry failed requests without having to clear all cache.
     *
     *  @return void
     */
    public function get($urls, $callback = '') {

        // normalize URLs
        // (transforms every allowed combination to the same type of array)
        $urls = $this->_prepare_urls($urls);

        // iterate through the list of URLs to process
        foreach ($urls as $values)

            // add each URL and associated properties to the "_requests" property
            $this->_requests[] = array(

                'url'               =>  $values['url'] . (isset($values['data']) ? '?' . (is_array($values['data']) ? http_build_query($values['data']) : $values['data']) : ''),

                // merge any custom options with the default ones
                'options'           =>
                    (isset($values['options']) ? $values['options'] : array()) +
                    array(
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
                'arguments'         =>  array_slice(func_get_args(), 2, null, true),

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
     *  - `CURLINFO_HEADER_OUT` = `TRUE`
     *  - `CURLOPT_HEADER` = `TRUE`
     *  - `CURLOPT_HTTPGET` = `TRUE`
     *  - `CURLOPT_NOBODY` = `TRUE`
     *
     *  ...and will unset the following options:
     *
     *  - `CURLOPT_BINARYTRANSFER`
     *  - `CURLOPT_CUSTOMREQUEST`
     *  - `CURLOPT_FILE`
     *  - `CURLOPT_POST`
     *  - `CURLOPT_POSTFIELDS`
     *
     *  Multiple requests are processed asynchronously, in parallel, and the callback function is called for each and every
     *  request as soon as the request finishes. The number of parallel requests to be constantly processed, at all times,
     *  is set through the {@link threads} property. See also {@link pause_interval}.
     *
     *  >   Because requests are done asynchronously, when initiating multiple requests at once, these may not finish in
     *      the order in which they were initiated!
     *
     *  <code>
     *  // instantiate the class
     *  $curl = new Zebra_cURL();
     *
     *  // process given URLs
     *  // and execute a callback function for each request, as soon as it finishes
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  $curl->header('https://www.somewebsite.com', function($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see https://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *          // show the server's response code
     *          } else trigger_error('Server responded with code ' . $result->info['http_code'], E_USER_ERROR);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else trigger_error('cURL responded with: ' . $result->response[0], E_USER_ERROR);
     *
     *  });
     *  </code>
     *
     *  @param  mixed       $urls           URL(s) to send the request(s) to.
     *
     *                                      Read full description of the argument at the {@link get} method.
     *
     *  @param  callable    $callback       (Optional) Callback function to be called as soon as the request finishes.
     *
     *                                      Read full description of the argument at the {@link get} method.
     *
     *  @return void
     */
    public function header($urls, $callback = '') {

        // normalize URLs
        // (transforms every allowed combination to the same type of array)
        $urls = $this->_prepare_urls($urls);

        // iterate through the list of URLs to process
        foreach ($urls as $values)

            // add each URL and associated properties to the "_requests" property
            $this->_requests[] = array(

                'url'               =>  $values['url'],

                // merge any custom options with the default ones
                'options'           =>
                    (isset($values['options']) ? $values['options'] : array()) +
                    array(
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
                'arguments'         =>  array_slice(func_get_args(), 2, null, true),

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
     *  // instantiate the class
     *  $curl = new Zebra_cURL();
     *
     *  // prepare user name and password
     *  $curl->http_authentication('username', 'password');
     *
     *  // if making requests over HTTPS we need to load a CA bundle
     *  // so we don't get CURLE_SSL_CACERT response from cURL
     *  // you can get this bundle from https://curl.haxx.se/docs/caextract.html
     *  $curl->ssl(true, 2, 'path/to/cacert.pem');
     *
     *  // get content from a page that requires prior HTTP authentication
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  $curl->get('https://www.some-page-requiring-prior-http-authentication.com', function($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see https://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *          // show the server's response code
     *          } else trigger_error('Server responded with code ' . $result->info['http_code'], E_USER_ERROR);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else trigger_error('cURL responded with: ' . $result->response[0], E_USER_ERROR);
     *
     *  });
     *  </code>
     *
     *  If you have to unset previously set values use
     *
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
     *                                      -   `CURLAUTH_BASIC`
     *                                      -   `CURLAUTH_DIGEST`
     *                                      -   `CURLAUTH_GSSNEGOTIATE`
     *                                      -   `CURLAUTH_NTLM`
     *                                      -   `CURLAUTH_ANY`
     *                                      -   `CURLAUTH_ANYSAFE`
     *
     *                                      The bitwise `|` (or) operator can be used to combine more than one method. If
     *                                      this is done, cURL will poll the server to see what methods it supports and
     *                                      pick the best one.
     *
     *                                      `CURLAUTH_ANY` is an alias for<br>
     *                                      `CURLAUTH_BASIC | CURLAUTH_DIGEST | CURLAUTH_GSSNEGOTIATE | CURLAUTH_NTLM`
     *
     *                                      `CURLAUTH_ANYSAFE` is an alias for<br>
     *                                      `CURLAUTH_DIGEST | CURLAUTH_GSSNEGOTIATE | CURLAUTH_NTLM`
     *
     *                                      Default is `CURLAUTH_ANY`
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
     *  Allows the setting of one or more {@link https://php.net/manual/en/function.curl-setopt.php cURL options}.
     *
     *  <code>
     *  // instantiate the class
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
     *  // requests are made here...
     *  </code>
     *
     *  @param  mixed   $option     A single option for which to set a value, or an associative array in the form of
     *                              *option* => *value*.
     *
     *                              *Setting a value to `null` will unset that option.*
     *
     *  @param  mixed   $value      (Optional) If the *$option* argument is not an array, then this argument represents
     *                              the value to be set for the respective option. If the *$option* argument is an array,
     *                              the value of this argument will be ignored.
     *
     *                              *Setting a value to `null` will unset that option.*
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
     *  Performs an HTTP `POST` request to one or more URLs and executes the callback function specified by the *$callback*
     *  argument for each and every request, as soon as the request finishes.
     *
     *  This method will automatically set the following options:
     *
     *  - `CURLINFO_HEADER_OUT` = `TRUE`
     *  - `CURLOPT_HEADER` = `TRUE`
     *  - `CURLOPT_NOBODY` = `FALSE`
     *  - `CURLOPT_POST` = `TRUE`
     *  - `CURLOPT_POSTFIELDS` = the POST data
     *
     *  ...and will unset the following options:
     *
     *  - `CURLOPT_BINARYTRANSFER`
     *  - `CURLOPT_CUSTOMREQUEST`
     *  - `CURLOPT_HTTPGET` = `TRUE`
     *  - `CURLOPT_FILE`
     *
     *  Multiple requests are processed asynchronously, in parallel, and the callback function is called for each and every
     *  request as soon as the request finishes. The number of parallel requests to be constantly processed, at all times,
     *  is set through the {@link threads} property. See also {@link pause_interval}.
     *
     *  >   Because requests are done asynchronously, when initiating multiple requests at once, these may not finish in
     *      the order in which they were initiated!
     *
     *  <code>
     *  // instantiate the class
     *  $curl = new Zebra_cURL();
     *
     *  // if making requests over HTTPS we need to load a CA bundle
     *  // so we don't get CURLE_SSL_CACERT response from cURL
     *  // you can get this bundle from https://curl.haxx.se/docs/caextract.html
     *  $curl->ssl(true, 2, 'path/to/cacert.pem');
     *
     *  // do a POST request and execute a callback function for each request, as soon as it finishes
     *  $curl->post(array(
     *
     *      'https://www.somewebsite.com'  =>  array(
     *          'data_1'  =>  'value 1',
     *          'data_2'  =>  'value 2',
     *      ),
     *
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  ), function($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see https://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *          // show the server's response code
     *          } else trigger_error('Server responded with code ' . $result->info['http_code'], E_USER_ERROR);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else trigger_error('cURL responded with: ' . $result->response[0], E_USER_ERROR);
     *
     *  });
     *  </code>
     *
     *  When uploading a file, we need to prefix the file name with `@`
     *
     *  <code>
     *  $curl->post(array(
     *      'https://www.somewebsite.com'  =>  array(
     *          'data_1'  =>  'value 1',
     *          'data_2'  =>  'value 2',
     *          'data_3'  =>  '@absolute/path/to/file.ext',
     *  ), 'mycallback');
     *  </code>
     *
     *  @param  mixed       $urls           URL(s) to send the request(s) to.
     *
     *                                      Can be any of the following:
     *
     *                                      <code>
     *                                      // a string (no POST values sent)
     *                                      $curl->post('https://address.com');
     *
     *                                      // an array, for multiple requests (no POST values sent)
     *                                      $curl->post(array(
     *                                          'https://address1.com',
     *                                          'https://address2.com',
     *                                      ));
     *
     *                                      // an associative array in the form of Array(url => post-data),
     *                                      // where "post-data" is an associative array in the form of
     *                                      // Array(name => value) and represents the value(s) to be set for
     *                                      // CURLOPT_POSTFIELDS;
     *                                      // "post‑data" can also be an arbitrary string - useful if you
     *                                      // want to send raw data (like a JSON)
     *                                      $curl->post(array('https://address.com' => array(
     *                                          'data_1'  =>  'value 1',
     *                                          'data_2'  =>  'value 2',
     *                                      )));
     *
     *                                      // just like above but an *array* of associative arrays, for
     *                                      // multiple requests
     *                                      $curl->post(array(
     *                                          array('https://address.com1' => array(
     *                                              'data_1'  =>  'value 1',
     *                                              'data_2'  =>  'value 2',
     *                                          )),
     *                                          array('https://address.com2' => array(
     *                                              'data_1'  =>  'value 1',
     *                                              'data_2'  =>  'value 2',
     *                                          )),
     *                                      ));
     *                                      </code>
     *
     *                                      If {@link option() custom options} need to be set for each request, use the
     *                                      following format:
     *
     *                                      <code>
     *                                      // this can also be an array of arrays, for multiple requests
     *                                      $curl->post(array(
     *
     *                                          // mandatory!
     *                                          'url'       =>  'https://address.com',
     *
     *                                          // optional, used to set any cURL option
     *                                          // in the same way you would set with the options() method
     *                                          'options'   =>  array(
     *                                                              CURLOPT_USERAGENT   =>  'Dummy scrapper 1.0',
     *                                                          ),
     *
     *                                          // optional, if you need to pass any arguments
     *                                          // (equivalent of setting CURLOPT_POSTFIELDS using
     *                                          // the "options" entry above)
     *                                          'data'      =>  array(
     *                                                              'data_1'  =>  'value 1',
     *                                                              'data_2'  =>  'value 2',
     *                                                          ),
     *                                      ));
     *                                      </code>
     *
     *                                      To post a file, prepend the filename with `@` and use the full server path.
     *
     *                                      For PHP 5.5+ files are uploaded using {@link https://php.net/manual/ro/class.curlfile.php CURLFile}
     *                                      and `{@link https://wiki.php.net/rfc/curl-file-upload CURLOPT_SAFE_UPLOAD}`
     *                                      will be set to `TRUE`.
     *
     *                                      For lower PHP versions, files will be uploaded the *old* way and the file's
     *                                      mime type should be explicitly specified by following the filename with the
     *                                      type in the format `';type=mimetype'` as most of the times cURL will send the
     *                                      wrong mime type...
     *
     *                                      <code>
     *                                      $curl->post(array('https://address.com' => array(
     *                                          'data_1'  =>  'value 1',
     *                                          'data_2'  =>  'value 2',
     *                                          'data_3'  =>  '@absolute/path/to/file.ext',
     *                                      )));
     *                                      </code>
     *
     *                                      >   If any data is sent, the "Content-Type" header will be set to
     *                                      "multipart/form-data"
     *
     *  @param  callable    $callback       (Optional) Callback function to be called as soon as the request finishes.
     *
     *                                      Read full description of the argument at the {@link get} method.
     *
     *  @return void
     */
    public function post($urls, $callback = '') {

        // normalize URLs
        // (transforms every allowed combination to the same type of array)
        $urls = $this->_prepare_urls($urls);

        // iterate through the list of URLs to process
        foreach ($urls as $values)

            // add each URL and associated properties to the "_requests" property
            $this->_requests[] = array(

                'url'               =>  $values['url'],

                'options'           =>
                    (isset($values['options']) ? $values['options'] : array()) +
                    array(
                        CURLINFO_HEADER_OUT     =>  1,
                        CURLOPT_HEADER          =>  1,
                        CURLOPT_NOBODY          =>  0,
                        CURLOPT_POST            =>  1,
                        CURLOPT_POSTFIELDS      =>  isset($values['data']) ? $values['data'] : '',
                        CURLOPT_BINARYTRANSFER  =>  null,
                        CURLOPT_CUSTOMREQUEST   =>  null,
                        CURLOPT_HTTPGET         =>  null,
                        CURLOPT_FILE            =>  null,
                    ),

                'callback'          =>  $callback,

                // additional arguments to pass to the callback function, if any
                'arguments'         =>  array_slice(func_get_args(), 2, null, true),

            );

        // if we're just queuing requests for now, do not execute the next lines
        if ($this->_queue) return;

        // if we have to pause between batches of requests, process them sequentially, in batches
        if ($this->pause_interval > 0) $this->_process_paused();

        // if we don't have to pause between batches of requests, process them all at once
        else $this->_process();

    }

    /**
     *  Instructs the library to tunnel all requests through a proxy server.
     *
     *  <code>
     *  // instantiate the class
     *  $curl = new Zebra_cURL();
     *
     *  // if making requests over HTTPS we need to load a CA bundle
     *  // so we don't get CURLE_SSL_CACERT response from cURL
     *  // you can get this bundle from https://curl.haxx.se/docs/caextract.html
     *  $curl->ssl(true, 2, 'path/to/cacert.pem');
     *
     *  // connect to a proxy server
     *  // (that's a random one i got from https://www.proxynova.com/proxy-server-list/)
     *  $curl->proxy('91.221.252.18', '8080');
     *
     *  // fetch a page and execute a callback function when done
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  $curl->get('https://www.somewebsite.com/', function($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see https://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *          // show the server's response code
     *          } else trigger_error('Server responded with code ' . $result->info['http_code'], E_USER_ERROR);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else trigger_error('cURL responded with: ' . $result->response[0], E_USER_ERROR);
     *
     *  });
     *  </code>
     *
     *  @param  string      $proxy      The HTTP proxy to tunnel requests through.
     *
     *                                  Can be an URL or an IP address.
     *
     *                                  *This option can also be set using the {@link option} method and setting
     *                                  `CURLOPT_PROXY` to the desired value.*
     *
     *                                  Setting this argument to `FALSE` will unset all the proxy-related options.
     *
     *  @param  string      $port       (Optional) The port number of the proxy to connect to.
     *
     *                                  Default is `80`.
     *
     *                                  *This option can also be set using the {@link option} method and setting
     *                                  `CURLOPT_PROXYPORT` to the desired value.*
     *
     *  @param  string      $username   (Optional) The username to be used for the connection to the proxy (if required
     *                                  by the proxy)
     *
     *                                  Default is `""` (an empty string)
     *
     *                                  *The username and the password can also be set using the {@link option} method
     *                                  and setting `CURLOPT_PROXYUSERPWD` to the desired value formatted like
     *                                  `[username]:[password]`.*
     *
     *  @param  string      $password   (Optional) The password to be used for the connection to the proxy (if required
     *                                  by the proxy)
     *
     *                                  Default is `""` (an empty string)
     *
     *                                  *The username and the password can also be set using the {@link option} method
     *                                  and setting `CURLOPT_PROXYUSERPWD` to the desired value formatted like
     *                                  `[username]:[password]`.*
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
     *  Performs an HTTP `PUT` request to one or more URLs and executes the callback function specified by the *$callback*
     *  argument for each and every request, as soon as the request finishes.
     *
     *  This method will automatically set the following options:
     *
     *  - `CURLINFO_HEADER_OUT` - `TRUE`
     *  - `CURLOPT_CUSTOMREQUEST` - `PUT`
     *  - `CURLOPT_HEADER` - `TRUE`
     *  - `CURLOPT_NOBODY` - `FALSE`
     *  - `CURLOPT_POST` - `FALSE`
     *  - `CURLOPT_POSTFIELDS` - the POST data
     *
     *  ...and will unset the following options:
     *
     *  - `CURLOPT_BINARYTRANSFER`
     *  - `CURLOPT_HTTPGET` = `TRUE`
     *  - `CURLOPT_FILE`
     *
     *  Multiple requests are processed asynchronously, in parallel, and the callback function is called for each and every
     *  request as soon as the request finishes. The number of parallel requests to be constantly processed, at all times,
     *  is set through the {@link threads} property. See also {@link pause_interval}.
     *
     *  >   Because requests are done asynchronously, when initiating multiple requests at once, these may not finish in
     *      the order in which they were initiated!
     *
     *  <code>
     *  // instantiate the class
     *  $curl = new Zebra_cURL();
     *
     *  // if making requests over HTTPS we need to load a CA bundle
     *  // so we don't get CURLE_SSL_CACERT response from cURL
     *  // you can get this bundle from https://curl.haxx.se/docs/caextract.html
     *  $curl->ssl(true, 2, 'path/to/cacert.pem');
     *
     *  // do a PUT request and execute a callback function for each request, as soon as it finishes
     *  $curl->put(array(
     *
     *      'https://www.somewebsite.com'  =>  array(
     *          'data_1'  =>  'value 1',
     *          'data_2'  =>  'value 2',
     *      ),
     *
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  ), function($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see https://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *          // show the server's response code
     *          } else trigger_error('Server responded with code ' . $result->info['http_code'], E_USER_ERROR);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else trigger_error('cURL responded with: ' . $result->response[0], E_USER_ERROR);
     *
     *  });
     *  </code>
     *
     *  @param  mixed       $urls           URL(s) to send the request(s) to.
     *
     *                                      Read full description of the argument at the {@link post} method.
     *
     *  @param  callable    $callback       (Optional) Callback function to be called as soon as the request finishes.
     *
     *                                      Read full description of the argument at the {@link get} method.
     *
     *  @since 1.3.3
     *
     *  @return void
     */
    public function put($urls, $callback = '') {

        // normalize URLs
        // (transforms every allowed combination to the same type of array)
        $urls = $this->_prepare_urls($urls);

        // iterate through the list of URLs to process
        foreach ($urls as $values)

            // add each URL and associated properties to the "_requests" property
            $this->_requests[] = array(

                'url'               =>  $values['url'],

                // merge any custom options with the default ones
                'options'           =>
                    (isset($values['options']) ? $values['options'] : array()) +
                    array(
                        CURLINFO_HEADER_OUT     =>  1,
                        CURLOPT_CUSTOMREQUEST   =>  'PUT',
                        CURLOPT_HEADER          =>  1,
                        CURLOPT_NOBODY          =>  0,
                        CURLOPT_POST            =>  0,
                        CURLOPT_POSTFIELDS      =>  isset($values['data']) ? $values['data'] : '',
                        CURLOPT_BINARYTRANSFER  =>  null,
                        CURLOPT_HTTPGET         =>  null,
                        CURLOPT_FILE            =>  null,
                    ),

                'callback'          =>  $callback,

                // additional arguments to pass to the callback function, if any
                'arguments'         =>  array_slice(func_get_args(), 2, null, true),

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
     *  // request, as soon as the request finishes
     *  // the callback function receives as argument an object with 4 properties
     *  // (info, header, body and response)
     *  function mycallback($result) {
     *
     *      // everything went well at cURL level
     *      if ($result->response[1] == CURLE_OK) {
     *
     *          // if server responded with code 200 (meaning that everything went well)
     *          // see https://httpstatus.es/ for a list of possible response codes
     *          if ($result->info['http_code'] == 200) {
     *
     *              // see all the returned data
     *              print_r('<pre>');
     *              print_r($result);
     *
     *          // show the server's response code
     *          } else trigger_error('Server responded with code ' . $result->info['http_code'], E_USER_ERROR);
     *
     *      // something went wrong
     *      // ($result still contains all data that could be gathered)
     *      } else trigger_error('cURL responded with: ' . $result->response[0], E_USER_ERROR);
     *
     *  }
     *
     *  // instantiate the class
     *  $curl = new Zebra_cURL();
     *
     *  // if making requests over HTTPS we need to load a CA bundle
     *  // so we don't get CURLE_SSL_CACERT response from cURL
     *  // you can get this bundle from https://curl.haxx.se/docs/caextract.html
     *  $curl->ssl(true, 2, 'path/to/cacert.pem');
     *
     *  // queue requests - useful for grouping different types of requests
     *  // in this example, when the "start" method is called, we'll execute
     *  // the "get" and the "post" requests asynchronously
     *  $curl->queue();
     *
     *  // do a POST and execute the callback function when done
     *  $curl->post(array(
     *      'https://www.somewebsite.com'  =>  array(
     *          'data_1'  =>  'value 1',
     *          'data_2'  =>  'value 2',
     *      ),
     *  ), 'mycallback');
     *
     *  // fetch the RSS feeds of some popular websites
     *  // and execute the callback function for each request, as soon as it finishes
     *  $curl->get(array(
     *      'https://alistapart.com/main/feed/',
     *      'https://www.smashingmagazine.com/feed/',
     *      'https://code.tutsplus.com/posts.atom',
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
     *  A shorthand for making a single {@link get} request without the need of a callback function.
     *
     *  <code>
     *  // instantiate the class
     *  $curl = new Zebra_cURL();
     *
     *  // if making requests over HTTPS we need to load a CA bundle
     *  // so we don't get CURLE_SSL_CACERT response from cURL
     *  // you can get this bundle from https://curl.haxx.se/docs/caextract.html
     *  $curl->ssl(true, 2, 'path/to/cacert.pem');
     *
     *  // get page's content only
     *  $content = $curl->scrap('https://www.somewebsite.com/');
     *
     *  // print that to screen
     *  echo $content;
     *
     *  // also get extra information about the page
     *  $content = $curl->scrap('https://www.somewebsite.com/', false);
     *
     *  // print that to screen
     *  print_r('<pre>');
     *  print_r($content);
     *  </code>
     *
     *  @param  string      $url        An URL to fetch.
     *
     *                                  >   Note that this method only supports a single URL. For processing multiple URLs
     *                                  at once, see the {@link get() get} method.
     *
     *  @param  boolean     $body_only  (Optional) When set to `TRUE`, will instruct the method to return *only* the page's
     *                                  content, without info, headers, responses, etc.
     *
     *                                  When set to `FALSE`, will instruct the method to return everything it can about
     *                                  the scrapped page, as an object with properties as described for the *$callback*
     *                                  argument of the {@link get} method.
     *
     *                                  Default is `TRUE`.
     *
     *  @since 1.3.3
     *
     *  @return mixed   Returns the scrapped page's content, when *$body_only* is set to `TRUE`, or an object with properties
     *                  as described for the *$callback* argument of the {@link get} method.
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
     *  Requests made over HTTPS usually require additional configuration, depending on the server. Most of the times
     *  {@link __construct() the defaults} set by the library will get you through but, if defaults are not working,
     *  you can set specific options using this method.
     *
     *  <code>
     *  // instantiate the class
     *  $curl = new Zebra_cURL();
     *
     *  // instruct the library to skip verifying peer's SSL certificate
     *  // (ignored if request is not made through HTTPS)
     *  $curl->ssl(false);
     *
     *  // fetch a page
     *  $curl->get('https://www.somewebsite.com/', function($result) {
     *      print_r("<pre>");
     *      print_r($result);
     *  });
     *  </code>
     *
     *  @param  boolean     $verify_peer        (Optional) Should the peer's certificate be verified by cURL?
     *
     *                                          Default is `TRUE`.
     *
     *                                          *This option can also be set using the {@link option} method and
     *                                          setting `CURLOPT_SSL_VERIFYPEER` to the desired value.*
     *
     *                                          When you are communicating over HTTPS (or any other protocol that
     *                                          uses TLS), it will, by default, verify that the server is signed by a
     *                                          trusted Certificate Authority (CA) and it will most likely fail.
     *
     *                                          When it does fail, instead of disabling this check, better
     *                                          {@link https://curl.haxx.se/docs/caextract.html download the CA bundle
     *                                          from Mozilla} and reference it through the *$file* argument below.
     *
     *  @param  integer     $verify_host        (Optional) Specifies whether to check the existence of a common name in
     *                                          the SSL peer certificate and that it matches with the provided hostname.
     *
     *                                          -   `1` to check the existence of a common name in the SSL peer certificate
     *                                          -   `2` to check the existence of a common name and also verify that it
     *                                                  matches the hostname provided; in production environments the value
     *                                                  of this option should be kept at `2`;
     *
     *                                          Default is `2`
     *
     *                                          >   Support for value 1 removed in cURL 7.28.1
     *
     *                                          *This option can also be set using the {@link option} method and setting
     *                                          `CURLOPT_SSL_VERIFYHOST` to the desired value.*
     *
     *  @param  mixed       $file               (Optional) An absolute path to a file holding the certificates to verify
     *                                          the peer with. This only makes sense if `CURLOPT_SSL_VERIFYPEER` is set
     *                                          to `TRUE`.
     *
     *                                          Default is `FALSE`.
     *
     *                                          *This option can also be set using the {@link option} method and setting
     *                                          `CURLOPT_CAINFO` to the desired value.*
     *
     *  @param  mixed       $path               (Optional) An absolute path to a directory that holds multiple CA
     *                                          certificates. This only makes sense if `CURLOPT_SSL_VERIFYPEER` is set
     *                                          to `TRUE`.
     *
     *                                          Default is `FALSE`.
     *
     *                                          *This option can also be set using the {@link option} method and setting
     *                                          `CURLOPT_CAPATH` to the desired value.*
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
     *  See {@link queue} method.
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
     *  contains an associative array of *name* => *value* for each row of data in the respective header.
     *
     *  @param  string  $headers    A string containing one or more HTTP headers, where multiple headers are separated by
     *                              a blank line.
     *
     *  @return mixed               Returns an array of headers where each entry also contains an associative array of
     *                              *name* => *value* for each row of data in the respective header.
     *
     *                              If `CURLOPT_HEADER` is set to `FALSE` or `0`, this method will return an empty string.
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
     *  Normalizes URLs.
     *
     *  Since URLs can be given as a string, an array of URLs, one associative array or an array of associative arrays,
     *  this method normalizes all of those into an array of associative arrays.
     *
     *  @return array
     *
     *  @access private
     */
    private function _prepare_urls($urls) {

        // if $urls is *one* associative array containing one of "url", "options" and "data" entries
        if (is_array($urls) && !empty(array_intersect(array('url', 'options', 'data'), array_keys($urls)))) {

            // since "url" is mandatory, stop if not present
            if (!isset($urls['url'])) trigger_error('<strong>url</strong> key is missing from argument', E_USER_ERROR);

            // return as an array of arrays
            return array($urls);

        // if $urls is an array
        } elseif (is_array($urls)) {

            $result = array();

            // iterate over the entries in the array
            foreach ($urls as $key => $values) {

                // if key is numeric
                // we take it that everything we need is inside $values
                if (is_numeric($key)) {

                    // if $values is an associative array containing one of "url", "options" and "data" entries, like
                    // array(
                    //      'url'       =>  'https://address.com',
                    //      'options'   =>  array(...)
                    // )
                    if (is_array($values) && !empty(array_intersect(array('url', 'options', 'data'), array_keys($values)))) {

                        // keep everything as it is
                        $result[] = $values;

                    // if $values is an array containing an associative array where the key is the URL and the value is
                    // another array containing at least one of "url", "options" or "data" entries, like
                    // array('https://address.com' => array(
                    //      'options'   =>  array(...),
                    // ))
                    } elseif (is_array($values) && !is_numeric(key($values)) && is_array(current($values)) && !empty(array_intersect(array('url', 'options', 'data'), array_keys(current($values))))) {

                        // normalize values
                        $result[] = array_merge(array('url' => key($values)), current($values));

                    // if $values is an associative array where the key is the URL and the value is
                    // another array of POST values
                    // array('https://address.com' => array('foo' => 'bar'))
                    } elseif (is_array($values)) {

                        // normalize values
                        $result[] = array(
                            'url'   =>  key($values),
                            'data'  =>  current($values)
                        );

                    // if $values is not an array or not an associative array containing one of "url", "options" and "data" entries, like
                    // 'https://address.com'
                    } else {

                        // it has to be the URL
                        $result[] = array('url' => $values);

                    }

                // if key is not numeric
                // we take it that key is the URL
                } else {

                    // if $values is an associative array containing one of "url", "options" and "data" entries, like
                    // array(
                    //      'url'       =>  'https://address.com',
                    //      'options'   =>  array(...)
                    // )
                    if (is_array($values) && !empty(array_intersect(array('url', 'options', 'data'), array_keys($values)))) {

                        // normalize values
                        $result[] = array_merge(array('url' => $key), $values);

                    // if $values is an associative array with POST values, like
                    // array('foo' => 'bar'))
                    } else {

                        // the values have to be the POST values
                        $result[] = array('url' => $key, 'data' => $values);

                    }

                }

            }

            // update the values
            $urls = $result;

        // if $urls is not an array, as in
        // 'https://address.com'
        } else {

            // it has to be the URL, and make it an array of arrays
            $urls = array(array('url' => $urls));

        }

        // walk recursively through the array
        array_walk_recursive($urls, function(&$value) {

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

        // return the normalized array
        return $urls;

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

                    // if PHP 8+, the we know the handle's ID because we stored a randomly generated one in this map when we called curl_init
                    if (PHP_MAJOR_VERSION >= 8) $resource_number = key(array_filter($this->_running_map, function($value) use ($handle) { return $value === $handle; }));

                    // for PHP 7 and below, get the handle's ID
                    else $resource_number = preg_replace('/Resource id #/', '', $handle);

                    // get the information associated with the request
                    $request = $this->_running[$resource_number];

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
                            $arguments[0]->info = array_merge($tmp_array, array('downloaded_filename' => $this->_running[$resource_number]['file_name']), $arguments[0]->info);

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
                        fclose($this->_running[$resource_number]['file_handler']);

                    // we don't need the information associated with this request anymore
                    unset($this->_running[$resource_number]);
                    unset($this->_running_map[$resource_number]);

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
            // (if PHP 8+ we generate a random one because $handle is no longer a "Resource" but a "CurlHandle")
            $resource_number = PHP_MAJOR_VERSION < 8 ? preg_replace('/Resource id #/', '', $handle) : uniqid('', true);

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

            // if PHP 8+ this is how we keep track of the running requests
            if (PHP_MAJOR_VERSION >= 8) $this->_running_map[$resource_number] = $handle;

            // add request to the list of running requests
            $this->_running[$resource_number] = $request;

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
