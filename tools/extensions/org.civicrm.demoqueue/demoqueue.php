<?php

require_once 'demoqueue.civix.php';

/**
 * Implements hook_civicrm_config().
 */
function demoqueue_civicrm_config(&$config) {
  _demoqueue_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 */
function demoqueue_civicrm_xmlMenu(&$files) {
  _demoqueue_civix_civicrm_xmlMenu($files);
}
