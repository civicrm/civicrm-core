<?php

require_once 'afform_admin.civix.php';
use CRM_AfformAdmin_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function afform_admin_civicrm_config(&$config) {
  _afform_admin_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function afform_admin_civicrm_install() {
  _afform_admin_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function afform_admin_civicrm_enable() {
  _afform_admin_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_permission().
 *
 * @link https://docs.civicrm.org/dev/en/latest/security/permissions/
 */
function afform_admin_civicrm_permission(&$permissions) {
  $permissions['edit afform html'] = [
    'label' => E::ts('FormBuilder: edit raw HTML markup'),
    'description' => E::ts('Allows editing the raw HTML markup of a form directly in the FormBuilder'),
    'implied_by' => ['administer CiviCRM'],
  ];
}
