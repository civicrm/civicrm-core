<?php

require_once 'chart_kit.civix.php';
use CRM_ChartKit_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function chart_kit_civicrm_config(&$config): void {
  _chart_kit_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function chart_kit_civicrm_install(): void {
  _chart_kit_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function chart_kit_civicrm_enable(): void {
  _chart_kit_civix_civicrm_enable();
}
