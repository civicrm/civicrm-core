<?php
/**
 * Net_UserAgent_Detect_APC.php 
 *
 * PHP version 4.2
 *
 * Copyright (c) 1997-2003 The PHP Group                                
 *
 * This source file is subject to version 2.0 of the PHP license,       
 * that is bundled with this package in the file LICENSE, and is        
 * available at through the world-wide-web at                           
 * http://www.php.net/license/2_02.txt.                                 
 * If you did not receive a copy of the PHP license and are unable to   
 * obtain it through the world-wide-web, please send a note to          
 * license@php.net so we can mail you a copy immediately.
 *
 * @category Net
 * @package  Net_UserAgent
 * @author   Lucas Nealan <lucas@facebook.com> 
 * @license  http://www.php.net/license/2_02.txt PHP 2.0 Licence
 * @version  CVS: $Id: APC.php,v 1.3 2009/06/08 04:49:23 clockwerx Exp $
 * @link     http://pear.php.net/package/Net_UserAgent_Detect
 */

require_once 'Net/UserAgent/Detect.php';

/**
 * Net_UserAgent_Detect_APC
 *
 * PHP version 4.2
 *
 * Copyright (c) 1997-2003 The PHP Group                                
 *
 * This source file is subject to version 2.0 of the PHP license,       
 * that is bundled with this package in the file LICENSE, and is        
 * available at through the world-wide-web at                           
 * http://www.php.net/license/2_02.txt.                                 
 * If you did not receive a copy of the PHP license and are unable to   
 * obtain it through the world-wide-web, please send a note to          
 * license@php.net so we can mail you a copy immediately.
 *
 * @category Net
 * @package  Net_UserAgent
 * @author   Lucas Nealan <lucas@facebook.com> 
 * @license  http://www.php.net/license/2_02.txt PHP 2.0 Licence
 * @link     http://pear.php.net/package/Net_UserAgent_Detect
 */
class Net_UserAgent_Detect_APC extends Net_UserAgent_Detect
{
    var $key = '';

    /**
     * Class constructor
     *
     * @param string $in_userAgent    (optional) User agent override.  
     * @param mixed  $in_detect       (optional) The level of checking to do. 
     * @param mixed  $ua_cache_window Unknown
     */
    function Net_UserAgent_Detect_APC($in_userAgent = null, $in_detect = null,
                                      $ua_cache_window = 600)
    {
        $data     = '';
        $restored = false;

        // don't cache after time period
        $ua_cache_timeout = apc_fetch('useragent:cache_timeout');               

        if ($ua_cache_window > 0) {
            if (!$ua_cache_timeout) {
                // check apc uptime and disable after x mins
                $apc_data = apc_cache_info('file', true);

                if (isset($apc_data['start_time'])) {
                    $uptime = $apc_data['start_time'];

                    // timeout and disable after 10 minutes of uptime
                    if (time() - $uptime > $ua_cache_window) {
                        apc_store('useragent:cache_timeout', true);
                        $ua_cache_timeout = true; // don't cache this one either
                    }
                }
            }

            if (!$this->key) {
                $key_flags = '';
                if ($in_detect !== null) {
                    $key_flags = implode('-', $in_detect);
                }
                $this->key = 'useragent:'.md5($in_userAgent.$key_flags);
            }

            if ($data = apc_fetch($this->key)) {
                $success = null;
                $data    = unserialize($data);

                if ($data) {
                    $restored = $this->cacheRestore($data);
                }
            }
        }

        if (!$data) {
            $this->detect($in_userAgent, $in_detect);

            if ($ua_cache_window > 0 && !$ua_cache_timeout) {
                $this->cacheSave();
            }
        }
    }

    /**
     * To be used in place of the contructor to return only open instance.
     *
     * @param string $in_userAgent (optional) User agent override.  
     * @param mixed  $in_detect    (optional) The level of checking to do. 
     *
     * @access public 
     * @return object Net_UserAgent_Detect instance
     */
    function &singleton($in_userAgent = null, $in_detect = null) 
    {
        static $instance;

        if (!isset($instance)) {
            $instance = new Net_UserAgent_Detect_APC($in_userAgent, $in_detect);
        }

        return $instance;
    }

    /**
     * Restore cached items
     *
     * @param mixed[] $cache An array of items to restore
     *
     * @return bool
     */
    function cacheRestore($cache) 
    {
        if (is_array($cache)) {
            foreach ($cache as $prop => $value) {
                $ptr = Net_UserAgent_Detect::_getStaticProperty($prop);
                $ptr = $value;
            }

            return true;
        }

        return false;
    }

    /**
     * Store items in APC
     *
     * @return void
     */
    function cacheSave() 
    {
        if ($this->key) {
            $items = array('browser',
                          'features',
                          'leadingIdentifier',
                          'majorVersion',
                          'options',
                          'os',
                          'quirks',
                          'subVersion',
                          'userAgent',
                          'version');

            $data = array();
            foreach ($items as $item) {
                $data[$item] = Net_UserAgent_Detect::_getStaticProperty($item);
            }

            apc_store($this->key, serialize($data));
        }
    }
}
?>
