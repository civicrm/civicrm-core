<?php
// $Id: Location.php 45502 2013-02-08 13:32:55Z kurund $


/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * File for the CiviCRM APIv2 location functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Location
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: Location.php 45502 2013-02-08 13:32:55Z kurund $
 */

/**
 * Include utility functions
 */
require_once 'api/v2/utils.php';

/**
 * Create an additional location for an existing contact
 *
 * @param array $params  input properties
 *
 * @return array  the created location's params
 *
 * @access public
 */
function civicrm_location_add(&$params) {
  _civicrm_initialize();

  $error = _civicrm_location_check_params($params);

  if (civicrm_error($error)) {
    return $error;
  }

  $locationTypeId = CRM_Utils_Array::value('location_type_id', $params);
  if (!$locationTypeId &&
    '2.0' == CRM_Utils_Array::value('location_format', $params)
  ) {
    require_once 'CRM/Core/DAO/LocationType.php';
    $locationTypeDAO = new CRM_Core_DAO_LocationType();
    $locationTypeDAO->name = $params['location_type'];
    $locationTypeDAO->find(TRUE);
    $locationTypeId = $locationTypeDAO->id;

    CRM_Core_PseudoConstant::flush('locationType');

    if (!isset($locationTypeId)) {
      return civicrm_create_error(ts('$location_type is not valid one'));
    }
  }

  $location = &_civicrm_location_add($params, $locationTypeId);
  return $location;
}
/*
 * Correctly named wrapper for 'add' function
 */
function civicrm_location_create($params) {
  $result = civicrm_location_add($params);
  return $result;
}

/**
 *  Update a specified location with the provided property values.
 *
 *  @param  object  $contact        A valid Contact object (passed by reference).
 *  @param  string  $location_id    Valid (db-level) id for location to be updated.
 *  @param  Array   $params         Associative array of property name/value pairs to be updated
 *
 *  @return Location object with updated property values
 *
 *  @access public
 *
 */
function civicrm_location_update($params) {
  _civicrm_initialize();

  if (!is_array($params)) {
    return civicrm_create_error('Params need to be of type array!');
  }

  if (!isset($params['contact_id'])) {
    return civicrm_create_error(ts('$contact is not valid contact datatype'));
  }

  $unsetVersion     = FALSE;
  $locationTypes    = array();
  $hasLocBlockId    = FALSE;
  $allLocationTypes = CRM_Core_PseudoConstant::locationType(TRUE);
  if ('2.0' == CRM_Utils_Array::value('location_format', $params)) {
    //force to use 3.0 location_format for get location api's.
    $params['location_format'] = '3.0';
    $unsetVersion = TRUE;

    if (!($locationTypeId = CRM_Utils_Array::value('location_type_id', $params)) &&
      !(CRM_Utils_Rule::integer($locationTypeId))
    ) {
      return civicrm_create_error(ts('missing or invalid location_type_id'));
    }
    $locationTypes = CRM_Utils_Array::value('location_type', $params);

    //if location_type array absent and location_type_id pass build array.
    if ((!is_array($locationTypes) || !count($locationTypes)) && $locationTypeId) {
      require_once 'CRM/Core/PseudoConstant.php';
      if ($locName = CRM_Utils_Array::value($locationTypeId, $allLocationTypes)) {
        $locationTypes = array($locName);
      }
    }
  }
  else {
    $locTypeIds = array();
    foreach (array(
      'email', 'phone', 'im', 'address', 'openid') as $name) {
      if (isset($params[$name]) && is_array($params[$name])) {
        foreach ($params[$name] as $count => & $values) {
          $locName = CRM_Utils_Array::value('location_type', $values);
          $LocTypeId = CRM_Utils_Array::value('location_type_id', $values);
          if ($locName && !in_array($locName, $locationTypes)) {
            $locationTypes[] = $locName;
          }
          if ($LocTypeId) {
            $locTypeIds[$LocTypeId] = $LocTypeId;
          }
          elseif (in_array($locName, $allLocationTypes)) {
            $values['location_type_id'] = array_search($locName, $allLocationTypes);
          }
          if (!$hasLocBlockId && CRM_Utils_Array::value('id', $values)) {
            $hasLocBlockId = TRUE;
          }
        }
      }
    }

    //get all location types.
    foreach ($locTypeIds as $locId) {
      $name = CRM_Utils_Array::value($locId, $allLocationTypes);
      if (!$name) {
        return civicrm_create_error(ts('Invalid Location Type Id : %1', array(1 => $locId)));
      }
      if (!in_array($name, $locationTypes)) {
        $locationTypes[] = $name;
      }
    }
  }

  $invalidTypes = array();
  foreach ($locationTypes as $name) {
    if (!in_array($name, $allLocationTypes)) {
      $invalidTypes[$name] = $name;
    }
  }
  if (!empty($invalidTypes)) {
    return civicrm_create_error(ts("Invalid Location Type(s) : %1", array(1 => implode(', ', $invalidTypes))));
  }

  //allow to swap locations.
  if ($hasLocBlockId) {
    $locationTypes = $allLocationTypes;
  }

  if (!empty($locationTypes)) {
    $params['location_type'] = $locationTypes;
  }
  else {
    return civicrm_create_error(ts('missing or invalid location_type_id'));
  }

  //get location filter by loc type.
  $locations = &civicrm_location_get($params);

  if ($unsetVersion) {
    unset($params['location_format']);
  }

  if (CRM_Utils_System::isNull($locations)) {
    return civicrm_create_error(ts("Invalid Location Type(s) : %1",
        array(1 => implode(', ', CRM_Utils_Array::value('location_type', $params)))
      ));
  }

  $location = &_civicrm_location_update($params, $locations);
  return $location;
}

/**
 * Deletes a contact location.
 *
 * @param object $contact        A valid Contact object (passed by reference).
 * @param string $location_id    A valid location ID.
 *
 * @return  null, if successful. CRM error object, if 'contact' or 'location_id' is invalid, permissions are insufficient, etc.
 *
 * @access public
 *
 */
function civicrm_location_delete(&$contact) {
  _civicrm_initialize();

  if (!is_array($contact)) {
    return civicrm_create_error('Params need to be of type array!');
  }

  if (!isset($contact['contact_id'])) {
    return civicrm_create_error(ts('$contact is not valid contact datatype'));
  }

  require_once 'CRM/Utils/Rule.php';
  $locationTypeID = CRM_Utils_Array::value('location_type', $contact);
  if (!$locationTypeID ||
    !CRM_Utils_Rule::integer($locationTypeID)
  ) {
    return civicrm_create_error(ts('missing or invalid location'));
  }

  $result = &_civicrm_location_delete($contact);

  return $result;
}

/**
 * Returns array of location(s) for a contact
 *
 * @param array $contact  a valid array of contact parameters
 *
 * @return array  an array of location parameters arrays
 *
 * @access public
 */
function civicrm_location_get($contact) {
  _civicrm_initialize();

  if (!is_array($contact)) {
    return civicrm_create_error('Params need to be of type array!');
  }

  if (!isset($contact['contact_id'])) {
    return civicrm_create_error('$contact is not valid contact datatype');
  }

  $locationTypes = CRM_Utils_Array::value('location_type', $contact);

  if (is_array($locationTypes) && !count($locationTypes)) {
    return civicrm_create_error('Location type array can not be empty');
  }

  $location = &_civicrm_location_get($contact, $locationTypes);

  return $location;
}

/**
 *
 * @param <type> $params
 * @param <type> $locationTypeId
 *
 * @return <type>
 */
function _civicrm_location_add(&$params, $locationTypeId = NULL) {
  // convert api params to 3.0 format.
  if ('2.0' == CRM_Utils_Array::value('location_format', $params)) {
    _civicrm_format_params_v2_to_v3($params, $locationTypeId);
  }

  // Get all existing location blocks.
  $blockParams = array(
    'contact_id' => $params['contact_id'],
    'entity_id' => $params['contact_id'],
  );

  require_once 'CRM/Core/BAO/Location.php';
  $allBlocks = CRM_Core_BAO_Location::getValues($blockParams);

  // get all blocks in contact array.
  $contact = array_merge(array('contact_id' => $params['contact_id']), $allBlocks);

  // copy params value in contact array.
  $primary = $billing = array();
  foreach (array(
    'email', 'phone', 'im', 'openid') as $name) {
    if (CRM_Utils_Array::value($name, $params)) {
      if (!isset($contact[$name]) ||
        !is_array($contact[$name])
      ) {
        $contact[$name] = array();
      }

      $blockCount = count($contact[$name]);
      if (is_array($params[$name])) {
        foreach ($params[$name] as $val) {
          $contact[$name][++$blockCount] = $val;
          // check for primary and billing.
          if (CRM_Utils_Array::value('is_primary', $val)) {
            $primary[$name][$blockCount] = TRUE;
          }
          if (CRM_Utils_Array::value('is_billing', $val)) {
            $primary[$name][$blockCount] = TRUE;
          }
        }
      }
    }
  }

  // get loc type id from params.
  if (!$locationTypeId) {
    $locationTypeId = CRM_Utils_Array::value('location_type_id', $params['address'][1]);
  }

  // address having 1-1 ( loc type - address ) mapping.
  $addressCount = 1;
  if (array_key_exists('address', $contact) && is_array($contact['address'])) {
    foreach ($contact['address'] as $addCount => $values) {
      if ($locationTypeId == CRM_Utils_Array::value('location_type_id', $values)) {
        $addressCount = $addCount;
        break;
      }
      $addressCount++;
    }
  }

  if (CRM_Utils_Array::value('1', $params['address']) && !empty($params['address'][1])) {
    $contact['address'][$addressCount] = $params['address'][1];

    // check for primary and billing address.
    if (CRM_Utils_Array::value('is_primary', $params['address'][1])) {
      $primary['address'][$addressCount] = TRUE;
    }
    if (CRM_Utils_Array::value('is_billing', $params['address'][1])) {
      $billing['address'][$addressCount] = TRUE;
    }

    // format state and country.
    foreach (array(
      'state_province', 'country') as $field) {
      $fName = ($field == 'state_province') ? 'stateProvinceAbbreviation' : 'countryIsoCode';
      if (CRM_Utils_Array::value($field, $contact['address'][$addressCount]) &&
        is_numeric($contact['address'][$addressCount][$field])
      ) {
        $fValue = &$contact['address'][$addressCount][$field];
        eval('$fValue = CRM_Core_PseudoConstant::' . $fName . '( $fValue );');

        //kill the reference.
        unset($fValue);
      }
    }
  }

  //handle primary and billing reset.
  foreach (array(
    'email', 'phone', 'im', 'address', 'openid') as $name) {
    if (!array_key_exists($name, $contact) || CRM_Utils_System::isNull($contact[$name])) {
      continue;
    }

    $errorMsg = NULL;
    $primaryBlockIndex = $billingBlockIndex = 0;
    if (array_key_exists($name, $primary)) {
      if (count($primary[$name]) > 1) {
        $errorMsg .= ts("Multiple primary %1.", array(1 => $name));
      }
      else {
        $primaryBlockIndex = key($primary[$name]);
      }
    }

    if (array_key_exists($name, $billing)) {
      if (count($billing[$name]) > 1) {
        $errorMsg .= ts("Multiple billing %1.", array(1 => $name));
      }
      else {
        $billingBlockIndex = key($billing[$name]);
      }
    }

    if ($errorMsg) {
      return civicrm_create_error($errorMsg);
    }

    foreach ($contact[$name] as $count => & $values) {
      if ($primaryBlockIndex && ($count != $primaryBlockIndex)) {
        $values['is_primary'] = FALSE;
      }
      if ($billingBlockIndex && ($count != $billingBlockIndex)) {
        $values['is_billing'] = FALSE;
      }

      //kill the reference.
      unset($values);
    }
  }

  // get all ids if not present.
  require_once 'CRM/Contact/BAO/Contact.php';
  CRM_Contact_BAO_Contact::resolveDefaults($contact, TRUE);

  require_once 'CRM/Core/BAO/Location.php';
  $result = CRM_Core_BAO_Location::create($contact);

  if (empty($result)) {
    return civicrm_create_error(ts("Location not created"));
  }

  $blocks = array('address', 'phone', 'email', 'im', 'openid');
  foreach ($blocks as $block) {
    for ($i = 0; $i < count($result[$block]); $i++) {
      $locArray[$block][$i] = $result[$block][$i]->id;
    }
  }

  // CRM-4800
  if (2.0 == CRM_Utils_Array::value('location_format', $params)) {
    $locArray['location_type_id'] = $locationTypeId;
  }

  return civicrm_create_success($locArray);
}

/**
 *
 * @param <type> $params
 * @param <type> $locationArray
 *
 * @return <type>
 */
function _civicrm_location_update($params, $locations) {
  // convert api params to 3.0 format.
  if ('2.0' == CRM_Utils_Array::value('location_format', $params)) {
    _civicrm_format_params_v2_to_v3($params);
  }

  $contact = array('contact_id' => $params['contact_id']);
  $primary = $billing = array();

  // copy params value in contact array.
  foreach (array(
    'email', 'phone', 'im', 'openid') as $name) {
    if (CRM_Utils_Array::value($name, $params) && is_array($params[$name])) {
      $blockCount = 0;
      $contact[$name] = array();
      foreach ($params[$name] as $val) {
        $contact[$name][++$blockCount] = $val;
        // check for primary and billing.
        if (CRM_Utils_Array::value('is_primary', $val)) {
          $primary[$name][$blockCount] = TRUE;
        }
        if (CRM_Utils_Array::value('is_billing', $val)) {
          $primary[$name][$blockCount] = TRUE;
        }
      }
    }
    else {
      // get values from db blocks so we dont lose them.
      if (!CRM_Utils_Array::value($name, $locations) || !is_array($locations[$name])) {
        continue;
      }
      $contact[$name] = $locations[$name];
    }
  }

  $addressCount = 1;
  if (CRM_Utils_Array::value(1, $params['address']) && !empty($params['address'][1])) {
    $contact['address'][$addressCount] = $params['address'][1];

    // check for primary and billing address.
    if (CRM_Utils_Array::value('is_primary', $params['address'][1])) {
      $primary['address'][$addressCount] = TRUE;
    }
    if (CRM_Utils_Array::value('is_billing', $params['address'][1])) {
      $billing['address'][$addressCount] = TRUE;
    }

    // format state and country.
    foreach (array(
      'state_province', 'country') as $field) {
      $fName = ($field == 'state_province') ? 'stateProvinceAbbreviation' : 'countryIsoCode';
      if (CRM_Utils_Array::value($field, $contact['address'][$addressCount]) &&
        is_numeric($contact['address'][$addressCount][$field])
      ) {
        $fValue = &$contact['address'][$addressCount][$field];
        eval('$fValue = CRM_Core_PseudoConstant::' . $fName . '( $fValue );');

        //kill the reference.
        unset($fValue);
      }
    }
  }

  //handle primary and billing reset.
  foreach (array(
    'email', 'phone', 'im', 'address', 'openid') as $name) {
    if (!array_key_exists($name, $contact) || CRM_Utils_System::isNull($contact[$name])) {
      continue;
    }
    $errorMsg = NULL;
    $primaryBlockIndex = $billingBlockIndex = 0;
    if (array_key_exists($name, $primary)) {
      if (count($primary[$name]) > 1) {
        $errorMsg .= ts("<br />Multiple Primary %1.", array(1 => $name));
      }
      else {
        $primaryBlockIndex = key($primary[$name]);
      }
    }

    if (array_key_exists($name, $billing)) {
      if (count($billing[$name]) > 1) {
        $errorMsg .= ts("<br />Multiple Billing %1.", array(1 => $name));
      }
      else {
        $billingBlockIndex = key($billing[$name]);
      }
    }

    if ($errorMsg) {
      return civicrm_create_error($errorMsg);
    }

    foreach ($contact[$name] as $count => & $values) {
      if ($primaryBlockIndex && ($count != $primaryBlockIndex)) {
        $values['is_primary'] = FALSE;
      }
      if ($billingBlockIndex && ($count != $billingBlockIndex)) {
        $values['is_billing'] = FALSE;
      }
      // kill the reference.
      unset($values);
    }
  }

  // get all ids if not present.
  require_once 'CRM/Contact/BAO/Contact.php';
  CRM_Contact_BAO_Contact::resolveDefaults($contact, TRUE);

  $location = CRM_Core_BAO_Location::create($contact);

  if (empty($location)) {
    return civicrm_create_error(ts("Location not created"));
  }

  $locArray = array();

  $blocks = array('address', 'phone', 'email', 'im', 'openid');
  $locationTypeId = NULL;
  foreach ($blocks as $block) {
    for ($i = 0; $i < count($location[$block]); $i++) {
      $locArray[$block][$i] = $location[$block][$i]->id;
      $locationTypeId = $location[$block][$i]->location_type_id;
    }
  }

  // CRM-4800
  if (2.0 == CRM_Utils_Array::value('location_format', $params)) {
    $locArray['location_type_id'] = $locationTypeId;
  }

  return civicrm_create_success($locArray);
}

/**
 *
 * @param <type> $contact
 *
 * @return <type>
 */
function _civicrm_location_delete(&$contact) {
  require_once 'CRM/Core/DAO/LocationType.php';
  $locationTypeDAO = new CRM_Core_DAO_LocationType();
  $locationTypeDAO->id = $contact['location_type'];

  if (!$locationTypeDAO->find()) {
    return civicrm_create_error(ts('invalid location type'));
  }

  require_once 'CRM/Core/BAO/Location.php';
  CRM_Core_BAO_Location::deleteLocationBlocks($contact['contact_id'], $contact['location_type']);

  return NULL;
}

/**
 *
 * @param <type> $contact
 * @param <type> $locationTypes = array(
    'Home', 'Work' ) else empty.
 *
 * @return <type>
 */
function &_civicrm_location_get($contact, $locationTypes = array(
  )) {
  $params = array(
    'contact_id' => $contact['contact_id'],
    'entity_id' => $contact['contact_id'],
  );

  require_once 'CRM/Core/BAO/Location.php';
  $locations = CRM_Core_BAO_Location::getValues($params);

  $locValues = array();

  // filter the blocks return only those from given loc type.
  if (is_array($locationTypes) && !empty($locationTypes)) {
    foreach ($locationTypes as $locName) {
      if (!$locName) {
        continue;
      }
      if ($locTypeId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_LocationType', $locName, 'id', 'name')) {
        foreach (array(
          'email', 'im', 'phone', 'address', 'openid') as $name) {
          if (!array_key_exists($name, $locations) || !is_array($locations[$name])) {
            continue;
          }
          $blkCount = 0;
          if (array_key_exists($name, $locValues)) {
            $blkCount = count($locValues[$name]);
          }
          foreach ($locations[$name] as $count => $values) {
            if ($locTypeId == $values['location_type_id']) {
              $locValues[$name][++$blkCount] = $values;
            }
          }
        }
      }
    }
  }
  else {
    $locValues = $locations;
  }


  // CRM-4800
  if ('2.0' == CRM_Utils_Array::value('location_format', $contact)) {
    _civicrm_location_get_v3_to_v2($locValues);
  }

  return $locValues;
}

/**
 * This function ensures that we have the right input location parameters
 *
 * We also need to make sure we run all the form rules on the params list
 * to ensure that the params are valid
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new location.
 *
 * @return bool|CRM_Utils_Error
 * @access public
 */
function _civicrm_location_check_params(&$params) {
  if (!is_array($params)) {
    return civicrm_create_error('Params need to be of type array!');
  }

  // cannot create a location with empty params
  if (empty($params)) {
    return civicrm_create_error('Input Parameters empty');
  }

  $errorField = NULL;
  if (!CRM_Utils_Array::value('contact_id', $params)) {
    $errorField = 'contact_id';
  }

  //lets have user option to send location type id or location type.
  if (!$errorField &&
    '2.0' == CRM_Utils_Array::value('location_format', $params) &&
    !CRM_Utils_Array::value('location_type_id', $params) &&
    !CRM_Utils_Array::value('location_type', $params)
  ) {
    $errorField = 'location_type';
  }

  if (!$errorField) {
    $blocks = array('address', 'email', 'phone', 'im', 'website');
    $emptyAddressBlock = TRUE;
    foreach ($blocks as $block) {
      if (isset($params[$block]) && !empty($params[$block])) {
        $emptyAddressBlock = FALSE;
        break;
      }
    }

    if ($emptyAddressBlock) {
      return civicrm_create_error('Please set atleast one location block. ( address or email or phone or im or website)');
    }
  }

  if ($errorField) {
    return civicrm_create_error("Required fields not found for location $errorField");
  }

  return array();
}

/**
 * This function provide interface between v3.0 => v2.2 location blocks.
 */
function _civicrm_location_get_v3_to_v2(&$locations) {
  $locValues = $blockCounts = array();
  $primaryLoc = $billingLoc = FALSE;
  foreach ($locations as $blockName => $blockValues) {
    if (!is_array($blockValues) || empty($blockValues)) {
      continue;
    }
    foreach ($blockValues as $count => $values) {
      $locTypeId = $values['location_type_id'];

      if (!array_key_exists($locTypeId, $locValues)) {
        $locValues[$locTypeId] = array('location_type_id' => $locTypeId);
      }
      if (!array_key_exists($blockName, $locValues[$locTypeId])) {
        $locValues[$locTypeId][$blockName] = array();
      }
      if ($blockName == 'address') {
        $locValues[$locTypeId][$blockName] = $values;
      }
      else {
        if (!array_key_exists($blockName, $blockCounts) ||
          !array_key_exists($locTypeId, $blockCounts[$blockName])
        ) {
          $blockCounts[$blockName][$locTypeId] = 1;
        }
        $blkCount = &$blockCounts[$blockName][$locTypeId];
        $locValues[$locTypeId][$blockName][$blkCount++] = $values;
      }

      if (!$primaryLoc && CRM_Utils_Array::value('is_primary', $values)) {
        $primaryLoc = TRUE;
        $locValues[$locTypeId]['is_primary'] = TRUE;
      }
      if (!$billingLoc && CRM_Utils_Array::value('is_billing', $values)) {
        $billingLoc = TRUE;
        $locValues[$locTypeId]['is_billing'] = TRUE;
      }
    }
  }

  foreach (array(
    'email', 'phone', 'im', 'address', 'openid') as $field) {
    if (array_key_exists($field, $locations))unset($locations[$field]);
  }
  $locations = $locValues;

  return $locValues;
}

/**
 * function convert params to v3.0 format before add location.
 */
function _civicrm_format_params_v2_to_v3(&$params, $locationTypeId = NULL) {

  // get the loc type id.
  if (!$locationTypeId) {
    // get location type.
    $locationTypeId = CRM_Utils_Array::value('location_type_id', $params);
    if (!$locationTypeId && array_key_exists('location_type', $params)) {
      require_once 'CRM/Core/PseudoConstant.php';
      $locTypes = CRM_Core_PseudoConstant::locationType();

      $locType = $params['location_type'];
      if (is_array($params['location_type'])) {
        $locType = array_pop($params['location_type']);
      }
      $locationTypeId = CRM_Utils_Array::key($locType, $locTypes);
    }
  }

  // convert params into v3.0 format.
  $primary = $billing = array();
  $blocks = array('Email', 'Phone', 'IM', 'OpenID');

  // format params array.
  $firstBlockCount = NULL;
  foreach ($blocks as $block) {
    require_once (str_replace('_', DIRECTORY_SEPARATOR, "CRM_Core_DAO_" . $block) . ".php");
    eval('$fields =& CRM_Core_DAO_' . $block . '::fields( );');
    $name = strtolower($block);
    $blockCount = 0;
    if (CRM_Utils_Array::value($name, $params)) {
      if (is_array($params[$name])) {
        $values = $params[$name];
        $params[$name] = array();
        foreach ($values as $val) {
          _civicrm_store_values($fields, $val, $params[$name][++$blockCount]);
          // check for primary and billing.
          if (CRM_Utils_Array::value('is_primary', $val)) {
            $primary[$name][$blockCount] = TRUE;
          }
          if (CRM_Utils_Array::value('is_billing', $val)) {
            $primary[$name][$blockCount] = TRUE;
          }
          if (!$firstBlockCount) {
            $firstBlockCount = $blockCount;
          }
        }
      }
      else {
        //need to get ids.
        if (in_array($name, array(
          'im', 'phone'))) {
          require_once 'CRM/Core/PseudoConstant.php';
          if ($name == 'im') {
            CRM_Utils_Array::lookupValue($params,
              'provider',
              CRM_Core_PseudoConstant::IMProvider(), TRUE
            );
          }
          else {
            CRM_Utils_Array::lookupValue($params,
              'phone_type',
              CRM_Core_PseudoConstant::phoneType(), TRUE
            );
          }
        }

        $locValues[$name] = array();
        _civicrm_store_values($fields, $params, $locValues[$name][++$blockCount]);
        $params[$name] = $locValues[$name];
        $firstBlockCount = $blockCount;
        unset($locValues[$name]);
      }

      // make first block as default primary when is_primary
      // is not set in sub array and set in main params array.
      if (!CRM_Utils_Array::value($name, $primary) && CRM_Utils_Array::value('is_primary', $params)) {
        $primary[$name][$firstBlockCount] = TRUE;
        $params[$name][$firstBlockCount]['is_primary'] = TRUE;
      }
      if (!CRM_Utils_Array::value($name, $billing) && CRM_Utils_Array::value('is_billing', $params)) {
        $billing[$name][$firstBlockCount] = TRUE;
        $params[$name][$firstBlockCount]['is_billing'] = TRUE;
      }
    }
  }

  //get the address fields.
  $addressCount = 1;
  $ids = array(
    'county', 'country_id', 'country',
    'state_province_id', 'state_province',
    'supplemental_address_1', 'supplemental_address_2',
    'StateProvince.name', 'city', 'street_address',
  );

  $addressTaken = FALSE;
  foreach ($ids as $id) {
    if (array_key_exists($id, $params)) {
      if (!$addressTaken) {
        require_once 'CRM/Core/DAO/Address.php';
        $fields = CRM_Core_DAO_Address::fields();
        _civicrm_store_values($fields, $params, $params['address'][$addressCount]);
        $addressTaken = TRUE;
      }
      $params['address'][$addressCount][$id] = $params[$id];
      unset($params[$id]);
    }
  }

  // format state and country.
  foreach (array(
    'state_province', 'country') as $field) {
    $fName = ($field == 'state_province') ? 'stateProvinceAbbreviation' : 'countryIsoCode';
    if (CRM_Utils_Array::value('address', $params) &&
      CRM_Utils_Array::value($field, $params['address'][$addressCount]) &&
      is_numeric($params['address'][$addressCount][$field])
    ) {
      $fValue = &$params['address'][$addressCount][$field];
      eval('$fValue = CRM_Core_PseudoConstant::' . $fName . '( $fValue );');

      //kill the reference.
      unset($fValue);
    }
  }

  // check for primary address.
  if (CRM_Utils_Array::value('is_primary', $params)) {
    if ($addressTaken) {
      $primary['address'][$addressCount] = TRUE;
      $params['address'][$addressCount]['is_primary'] = TRUE;
    }
    unset($params['is_primary']);
  }

  if (CRM_Utils_Array::value('is_billing', $params)) {
    if ($addressTaken) {
      $billing['address'][$addressCount] = TRUE;
      $params['address'][$addressCount]['is_billing'] = TRUE;
    }
    unset($params['is_billing']);
  }

  // handle primary and billing reset.
  foreach (array(
    'email', 'phone', 'im', 'address', 'openid') as $name) {
    if (!array_key_exists($name, $params) || CRM_Utils_System::isNull($params[$name])) {
      continue;
    }

    $errorMsg = NULL;
    $primaryBlockIndex = $billingBlockIndex = 0;
    if (array_key_exists($name, $primary)) {
      if (count($primary[$name]) > 1) {
        $errorMsg .= ts("<br />Multiple Primary %1.", array(1 => $block));
      }
      else {
        $primaryBlockIndex = key($primary[$name]);
      }
    }

    if (array_key_exists($name, $billing)) {
      if (count($billing[$name]) > 1) {
        $errorMsg .= ts("<br />Multiple Billing %1.", array(1 => $block));
      }
      else {
        $billingBlockIndex = key($billing[$name]);
      }
    }

    if ($errorMsg) {
      return civicrm_create_error($errorMsg);
    }

    foreach ($params[$name] as $count => & $values) {
      if ($primaryBlockIndex && ($count != $primaryBlockIndex)) {
        $values['is_primary'] = FALSE;
      }
      if ($billingBlockIndex && ($count != $billingBlockIndex)) {
        $values['is_billing'] = FALSE;
      }

      // get location type if not present in sub array.
      if (!CRM_Utils_Array::value('location_type_id', $values)) {
        $values['location_type_id'] = $locationTypeId;
      }

      //kill the reference.
      unset($values);
    }
  }

  // finally unset location_type and location type id.
  foreach (array(
    'location_type', 'location_type_id') as $f) {
    if (isset($params[$f]))unset($params[$f]);
  }

  return $params;
}

