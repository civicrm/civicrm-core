<?php

use Civi\BAO\Import;

require_once 'civiimport.civix.php';
// phpcs:disable
use Civi\Api4\Event\Subscriber\ImportSubscriber;
use CRM_Civiimport_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function civiimport_civicrm_config(&$config) {
  _civiimport_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function civiimport_civicrm_install() {
  _civiimport_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function civiimport_civicrm_postInstall() {
  _civiimport_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function civiimport_civicrm_uninstall() {
  _civiimport_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function civiimport_civicrm_enable() {
  _civiimport_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function civiimport_civicrm_disable() {
  _civiimport_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function civiimport_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _civiimport_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare each import table as an entity type. This function
 * was intended to be in the ImportSubscriber class but kept
 * getting errors when it was there so it's here, at least for now.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function civiimport_civicrm_entityTypes(array &$entityTypes): void {
  $importEntities = Import::getImportTables();

  foreach ($importEntities as $userJobID => $table) {
    $entityTypes['Import_' . $userJobID] = [
      'name' => 'Import_' . $userJobID,
      'class' => Import::class,
      'table' => $table['table_name'],
    ];
  }
}
