<?php

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Placeholder class retained for legacy compatibility.
 *
 * @property int|string|null $id
 * @property int|string|null $contact_id
 * @property int|string|null $location_type_id
 * @property bool|string $is_primary
 * @property bool|string $is_billing
 * @property string|null $street_address
 * @property int|string|null $street_number
 * @property string|null $street_number_suffix
 * @property string|null $street_number_predirectional
 * @property string|null $street_name
 * @property string|null $street_type
 * @property string|null $street_number_postdirectional
 * @property string|null $street_unit
 * @property string|null $supplemental_address_1
 * @property string|null $supplemental_address_2
 * @property string|null $supplemental_address_3
 * @property string|null $city
 * @property int|string|null $county_id
 * @property int|string|null $state_province_id
 * @property string|null $postal_code_suffix
 * @property string|null $postal_code
 * @property string|null $usps_adc
 * @property int|string|null $country_id
 * @property float|string|null $geo_code_1
 * @property float|string|null $geo_code_2
 * @property bool|string $manual_geo_code
 * @property string|null $timezone
 * @property string|null $name
 * @property int|string|null $master_id
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
