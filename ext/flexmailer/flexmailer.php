<?php

/**
 */
require_once 'flexmailer.civix.php';

use CRM_Flexmailer_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function flexmailer_civicrm_config(&$config) {
  _flexmailer_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function flexmailer_civicrm_install() {
  _flexmailer_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function flexmailer_civicrm_enable() {
  _flexmailer_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_container().
 */
function flexmailer_civicrm_container($container) {
  $container->addResource(new \Symfony\Component\Config\Resource\FileResource(__FILE__));
  \Civi\FlexMailer\Services::registerServices($container);
}

/**
 * @see \CRM_Utils_Hook::scanClasses()
 */
function flexmailer_civicrm_scanClasses(array &$classes): void {
  $prefix = 'Civi\\FlexMailer\\';
  $dir = __DIR__ . '/src';
  $delim = '\\';

  foreach (\CRM_Utils_File::findFiles($dir, '*.php', TRUE) as $relFile) {
    $relFile = str_replace(DIRECTORY_SEPARATOR, '/', $relFile);
    $classes[] = $prefix . str_replace('/', $delim, substr($relFile, 0, -4));
  }
}
