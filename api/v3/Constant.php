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
 * CiviCRM APIv3 pseudoconstants
 *
 * @deprecated
 *   The Constant api is deprecated as of CiviCRM 4.4. Please use the getoptions api action instead.
 * @package CiviCRM_APIv3
 */

/**
 * Declare deprecated api entity.
 *
 * @deprecated api notice
 * @return string
 *   to indicate this entire api entity is deprecated
 */
function _civicrm_api3_constant_deprecation() {
  return 'The Constant api is deprecated as of CiviCRM 4.4. Please use the getoptions api action instead.';
}

/**
 * Get constant values (deprecated).
 *
 * @deprecated as of CiviCRM 4.4.
 * It's recommended to use the api getoptions action instead
 *
 * @param array $params
 *  Name of a public static method of
 *  CRM_Core_PseudoConstant: one of
 *    activityStatus
 *    activityType
 *    addressee
 *    allGroup
 *    country
 *    countryIsoCode
 *    county
 *    currencyCode
 *    currencySymbols
 *    customGroup
 *    emailGreeting
 *    fromEmailAddress
 *    gender
 *    group
 *    groupIterator
 *    honor
 *    IMProvider
 *    individualPrefix
 *    individualSuffix
 *    locationType
 *    locationVcardName
 *    mailProtocol
 *    mappingTypes
 *    paymentProcessor
 *    paymentProcessorType
 *    pcm
 *    phoneType
 *    postalGreeting
 *    priority
 *    relationshipType
 *    stateProvince
 *    stateProvinceAbbreviation
 *    stateProvinceForCountry
 *    staticGroup
 *    tag
 *    tasks
 *    ufGroup
 *    visibility
 *    worldRegion
 *    wysiwygEditor
 *
 * @return array
 */
function civicrm_api3_constant_get($params) {

  $name = $params['name'];
  // all the stuff about classes should be adequately replaced by the bit in the 'else'
  //ie $values = call_user_func(array('CRM_Utils_PseudoConstant', 'getConstant'), $name);
  // once tests are 100% can try removing the first block & a similar block from Generic:getoptions

  // Whitelist approach is safer
  $allowedClasses = array(
    'CRM_Core_PseudoConstant',
    'CRM_Event_PseudoConstant',
    'CRM_Contribute_PseudoConstant',
    'CRM_Member_PseudoConstant',
  );
  $className = $allowedClasses[0];
  if (!empty($params['class']) && in_array($params['class'], $allowedClasses)) {
    $className = $params['class'];
  }
  $callable = "$className::$name";
  if (is_callable($callable)) {
    if (empty($params)) {
      $values = call_user_func(array($className, $name));
    }
    else {
      $values = call_user_func(array($className, $name));
      //@TODO XAV take out the param the COOKIE, Entity, Action and so there are only the "real param" in it
      //$values = call_user_func_array( array( $className, $name ), $params );
    }
    return civicrm_api3_create_success($values, $params, 'Constant');
  }
  else {
    $values = call_user_func(array('CRM_Utils_PseudoConstant', 'getConstant'), $name);
    if (!empty($values)) {
      return civicrm_api3_create_success($values, $params, 'Constant');
    }
  }
  return civicrm_api3_create_error('Unknown civicrm constant or method not callable');
}

/**
 * Adjust metadata for constant get action.
 *
 * @param array $params
 */
function _civicrm_api3_constant_get_spec(&$params) {
  $options = array(
    'activityStatus',
    'activityType',
    'addressee',
    'allGroup',
    'country',
    'countryIsoCode',
    'county',
    'currencyCode',
    'currencySymbols',
    'customGroup',
    'emailGreeting',
    'fromEmailAddress',
    'gender',
    'group',
    'groupIterator',
    'honor',
    'IMProvider',
    'individualPrefix',
    'individualSuffix',
    'locationType',
    'locationVcardName',
    'mailProtocol',
    'mappingTypes',
    'paymentInstrument',
    'paymentProcessor',
    'paymentProcessorType',
    'pcm',
    'phoneType',
    'postalGreeting',
    'priority',
    'relationshipType',
    'stateProvince',
    'stateProvinceAbbreviation',
    'stateProvinceForCountry',
    'staticGroup',
    'tag',
    'tasks',
    'ufGroup',
    'visibility',
    'worldRegion',
    'wysiwygEditor',
  );
  $params = array(
    'name' => array(
      'title' => 'Constant Name',
      'name' => 'name',
      'api.required' => 1,
      'options' => array_combine($options, $options),
      'type' => CRM_Utils_Type::T_STRING,
    ),
  );
}
