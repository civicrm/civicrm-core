<?php
// {{{ license

// +----------------------------------------------------------------------+
// | PHP version 4.2                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2007 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Dan Allen <dan@mojavelinux.com>                             |
// |          Jason Rust <jrust@php.net>                                  |
// +----------------------------------------------------------------------+

// $Id: Detect.php,v 1.28 2009/06/09 04:07:00 clockwerx Exp $

// }}}
// {{{ constants

define('NET_USERAGENT_DETECT_BROWSER',  'browser');
define('NET_USERAGENT_DETECT_OS',       'os');
define('NET_USERAGENT_DETECT_FEATURES', 'features');
define('NET_USERAGENT_DETECT_QUIRKS',   'quirks');
define('NET_USERAGENT_DETECT_ACCEPT',   'accept');
define('NET_USERAGENT_DETECT_ALL',      'all');

// }}}
// {{{ class Net_UserAgent_Detect

/**
 * The Net_UserAgent_Detect object does a number of tests on an HTTP user
 * agent string.  The results of these tests are available via methods of
 * the object.  Note that all methods in this class can be called
 * statically.  The constructor and singleton methods are only retained
 * for BC.
 *
 * This module is based upon the JavaScript browser detection code
 * available at http://www.mozilla.org/docs/web-developer/sniffer/browser_type.html.
 * This module had many influences from the lib/Browser.php code in
 * version 1.3 of Horde.
 *
 * @author   Jason Rust <jrust@php.net>
 * @author   Dan Allen <dan@mojavelinux.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@horde.org>
 * @package  Net_UserAgent
 */

// }}}
class Net_UserAgent_Detect {
    // {{{ constructor

    function Net_UserAgent_Detect($in_userAgent = null, $in_detect = null)
    {
        $this->detect($in_userAgent, $in_detect);
    }

    // }}}
    // {{{ singleton

    /**
     * To be used in place of the contructor to return only open instance.
     *
     * @access public 
     * @return object Net_UserAgent_Detect instance
     */
    function &singleton($in_userAgent = null, $in_detect = null) 
    {
        static $instance;
       
        if (!isset($instance)) { 
            $instance = new Net_UserAgent_Detect($in_userAgent, $in_detect); 
        }
        
        return $instance; 
    }

    // }}}
    // {{{ detect()

    /**
     * Detect the user agent and prepare flags, features and quirks
     * based on what is found
     *
     * This is the core of the Net_UserAgent_Detect class.  It moves its
     * way through the user agent string setting up the flags based on
     * the vendors and versions of the browsers, determining the OS and
     * setting up the features and quirks owned by each of the relevant
     * clients.  Note that if you are going to be calling methods of
     * this class statically then set all the parameters using th
     * setOption()
     *
     * @param  string $in_userAgent (optional) User agent override.  
     * @param  mixed $in_detect (optional) The level of checking to do. 
     *
     * @access public
     * @return void
     */
    function detect($in_userAgent = null, $in_detect = null)
    {
        static $hasRun;
        $options = Net_UserAgent_Detect::_getStaticProperty('options');
        if (!empty($hasRun) && empty($options['re-evaluate'])) {
            return;
        }

        $hasRun = true;
        // {{{ set up static properties

        $in_userAgent = isset($options['userAgent']) && is_null($in_userAgent) ? $options['userAgent'] : $in_userAgent;
        $in_detect = isset($options['detectOptions']) && is_null($in_detect) ? $options['detectOptions'] : $in_detect;

        // User agent string that is being analyzed
        $userAgent = Net_UserAgent_Detect::_getStaticProperty('userAgent');

        // Array that stores all of the flags for the vendor and version
        // of the different browsers
        $browser = Net_UserAgent_Detect::_getStaticProperty('browser');
        $browser = array_flip(array('ns', 'ns2', 'ns3', 'ns4', 'ns4up', 'nav', 'ns6', 'belowns6', 'ns6up', 'firefox', 'firefox0.x', 'firefox1.x', 'firefox1.5', 'firefox2.x', 'firefox3.x', 'gecko', 'ie', 'ie3', 'ie4', 'ie4up', 'ie5', 'ie5_5', 'ie5up', 'ie6', 'belowie6', 'ie6up', 'ie7', 'ie7up', 'ie8', 'ie8tr', 'ie8up', 'opera', 'opera2', 'opera3', 'opera4', 'opera5', 'opera6', 'opera7', 'opera8', 'opera9', 'opera5up', 'opera6up', 'opera7up', 'belowopera8', 'opera8up', 'opera9up', 'aol', 'aol3', 'aol4', 'aol5', 'aol6', 'aol7', 'aol8', 'webtv', 'aoltv', 'tvnavigator', 'hotjava', 'hotjava3', 'hotjava3up', 'konq', 'safari', 'safari_mobile', 'chrome', 'netgem', 'webdav', 'icab'));
        
        // Array that stores all of the flags for the operating systems,
        // and in some cases the versions of those operating systems (windows)
        $os = Net_UserAgent_Detect::_getStaticProperty('os');
        $os = array_flip(array('win', 'win95', 'win16', 'win31', 'win9x', 'win98', 'wince', 'winme', 'win2k', 'winxp', 'winnt', 'win2003', 'vista', 'win7', 'os2', 'mac', 'mac68k', 'macppc', 'linux', 'unix', 'vms', 'sun', 'sun4', 'sun5', 'suni86', 'irix', 'irix5', 'irix6', 'hpux', 'hpux9', 'hpux10', 'aix', 'aix1', 'aix2', 'aix3', 'aix4', 'sco', 'unixware', 'mpras', 'reliant', 'dec', 'sinix', 'freebsd', 'bsd'));

        // Array which stores known issues with the given client that can
        // be used for on the fly tweaking so that the client may recieve
        // the proper handling of this quirk.
        $quirks = Net_UserAgent_Detect::_getStaticProperty('quirks');
        $quirks = array(
                'must_cache_forms'         => false,
                'popups_disabled'          => false,
                'empty_file_input_value'   => false,
                'cache_ssl_downloads'      => false,
                'scrollbar_in_way'         => false,
                'break_disposition_header' => false,
                'nested_table_render_bug'  => false);

        // Array that stores credentials for each of the browser/os
        // combinations.  These allow quick access to determine if the
        // current client has a feature that is going to be implemented
        // in the script.
        $features = Net_UserAgent_Detect::_getStaticProperty('features');
        $features = array(
                'javascript'   => false,
                'dhtml'        => false,
                'dom'          => false,
                'sidebar'      => false,
                'gecko'        => false,
                'svg'          => false,
                'css2'         => false,
                'ajax'         => false);

        // The leading identifier is the very first term in the user
        // agent string, which is used to identify clients which are not
        // Mosaic-based browsers.
        $leadingIdentifier = Net_UserAgent_Detect::_getStaticProperty('leadingIdentifier');

        // The full version of the client as supplied by the very first
        // numbers in the user agent
        $version = Net_UserAgent_Detect::_getStaticProperty('version');
        $version = 0;

        // The major part of the client version, which is the integer
        // value of the version.
        $majorVersion = Net_UserAgent_Detect::_getStaticProperty('majorVersion');
        $majorVersion = 0;

        // The minor part of the client version, which is the decimal
        // parts of the version
        $subVersion = Net_UserAgent_Detect::_getStaticProperty('subVersion');
        $subVersion = 0;

        // }}}
        // detemine what user agent we are using
        if (is_null($in_userAgent)) {
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $userAgent = $_SERVER['HTTP_USER_AGENT'];
            }
            elseif (isset($GLOBALS['HTTP_SERVER_VARS']['HTTP_USER_AGENT'])) {
                $userAgent = $GLOBALS['HTTP_SERVER_VARS']['HTTP_USER_AGENT'];
            }
            else {
                $userAgent = '';
            }
        }
        else {
            $userAgent = $in_userAgent;
        }

        // get the lowercase version for case-insensitive searching
        $agt = strtolower($userAgent);

        // figure out what we need to look for
        $detectOptions = array(NET_USERAGENT_DETECT_BROWSER,
                NET_USERAGENT_DETECT_OS, NET_USERAGENT_DETECT_FEATURES,
                NET_USERAGENT_DETECT_QUIRKS, NET_USERAGENT_DETECT_ACCEPT, 
                NET_USERAGENT_DETECT_ALL);
        $detect = is_null($in_detect) ? NET_USERAGENT_DETECT_ALL : $in_detect;
        settype($detect, 'array');
        foreach($detectOptions as $option) {
            if (in_array($option, $detect)) {
                $detectFlags[$option] = true; 
            }
            else {
                $detectFlags[$option] = false;
            }
        }

        // initialize the arrays of browsers and operating systems

        // Get the type and version of the client
        if (preg_match(";^([[:alnum:]]+)[ /\(]*[[:alpha:]]*([\d]*)(\.[\d\.]*);", $agt, $matches)) {
            list(, $leadingIdentifier, $majorVersion, $subVersion) = $matches;
        }

        if (empty($leadingIdentifier)) {
            $leadingIdentifier = 'Unknown';
        }

        $version = $majorVersion . $subVersion;
    
        // Browser type
        if ($detectFlags[NET_USERAGENT_DETECT_ALL] || $detectFlags[NET_USERAGENT_DETECT_BROWSER]) {
            $browser['webdav']  = ($agt == 'microsoft data access internet publishing provider dav' || $agt == 'microsoft data access internet publishing provider protocol discovery');
            $browser['konq'] = (strpos($agt, 'konqueror') !== false ||  strpos($agt, 'safari') !== false );
            $browser['safari'] = (strpos($agt, 'safari') !== false);
            $browser['chrome'] = (strpos($agt, 'chrome') !== false);
            $browser['safari_mobile'] = (strpos($agt, 'safari') !== false && strpos($agt, 'mobile') !== false );
            $browser['text']    = strpos($agt, 'links') !== false || strpos($agt, 'lynx') !== false || strpos($agt, 'w3m') !== false;
            $browser['ns']      = strpos($agt, 'mozilla') !== false && !(strpos($agt, 'spoofer') !== false) && !(strpos($agt, 'compatible') !== false) && !(strpos($agt, 'hotjava') !== false) && !(strpos($agt, 'opera') !== false) && !(strpos($agt, 'webtv') !== false) ? 1 : 0;
            $browser['netgem']  = strpos($agt, 'netgem') !== false;
            $browser['icab']    = strpos($agt, 'icab') !== false;
            $browser['ns2']     = $browser['ns'] && $majorVersion == 2;
            $browser['ns3']     = $browser['ns'] && $majorVersion == 3;
            $browser['ns4']     = $browser['ns'] && $majorVersion == 4;
            $browser['ns4up']   = $browser['ns'] && $majorVersion >= 4;
            // determine if this is a Netscape Navigator
            $browser['nav'] = $browser['belowns6'] = $browser['ns'] && $majorVersion < 5;
            $browser['ns6']     = !$browser['konq'] && $browser['ns'] && $majorVersion == 5;
            $browser['ns6up']   = $browser['ns6'] && $majorVersion >= 5;
            $browser['gecko']   = strpos($agt, 'gecko') !== false && !$browser['konq'];
            $browser['firefox'] = $browser['gecko'] && strpos($agt, 'firefox') !== false;
            $browser['firefox0.x'] = $browser['firefox'] && strpos($agt, 'firefox/0.') !== false;
            $browser['firefox1.x'] = $browser['firefox'] && strpos($agt, 'firefox/1.') !== false;
            $browser['firefox1.5'] = $browser['firefox'] && strpos($agt, 'firefox/1.5') !== false;
            $browser['firefox2.x'] = $browser['firefox'] && strpos($agt, 'firefox/2.') !== false;
            $browser['firefox3.x'] = $browser['firefox'] && strpos($agt, 'firefox/3.') !== false;
            $browser['ie']      = strpos($agt, 'msie') !== false && !(strpos($agt, 'opera') !== false);
            $browser['ie3']     = $browser['ie'] && $majorVersion < 4;
            $browser['ie4']     = $browser['ie'] && $majorVersion == 4 && (strpos($agt, 'msie 4') !== false);
            $browser['ie4up']   = $browser['ie'] && !$browser['ie3'];
            $browser['ie5']     = $browser['ie4up'] && (strpos($agt, 'msie 5') !== false);
            $browser['ie5_5']   = $browser['ie4up'] && (strpos($agt, 'msie 5.5') !== false);
            $browser['ie5up']   = $browser['ie4up'] && !$browser['ie3'] && !$browser['ie4'];
            $browser['ie5_5up'] = $browser['ie5up'] && !$browser['ie5'];
            $browser['ie6']     = strpos($agt, 'msie 6') !== false;
            $browser['ie6up']   = $browser['ie5up'] && !$browser['ie5'] && !$browser['ie5_5'];
            $browser['ie7'] = strpos($agt, 'msie 7') && !strpos($agt,'trident/4');
            $browser['ie7up']   = $browser['ie6up'] && !$browser['ie6'];
            $browser['ie8tr'] = strpos($agt, 'msie 7') && strpos($agt,'trident/4') !== false;
            $browser['ie8'] = strpos($agt, 'msie 8') !== false;
            $browser['ie8up'] = $browser['ie7up'] && !$browser['ie7']; 
            $browser['belowie6']= $browser['ie'] && !$browser['ie6up'];
            $browser['opera']   = strpos($agt, 'opera') !== false;
            $browser['opera2']  = strpos($agt, 'opera 2') !== false || strpos($agt, 'opera/2') !== false;
            $browser['opera3']  = strpos($agt, 'opera 3') !== false || strpos($agt, 'opera/3') !== false;
            $browser['opera4']  = strpos($agt, 'opera 4') !== false || strpos($agt, 'opera/4') !== false;
            $browser['opera5']  = strpos($agt, 'opera 5') !== false || strpos($agt, 'opera/5') !== false;
            $browser['opera6']  = strpos($agt, 'opera 6') !== false || strpos($agt, 'opera/6') !== false;
            $browser['opera7']  = strpos($agt, 'opera 7') !== false || strpos($agt, 'opera/7') !== false;
            $browser['opera8']  = strpos($agt, 'opera 8') !== false || strpos($agt, 'opera/8') !== false;
            $browser['opera9']  = strpos($agt, 'opera 9') !== false || strpos($agt, 'opera/9') !== false;
            $browser['opera5up'] = $browser['opera'] && !$browser['opera2'] && !$browser['opera3'] && !$browser['opera4'];
            $browser['opera6up'] = $browser['opera'] && !$browser['opera2'] && !$browser['opera3'] && !$browser['opera4'] && !$browser['opera5'];
            $browser['opera7up'] = $browser['opera'] && !$browser['opera2'] && !$browser['opera3'] && !$browser['opera4'] && !$browser['opera5'] && !$browser['opera6'];
            $browser['opera8up'] = $browser['opera'] && !$browser['opera2'] && !$browser['opera3'] && !$browser['opera4'] && !$browser['opera5'] && !$browser['opera6'] && !$browser['opera7'];
            $browser['opera9up'] = $browser['opera'] && !$browser['opera2'] && !$browser['opera3'] && !$browser['opera4'] && !$browser['opera5'] && !$browser['opera6'] && !$browser['opera7'] && !$browser['opera8'];
            $browser['belowopera8'] = $browser['opera'] && !$browser['opera8up'];
            $browser['aol']   = strpos($agt, 'aol') !== false;
            $browser['aol3']  = $browser['aol'] && $browser['ie3'];
            $browser['aol4']  = $browser['aol'] && $browser['ie4'];
            $browser['aol5']  = strpos($agt, 'aol 5') !== false;
            $browser['aol6']  = strpos($agt, 'aol 6') !== false;
            $browser['aol7']  = strpos($agt, 'aol 7') !== false || strpos($agt, 'aol7') !== false;
            $browser['aol8']  = strpos($agt, 'aol 8') !== false || strpos($agt, 'aol8') !== false;
            $browser['webtv'] = strpos($agt, 'webtv') !== false; 
            $browser['aoltv'] = $browser['tvnavigator'] = strpos($agt, 'navio') !== false || strpos($agt, 'navio_aoltv') !== false; 
            $browser['hotjava'] = strpos($agt, 'hotjava') !== false;
            $browser['hotjava3'] = $browser['hotjava'] && $majorVersion == 3;
            $browser['hotjava3up'] = $browser['hotjava'] && $majorVersion >= 3;
            $browser['iemobile'] = strpos($agt, 'iemobile') !== false || strpos($agt, 'windows ce') !== false && (strpos($agt, 'ppc') !== false || strpos($agt, 'smartphone') !== false);
        }

        if ($detectFlags[NET_USERAGENT_DETECT_ALL] || 
            ($detectFlags[NET_USERAGENT_DETECT_BROWSER] && $detectFlags[NET_USERAGENT_DETECT_FEATURES])) {
            // Javascript Check
            if ($browser['ns2'] || $browser['ie3']) {
                Net_UserAgent_Detect::setFeature('javascript', 1.0);
            }
            elseif ($browser['iemobile']) {
              // no javascript
            }
            elseif ($browser['opera5up']) {
                Net_UserAgent_Detect::setFeature('javascript', 1.3);
            }
            elseif ($browser['opera'] || $browser['ns3']) {
                Net_UserAgent_Detect::setFeature('javascript', 1.1);
            }
            elseif (($browser['ns4'] && ($version <= 4.05)) || $browser['ie4']) {
                Net_UserAgent_Detect::setFeature('javascript', 1.2);
            }
            elseif (($browser['ie5up'] && strpos($agt, 'mac') !== false) || $browser['konq']) {
                Net_UserAgent_Detect::setFeature('javascript', 1.4);
            }
            // I can't believe IE6 still has javascript 1.3, what a shitty browser
            elseif (($browser['ns4'] && ($version > 4.05)) || $browser['ie5up'] || $browser['hotjava3up']) {
                Net_UserAgent_Detect::setFeature('javascript', 1.3);
            }
            elseif ($browser['ns6up'] || $browser['gecko'] || $browser['netgem']) {
                Net_UserAgent_Detect::setFeature('javascript', 1.5);
            }
        }
        
        /** OS Check **/
        if ($detectFlags[NET_USERAGENT_DETECT_ALL] || $detectFlags[NET_USERAGENT_DETECT_OS]) {
            $os['win']   = strpos($agt, 'win') !== false || strpos($agt, '16bit') !== false;
            $os['win95'] = strpos($agt, 'win95') !== false || strpos($agt, 'windows 95') !== false;
            $os['win16'] = strpos($agt, 'win16') !== false || strpos($agt, '16bit') !== false || strpos($agt, 'windows 3.1') !== false || strpos($agt, 'windows 16-bit') !== false;  
            $os['win31'] = strpos($agt, 'windows 3.1') !== false || strpos($agt, 'win16') !== false || strpos($agt, 'windows 16-bit') !== false;
            $os['winme'] = strpos($agt, 'win 9x 4.90') !== false;
            $os['wince'] = strpos($agt, 'windows ce') !== false;
            $os['win2k'] = strpos($agt, 'windows nt 5.0') !== false;
            $os['winxp'] = strpos($agt, 'windows nt 5.1') !== false;
            $os['win2003'] = strpos($agt, 'windows nt 5.2') !== false;
            $os['win98'] = strpos($agt, 'win98') !== false || strpos($agt, 'windows 98') !== false;
            $os['win9x'] = $os['win95'] || $os['win98'];
            $os['winnt'] = (strpos($agt, 'winnt') !== false || strpos($agt, 'windows nt') !== false) && strpos($agt, 'windows nt 5') === false;
            $os['win32'] = $os['win95'] || $os['winnt'] || $os['win98'] || $majorVersion >= 4 && strpos($agt, 'win32') !== false || strpos($agt, '32bit') !== false;
            $os['vista'] = strpos($agt, 'windows nt 6.0') !== false;
            $os['win7'] = strpos($agt, 'windows nt 6.1') !== false; 
            $os['os2']   = strpos($agt, 'os/2') !== false || strpos($agt, 'ibm-webexplorer') !== false;
            $os['mac']   = strpos($agt, 'mac') !== false;
            $os['mac68k']   = $os['mac'] && (strpos($agt, '68k') !== false || strpos($agt, '68000') !== false);
            $os['macppc']   = $os['mac'] && (strpos($agt, 'ppc') !== false || strpos($agt, 'powerpc') !== false);
            $os['sun']      = strpos($agt, 'sunos') !== false;
            $os['sun4']     = strpos($agt, 'sunos 4') !== false;
            $os['sun5']     = strpos($agt, 'sunos 5') !== false;
            $os['suni86']   = $os['sun'] && strpos($agt, 'i86') !== false;
            $os['irix']     = strpos($agt, 'irix') !== false;
            $os['irix5']    = strpos($agt, 'irix 5') !== false;
            $os['irix6']    = strpos($agt, 'irix 6') !== false || strpos($agt, 'irix6') !== false;
            $os['hpux']     = strpos($agt, 'hp-ux') !== false;
            $os['hpux9']    = $os['hpux'] && strpos($agt, '09.') !== false;
            $os['hpux10']   = $os['hpux'] && strpos($agt, '10.') !== false;
            $os['aix']      = strpos($agt, 'aix') !== false;
            $os['aix1']     = strpos($agt, 'aix 1') !== false;
            $os['aix2']     = strpos($agt, 'aix 2') !== false;
            $os['aix3']     = strpos($agt, 'aix 3') !== false;
            $os['aix4']     = strpos($agt, 'aix 4') !== false;
            $os['linux']    = strpos($agt, 'inux') !== false;
            $os['sco']      = strpos($agt, 'sco') !== false || strpos($agt, 'unix_sv') !== false;
            $os['unixware'] = strpos($agt, 'unix_system_v') !== false; 
            $os['mpras']    = strpos($agt, 'ncr') !== false; 
            $os['reliant']  = strpos($agt, 'reliant') !== false;
            $os['dec']      = strpos($agt, 'dec') !== false || strpos($agt, 'osf1') !== false || strpos($agt, 'dec_alpha') !== false || strpos($agt, 'alphaserver') !== false || strpos($agt, 'ultrix') !== false || strpos($agt, 'alphastation') !== false;
            $os['sinix']    = strpos($agt, 'sinix') !== false;
            $os['freebsd']  = strpos($agt, 'freebsd') !== false;
            $os['bsd']      = strpos($agt, 'bsd') !== false;
            $os['unix']     = strpos($agt, 'x11') !== false || strpos($agt, 'unix') !== false || $os['sun'] || $os['irix'] || $os['hpux'] || $os['sco'] || $os['unixware'] || $os['mpras'] || $os['reliant'] || $os['dec'] || $os['sinix'] || $os['aix'] || $os['linux'] || $os['bsd'] || $os['freebsd'];
            $os['vms']      = strpos($agt, 'vax') !== false || strpos($agt, 'openvms') !== false;
        }

        // Setup the quirks
        if ($detectFlags[NET_USERAGENT_DETECT_ALL] || 
            ($detectFlags[NET_USERAGENT_DETECT_BROWSER] && $detectFlags[NET_USERAGENT_DETECT_QUIRKS])) {
            if ($browser['konq']) {
                Net_UserAgent_Detect::setQuirk('empty_file_input_value');
            }

            if ($browser['ie']) {
                Net_UserAgent_Detect::setQuirk('cache_ssl_downloads');
            }

            if ($browser['ie6']) {
                Net_UserAgent_Detect::setQuirk('scrollbar_in_way');
            }

            if ($browser['ie5']) {
                Net_UserAgent_Detect::setQuirk('break_disposition_header');
            }

            if ($browser['ie7']) {
                Net_UserAgent_Detect::setQuirk('popups_disabled');
            }

            if ($browser['ns6']) {
                Net_UserAgent_Detect::setQuirk('popups_disabled');
                Net_UserAgent_Detect::setQuirk('must_cache_forms');
            }
            
            if ($browser['nav'] && $subVersion < .79) {
                Net_UserAgent_Detect::setQuirk('nested_table_render_bug');
            }
        }
            
        // Set features
        if ($detectFlags[NET_USERAGENT_DETECT_ALL] || 
            ($detectFlags[NET_USERAGENT_DETECT_BROWSER] && $detectFlags[NET_USERAGENT_DETECT_FEATURES])) {
            if ($browser['gecko']) {
                preg_match(';gecko/([\d]+)\b;i', $agt, $matches);
                Net_UserAgent_Detect::setFeature('gecko', $matches[1]);
            }

            if ($browser['gecko'] || ($browser['ie5up'] && !$browser['iemobile']) || $browser['konq'] || $browser['opera8up'] && !$os['wince']) {
                Net_UserAgent_Detect::setFeature('ajax');
            }

            if ($browser['ns6up'] || $browser['opera5up'] || $browser['konq'] || $browser['netgem']) {
                Net_UserAgent_Detect::setFeature('dom');
            }

            if ($browser['ie4up'] || $browser['ns4up'] || $browser['opera5up'] || $browser['konq'] || $browser['netgem']) {
                Net_UserAgent_Detect::setFeature('dhtml');
            }

            if ($browser['firefox1.5'] || $browser['firefox2.x'] || $browser['opera9up']) {
                Net_UserAgent_Detect::setFeature('svg');
            }

            if ($browser['gecko'] || $browser['ns6up'] || $browser['ie5up'] || $browser['konq'] || $browser['opera7up']) {
                Net_UserAgent_Detect::setFeature('css2');
            }
        }

        if ($detectFlags[NET_USERAGENT_DETECT_ALL] || $detectFlags[NET_USERAGENT_DETECT_ACCEPT]) {
            $mimetypes = preg_split(';[\s,]+;', substr(getenv('HTTP_ACCEPT'), 0, strpos(getenv('HTTP_ACCEPT') . ';', ';')), -1, PREG_SPLIT_NO_EMPTY);
            Net_UserAgent_Detect::setAcceptType((array) $mimetypes, 'mimetype');

            $languages = preg_split(';[\s,]+;', substr(getenv('HTTP_ACCEPT_LANGUAGE'), 0, strpos(getenv('HTTP_ACCEPT_LANGUAGE') . ';', ';')), -1, PREG_SPLIT_NO_EMPTY);
            if (empty($languages)) {
                $languages = 'en';
            }

            Net_UserAgent_Detect::setAcceptType((array) $languages, 'language');

            $encodings = preg_split(';[\s,]+;', substr(getenv('HTTP_ACCEPT_ENCODING'), 0, strpos(getenv('HTTP_ACCEPT_ENCODING') . ';', ';')), -1, PREG_SPLIT_NO_EMPTY);
            Net_UserAgent_Detect::setAcceptType((array) $encodings, 'encoding');
            
            $charsets = preg_split(';[\s,]+;', substr(getenv('HTTP_ACCEPT_CHARSET'), 0, strpos(getenv('HTTP_ACCEPT_CHARSET') . ';', ';')), -1, PREG_SPLIT_NO_EMPTY);
            Net_UserAgent_Detect::setAcceptType((array) $charsets, 'charset');
        }
    }
    
    // }}}
    // {{{ setOption()

    /**
     * Sets a class option.  The available settings are:
     * o 'userAgent' => The user agent string to detect (useful for
     * checking a string manually).
     * o 'detectOptions' => The level of checking to do.  A single level
     * or an array of options.  Default is NET_USERAGENT_DETECT_ALL.
     *
     * @param string $in_field The option field (userAgent or detectOptions)
     * @param mixed $in_value The value for the field
     */
    function setOption($in_field, $in_value)
    {
        $options = Net_UserAgent_Detect::_getStaticProperty('options');
        $options[$in_field] = $in_value;
    }

    // }}}
    // {{{ isBrowser()

    /**
     * Look up the provide browser flag and return a boolean value
     *
     * Given one of the flags listed in the properties, this function will return
     * the value associated with that flag.
     *
     * @param  string $in_match flag to lookup
     *
     * @access public
     * @return boolean whether or not the browser satisfies this flag
     */
    function isBrowser($in_match)
    {
        Net_UserAgent_Detect::detect();
        $browser = Net_UserAgent_Detect::_getStaticProperty('browser');
        return isset($browser[strtolower($in_match)]) ? $browser[strtolower($in_match)] : false;
    }

    // }}}
    // {{{ getBrowser()

    /**
     * Since simply returning the "browser" is somewhat ambiguous since there
     * are different ways to classify the browser, this function works by taking
     * an expect list and returning the string of the first match, so put the important
     * ones first in the array.
     *
     * @param  array $in_expectList the browser flags to search for
     *
     * @access public
     * @return string first flag that matches
     */
    function getBrowser($in_expectList)
    {
        Net_UserAgent_Detect::detect();
        $browser = Net_UserAgent_Detect::_getStaticProperty('browser');
        foreach((array) $in_expectList as $brwsr) {
            if (!empty($browser[strtolower($brwsr)])) {
                return $brwsr;
            }
        }
    }

    // }}}
    // {{{ getBrowserString()

    /**
     * This function returns the vendor string corresponding to the flag.
     *
     * Either use the default matches or pass in an associative array of
     * flags and corresponding vendor strings.  This function will find
     * the highest version flag and return the vendor string corresponding
     * to the appropriate flag.  Be sure to pass in the flags in ascending order
     * if you want a basic matches first, followed by more detailed matches.
     *
     * @param  array $in_vendorStrings (optional) array of flags matched with vendor strings
     *
     * @access public
     * @return string vendor string matches appropriate flag
     */
    function getBrowserString($in_vendorStrings = null)
    {
        if (is_null($in_vendorStrings)) {
            $in_vendorStrings = array (
                    'ie'       => 'Microsoft Internet Explorer',
                    'ie4up'    => 'Microsoft Internet Explorer 4.x',
                    'ie5up'    => 'Microsoft Internet Explorer 5.x',
                    'ie6up'    => 'Microsoft Internet Explorer 6.x',
                    'ie7up'    => 'Microsoft Internet Explorer 7.x',
                    'ie8up'    => 'Microsoft Internet Explorer 8.x',
                    'ie8tr'    => 'Microsoft Internet Explorer 8.x (Compatibility View)', 
                    'opera4'   => 'Opera 4.x',
                    'opera5up' => 'Opera 5.x',
                    'nav'      => 'Netscape Navigator',
                    'ns4'      => 'Netscape 4.x',
                    'ns6up'    => 'Mozilla/Netscape 6.x',
                    'firefox0.x' => 'Firefox 0.x',
                    'firefox1.x' => 'Firefox 1.x',
                    'firefox1.5' => 'Firefox 1.5',
                    'firefox2.x' => 'Firefox 2.x',
                    'firefox3.x' => 'Firefox 3.x',
                    'konq'     => 'Konqueror',
                    'safari'   => 'Safari',
                    'safari_mobile'     => 'Safari Mobile',
                    'chrome'   => 'Google Chrome',
                    'netgem'   => 'Netgem/iPlayer');
        }

        Net_UserAgent_Detect::detect();
        $browser = Net_UserAgent_Detect::_getStaticProperty('browser');
        foreach((array) $in_vendorStrings as $flag => $string) {
            if (!empty($browser[$flag])) {
                $vendorString = $string;
            }
        }

        // if there are no matches just use the user agent leading idendifier (usually Mozilla)
        if (!isset($vendorString)) {
            $leadingIdentifier = Net_UserAgent_Detect::_getStaticProperty('leadingIdentifier');
            $vendorString = $leadingIdentifier;
        }
        
        return $vendorString;
    }

    // }}}
    // {{{ isIE()

    /**
     * Determine if the browser is an Internet Explorer browser
     *
     * @access public
     * @return bool whether or not this browser is an ie browser
     */
    function isIE()
    {
        Net_UserAgent_Detect::detect();
        $browser = Net_UserAgent_Detect::_getStaticProperty('browser');
        return !empty($browser['ie']);
    }

    // }}}
    // {{{ isNavigator()

    /**
     * Determine if the browser is a Netscape Navigator browser
     *
     * @access public
     * @return bool whether or not this browser is a Netscape Navigator browser
     */
    function isNavigator()
    {
        Net_UserAgent_Detect::detect();
        $browser = Net_UserAgent_Detect::_getStaticProperty('browser');
        return !empty($browser['nav']);
    }

    // }}}
    // {{{ isNetscape()

    /**
     * Determine if the browser is a Netscape or Mozilla browser
     *
     * Note that this function is not the same as isNavigator, since the
     * new Mozilla browsers are still sponsered by Netscape, and hence are
     * Netscape products, but not the original Navigators
     *
     * @access public
     * @return bool whether or not this browser is a Netscape product
     */
    function isNetscape()
    {
        Net_UserAgent_Detect::detect();
        $browser = Net_UserAgent_Detect::_getStaticProperty('browser');
        return !empty($browser['ns4up']);
    }
    
    // }}}
    // {{{ isOS()

    /**
     * Look up the provide OS flag and return a boolean value
     *
     * Given one of the flags listed in the properties, this function will return
     * the value associated with that flag for the operating system.
     *
     * @param  string $in_match flag to lookup
     *
     * @access public
     * @return boolean whether or not the OS satisfies this flag
     */
    function isOS($in_match)
    {
        Net_UserAgent_Detect::detect();
        $os = Net_UserAgent_Detect::_getStaticProperty('os');
        return isset($os[strtolower($in_match)]) ? $os[strtolower($in_match)] : false;
    }

    // }}}
    // {{{ getOS()

    /**
     * Since simply returning the "os" is somewhat ambiguous since there
     * are different ways to classify the browser, this function works by taking
     * an expect list and returning the string of the first match, so put the important
     * ones first in the array.
     *
     * @access public
     * @return string first flag that matches
     */
    function getOS($in_expectList)
    {
        Net_UserAgent_Detect::detect();
        $os = Net_UserAgent_Detect::_getStaticProperty('os');
        foreach((array) $in_expectList as $expectOs) {
            if (!empty($os[strtolower($expectOs)])) {
                return $expectOs;
            }
        }
    }

    // }}}
    // {{{ getOSString()

    /**
     * This function returns the os string corresponding to the flag.
     *
     * Either use the default matches or pass in an associative array of
     * flags and corresponding os strings.  This function will find
     * the highest version flag and return the os string corresponding
     * to the appropriate flag.  Be sure to pass in the flags in ascending order
     * if you want a basic matches first, followed by more detailed matches.
     *
     * @param  array $in_osStrings (optional) array of flags matched with os strings
     *
     * @access public
     * @return string os string matches appropriate flag
     */
    function getOSString($in_osStrings = null)
    {
        if (is_null($in_osStrings)) {
            $in_osStrings = array(
                   'win'   => 'Microsoft Windows',
                   'wince' => 'Microsoft Windows CE',
                   'win9x' => 'Microsoft Windows 9x',
                   'winme' => 'Microsoft Windows Millenium',
                   'win2k' => 'Microsoft Windows 2000',
                   'winnt' => 'Microsoft Windows NT',
                   'winxp' => 'Microsoft Windows XP',
                   'win2003' => 'Microsoft Windows 2003',
                   'vista' => 'Microsoft Windows Vista',
                   'win7' => 'Microsoft Windows 7', 
                   'mac'   => 'Macintosh',
                   'unix'  => 'Linux/Unix');
        }

        Net_UserAgent_Detect::detect();
        $osString = 'Unknown';

        $os = Net_UserAgent_Detect::_getStaticProperty('os');
        foreach((array) $in_osStrings as $flag => $string) {
            if (!empty($os[$flag])) {
                $osString = $string;
            }
        }

        return $osString;
    }

    // }}}
    // {{{ setQuirk()

    /**
     * Set a unique behavior for the current browser.
     *
     * Many client browsers do some really funky things, and this
     * mechanism allows the coder to determine if an excepetion must
     * be made with the current client.
     *
     * @param string $in_quirk The quirk to set
     * @param string $in_hasQuirk (optional) Does the browser have the quirk?
     *
     * @access public
     * @return void
     */
    function setQuirk($in_quirk, $in_hasQuirk = true)
    {
        $quirks = Net_UserAgent_Detect::_getStaticProperty('quirks');
        $hasQuirk = !empty($in_hasQuirk); 
        $quirks[strtolower($in_quirk)] = $hasQuirk;
    }

    // }}}
    // {{{ hasQuirk()

    /**
     * Check a unique behavior for the current browser.
     *
     * Many client browsers do some really funky things, and this
     * mechanism allows the coder to determine if an excepetion must
     * be made with the current client.
     *
     * @param string $in_quirk The quirk to detect
     *
     * @access public
     * @return bool whether or not browser has this quirk
     */
    function hasQuirk($in_quirk)
    {
        return (bool) Net_UserAgent_Detect::getQuirk($in_quirk);
    }
    
    // }}}
    // {{{ getQuirk()

    /**
     * Get the unique behavior for the current browser.
     *
     * Many client browsers do some really funky things, and this
     * mechanism allows the coder to determine if an excepetion must
     * be made with the current client.
     *
     * @param string $in_quirk The quirk to detect
     *
     * @access public
     * @return string value of the quirk, in this case usually a boolean
     */
    function getQuirk($in_quirk)
    {
        Net_UserAgent_Detect::detect();
        $quirks = Net_UserAgent_Detect::_getStaticProperty('quirks');
        return isset($quirks[strtolower($in_quirk)]) ? $quirks[strtolower($in_quirk)] : null; 
    }

    // }}}
    // {{{ setFeature()

    /**
     * Set capabilities for the current browser.
     *
     * Since the capabilities of client browsers vary widly, this interface
     * helps keep track of the core features of a client, such as if the client
     * supports dhtml, dom, javascript, etc.
     *
     * @param string $in_feature The feature to set
     * @param string $in_hasFeature (optional) Does the browser have the feature?
     *
     * @access public
     * @return void
     */
    function setFeature($in_feature, $in_hasFeature = true)
    {
        $features = Net_UserAgent_Detect::_getStaticProperty('features');
        $features[strtolower($in_feature)] = $in_hasFeature;
    }

    // }}}
    // {{{ hasFeature()

    /**
     * Check the capabilities for the current browser.
     *
     * Since the capabilities of client browsers vary widly, this interface
     * helps keep track of the core features of a client, such as if the client
     * supports dhtml, dom, javascript, etc.
     *
     * @param string $in_feature The feature to detect
     *
     * @access public
     * @return bool whether or not the current client has this feature
     */
    function hasFeature($in_feature)
    {
        return (bool) Net_UserAgent_Detect::getFeature($in_feature);
    }
    
    // }}}
    // {{{ getFeature()

    /**
     * Get the capabilities for the current browser.
     *
     * Since the capabilities of client browsers vary widly, this interface
     * helps keep track of the core features of a client, such as if the client
     * supports dhtml, dom, javascript, etc.
     *
     * @param string $in_feature The feature to detect
     *
     * @access public
     * @return string value of the feature requested
     */
    function getFeature($in_feature)
    {
        Net_UserAgent_Detect::detect();
        $features = Net_UserAgent_Detect::_getStaticProperty('features');
        return isset($features[strtolower($in_feature)]) ? $features[strtolower($in_feature)] : null; 
    }

    // }}}
    // {{{ getAcceptType()

    /**
     * Retrive the accept type for the current browser.
     *
     * To keep track of the mime-types, languages, charsets and encodings
     * that each browser accepts we use associative arrays for each type.
     * This function works like getBrowser() as it takes an expect list
     * and returns the first match.  For instance, to find the language
     * you would pass in your allowed languages and see if any of the
     * languages set in the browser match.
     *
     * @param  string $in_expectList values to check
     * @param  string $in_type type of accept
     *
     * @access public
     * @return string the first matched value
     */
    function getAcceptType($in_expectList, $in_type)
    {
        Net_UserAgent_Detect::detect();
        $type = strtolower($in_type);

        if ($type == 'mimetype' || $type == 'language' || $type == 'charset' || $type == 'encoding') {
            $typeArray = Net_UserAgent_Detect::_getStaticProperty($type);
            foreach((array) $in_expectList as $match) {
                if (!empty($typeArray[$match])) {
                    return $match;
                }
            }
        }

        return null;
    }

    // }}}
    // {{{ setAcceptType()

    /**
     * Set the accept types for the current browser.
     *
     * To keep track of the mime-types, languages, charsets and encodings
     * that each browser accepts we use associative arrays for each type.
     * This function takes and array of accepted values for the type and
     * records them for retrieval.
     *
     * @param  array $in_values values of the accept type
     * @param  string $in_type type of accept
     *
     * @access public
     * @return void
     */
    function setAcceptType($in_values, $in_type)
    {
        $type = strtolower($in_type);

        if ($type == 'mimetype' || $type == 'language' || $type == 'charset' || $type == 'encoding') {
            $typeArray = Net_UserAgent_Detect::_getStaticProperty($type);
            foreach((array) $in_values as $value) {
                $typeArray[$value] = true;
            }
        }
    }

    // }}}
    // {{{ hasAcceptType()

    /**
     * Check the accept types for the current browser.
     *
     * To keep track of the mime-types, languages, charsets and encodings
     * that each browser accepts we use associative arrays for each type.
     * This function checks the array for the given type and determines if
     * the browser accepts it.
     *
     * @param  string $in_value values to check
     * @param  string $in_type type of accept
     *
     * @access public
     * @return bool whether or not the value is accept for this type
     */
    function hasAcceptType($in_value, $in_type)
    {
        return (bool) Net_UserAgent_Detect::getAcceptType((array) $in_value, $in_type);
    }

    // }}}
    // {{{ getUserAgent()

    /**
     * Return the user agent string that is being worked on
     *
     * @access public
     * @return string user agent
     */
    function getUserAgent()
    {
        Net_UserAgent_Detect::detect();
        $userAgent = Net_UserAgent_Detect::_getStaticProperty('userAgent');
        return $userAgent;
    }

    // }}}
    // {{{ _getStaticProperty()

    /**
     * Copy of getStaticProperty() from PEAR.php to avoid having to
     * include PEAR.php
     *
     * @access private
     * @param  string $var    The variable to retrieve.
     * @return mixed   A reference to the variable. If not set it will be
     *                 auto initialised to NULL.
     */
    function &_getStaticProperty($var)
    {
        static $properties;
        return $properties[$var];
    }

    // }}}
}
?>
