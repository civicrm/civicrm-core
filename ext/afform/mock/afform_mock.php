<?php

require_once 'afform_mock.civix.php';
use CRM_AfformMock_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function afform_mock_civicrm_config(&$config) {
  _afform_mock_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function afform_mock_civicrm_install() {
  _afform_mock_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function afform_mock_civicrm_enable() {
  _afform_mock_civix_civicrm_enable();
}
