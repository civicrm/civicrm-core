<?php

/**
 * An Object Oriented interface to PHP's cURL extension
 *
 * PHP version 5.1.0+
 *
 * Copyright (c) 2007, The PEAR Group
 * 
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without 
 * modification, are permitted provided that the following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  - Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *  - Neither the name of the The PEAR Group nor the names of its contributors 
 *    may be used to endorse or promote products derived from this software 
 *    without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" 
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE 
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE 
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE 
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR 
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF 
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS 
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) 
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Net
 * @package   Net_Curl
 * @author    David Costa <gurugeek@php.net>
 * @author    Sterling Hughes <sterling@php.net>
 * @author    Joe Stump <joe@joestump.net>
 * @author    Philippe Jausions <jausions@php.net>
 * @copyright 1997-2008 The PHP Group
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   CVS: $Revision: 1.15 $
 * @link      http://pear.php.net/package/Net_Curl
 */

/**
 * Include PEAR package for error handling
 */
require_once 'PEAR.php';

/**
 * Object-oriented implementation of the Curl extension
 *
 * @category Net
 * @package  Net_Curl
 * @author   David Costa <gurugeek@php.net>
 * @author   Sterling Hughes <sterling@php.net>
 * @author   Joe Stump <joe@joestump.net>
 * @author   Philippe Jausions <jausions@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link     http://pear.php.net/package/Net_Curl
 */
class Net_Curl
{
    // {{{ Public Properties
    /**
     * The URL for cURL to work with
     *
     * @var string $url
     * @access public
     */
    var $url;

    /**
     * The Username for standard HTTP Authentication
     *
     * @var string $username
     * @access public
     */
    var $username = '';

    /**
     * The Password for standard HTTP Authentication
     *
     * @var string $password
     * @access public
     */
    var $password = '';

    /**
     * The SSL version for the transfer
     *
     * @var integer $sslVersion
     * @access public
     */
    var $sslVersion;

    /**
     * The filename of the SSL certificate
     *
     * @var string $sslCert
     * @access public
     */
    var $sslCert;

    /**
     * The password corresponding to the certificate
     * in the $sslCert property
     *
     * @var string $sslCertPasswd
     * @access public
     */
    var $sslCertPasswd;

    /**
     * User Agent string when making an HTTP request
     *
     * @var string $userAgent
     * @access public
     */
    var $userAgent;

    /**
     * Whether or not to include the header in the results
     * of the CURL transfer
     *
     * @var boolean $header
     */
    var $header = false;

    /**
     * Whether or not to output debug information while executing a
     * curl transfer
     *
     * @var boolean $verbose
     * @access public
     */
    var $verbose = false;

    /**
     * Whether or not to display a progress meter for the current transfer
     *
     * @var boolean $progress
     * @access public
     */
    var $progress = false;

    /**
     * Whether or not to suppress error messages
     *
     * @var boolean $mute
     * @access public
     */
    var $mute = false;

    /**
     * Whether or not to follow HTTP Location headers.
     *
     * @var boolean $followLocation
     * @access public
     */
    var $followLocation = true;

    /**
     * Whether or not to follow HTTP Location headers.
     *
     * @var boolean $follow_location
     * @access public
     * @deprecated
     */
    var $follow_location = false;

    /**
     * Time allowed for current transfer, in seconds.  0 means no limit
     *
     * @var int $timeout
     * @access public
     */
    var $timeout = 0;

    /**
     * Whether or not to return the results of the
     * current transfer
     *
     * @var boolean $returnTransfer
     * @access public
     */
    var $returnTransfer = true;

    /**
     * Whether or not to return the results of the
     * current transfer
     *
     * @var boolean $return_transfer
     * @access public
     * @deprecated
     */
    var $return_transfer = false;

    /**
     * The type of transfer to perform (ie. 'POST', 'GET', 'PUT', etc)
     *
     * @var string $type
     * @access public
     */
    var $type;

    /**
     * The file to upload (PUT, or FTP methods)
     *
     * @var string $file
     * @access public
     */
    var $file;

    /**
     * The file size of the file pointed to by the $file
     * property
     *
     * @var integer $fileSize
     * @access public
     */
    var $fileSize;

    /**
     * The file size of the file pointed to by the $file
     * property
     *
     * @var integer $file_size
     * @access public
     * @deprecated
     */
    var $file_size = false;


    /**
     * The cookies to send to the remote site
     *
     * @var array $cookies
     * @access public
     */
    var $cookies = array();

    /**
     * Additional HTTP headers to send to the remote site
     *
     * @var array $httpHeaders
     * @access public
     */
    var $httpHeaders = null;

    /**
     * Additional HTTP headers to send to the remote site
     *
     * @var array $http_headers
     * @access public
     * @deprecated
     */
    var $http_headers = false;

    /**
     * The fields to send in a 'POST' request
     *
     * @var array $fields
     * @access public
     */
    var $fields;

    /**
     * The proxy server to go through
     *
     * @var string $proxy
     * @access public
     */
    var $proxy;

    /**
     * The username for the Proxy server
     *
     * @var string $proxyUser
     * @access public
     */
    var $proxyUser;

    /**
     * The password for the Proxy server
     *
     * @var string $proxyPassword
     * @access public
     */
    var $proxyPassword;

    /**
     * $verifyPeer
     *
     * FALSE to stop CURL from verifying the peer's certificate.
     * Alternate certificates to verify against can be specified
     * with the CURLOPT_CAINFO option or a certificate directory
     * can be specified with the CURLOPT_CAPATH option.
     * CURLOPT_SSL_VERIFYHOST may also need to be TRUE or FALSE
     * if CURLOPT_SSL_VERIFYPEER is disabled (it defaults to 2).
     *
     * @var boolean $verifyPeer
     * @access public
     */
    var $verifyPeer = true;

    /**
     * $verifyHost
     *
     * 0 : to stop CURL from verifying the host's certificate.
     * 1 : to check the existence of a common name in the SSL peer certificate.
     * 2 : to check the existence of a common name  and also verify that it
     *     matches the hostname provided.
     *
     * @var bool $verifyHost
     * @access public
     */
    var $verifyHost = 2;

    /**
     * $caInfo
     *
     * Set value for CURLOPT_CAINFO. The name of a file holding one or more
     * certificates to verify the peer with. This only makes sense when used
     * in combination with CURLOPT_SSL_VERIFYPEER. curl-ca-bundle.crt is
     * avaible on the Curl website http://curl.haxx.se/ for download inside
     * the packages.
     *
     * @var string $caInfo
     * @access public
     */
    var $caInfo = '';

    /**
     * $caPath
     *
     * Set value for CURLOPT_CAPATH. A directory that holds multiple CA
     * certificates. Use this option alongside CURLOPT_SSL_VERIFYPEER.
     *
     * @var string $caPath
     * @access public
     */
    var $caPath;
    // }}}
    // {{{ Private Properties
    /**
     * The current curl handle
     *
     * @var resource $_ch
     * @access private
     * @see Net_Curl::create()
     */
    var $_ch = null;

    /**
     * The file upload resource
     *
     * The CURLOPT_INFILE requires a file resource and not just a file name.
     * This is used by execute to open the file.
     *
     * @var resource $_fp
     * @access private
     * @see Net_Curl::execute()
     */
    var $_fp = null;
    // }}}

    // {{{ __construct($url = '', $userAgent = '')
    /**
     * The Net_Curl PHP 5.x constructor, called when a new Net_Curl object
     * is initialized (also called via 4.x constructor)
     *
     * @param string $url       The URL to fetch (can be set using the $url 
     *                          property as well)
     * @param string $userAgent The userAgent string (can be set using the 
     *                          $userAgent property as well)
     *
     * @access public
     * @author Joe Stump <joe@joestump.net>
     * @return void
     */
    function __construct($url = '', $userAgent = '')
    {
        if (is_string($url) && strlen($url)) {
            $this->url = $url;
        }

        if (is_string($userAgent) && strlen($userAgent)) {
            $this->userAgent = $userAgent;
        }
    }
    // }}}

    // {{{ Net_Curl($url = '', $userAgent = '')
    /**
     * Net_Curl
     *
     * PHP 4.x constructor.
     *
     * @param string $url       The URL to fetch (can be set using the $url 
     *                          property as well)
     * @param string $userAgent The userAgent string (can be set using the 
     *                          $userAgent property as well)
     *
     * @access public
     * @return void
     *
    function Net_Curl($url = '', $userAgent = '')
    {
        $this->__construct($url, $userAgent);
    }
    // }}} */

    // {{{ execute()
    /**
     * Executes a prepared CURL transfer
     *
     * Run this function to execute your cURL request. If all goes well you
     * should get a string (the output from the remote host regarding your
     * request) or true (if you choose to output directly to the browser). If
     * something fails then PEAR_Error is returned.
     *
     * <code>
     * <?php
     *     require_once 'Net/Curl.php';
     *
     *     $curl = new Net_Curl('http://www.example.com');
     *     $curl->fields = array('foo' => '1', 'bar' => 'apple');
     *     $result = $curl->execute();
     *     if (!PEAR::isError($result)) {
     *         echo $result;
     *     }
     * ?>
     * </code>
     *
     * @access public
     * @author Sterling Hughes <sterling@php.net>
     * @author Joe Stump <joe@joestump.net>
     * @return PEAR_Error on failure, true/result on success
     * @since  PHP 4.0.5
     */
    function execute()
    {
        // Create cURL handle if it hasn't already been created
        if (!is_resource($this->_ch)) {
            $result = $this->create();
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        // Map the deprecated variables and throw a bunch of errors
        $this->_mapDeprecatedVariables();

        // Default return value is true.
        $ret = true;

        // Basic stuff
        $ret = curl_setopt($this->_ch, CURLOPT_URL, $this->url);
        $ret = curl_setopt($this->_ch, CURLOPT_HEADER, $this->header);

        // Whether or not to return the transfer contents
        if ($this->returnTransfer === true || $this->mute === true) {
            $ret = curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        }

        // HTTP Authentication
        if ($this->username != '') {
            $ret = curl_setopt($this->_ch, 
                               CURLOPT_USERPWD, 
                               $this->username . ':' . $this->password);
        }

        // SSL Checks
        if (isset($this->sslVersion)) {
            $ret = curl_setopt($this->_ch, 
                               CURLOPT_SSLVERSION, 
                               $this->sslVersion);
        }

        if (isset($this->sslCert)) {
            $ret = curl_setopt($this->_ch, CURLOPT_SSLCERT, $this->sslCert);
        }

        if (isset($this->sslCertPasswd)) {
            $ret = curl_setopt($this->_ch, 
                               CURLOPT_SSLCERTPASSWD, 
                               $this->sslCertPasswd);
        }

        // Proxy Related checks
        if (isset($this->proxy)) {
            $ret = curl_setopt($this->_ch, CURLOPT_PROXY, $this->proxy);
        }

        if (isset($this->proxyUser) || isset($this->proxyPassword)) {
            $ret = curl_setopt($this->_ch, 
                               CURLOPT_PROXYUSERPWD, 
                               $this->proxyUser . ':' . $this->proxyPassword);
        }

        if (is_bool($this->verifyPeer)) {
            if (!$this->setOption(CURLOPT_SSL_VERIFYPEER, $this->verifyPeer)) {
                return PEAR::raiseError('Error setting CURLOPT_SSL_VERIFYPEER');
            }
        }

        if (is_numeric($this->verifyHost) && $this->verifyHost >= 0 &&
            $this->verifyHost <= 2) {
            if (!$this->setOption(CURLOPT_SSL_VERIFYHOST, $this->verifyHost)) {
                return PEAR::raiseError('Error setting CURLOPT_SSL_VERIFYPEER');
            }
        }

        if (is_bool($this->verifyPeer) && $this->verifyPeer == true) {
            if (isset($this->caInfo) && strlen($this->caInfo)) {
                if (file_exists($this->caInfo)) {
                    if (!$this->setOption(CURLOPT_CAINFO, $this->caInfo)) {
                        return PEAR::raiseError('Error setting CURLOPT_CAINFO');
                    }
                } else {
                    return PEAR::raiseError('Could not find CA info: '.
                                            $this->caInfo);
                }
            }

            if (isset($this->caPath) && is_string($this->caPath)) {
                if (!$this->setOption(CURLOPT_CAPATH, $this->caPath)) {
                    return PEAR::raiseError('Error setting CURLOPT_CAPATH');
                }
            }
        }

        // Transfer type
        if (isset($this->type)) {
            switch (strtolower($this->type)) {
            case 'post':
                $ret = curl_setopt($this->_ch, CURLOPT_POST, true);
                break;
            case 'put':
                $ret = curl_setopt($this->_ch, CURLOPT_PUT, true);
                break;
            }
        }

        // Transfer upload, etc. related
        if (isset($this->file)) {
            if (!file_exists($this->file)) {
                return PEAR::raiseError('File does not exist: '.$this->file);
            }

            $this->_fp = fopen($this->file, 'r');
            if (!is_resource($this->_fp)) {
                return PEAR::raiseError('Could not open file: '.$this->file);
            }

            if (!isset($this->fileSize)) {
                $this->fileSize = filesize($this->file);
            }

            $ret = curl_setopt($this->_ch, CURLOPT_INFILE, $this->_fp);
            $ret = curl_setopt($this->_ch, CURLOPT_INFILESIZE, $this->fileSize);
            $ret = curl_setopt($this->_ch, CURLOPT_UPLOAD, true);
        }

        if (isset($this->fields)) {
            $sets = null;
            if (!isset($this->type)) {
                $this->type = 'post';
                $ret        = curl_setopt($this->_ch, CURLOPT_POST, true);
            }

            // If fields is an array then turn it into a string. Sometimes
            // cURL doesn't like fields as an array.
            // Exception: if a value is prefixed with "@" and the rest of the
            //            value resolves to an existing file, then pass
            //            the values as the original array.
            if (is_array($this->fields)) {
                $sets = array();
                foreach ($this->fields as $key => $val) {
                    if (strlen($val) > 1 && $val{0} == '@') {
                        $file = substr($val, 1);
                        if (is_file($file) && is_readable($file)) {
                            $sets = null;
                            break;
                        }
                    }
                    $sets[] = urlencode($key) . '=' . urlencode($val);
                }
            }

            if (!is_null($sets)) {
                $fields = implode('&', $sets);
            } else {
                $fields = $this->fields;
            }
            $ret = curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $fields);
        }

        // Error related
        if ($this->progress === true) {
            $ret = curl_setopt($this->_ch, CURLOPT_PROGRESS, true);
        }

        if ($this->verbose === true) {
            $ret = curl_setopt($this->_ch, CURLOPT_VERBOSE, true);
        }

        // If a Location: header is passed then follow it
        $ret = curl_setopt($this->_ch, 
                           CURLOPT_FOLLOWLOCATION, 
                           $this->followLocation);

        // If a timeout is set and is greater then zero then set it
        if (is_numeric($this->timeout) && $this->timeout > 0) {
            $ret = curl_setopt($this->_ch, CURLOPT_TIMEOUT, $this->timeout);
        }

        if (isset($this->userAgent)) {
            $ret = curl_setopt($this->_ch, CURLOPT_USERAGENT, $this->userAgent);
        }

        // Cookies
        if (is_array($this->cookies) && count($this->cookies)) {
            $cookieData = '';
            foreach ($this->cookies as $name => $value) {
                $cookieData .= $name . '=' . $value . ';';
            }

            $ret = curl_setopt($this->_ch, CURLOPT_COOKIE, $cookieData);
        }

        // Other HTTP headers
        if ($this->httpHeaders !== null) {
            if (is_array($this->httpHeaders)) {
                $ret = curl_setopt($this->_ch, 
                                   CURLOPT_HTTPHEADER, 
                                   $this->httpHeaders);
            } else {
                return PEAR::raiseError('Net_Curl::$httpHeaders must be an array');
            }
        }

        $ret = curl_exec($this->_ch);

        // Close the file before we return anything
        if (is_resource($this->_fp)) {
            fclose($this->_fp);
        }

        if (curl_errno($this->_ch)) {
            return PEAR::raiseError(curl_error($this->_ch), curl_errno($this->_ch));
        }

        // Check to make sure we get a 2XX/3XX code and not a 404 or something.
        $info = $this->getInfo();
        if (!isset($info['http_code'])) {
            return PEAR::raiseError('Unknown or invalid HTTP response');
        } else {
            $type = substr($info['http_code'], 0, 1);
            if ($type != 2 && $type != 3) {
                return PEAR::raiseError('Unexpected HTTP code: ' .
                                        $info['http_code']);
            }
        }

        return $ret;
    }
    // }}}

    // {{{ setOption($option, $value)
    /**
     * Sets an option for your cURL session. Please note that the cURL handler
     * is NOT created before execute(). This is for error checking purposes.
     * You should use setOption() in the following manner:
     *
     * <code>
     * <?php
     *
     * require_once 'Net/Curl.php';
     * $curl = new Net_Curl('http://www.example.com');
     * $check = $curl->create();
     * if (!PEAR::isError($check)) {
     *     $curl->setOption(CURLOPT_FOO, 'bar');
     *     $result = $curl->execute();
     *     if (!PEAR::isError($result)) {
     *         echo $result;
     *     }
     * }
     *
     * ?>
     * </code>
     *
     * @param int   $option cURL constant (ie. CURLOPT_URL)
     * @param mixed $value  The option's value
     * 
     * @author Joe Stump <joe@joestump.net>
     * @access public
     * @return boolean
     */
    function setOption($option, $value)
    {
        if (is_resource($this->_ch)) {
            return curl_setopt($this->_ch, $option, $value);
        }

        return false;
    }
    // }}}

    // {{{ getInfo()
    /**
     * Returns the info from the cURL session. PEAR_Error if you try and run
     * this before you execute the session.
     *
     * @author Joe Stump <joe@joestump.net>
     * @access public
     * @return mixed PEAR_Error if there is no resource, info on success
     */
    function getInfo()
    {
        if (is_resource($this->_ch)) {
            return curl_getinfo($this->_ch);
        }

        return PEAR::isError('cURL handler does not exist!');
    }
    // }}}

    // {{{ create()
    /**
     * Creates a cURL resource. If curl_init() doesn't exist or we could not
     * create a resource it will error out.
     *
     * @author Joe Stump <joe@joestump.net>
     * @return boolean TRUE on success, PEAR_Error on failure
     */
    function create()
    {
        if (!PEAR::loadExtension('curl')) {
            return PEAR::raiseError('CURL extension is not available');
        }
        if (!function_exists('curl_init')) {
            return PEAR::raiseError('Function curl_init() not found');
        }

        $this->_ch = curl_init();
        if (!is_resource($this->_ch)) {
            return PEAR::raiseError('Could not initialize cURL handler');
        }

        return true;
    }
    // }}}

    // {{{ verboseAll()
    /**
     * Sets verbose output
     *
     * Turns on super debugging mode by not suppressing errors, turning on
     * verbose mode, showing headers and displaying progress.
     *
     * @access public
     * @author David Costa <gurugeek@php.net>
     * @return void
     */
    function verboseAll()
    {
        $this->verbose  = true;
        $this->mute     = false;
        $this->header   = true;
        $this->progress = true;
    }
    // }}}

    // {{{ verbose_all()
    /**
     * Sets verbose output
     *
     * @access public
     * @author David Costa <gurugeek@php.net>
     * @return void
     * @deprecated
     */
    function verbose_all()
    {
        $this->verboseAll();
        PEAR::raiseError('Net_Curl::verbose_all() is deprecated! Please use Net_Curl::verboseAll()'." <br />\n", null, PEAR_ERROR_PRINT);
    }
    // }}}

    // {{{ close()
    /**
     * Closes the curl transfer and finishes the object (kinda ;)
     *
     * @access public
     * @author Sterling Hughes <sterling@php.net>
     * @return void
     * @since  PHP 4.0.5
     */
    function close()
    {
        if (is_resource($this->_ch)) {
            curl_close($this->_ch);
        }
    }
    // }}}

    // {{{ _mapDeprecatedVariables()
    /**
     * Maps deprecated variables into the appropriate places. It also throws
     * the necessary notices.
     *
     * @author Joe Stump <joe@joestump.net>
     * @access private
     * @return void
     */
    function _mapDeprecatedVariables() 
    {
        $bad = array();
        if ($this->follow_location !== false) {
            if ($this->follow_location > 0) {
                $this->followLocation = true;
            } else {
                $this->followLocation = false;
            }

            $bad[] = array('follow_location', 'followLocation');
        }

        if ($this->return_transfer !== false) {
            if ($this->return_transfer > 0) {
                $this->returnTransfer = true;
            } else {
                $this->returnTransfer = false;
            }

            $bad[] = array('return_transfer', 'returnTransfer');
        }

        if ($this->file_size !== false) {
            $this->fileSize = $this->file_size;
            $bad[]          = array('file_size', 'fileSize');
        }

        if ($this->http_headers !== false) {
            $this->httpHeaders = $this->http_headers;
            $bad[]             = array('http_headers', 'httpHeaders');
        }

        foreach ($bad as $map) {
            PEAR::raiseError('Net_Curl::$'. $map[0]. ' is deprecated! Please use Net_Curl::$'.$map[1]." instead! <br />\n", null, PEAR_ERROR_PRINT);
        }
    }
    // }}}

    // {{{ __destruct()
    /**
     * PHP 5.x destructor.
     *
     * Runs Net_Curl::close() to make sure we close our cURL connection.
     *
     * @author Joe Stump <joe@joestump.net>
     * @see Net_Curl::close()
     */
    function __destruct()
    {
        $this->close();
    }
    // }}}
}

?>
