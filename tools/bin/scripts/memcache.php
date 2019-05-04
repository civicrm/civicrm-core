<?php
require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';
require_once 'CRM/Utils/Cache.php';

define('CIVICRM_USE_MEMCACHE', 1);

$config = CRM_Core_Config::singleton();
$cache = CRM_Utils_Cache::singleton();

$cache->set('CRM_Core_Config' . CRM_Core_Config::domainID(), $config);
CRM_Core_Error::debug('get', $cache->get('CRM_Core_Config' . CRM_Core_Config::domainID()));

