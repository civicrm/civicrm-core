<?php

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Placeholder class retained for legacy compatibility.
 */
class CRM_Core_DAO_Address extends CRM_Core_DAO_Base {

  /**
   * Override the list of fields that can be imported, with additional entities
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function import($prefix = FALSE) {
    return CRM_Core_DAO_AllCoreTables::getImports(__CLASS__, 'address', $prefix, [
      'CRM_Core_DAO_County',
      'CRM_Core_DAO_StateProvince',
      'CRM_Core_DAO_Country',
    ]);
  }

  /**
   * Override the list of fields that can be exported, with additional entities
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function export($prefix = FALSE) {
    return CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, 'address', $prefix, [
      'CRM_Core_DAO_County',
      'CRM_Core_DAO_StateProvince',
      'CRM_Core_DAO_Country',
    ]);
  }

}
