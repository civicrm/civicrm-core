<?php

require_once 'afform_html.civix.php';
use CRM_AfformHtml_ExtensionUtil as E;

if (!defined('AFFORM_HTML_MONACO')) {
  define('AFFORM_HTML_MONACO', 'bower_components/monaco-editor/min/vs');
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function afform_html_civicrm_config(&$config) {
  _afform_html_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function afform_html_civicrm_install() {
  _afform_html_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function afform_html_civicrm_enable() {
  _afform_html_civix_civicrm_enable();
}
