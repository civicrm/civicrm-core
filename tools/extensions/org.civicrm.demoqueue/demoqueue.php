<?php

require_once 'demoqueue.civix.php';

/**
 * Implementation of hook_civicrm_config
 * @param $config
 */
function demoqueue_civicrm_config(&$config) {
  _demoqueue_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function demoqueue_civicrm_xmlMenu(&$files) {
  _demoqueue_civix_civicrm_xmlMenu($files);
}
