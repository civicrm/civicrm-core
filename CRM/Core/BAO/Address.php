<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * This is class to handle address related functions.
 */
class CRM_Core_BAO_Address extends CRM_Core_DAO_Address {

  /**
   * Takes an associative array and creates a address.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param bool $fixAddress
   *   True if you need to fix (format) address values.
   *                               before inserting in db
   *
   * @param null $entity
   *
   * @return array|NULL
   *   array of created address
   */
  public static function create(&$params, $fixAddress = TRUE, $entity = NULL) {
    if (!isset($params['address']) || !is_array($params['address'])) {
      return NULL;
    }
    CRM_Core_BAO_Block::sortPrimaryFirst($params['address']);
    $addresses = array();
    $contactId = NULL;

    $updateBlankLocInfo = CRM_Utils_Array::value('updateBlankLocInfo', $params, FALSE);
    if (!$entity) {
      $contactId = $params['contact_id'];
      //get all the addresses for this contact
      $addresses = self::allAddress($contactId);
    }
    else {
      // get all address from location block
      $entityElements = array(
        'entity_table' => $params['entity_table'],
        'entity_id' => $params['entity_id'],
      );
      $addresses = self::allEntityAddress($entityElements);
    }

    $isPrimary = $isBilling = TRUE;
    $blocks = array();
    foreach ($params['address'] as $key => $value) {
      if (!is_array($value)) {
        continue;
      }

      $addressExists = self::dataExists($value);
      if (empty($value['id'])) {
        if (!empty($addresses) && array_key_exists(CRM_Utils_Array::value('location_type_id', $value), $addresses)) {
          $value['id'] = $addresses[CRM_Utils_Array::value('location_type_id', $value)];
        }
      }

      // Note there could be cases when address info already exist ($value[id] is set) for a contact/entity
      // BUT info is not present at this time, and therefore we should be really careful when deleting the block.
      // $updateBlankLocInfo will help take appropriate decision. CRM-5969
      if (isset($value['id']) && !$addressExists && $updateBlankLocInfo) {
        //delete the existing record
        CRM_Core_BAO_Block::blockDelete('Address', array('id' => $value['id']));
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
      $blocks[] = self::add($value, $fixAddress);
    }

    return $blocks;
  }

  /**
   * Takes an associative array and adds address.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param bool $fixAddress
   *   True if you need to fix (format) address values.
   *                               before inserting in db
   *
   * @return CRM_Core_BAO_Address|null
   */
  public static function add(&$params, $fixAddress) {

    $address = new CRM_Core_DAO_Address();
    $checkPermissions = isset($params['check_permissions']) ? $params['check_permissions'] : TRUE;

    // fixAddress mode to be done
    if ($fixAddress) {
      CRM_Core_BAO_Address::fixAddress($params);
    }

    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'Address', CRM_Utils_Array::value('id', $params), $params);

    // if id is set & is_primary isn't we can assume no change
    if (is_numeric(CRM_Utils_Array::value('is_primary', $params)) || empty($params['id'])) {
      CRM_Core_BAO_Block::handlePrimary($params, get_class());
    }

    $address->copyValues($params);

    $address->save();

    if ($address->id) {
      $customFields = CRM_Core_BAO_CustomField::getFields('Address', FALSE, TRUE, NULL, NULL, FALSE, FALSE, $checkPermissions);

      if (!empty($customFields)) {
        $addressCustom = CRM_Core_BAO_CustomField::postProcess($params,
          $address->id,
          'Address',
          FALSE,
          $checkPermissions
        );
      }
      if (!empty($addressCustom)) {
        CRM_Core_BAO_CustomValueTable::store($addressCustom, 'civicrm_address', $address->id);
      }

      //call the function to sync shared address
      self::processSharedAddress($address->id, $params);

      // call the function to create shared relationships
      // we only create create relationship if address is shared by Individual
      if (!CRM_Utils_System::isNull($address->master_id)) {
        self::processSharedAddressRelationship($address->master_id, $params);
      }

      // lets call the post hook only after we've done all the follow on processing
      CRM_Utils_Hook::post($hook, 'Address', $address->id, $address);
    }

    return $address;
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
      $billing = array(
        'street_address' => 'billing_street_address',
        'city' => 'billing_city',
        'postal_code' => 'billing_postal_code',
        'state_province' => 'billing_state_province',
        'state_province_id' => 'billing_state_province_id',
        'country' => 'billing_country',
        'country_id' => 'billing_country_id',
      );

      foreach ($billing as $key => $val) {
        if ($value = CRM_Utils_Array::value($val, $params)) {
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

    $config = CRM_Core_Config::singleton();

    $asp = Civi::settings()->get('address_standardization_provider');
    // clean up the address via USPS web services if enabled
    if ($asp === 'USPS' &&
      $params['country_id'] == 1228
    ) {
      CRM_Utils_Address_USPS::checkAddress($params);

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
        foreach (array(
                   'street_number',
                   'street_name',
                   'street_unit',
                   'street_number_suffix',
                 ) as $fld) {
          unset($params[$fld]);
        }
        // main parse string.
        $parseString = CRM_Utils_Array::value('street_address', $params);
        $parsedFields = CRM_Core_BAO_Address::parseStreetAddress($parseString);

        // merge parse address in to main address block.
        $params = array_merge($params, $parsedFields);
      }
    }

    // check if geocode should be skipped (can be forced with an optional parameter through the api)
    $skip_geocode = (isset($params['skip_geocode']) && $params['skip_geocode']) ? TRUE : FALSE;

    // add latitude and longitude and format address if needed
    if (!$skip_geocode && !empty($config->geocodeMethod) && ($config->geocodeMethod != 'CRM_Utils_Geocode_OpenStreetMaps') && empty($params['manual_geo_code'])) {
      $class = $config->geocodeMethod;
      $class::format($params);
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
      if (in_array($name, array(
        'is_primary',
        'location_type_id',
        'id',
        'contact_id',
        'is_billing',
        'display',
        'master_id',
      ))) {
        continue;
      }
      elseif (!CRM_Utils_System::isNull($value)) {
        // name could be country or country id
        if (substr($name, 0, 7) == 'country') {
          // make sure its different from the default country
          // iso code
          $defaultCountry = $config->defaultContactCountry();
          // full name
          $defaultCountryName = $config->defaultContactCountryName();

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
    $addresses = array();
    $address = new CRM_Core_BAO_Address();

    if (empty($entityBlock['entity_table'])) {
      $address->$fieldName = CRM_Utils_Array::value($fieldName, $entityBlock);
    }
    else {
      $addressIds = array();
      $addressIds = self::allEntityAddress($entityBlock);

      if (!empty($addressIds[1])) {
        $address->id = $addressIds[1];
      }
      else {
        return $addresses;
      }
    }
    //get primary address as a first block.
    $address->orderBy('is_primary desc, id');

    $address->find();

    $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $count = 1;
    while ($address->fetch()) {
      // deprecate reference.
      if ($count > 1) {
        foreach (array(
                   'state',
                   'state_name',
                   'country',
                   'world_region',
                 ) as $fld) {
          if (isset($address->$fld)) {
            unset($address->$fld);
          }
        }
      }
      $stree = $address->street_address;
      $values = array();
      CRM_Core_DAO::storeValues($address, $values);

      // add state and country information: CRM-369
      if (!empty($address->location_type_id)) {
        $values['location_type'] = CRM_Utils_Array::value($address->location_type_id, $locationTypes);
      }
      if (!empty($address->state_province_id)) {
        $address->state = CRM_Core_PseudoConstant::stateProvinceAbbreviation($address->state_province_id, FALSE);
        $address->state_name = CRM_Core_PseudoConstant::stateProvince($address->state_province_id, FALSE);
      }

      if (!empty($address->country_id)) {
        $address->country = CRM_Core_PseudoConstant::country($address->country_id);

        //get world region
        $regionId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Country', $address->country_id, 'region_id');

        $address->world_region = CRM_Core_PseudoConstant::worldregion($regionId);
      }

      $address->addDisplay($microformat);

      $values['display'] = $address->display;
      $values['display_text'] = $address->display_text;

      if (is_numeric($address->master_id)) {
        $values['use_shared_address'] = 1;
      }

      $addresses[$count] = $values;

      //unset is_primary after first block. Due to some bug in earlier version
      //there might be more than one primary blocks, hence unset is_primary other than first
      if ($count > 1) {
        unset($addresses[$count]['is_primary']);
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
    $fields = array(
      // added this for CRM 1200
      'address_id' => $this->id,
      // CRM-4003
      'address_name' => str_replace('', ' ', $this->name),
      'street_address' => $this->street_address,
      'supplemental_address_1' => $this->supplemental_address_1,
      'supplemental_address_2' => $this->supplemental_address_2,
      'city' => $this->city,
      'state_province_name' => isset($this->state_name) ? $this->state_name : "",
      'state_province' => isset($this->state) ? $this->state : "",
      'postal_code' => isset($this->postal_code) ? $this->postal_code : "",
      'postal_code_suffix' => isset($this->postal_code_suffix) ? $this->postal_code_suffix : "",
      'country' => isset($this->country) ? $this->country : "",
      'world_region' => isset($this->world_region) ? $this->world_region : "",
    );

    if (isset($this->county_id) && $this->county_id) {
      $fields['county'] = CRM_Core_PseudoConstant::county($this->county_id);
    }
    else {
      $fields['county'] = NULL;
    }

    $this->display = CRM_Utils_Address::format($fields, NULL, $microformat);
    $this->display_text = CRM_Utils_Address::format($fields);
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
    $params = array(1 => array($id, 'Integer'));

    $addresses = array();
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
    $addresses = array();
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

    $params = array(1 => array($entityId, 'Integer'));
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
    $config = CRM_Core_Config::singleton();
    $addressSequence = $config->addressSequence();

    $countryState = $cityPostal = FALSE;
    foreach ($addressSequence as $key => $field) {
      if (
        in_array($field, array('country', 'state_province')) &&
        !$countryState
      ) {
        $countryState = TRUE;
        $addressSequence[$key] = 'country_state_province';
      }
      elseif (
        in_array($field, array('city', 'postal_code')) &&
        !$cityPostal
      ) {
        $cityPostal = TRUE;
        $addressSequence[$key] = 'city_postal_code';
      }
      elseif (
      in_array($field, array('country', 'state_province', 'city', 'postal_code'))
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
    $config = CRM_Core_Config::singleton();

    /* locales supported include:
     *  en_US - http://pe.usps.com/cpim/ftp/pubs/pub28/pub28.pdf
     *  en_CA - http://www.canadapost.ca/tools/pg/manual/PGaddress-e.asp
     *  fr_CA - http://www.canadapost.ca/tools/pg/manual/PGaddress-f.asp
     *          NB: common use of comma after street number also supported
     *  default is en_US
     */

    $supportedLocalesForParsing = array('en_US', 'en_CA', 'fr_CA');
    if (!$locale) {
      $locale = $config->lcMessages;
    }
    // as different locale explicitly requested but is not available, display warning message and set $locale = 'en_US'
    if (!in_array($locale, $supportedLocalesForParsing)) {
      CRM_Core_Session::setStatus(ts('Unsupported locale specified to parseStreetAddress: %1. Proceeding with en_US locale.', array(1 => $locale)), ts('Unsupported Locale'), 'alert');
      $locale = 'en_US';
    }
    $emptyParseFields = $parseFields = array(
      'street_name' => '',
      'street_unit' => '',
      'street_number' => '',
      'street_number_suffix' => '',
    );

    if (empty($streetAddress)) {
      return $parseFields;
    }

    $streetAddress = trim($streetAddress);

    $matches = array();
    if (in_array($locale, array(
        'en_CA',
        'fr_CA',
      )) && preg_match('/^([A-Za-z0-9]+)[ ]*\-[ ]*/', $streetAddress, $matches)
    ) {
      $parseFields['street_unit'] = $matches[1];
      // unset from rest of street address
      $streetAddress = preg_replace('/^([A-Za-z0-9]+)[ ]*\-[ ]*/', '', $streetAddress);
    }

    // get street number and suffix.
    $matches = array();
    //alter street number/suffix handling so that we accept -digit
    if (preg_match('/^[A-Za-z0-9]+([\S]+)/', $streetAddress, $matches)) {
      // check that $matches[0] is numeric, else assume no street number
      if (preg_match('/^(\d+)/', $matches[0])) {
        $streetNumAndSuffix = $matches[0];

        // get street number.
        $matches = array();
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

    // suffix might be like 1/2
    $matches = array();
    if (preg_match('/^\d\/\d/', $streetAddress, $matches)) {
      $parseFields['street_number_suffix'] .= $matches[0];

      // unset from main street address.
      $streetAddress = preg_replace('/^\d+\/\d+/', '', $streetAddress);
      $streetAddress = trim($streetAddress);
    }

    // now get the street unit.
    // supportable street unit formats.
    $streetUnitFormats = array(
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
    );

    // overwriting $streetUnitFormats for 'en_CA' and 'fr_CA' locale
    if (in_array($locale, array(
      'en_CA',
      'fr_CA',
    ))) {
      $streetUnitFormats = array('APT', 'APP', 'SUITE', 'BUREAU', 'UNIT');
    }
    //@todo per CRM-14459 this regex picks up words with the string in them - e.g APT picks up
    //Captain - presuming fixing regex (& adding test) to ensure a-z does not preced string will fix
    $streetUnitPreg = '/(' . implode('|\s', $streetUnitFormats) . ')(.+)?/i';
    $matches = array();
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
      if (!empty($field['maxlength']) && strlen(CRM_Utils_Array::value($fieldname, $parseFields)) > $field['maxlength']) {
        return $emptyParseFields;
      }
    }

    return $parseFields;
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
    return CRM_Core_DAO::singleValueQuery($query, array(1 => array($addressId, 'Integer')));
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

    $sharedLocations = array();

    $query = "
SELECT is_primary,
       location_type_id
  FROM civicrm_address
 WHERE contact_id = %1
   AND master_id IS NOT NULL";

    $dao = CRM_Core_DAO::executeQuery($query, array(1 => array($contactId, 'Positive')));
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

    $addressFields = array(
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
    );

    foreach ($fields as $name => & $values) {
      if (!is_array($values) || empty($values)) {
        continue;
      }

      $nameVal = explode('-', $values['name']);
      $fldName = CRM_Utils_Array::value(0, $nameVal);
      $locType = CRM_Utils_Array::value(1, $nameVal);
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
   * Update the shared addresses if master address is modified.
   *
   * @param int $addressId
   *   Address id.
   * @param array $params
   *   Associated array of address params.
   */
  public static function processSharedAddress($addressId, $params) {
    $query = 'SELECT id FROM civicrm_address WHERE master_id = %1';
    $dao = CRM_Core_DAO::executeQuery($query, array(1 => array($addressId, 'Integer')));

    // unset contact id
    $skipFields = array('is_primary', 'location_type_id', 'is_billing', 'master_id', 'contact_id');
    foreach ($skipFields as $value) {
      unset($params[$value]);
    }

    $addressDAO = new CRM_Core_DAO_Address();
    while ($dao->fetch()) {
      $addressDAO->copyValues($params);
      $addressDAO->id = $dao->id;
      $addressDAO->save();
      $addressDAO->free();
    }
  }

  /**
   * Merge contacts with the Same address to get one shared label.
   * @param array $rows
   *   Array[contact_id][contactDetails].
   */
  public static function mergeSameAddress(&$rows) {
    $uniqueAddress = array();
    foreach (array_keys($rows) as $rowID) {
      // load complete address as array key
      $address = trim($rows[$rowID]['street_address'])
        . trim($rows[$rowID]['city'])
        . trim($rows[$rowID]['state_province'])
        . trim($rows[$rowID]['postal_code'])
        . trim($rows[$rowID]['country']);
      if (isset($rows[$rowID]['last_name'])) {
        $name = $rows[$rowID]['last_name'];
      }
      else {
        $name = $rows[$rowID]['display_name'];
      }

      // CRM-15120
      $formatted = array(
        'first_name' => $rows[$rowID]['first_name'],
        'individual_prefix' => $rows[$rowID]['individual_prefix'],
      );
      $format = Civi::settings()->get('display_name_format');
      $firstNameWithPrefix = CRM_Utils_Address::format($formatted, $format, FALSE, FALSE, TRUE);
      $firstNameWithPrefix = trim($firstNameWithPrefix);

      // fill uniqueAddress array with last/first name tree
      if (isset($uniqueAddress[$address])) {
        $uniqueAddress[$address]['names'][$name][$firstNameWithPrefix]['first_name'] = $rows[$rowID]['first_name'];
        $uniqueAddress[$address]['names'][$name][$firstNameWithPrefix]['addressee_display'] = $rows[$rowID]['addressee_display'];
        // drop unnecessary rows
        unset($rows[$rowID]);
        // this is the first listing at this address
      }
      else {
        $uniqueAddress[$address]['ID'] = $rowID;
        $uniqueAddress[$address]['names'][$name][$firstNameWithPrefix]['first_name'] = $rows[$rowID]['first_name'];
        $uniqueAddress[$address]['names'][$name][$firstNameWithPrefix]['addressee_display'] = $rows[$rowID]['addressee_display'];
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
   * Note that currently we create relationship only for Individual contacts
   * Individual + Household and Individual + Orgnization
   *
   * @param int $masterAddressId
   *   Master address id.
   * @param array $params
   *   Associated array of submitted values.
   */
  public static function processSharedAddressRelationship($masterAddressId, $params) {
    // get the contact type of contact being edited / created
    $currentContactType = CRM_Contact_BAO_Contact::getContactType($params['contact_id']);
    $currentContactId = $params['contact_id'];

    // if current contact is not of type individual return
    if ($currentContactType != 'Individual') {
      return;
    }

    // get the contact id and contact type of shared contact
    // check the contact type of shared contact, return if it is of type Individual
    $query = 'SELECT cc.id, cc.contact_type
                 FROM civicrm_contact cc INNER JOIN civicrm_address ca ON cc.id = ca.contact_id
                 WHERE ca.id = %1';

    $dao = CRM_Core_DAO::executeQuery($query, array(1 => array($masterAddressId, 'Integer')));
    $dao->fetch();

    // if current contact is not of type individual return, since we don't create relationship between
    // 2 individuals
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
      CRM_Core_Error::fatal(ts("You seem to have deleted the relationship type 'Household Member of'"));
    }

    $relParam = array(
      'is_active' => TRUE,
      'relationship_type_id' => $relTypeId,
      'contact_id_a' => $currentContactId,
      'contact_id_b' => $sharedContactId,
    );

    // If already there is a relationship record of $relParam criteria, avoid creating relationship again or else
    // it will casue CRM-16588 as the Duplicate Relationship Exception will revert other contact field values on update
    if (CRM_Contact_BAO_Relationship::checkDuplicateRelationship($relParam, $currentContactId, $sharedContactId)) {
      return;
    }

    try {
      // create relationship
      civicrm_api3('relationship', 'create', $relParam);
    }
    catch (CiviCRM_API3_Exception $e) {
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
   * @return string
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

    $dao = CRM_Core_DAO::executeQuery($query, array(1 => array($entityId, 'Integer')));

    $deleteStatus = array();
    $sharedContactList = array();
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
      return array(
        'contactList' => $sharedContactList,
        'count' => $addressCount,
      );
    }
  }

  /**
   * Call common delete function.
   *
   * @param int $id
   *
   * @return bool
   */
  public static function del($id) {
    return CRM_Contact_BAO_Contact::deleteObjectWithPrimary('Address', $id);
  }

  /**
   * Get options for a given address field.
   * @see CRM_Core_DAO::buildOptions
   *
   * TODO: Should we always assume chainselect? What fn should be responsible for controlling that flow?
   * TODO: In context of chainselect, what to return if e.g. a country has no states?
   *
   * @param string $fieldName
   * @param string $context
   * @see CRM_Core_DAO::buildOptionsContext
   * @param array $props
   *   whatever is known about this dao object.
   *
   * @return array|bool
   */
  public static function buildOptions($fieldName, $context = NULL, $props = array()) {
    $params = array();
    // Special logic for fields whose options depend on context or properties
    switch ($fieldName) {
      // Filter state_province list based on chosen country or site defaults
      case 'state_province_id':
      case 'state_province_name':
      case 'state_province':
        // change $fieldName to DB specific names.
        $fieldName = 'state_province_id';
        if (empty($props['country_id'])) {
          $config = CRM_Core_Config::singleton();
          if (!empty($config->provinceLimit)) {
            $props['country_id'] = $config->provinceLimit;
          }
          else {
            $props['country_id'] = $config->defaultContactCountry;
          }
        }
        if (!empty($props['country_id']) && $context !== 'validate') {
          $params['condition'] = 'country_id IN (' . implode(',', (array) $props['country_id']) . ')';
        }
        break;

      // Filter country list based on site defaults
      case 'country_id':
      case 'country':
        // change $fieldName to DB specific names.
        $fieldName = 'country_id';
        if ($context != 'get' && $context != 'validate') {
          $config = CRM_Core_Config::singleton();
          if (!empty($config->countryLimit) && is_array($config->countryLimit)) {
            $params['condition'] = 'id IN (' . implode(',', $config->countryLimit) . ')';
          }
        }
        break;

      // Filter county list based on chosen state
      case 'county_id':
        if (!empty($props['state_province_id'])) {
          $params['condition'] = 'state_province_id IN (' . implode(',', (array) $props['state_province_id']) . ')';
        }
        break;

      // Not a real field in this entity
      case 'world_region':
      case 'worldregion':
      case 'worldregion_id':
        return CRM_Core_PseudoConstant::worldRegion();
    }
    return CRM_Core_PseudoConstant::get(__CLASS__, $fieldName, $params, $context);
  }

}
