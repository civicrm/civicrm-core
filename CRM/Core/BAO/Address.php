<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Api4\Address;
use Civi\Token\TokenProcessor;

/**
 * This is class to handle address related functions.
 */
class CRM_Core_BAO_Address extends CRM_Core_DAO_Address implements Civi\Core\HookInterface {
  use CRM_Contact_AccessTrait;

  /**
   * @deprecated
   *
   * @param array $params
   * @param bool $fixAddress
   * @return CRM_Core_BAO_Address
   * @throws CRM_Core_Exception
   */
  public static function create(array &$params, $fixAddress = TRUE) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    if ($fixAddress) {
      CRM_Core_BAO_Address::fixAddress($params);
    }
    return self::writeRecord($params);
  }

  /**
   * @deprecated
   *
   * @param array $params
   * @param bool $fixAddress
   * @return CRM_Core_BAO_Address
   * @throws CRM_Core_Exception
   */
  public static function add(&$params, $fixAddress = FALSE) {
    return self::create($params, $fixAddress);
  }

  /**
   * Event fired before modifying an Address.
   * @param \Civi\Core\Event\PreEvent $event
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if (in_array($event->action, ['create', 'edit'])) {
      CRM_Core_BAO_Block::handlePrimary($event->params, __CLASS__);
      CRM_Core_BAO_Block::handleBilling($event->params, __CLASS__);

      // (prevent chaining 1 and 3) CRM-21214
      if (isset($event->params['master_id']) && !CRM_Utils_System::isNull($event->params['master_id'])) {
        self::fixSharedAddress($event->params);
      }
    }
  }

  /**
   * Event fired after modifying an Address.
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    // Copy custom data from master address if not supplied
    if ($event->action === 'create' && !isset($event->params['custom'])) {
      if (isset($event->params['master_id']) && !CRM_Utils_System::isNull($event->params['master_id'])) {
        $event->object->copyCustomFields($event->params['master_id'], $event->id, $event->action);
      }
    }
    if (in_array($event->action, ['create', 'edit'])) {
      // call the function to sync shared address and create relationships
      // if address is already shared, share master_id with all children and update relationships accordingly
      // (prevent chaining 2) CRM-21214
      self::processSharedAddress($event->id, $event->params, $event->action);
    }
  }

  /**
   * Format the address params to have reasonable values.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   */
  public static function fixAddress(&$params) {
    if (!empty($params['billing_street_address'])) {
      //Check address is coming from online contribution / registration page
      //Fixed :CRM-5076
      $billing = [
        'street_address' => 'billing_street_address',
        'city' => 'billing_city',
        'postal_code' => 'billing_postal_code',
        'state_province' => 'billing_state_province',
        'state_province_id' => 'billing_state_province_id',
        'country' => 'billing_country',
        'country_id' => 'billing_country_id',
      ];

      foreach ($billing as $key => $val) {
        $value = $params[$val] ?? NULL;
        if ($value) {
          if (!empty($params[$key])) {
            unset($params[$val]);
          }
          else {
            //add new key and removed old
            $params[$key] = $value;
            unset($params[$val]);
          }
        }
      }
    }

    /* Split the zip and +4, if it's in US format */
    if (!empty($params['postal_code']) &&
      preg_match('/^(\d{4,5})[+-](\d{4})$/',
        $params['postal_code'],
        $match
      )
    ) {
      $params['postal_code'] = $match[1];
      $params['postal_code_suffix'] = $match[2];
    }

    // add country id if not set
    if ((!isset($params['country_id']) || !is_numeric($params['country_id'])) &&
      isset($params['country'])
    ) {
      $country = new CRM_Core_DAO_Country();
      $country->name = $params['country'];
      if (!$country->find(TRUE)) {
        $country->name = NULL;
        $country->iso_code = $params['country'];
        $country->find(TRUE);
      }
      $params['country_id'] = $country->id;
    }

    // add state_id if state is set
    if ((!isset($params['state_province_id']) || !is_numeric($params['state_province_id']))
      && isset($params['state_province'])
    ) {
      if (!empty($params['state_province'])) {
        $state_province = new CRM_Core_DAO_StateProvince();
        $state_province->name = $params['state_province'];

        // add country id if present
        if (!empty($params['country_id'])) {
          $state_province->country_id = $params['country_id'];
        }

        if (!$state_province->find(TRUE)) {
          unset($state_province->name);
          $state_province->abbreviation = $params['state_province'];
          $state_province->find(TRUE);
        }
        $params['state_province_id'] = $state_province->id;
        if (empty($params['country_id'])) {
          // set this here since we have it
          $params['country_id'] = $state_province->country_id;
        }
      }
      else {
        $params['state_province_id'] = 'null';
      }
    }

    // add county id if county is set
    // CRM-7837
    if ((!isset($params['county_id']) || !is_numeric($params['county_id']))
      && isset($params['county']) && !empty($params['county'])
    ) {
      $county = new CRM_Core_DAO_County();
      $county->name = $params['county'];

      if (isset($params['state_province_id'])) {
        $county->state_province_id = $params['state_province_id'];
      }

      if ($county->find(TRUE)) {
        $params['county_id'] = $county->id;
      }
    }

    // currently copy values populates empty fields with the string "null"
    // and hence need to check for the string null
    if (isset($params['state_province_id']) &&
      is_numeric($params['state_province_id']) &&
      (!isset($params['country_id']) || empty($params['country_id']))
    ) {
      // since state id present and country id not present, hence lets populate it
      // jira issue http://issues.civicrm.org/jira/browse/CRM-56
      $params['country_id'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_StateProvince',
        $params['state_province_id'],
        'country_id'
      );
    }

    //special check to ignore non numeric values if they are not
    //detected by formRule(sometimes happens due to internet latency), also allow user to unselect state/country
    if (isset($params['state_province_id'])) {
      if (empty($params['state_province_id'])) {
        $params['state_province_id'] = 'null';
      }
      elseif (!is_numeric($params['state_province_id']) ||
        ((int ) $params['state_province_id'] < 1000)
      ) {
        // CRM-3393 ( the hacky 1000 check)
        $params['state_province_id'] = 'null';
      }
    }

    if (isset($params['country_id'])) {
      if (empty($params['country_id'])) {
        $params['country_id'] = 'null';
      }
      elseif (!is_numeric($params['country_id']) ||
        ((int ) $params['country_id'] < 1000)
      ) {
        // CRM-3393 ( the hacky 1000 check)
        $params['country_id'] = 'null';
      }
    }

    // add state and country names from the ids
    if (isset($params['state_province_id']) && is_numeric($params['state_province_id'])) {
      $params['state_province'] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($params['state_province_id']);
    }

    if (isset($params['country_id']) && is_numeric($params['country_id'])) {
      $params['country'] = CRM_Core_PseudoConstant::country($params['country_id']);
    }

    $asp = Civi::settings()->get('address_standardization_provider');
    // clean up the address via USPS web services if enabled
    if ($asp === 'USPS' &&
      $params['country_id'] == 1228
    ) {
      CRM_Utils_Address_USPS::checkAddress($params);
    }
    // do street parsing again if enabled, since street address might have changed
    $parseStreetAddress = CRM_Utils_Array::value(
      'street_address_parsing',
      CRM_Core_BAO_Setting::valueOptions(
        CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'address_options'
      ),
      FALSE
    );

    if ($parseStreetAddress && !empty($params['street_address'])) {
      foreach (['street_number', 'street_name', 'street_unit', 'street_number_suffix'] as $fld) {
        unset($params[$fld]);
      }
      // main parse string.
      $parseString = $params['street_address'] ?? NULL;
      $parsedFields = CRM_Core_BAO_Address::parseStreetAddress($parseString);

      // merge parse address in to main address block.
      $params = array_merge($params, $parsedFields);
    }

    // skip_geocode is an optional parameter through the api.
    // manual_geo_code is on the contact edit form. They do the same thing....
    if (empty($params['skip_geocode']) && empty($params['manual_geo_code'])) {
      self::addGeocoderData($params);
    }

  }

  /**
   * Check if there is data to create the object.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return bool
   */
  public static function dataExists(&$params) {
    //check if location type is set if not return false
    if (!isset($params['location_type_id'])) {
      return FALSE;
    }

    $config = CRM_Core_Config::singleton();
    foreach ($params as $name => $value) {
      if (is_array($value) && str_starts_with($name, 'custom_')) {
        // This could be a custom field of type file. We want to unset these as they could
        // give false positives.
        unset($value['error'], $value['size']);
      }
      if (in_array($name, [
        'is_primary',
        'location_type_id',
        'id',
        'contact_id',
        'is_billing',
        'display',
      ])) {
        continue;
      }
      elseif (!CRM_Utils_System::isNull($value)) {
        // name could be country or country id
        if (substr($name, 0, 7) == 'country') {
          // make sure its different from the default country
          // iso code
          $defaultCountry = CRM_Core_BAO_Country::defaultContactCountry();
          // full name
          $defaultCountryName = CRM_Core_BAO_Country::defaultContactCountryName();

          if ($defaultCountry) {
            if ($value == $defaultCountry ||
              $value == $defaultCountryName ||
              $value == $config->defaultContactCountry
            ) {
              // do nothing
            }
            else {
              return TRUE;
            }
          }
          else {
            // return if null default
            return TRUE;
          }
        }
        else {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $entityBlock
   *   Associated array of fields.
   * @param bool $microformat
   *   If microformat output is required.
   * @param int|string $fieldName conditional field name
   *
   * @return array
   *   array with address fields
   */
  public static function &getValues($entityBlock, $microformat = FALSE, $fieldName = 'contact_id') {
    if (empty($entityBlock)) {
      return NULL;
    }
    $addresses = [];
    $address = new CRM_Core_BAO_Address();

    if (empty($entityBlock['entity_table'])) {
      $address->$fieldName = $entityBlock[$fieldName] ?? NULL;
    }
    else {
      $addressIds = [];
      $addressIds = self::allEntityAddress($entityBlock);

      if (!empty($addressIds[1])) {
        $address->id = $addressIds[1];
      }
      else {
        return $addresses;
      }
    }
    if (isset($entityBlock['is_billing']) && $entityBlock['is_billing'] == 1) {
      $address->orderBy('is_billing desc, id');
    }
    else {
      //get primary address as a first block.
      $address->orderBy('is_primary desc, id');
    }

    $address->find();

    $locationTypes = CRM_Core_DAO_Address::buildOptions('location_type_id');
    $count = 1;
    while ($address->fetch()) {
      // deprecate reference.
      if ($count > 1) {
        foreach (['state', 'state_name', 'country', 'world_region'] as $fld) {
          if (isset($address->$fld)) {
            unset($address->$fld);
          }
        }
      }

      $values = [];
      CRM_Core_DAO::storeValues($address, $values);

      // add state and country information: CRM-369
      if (!empty($address->location_type_id)) {
        $values['location_type'] = $locationTypes[$address->location_type_id] ?? NULL;
      }
      if (!empty($address->state_province_id)) {
        $address->state = CRM_Core_PseudoConstant::stateProvinceAbbreviation($address->state_province_id, FALSE);
        $address->state_name = CRM_Core_PseudoConstant::stateProvince($address->state_province_id, FALSE);
        $values['state_province_abbreviation'] = $address->state;
        $values['state_province'] = $address->state_name;
      }

      if (!empty($address->country_id)) {
        $address->country = CRM_Core_PseudoConstant::country($address->country_id);
        $values['country'] = $address->country;

        //get world region
        $regionId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Country', $address->country_id, 'region_id');
        $values['world_region'] = CRM_Core_PseudoConstant::worldregion($regionId);
      }

      $address->addDisplay($microformat);

      $values['display'] = $address->display;
      $values['display_text'] = $address->display_text;

      if (isset($address->master_id) && !CRM_Utils_System::isNull($address->master_id)) {
        $values['use_shared_address'] = 1;
      }

      $addresses[$count] = $values;

      //There should never be more than one primary blocks, hence set is_primary = 0 other than first
      // Calling functions expect the key is_primary to be set, so do not unset it here!
      if ($count > 1) {
        $addresses[$count]['is_primary'] = 0;
      }

      $count++;
    }

    return $addresses;
  }

  /**
   * Add the formatted address to $this-> display.
   *
   * @param bool $microformat
   *   Unexplained parameter that I've always wondered about.
   */
  public function addDisplay($microformat = FALSE) {
    $fields = [
      // added this for CRM 1200
      'address_id' => $this->id,
      // CRM-4003
      'address_name' => str_replace('', ' ', ($this->name ?? '')),
      'street_address' => $this->street_address,
      'supplemental_address_1' => $this->supplemental_address_1,
      'supplemental_address_2' => $this->supplemental_address_2,
      'supplemental_address_3' => $this->supplemental_address_3,
      'city' => $this->city,
      'state_province_name' => $this->state_name ?? "",
      'state_province' => $this->state ?? "",
      'postal_code' => $this->postal_code ?? "",
      'postal_code_suffix' => $this->postal_code_suffix ?? "",
      'country' => $this->country ?? "",
      'world_region' => $this->world_region ?? "",
    ];

    if (isset($this->county_id) && $this->county_id) {
      $fields['county'] = CRM_Core_PseudoConstant::county($this->county_id);
    }
    else {
      $fields['county'] = NULL;
    }
    if ($microformat) {
      $this->display = CRM_Utils_Address::formatVCard($fields);
      $this->display_text = CRM_Utils_Address::format($fields);
    }
    else {
      $this->display = $this->display_text = CRM_Utils_Address::format($fields);
    }
  }

  /**
   * Get all the addresses for a specified contact_id, with the primary address being first
   *
   * @param int $id
   *   The contact id.
   *
   * @param bool $updateBlankLocInfo
   *
   * @return array
   *   the array of adrress data
   */
  public static function allAddress($id, $updateBlankLocInfo = FALSE) {
    if (!$id) {
      return NULL;
    }

    $query = "
SELECT civicrm_address.id as address_id, civicrm_address.location_type_id as location_type_id
FROM civicrm_contact, civicrm_address
WHERE civicrm_address.contact_id = civicrm_contact.id AND civicrm_contact.id = %1
ORDER BY civicrm_address.is_primary DESC, address_id ASC";
    $params = [1 => [$id, 'Integer']];

    $addresses = [];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $count = 1;
    while ($dao->fetch()) {
      if ($updateBlankLocInfo) {
        $addresses[$count++] = $dao->address_id;
      }
      else {
        $addresses[$dao->location_type_id] = $dao->address_id;
      }
    }
    return $addresses;
  }

  /**
   * Get all the addresses for a specified location_block id, with the primary address being first
   *
   * @param array $entityElements
   *   The array containing entity_id and.
   *   entity_table name
   *
   * @return array
   *   the array of adrress data
   */
  public static function allEntityAddress(&$entityElements) {
    $addresses = [];
    if (empty($entityElements)) {
      return $addresses;
    }

    $entityId = $entityElements['entity_id'];
    $entityTable = $entityElements['entity_table'];

    $sql = "
SELECT civicrm_address.id as address_id
FROM civicrm_loc_block loc, civicrm_location_type ltype, civicrm_address, {$entityTable} ev
WHERE ev.id = %1
  AND loc.id = ev.loc_block_id
  AND civicrm_address.id IN (loc.address_id, loc.address_2_id)
  AND ltype.id = civicrm_address.location_type_id
ORDER BY civicrm_address.is_primary DESC, civicrm_address.location_type_id DESC, address_id ASC ";

    $params = [1 => [$entityId, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $locationCount = 1;
    while ($dao->fetch()) {
      $addresses[$locationCount] = $dao->address_id;
      $locationCount++;
    }
    return $addresses;
  }

  /**
   * Get address sequence.
   *
   * @return array
   *   Array of address sequence.
   */
  public static function addressSequence() {
    $addressSequence = CRM_Utils_Address::sequence(\Civi::settings()->get('address_format'));

    $countryState = $cityPostal = FALSE;
    foreach ($addressSequence as $key => $field) {
      if (
        in_array($field, ['country', 'state_province']) &&
        !$countryState
      ) {
        $countryState = TRUE;
        $addressSequence[$key] = 'country_state_province';
      }
      elseif (
        in_array($field, ['city', 'postal_code']) &&
        !$cityPostal
      ) {
        $cityPostal = TRUE;
        $addressSequence[$key] = 'city_postal_code';
      }
      elseif (
      in_array($field, ['country', 'state_province', 'city', 'postal_code'])
      ) {
        unset($addressSequence[$key]);
      }
    }

    return $addressSequence;
  }

  /**
   * Parse given street address string in to street_name,
   * street_unit, street_number and street_number_suffix
   * eg "54A Excelsior Ave. Apt 1C", or "917 1/2 Elm Street"
   *
   * NB: civic street formats for en_CA and fr_CA used by default if those locales are active
   *     otherwise en_US format is default action
   *
   * @param string $streetAddress
   *   Street address including number and apt.
   * @param string $locale
   *   Locale used to parse address.
   *
   * @return array
   *   parsed fields values.
   */
  public static function parseStreetAddress($streetAddress, $locale = NULL) {
    // use 'en_US' for address parsing if the requested locale is not supported.
    if (!self::isSupportedParsingLocale($locale)) {
      $locale = 'en_US';
    }

    $emptyParseFields = $parseFields = [
      'street_name' => '',
      'street_unit' => '',
      'street_number' => '',
      'street_number_suffix' => '',
    ];

    if (empty($streetAddress)) {
      return $parseFields;
    }

    $streetAddress = trim($streetAddress);

    $matches = [];
    if (in_array($locale, ['en_CA', 'fr_CA'])
      && preg_match('/^([A-Za-z0-9]+)[ ]*\-[ ]*/', $streetAddress, $matches)
    ) {
      $parseFields['street_unit'] = $matches[1];
      // unset from rest of street address
      $streetAddress = preg_replace('/^([A-Za-z0-9]+)[ ]*\-[ ]*/', '', $streetAddress);
    }

    // get street number and suffix.
    $matches = [];
    //alter street number/suffix handling so that we accept -digit
    if (preg_match('/^[A-Za-z0-9]+([\S]+)/', $streetAddress, $matches)) {
      // check that $matches[0] is numeric, else assume no street number
      if (preg_match('/^(\d+)/', $matches[0])) {
        $streetNumAndSuffix = $matches[0];

        // get street number.
        $matches = [];
        if (preg_match('/^(\d+)/', $streetNumAndSuffix, $matches)) {
          $parseFields['street_number'] = $matches[0];
          $suffix = preg_replace('/^(\d+)/', '', $streetNumAndSuffix);
          $parseFields['street_number_suffix'] = trim($suffix);
        }

        // unset from main street address.
        $streetAddress = preg_replace('/^[A-Za-z0-9]+([\S]+)/', '', $streetAddress);
        $streetAddress = trim($streetAddress);
      }
    }
    elseif (preg_match('/^(\d+)/', $streetAddress, $matches)) {
      $parseFields['street_number'] = $matches[0];
      // unset from main street address.
      $streetAddress = preg_replace('/^(\d+)/', '', $streetAddress);
      $streetAddress = trim($streetAddress);
    }

    // If street number is too large, we cannot store it.
    if ($parseFields['street_number'] > CRM_Utils_Type::INT_MAX) {
      return $emptyParseFields;
    }

    // suffix might be like 1/2
    $matches = [];
    if (preg_match('/^\d\/\d/', $streetAddress, $matches)) {
      $parseFields['street_number_suffix'] .= $matches[0];

      // unset from main street address.
      $streetAddress = preg_replace('/^\d+\/\d+/', '', $streetAddress);
      $streetAddress = trim($streetAddress);
    }

    // now get the street unit.
    // supportable street unit formats.
    $streetUnitFormats = [
      'APT',
      'APARTMENT',
      'BSMT',
      'BASEMENT',
      'BLDG',
      'BUILDING',
      'DEPT',
      'DEPARTMENT',
      'FL',
      'FLOOR',
      'FRNT',
      'FRONT',
      'HNGR',
      'HANGER',
      'LBBY',
      'LOBBY',
      'LOWR',
      'LOWER',
      'OFC',
      'OFFICE',
      'PH',
      'PENTHOUSE',
      'TRLR',
      'TRAILER',
      'UPPR',
      'RM',
      'ROOM',
      'SIDE',
      'SLIP',
      'KEY',
      'LOT',
      'PIER',
      'REAR',
      'SPC',
      'SPACE',
      'STOP',
      'STE',
      'SUITE',
      'UNIT',
      '#',
    ];

    // overwriting $streetUnitFormats for 'en_CA' and 'fr_CA' locale
    if (in_array($locale, ['en_CA', 'fr_CA'])) {
      $streetUnitFormats = ['APT', 'APP', 'SUITE', 'BUREAU', 'UNIT'];
    }
    //@todo per CRM-14459 this regex picks up words with the string in them - e.g APT picks up
    //Captain - presuming fixing regex (& adding test) to ensure a-z does not preced string will fix
    $streetUnitPreg = '/(' . implode('|\s', $streetUnitFormats) . ')(.+)?/i';
    $matches = [];
    if (preg_match($streetUnitPreg, $streetAddress, $matches)) {
      $parseFields['street_unit'] = trim($matches[0]);
      $streetAddress = str_replace($matches[0], '', $streetAddress);
      $streetAddress = trim($streetAddress);
    }

    // consider remaining string as street name.
    $parseFields['street_name'] = $streetAddress;

    //run parsed fields through stripSpaces to clean
    foreach ($parseFields as $parseField => $value) {
      $parseFields[$parseField] = CRM_Utils_String::stripSpaces($value);
    }
    //CRM-14459 if the field is too long we should assume it didn't get it right & skip rather than allow
    // the DB to fatal
    $fields = CRM_Core_BAO_Address::fields();
    foreach ($fields as $fieldname => $field) {
      if (!empty($field['maxlength']) && strlen(($parseFields[$fieldname] ?? '')) > $field['maxlength']) {
        return $emptyParseFields;
      }
    }

    return $parseFields;
  }

  /**
   * Determines if the specified locale is
   * supported by address parsing.
   * If no locale is specified then it
   * will check the default configured locale.
   *
   * locales supported include:
   *  en_US - http://pe.usps.com/cpim/ftp/pubs/pub28/pub28.pdf
   *  en_CA - http://www.canadapost.ca/tools/pg/manual/PGaddress-e.asp
   *  fr_CA - http://www.canadapost.ca/tools/pg/manual/PGaddress-f.asp
   *          NB: common use of comma after street number also supported
   *
   * @param string $locale
   *   The locale to be checked
   *
   * @return bool
   */
  public static function isSupportedParsingLocale($locale = NULL) {
    if (!$locale) {
      $config = CRM_Core_Config::singleton();
      $locale = $config->lcMessages;
    }

    $parsingSupportedLocales = ['en_US', 'en_CA', 'fr_CA'];

    return in_array($locale, $parsingSupportedLocales);
  }

  /**
   * Validate the address fields based on the address options enabled.
   * in the Address Settings
   *
   * @param array $fields
   *   An array of importable/exportable contact fields.
   *
   * @return array
   *   an array of contact fields and only the enabled address options
   */
  public static function validateAddressOptions($fields) {
    static $addressOptions = NULL;
    if (!$addressOptions) {
      $addressOptions = CRM_Core_BAO_Setting::valueOptions(
        CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'address_options'
      );
    }

    if (is_array($fields) && !empty($fields)) {
      foreach ($addressOptions as $key => $value) {
        if (!$value && isset($fields[$key])) {
          unset($fields[$key]);
        }
      }
    }
    return $fields;
  }

  /**
   * Check if current address is used by any other contacts.
   *
   * @param int $addressId
   *   Address id.
   *
   * @return int
   *   count of contacts that use this shared address
   */
  public static function checkContactSharedAddress($addressId) {
    $query = 'SELECT count(id) FROM civicrm_address WHERE master_id = %1';
    return CRM_Core_DAO::singleValueQuery($query, [1 => [$addressId, 'Integer']]);
  }

  /**
   * Check if current address fields are shared with any other address.
   *
   * @param array $fields
   *   Address fields in profile.
   * @param int $contactId
   *   Contact id.
   *
   */
  public static function checkContactSharedAddressFields(&$fields, $contactId) {
    if (!$contactId || !is_array($fields) || empty($fields)) {
      return;
    }

    $sharedLocations = [];

    $query = "
SELECT is_primary,
       location_type_id
  FROM civicrm_address
 WHERE contact_id = %1
   AND master_id IS NOT NULL";

    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$contactId, 'Positive']]);
    while ($dao->fetch()) {
      $sharedLocations[$dao->location_type_id] = $dao->location_type_id;
      if ($dao->is_primary) {
        $sharedLocations['Primary'] = 'Primary';
      }
    }

    //no need to process further.
    if (empty($sharedLocations)) {
      return;
    }

    $addressFields = [
      'city',
      'county',
      'country',
      'geo_code_1',
      'geo_code_2',
      'postal_code',
      'address_name',
      'state_province',
      'street_address',
      'postal_code_suffix',
      'supplemental_address_1',
      'supplemental_address_2',
      'supplemental_address_3',
    ];

    foreach ($fields as $name => & $values) {
      if (!is_array($values) || empty($values)) {
        continue;
      }

      $nameVal = explode('-', $values['name']);
      $fldName = $nameVal[0] ?? NULL;
      $locType = $nameVal[1] ?? NULL;
      if (!empty($values['location_type_id'])) {
        $locType = $values['location_type_id'];
      }

      if (in_array($fldName, $addressFields) &&
        in_array($locType, $sharedLocations)
      ) {
        $values['is_shared'] = TRUE;
      }
    }
  }

  /**
   * Fix the shared address if address is already shared
   * or if address will be shared with itself.
   *
   * Add in the details from the master address.
   *
   * @param array $params
   *   Associated array of address params.
   */
  public static function fixSharedAddress(&$params) {
    // if address master address is shared, use its master (prevent chaining 1) CRM-21214
    $masterMasterId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Address', $params['master_id'], 'master_id');
    if ($masterMasterId > 0) {
      $params['master_id'] = $masterMasterId;
    }

    // prevent an endless chain between two shared addresses (prevent chaining 3) CRM-21214
    if (($params['id'] ?? NULL) == $params['master_id']) {
      $params['master_id'] = NULL;
      CRM_Core_Session::setStatus(ts("You can't connect an address to itself"), '', 'warning');
    }
    if ($params['master_id']) {
      $masterAddressParams = Address::get(FALSE)
        ->addWhere('id', '=', $params['master_id'])->execute()->first();
      unset($masterAddressParams['id'], $masterAddressParams['is_primary'], $masterAddressParams['is_billing'], $masterAddressParams['contact_id']);
      $params += $masterAddressParams;
    }
  }

  /**
   * Update the shared addresses if master address is modified.
   *
   * @param int $addressId
   *   Address id.
   * @param array $params
   *   Associated array of address params.
   * @param string $parentOperation Operation being taken on the parent entity.
   */
  public static function processSharedAddress($addressId, $params, $parentOperation = NULL) {
    // Collect all downstream records of our record so we can update them.
    $query = 'SELECT id, contact_id FROM civicrm_address WHERE master_id = %1';
    $downstreamDao = CRM_Core_DAO::executeQuery($query, [1 => [$addressId, 'Integer']]);

    // Default to TRUE if not set to maintain api backward compatibility.
    $createRelationship = $params['add_relationship'] ?? TRUE;

    // If master_id is set and has a value, we should use it. If it's set and
    // there is no value, we use the empty value. If it is not set, we should
    // look it up in the database (could be an update that is not sending the
    // master id, e.g. a contact added via a profile that is matched with an
    // exiting contact).
    if (!array_key_exists('master_id', $params)) {
      $masterId = \Civi\Api4\Address::get(FALSE)
        ->addWhere('id', '=', $addressId)
        ->addSelect('master_id')
        ->execute()->first()['master_id'] ?? NULL;
    }
    else {
      $masterId = $params['master_id'] ?? NULL;
    }

    if ($masterId) {
      if ($createRelationship) {
        $contactId = $params['contact_id'] ?? NULL;
        if (!$contactId) {
          $contactId = \Civi\Api4\Address::get(FALSE)
            ->addWhere('id', '=', $addressId)
            ->addSelect('contact_id')
            ->execute()->first()['contact_id'];
        }
        // call the function to create a relationship for the new shared address
        self::processSharedAddressRelationship($masterId, $contactId);
      }

      // Collect the upstream record so we can update it.
      $query = 'SELECT id, contact_id FROM civicrm_address WHERE id = %1';
      $upstreamDao = CRM_Core_DAO::executeQuery($query, [1 => [$masterId, 'Integer']]);
    }

    // These fields never get copied to shared addresses, upstream or down.
    $skipFields = [
      'is_primary',
      'location_type_id',
      'is_billing',
      'contact_id',
    ];
    foreach ($skipFields as $value) {
      unset($params[$value]);
    }

    while ($downstreamDao->fetch()) {
      $params['master_id'] = $addressId;
      if ($masterId) {
        // If we have a master_id AND we have downstream addresses, this is
        // untenable. Ensure we overwrite the downstream addresses so they have
        // a direct relationship with our master_id
        $params['master_id'] = $masterId;
      }
      elseif ($createRelationship) {
        // call the function to update the relationship
        self::processSharedAddressRelationship($addressId, $downstreamDao->contact_id);
      }

      $addressDAO = new CRM_Core_DAO_Address();
      $addressDAO->copyValues($params);
      $addressDAO->id = $downstreamDao->id;
      $addressDAO->save();
      $addressDAO->copyCustomFields($addressId, $addressDAO->id, $parentOperation);
    }
    if ($masterId) {
      // Update the upstream address. But don't copy up the master_id.
      unset($params['master_id']);
      $addressDAO = new CRM_Core_DAO_Address();
      $upstreamDao->fetch();
      $addressDAO->copyValues($params);
      $addressDAO->id = $upstreamDao->id;
      $addressDAO->save();
      $addressDAO->copyCustomFields($addressId, $addressDAO->id, $parentOperation);
    }
  }

  /**
   * Merge contacts with the Same address to get one shared label.
   * @param array $rows
   *   Array[contact_id][contactDetails].
   */
  public static function mergeSameAddress(&$rows) {
    $uniqueAddress = [];
    foreach (array_keys($rows) as $rowID) {
      // load complete address as array key
      $address = trim((string) $rows[$rowID]['street_address'])
        . trim((string) $rows[$rowID]['city'])
        . trim((string) $rows[$rowID]['state_province'])
        . trim((string) $rows[$rowID]['postal_code'])
        . trim((string) $rows[$rowID]['country']);
      if (isset($rows[$rowID]['last_name'])) {
        $name = $rows[$rowID]['last_name'];
      }
      else {
        $name = $rows[$rowID]['display_name'];
      }

      // CRM-15120
      $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), ['schema' => ['contactId'], 'smarty' => FALSE]);
      $tokenProcessor->addMessage('name', Civi::settings()->get('display_name_format'), 'text/plain');
      $tokenProcessor->addRow(['contact' => ['id' => $rowID] + $rows[$rowID]]);
      $tokenProcessor->evaluate();
      $firstNameWithPrefix = trim($tokenProcessor->getRow(0)->render('name'));

      // fill uniqueAddress array with last/first name tree
      if (isset($uniqueAddress[$address])) {
        $uniqueAddress[$address]['names'][$name][$firstNameWithPrefix]['first_name'] = $rows[$rowID]['first_name'];
        $uniqueAddress[$address]['names'][$name][$firstNameWithPrefix]['addressee_display'] = $rows[$rowID]['addressee_display'] ?? '';
        // drop unnecessary rows
        unset($rows[$rowID]);
        // this is the first listing at this address
      }
      else {
        $uniqueAddress[$address]['ID'] = $rowID;
        $uniqueAddress[$address]['names'][$name][$firstNameWithPrefix]['first_name'] = $rows[$rowID]['first_name'];
        $uniqueAddress[$address]['names'][$name][$firstNameWithPrefix]['addressee_display'] = $rows[$rowID]['addressee_display'] ?? '';
      }
    }
    foreach ($uniqueAddress as $address => $data) {
      // copy data back to $rows
      $count = 0;
      // one last name list per row
      foreach ($data['names'] as $last_name => $first_names) {
        // too many to list
        if ($count > 2) {
          break;
        }
        if (count($first_names) == 1) {
          $family = $first_names[current(array_keys($first_names))]['addressee_display'];
        }
        else {
          // collapse the tree to summarize
          $family = trim(implode(" & ", array_keys($first_names)) . " " . $last_name);
        }
        if ($count) {
          $processedNames .= "\n" . $family;
        }
        else {
          // build display_name string
          $processedNames = $family;
        }
        $count++;
      }
      $rows[$data['ID']]['addressee'] = $rows[$data['ID']]['addressee_display'] = $rows[$data['ID']]['display_name'] = $processedNames;
    }
  }

  /**
   * Create relationship between contacts who share an address.
   *
   * Note that currently we create relationship between
   * Individual + Household and Individual + Organization
   *
   * @param int $masterAddressId
   *   Master address id.
   * @param int $currentContactId
   *   Current contact id.
   */
  public static function processSharedAddressRelationship($masterAddressId, $currentContactId) {
    // get the contact type of contact being edited / created
    $currentContactType = CRM_Contact_BAO_Contact::getContactType($currentContactId);

    // if current contact is not of type individual return
    if ($currentContactType != 'Individual') {
      return;
    }

    // get the contact id and contact type of shared contact
    // check the contact type of shared contact, return if it is of type Individual
    $query = 'SELECT cc.id, cc.contact_type
                 FROM civicrm_contact cc INNER JOIN civicrm_address ca ON cc.id = ca.contact_id
                 WHERE ca.id = %1';

    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$masterAddressId, 'Integer']]);
    $dao->fetch();

    // master address contact needs to be Household or Organization, otherwise return
    if ($dao->contact_type == 'Individual') {
      return;
    }
    $sharedContactType = $dao->contact_type;
    $sharedContactId = $dao->id;

    // create relationship between ontacts who share an address
    if ($sharedContactType == 'Organization') {
      return CRM_Contact_BAO_Contact_Utils::createCurrentEmployerRelationship($currentContactId, $sharedContactId);
    }

    // get the relationship type id of "Household Member of"
    $relTypeId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', 'Household Member of', 'id', 'name_a_b');

    if (!$relTypeId) {
      throw new CRM_Core_Exception(ts("You seem to have deleted the relationship type 'Household Member of'"));
    }

    $relParam = [
      'is_active' => TRUE,
      'relationship_type_id' => $relTypeId,
      'contact_id_a' => $currentContactId,
      'contact_id_b' => $sharedContactId,
    ];

    // If already there is a relationship record of $relParam criteria, avoid creating relationship again or else
    // it will casue CRM-16588 as the Duplicate Relationship Exception will revert other contact field values on update
    if (CRM_Contact_BAO_Relationship::checkDuplicateRelationship($relParam, (int) $currentContactId, (int) $sharedContactId)) {
      return;
    }

    try {
      // create relationship
      civicrm_api3('relationship', 'create', $relParam);
    }
    catch (CRM_Core_Exception $e) {
      // We catch and ignore here because this has historically been a best-effort relationship create call.
      // presumably it could refuse due to duplication or similar and we would ignore that.
    }
  }

  /**
   * Check and set the status for shared address delete.
   *
   * @param int $addressId
   *   Address id.
   * @param int $contactId
   *   Contact id.
   * @param bool $returnStatus
   *   By default false.
   *
   * @return array|void
   *   If ($returnStatus == true) the returned value is an array containing contactList and count
   */
  public static function setSharedAddressDeleteStatus($addressId = NULL, $contactId = NULL, $returnStatus = FALSE) {
    // check if address that is being deleted has any shared
    if ($addressId) {
      $entityId = $addressId;
      $query = 'SELECT cc.id, cc.display_name
                 FROM civicrm_contact cc INNER JOIN civicrm_address ca ON cc.id = ca.contact_id
                 WHERE ca.master_id = %1';
    }
    else {
      $entityId = $contactId;
      $query = 'SELECT cc.id, cc.display_name
                FROM civicrm_address ca1
                    INNER JOIN civicrm_address ca2 ON ca1.id = ca2.master_id
                    INNER JOIN civicrm_contact cc  ON ca2.contact_id = cc.id
                WHERE ca1.contact_id = %1';
    }

    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$entityId, 'Integer']]);

    $deleteStatus = [];
    $sharedContactList = [];
    $statusMessage = NULL;
    $addressCount = 0;
    while ($dao->fetch()) {
      if (empty($deleteStatus)) {
        $deleteStatus[] = ts('The following contact(s) have address records which were shared with the address you removed from this contact. These address records are no longer shared - but they have not been removed or altered.');
      }

      $contactViewUrl = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$dao->id}");
      $sharedContactList[] = "<a href='{$contactViewUrl}'>{$dao->display_name}</a>";
      $deleteStatus[] = "<a href='{$contactViewUrl}'>{$dao->display_name}</a>";

      $addressCount++;
    }

    if (!empty($deleteStatus)) {
      $statusMessage = implode('<br/>', $deleteStatus) . '<br/>';
    }

    if (!$returnStatus) {
      CRM_Core_Session::setStatus($statusMessage, '', 'info');
    }
    else {
      return [
        'contactList' => $sharedContactList,
        'count' => $addressCount,
      ];
    }
  }

  /**
   * Call common delete function.
   *
   * @see \CRM_Contact_BAO_Contact::on_hook_civicrm_post
   *
   * @param int $id
   * @deprecated
   * @return bool
   */
  public static function del($id) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    return (bool) self::deleteRecord(['id' => $id]);
  }

  /**
   * Legacy option getter
   *
   * @deprecated
   * @inheritDoc
   */
  public static function buildOptions($fieldName, $context = NULL, $props = []) {
    // Convert legacy fieldnames for Api3 and old quickforms
    switch ($fieldName) {
      case 'state_province_name':
      case 'state_province':
        $fieldName = 'state_province_id';
        break;

      case 'country':
        $fieldName = 'country_id';
        break;

      // Not a real field in this entity
      case 'world_region':
      case 'worldregion':
      case 'worldregion_id':
        return CRM_Core_BAO_Country::buildOptions('region_id', $context, $props);
    }
    return parent::buildOptions($fieldName, $context, $props);
  }

  /**
   * Pseudoconstant condition_provider for state_province_id field.
   * @see \Civi\Schema\EntityMetadataBase::getConditionFromProvider
   */
  public static function alterStateProvince(string $fieldName, CRM_Utils_SQL_Select $conditions, $params) {
    // Filter state_province list based on chosen country or site defaults
    if (empty($params['values']['country_id']) && !$params['include_disabled']) {
      $params['values']['country_id'] = Civi::settings()->get('provinceLimit') ?: Civi::settings()->get('defaultContactCountry');
    }
    if (!empty($params['values']['country_id'])) {
      $conditions->where('country_id IN (#countryLimit)', ['countryLimit' => $params['values']['country_id']]);
    }
  }

  /**
   * Pseudoconstant condition_provider for country_id field.
   * @see \Civi\Schema\EntityMetadataBase::getConditionFromProvider
   */
  public static function alterCountry(string $fieldName, CRM_Utils_SQL_Select $conditions, $params) {
    // Filter country list based on site defaults
    if (!$params['include_disabled']) {
      $countryLimit = Civi::settings()->get('countryLimit');
      if ($countryLimit) {
        $conditions->where('id IN (#countryLimit)', ['countryLimit' => $countryLimit]);
      }
    }
  }

  /**
   * Pseudoconstant condition_provider for county_id field.
   * @see \Civi\Schema\EntityMetadataBase::getConditionFromProvider
   */
  public static function alterCounty(string $fieldName, CRM_Utils_SQL_Select $conditions, $params) {
    // Filter county list based on chosen state
    if (!empty($params['values']['state_province_id'])) {
      $conditions->where('state_province_id IN (#stateProvince)', ['stateProvince' => $params['values']['state_province_id']]);
    }
  }

  /**
   * Add data from the configured geocoding provider.
   *
   * Generally this means latitude & longitude data.
   *
   * @param array $params
   * @return bool
   *   TRUE if params could be passed to a provider, else FALSE.
   */
  public static function addGeocoderData(&$params) {
    try {
      $provider = CRM_Utils_GeocodeProvider::getConfiguredProvider();
      $providerExists = TRUE;
    }
    catch (CRM_Core_Exception $e) {
      $providerExists = FALSE;
    }
    try {
      if ($providerExists) {
        $provider::format($params);
      }
    }
    catch (CRM_Core_Exception $e) {
      \Civi::log()->error('Geocoding error:' . $e->getMessage(), ['geocoder' => get_class($provider), 'input' => $params]);
    }
    // dev/core#2379 - Limit geocode length to 14 characters to avoid validation error on save in UI.
    foreach (['geo_code_1', 'geo_code_2'] as $geocode) {
      if ($params[$geocode] ?? FALSE) {
        // ensure that if the geocoding provider (Google, OSM etc) has returned the string 'null' because they can't geocode, ensure that contacts are not placed on null island 0,0
        if ($params[$geocode] !== 'null') {
          $params[$geocode] = (float) substr($params[$geocode], 0, 14);
          //set manual_geo_code to 0
          $params['manual_geo_code'] = FALSE;
        }
      }
    }
    return $providerExists;
  }

  /**
   * Create multiple addresses using legacy methodology.
   *
   * @param array $params
   * @param bool $fixAddress
   *
   * @return array|null
   */
  public static function legacyCreate(array $params, bool $fixAddress) {
    if (!isset($params['address']) || !is_array($params['address'])) {
      return NULL;
    }
    CRM_Core_BAO_Block::sortPrimaryFirst($params['address']);

    $updateBlankLocInfo = $params['updateBlankLocInfo'] ?? FALSE;
    $contactId = $params['contact_id'];
    //get all the addresses for this contact
    $addresses = self::allAddress($contactId);

    $isPrimary = $isBilling = TRUE;
    $blocks = [];
    foreach ($params['address'] as $key => $value) {
      if (!is_array($value)) {
        continue;
      }

      $addressExists = self::dataExists($value);
      if (empty($value['id'])) {
        if (!empty($addresses) && !empty($value['location_type_id']) && array_key_exists($value['location_type_id'], $addresses)) {
          $value['id'] = $addresses[$value['location_type_id']];
        }
      }

      // Note there could be cases when address info already exist ($value[id] is set) for a contact/entity
      // BUT info is not present at this time, and therefore we should be really careful when deleting the block.
      // $updateBlankLocInfo will help take appropriate decision. CRM-5969
      if (isset($value['id']) && !$addressExists && $updateBlankLocInfo) {
        Address::delete(FALSE)->addWhere('id', '=', $value['id'])->execute();
        continue;
      }
      elseif (!$addressExists) {
        continue;
      }

      if ($isPrimary && !empty($value['is_primary'])) {
        $isPrimary = FALSE;
      }
      else {
        $value['is_primary'] = 0;
      }

      if ($isBilling && !empty($value['is_billing'])) {
        $isBilling = FALSE;
      }
      else {
        $value['is_billing'] = 0;
      }

      if (empty($value['manual_geo_code'])) {
        $value['manual_geo_code'] = 0;
      }
      $value['contact_id'] = $contactId;

      if ($fixAddress) {
        self::fixAddress($value);
      }

      // Format custom data
      if (!isset($value['custom'])) {
        $value['custom'] = CRM_Core_BAO_CustomField::postProcess($value, $value['id'] ?? NULL, 'Address');
      }

      $blocks[] = self::writeRecord($value);
    }
    return $blocks;
  }

}
