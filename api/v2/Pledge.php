<?php
// $Id$

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



/*
*DRAFT CODE WRITTEN BY EILEEN still dev version 
*Starting point was Contribute API. I tried to use the format params from 
*contribute API to handle incorrect data prior to hitting core & help
*prevent CORE errors (the bane of API users since there is a proper API
*error format). However, I found many fields needed to be manipulated after 
*doing the field rationalisation in the contribute module. The way I have done it
*is cumbersome from a coding point of view in order to allow a lot of commenting / clarity
* I concluded that in the absence
*of a clear standard to say when the fields unique name & when it's table name should
*be used I should facilitate both as much as possible as either would be a reasonable 
*expectation from a developer and I know from experience what huge amounts of developer
*time go into 'trial and error' & 'guessing' what the paramaters might be for the api
*Also, the version of a variable that is returned is a bit variable - ie. pledge_ vs not so
*acceptable params should reflect that
*Note my attempt at a couple of things that have been discussed:
*1) interrogate function - feedback on possible variables (I presume that 'check_permissions' or similar might
*be relevant here too)? What should default for check_permissions be?
*2) $params['sequential'] - array not indexed by id
*Would be nice to keep explanatory
*notes in - I know 'dumb comments' normally get removed by core team when committing
*but they do help dumb developers:-)
*/

/**
 * File for the CiviCRM APIv2 Pledge functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Pledge
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: Pledge.php
 *
 */

/**
 * Include utility functions
 */
require_once 'api/v2/utils.php';
require_once 'CRM/Utils/Rule.php';
function &civicrm_pledge_add(&$params) {
  $result = civicrm_pledge_create($params);
  return $result;
}

/**
 * Add or update a plege
 *
 * @param  array   $params           (reference ) input parameters. Fields from interogate function should all work
 *
 * @return array (reference )        array representing created pledge
 * @static void
 * @access public
 */
function &civicrm_pledge_create(&$params) {
  _civicrm_initialize();
  if (empty($params)) {
    return civicrm_create_error('No input parameters present');
  }

  if (!is_array($params)) {
    return civicrm_create_error('Input parameters is not an array');
  }
  //check for required fields
  $error = _civicrm_pledge_check_params($params);
  if (civicrm_error($error)) {
    return $error;
  }

  $values = array();
  require_once 'CRM/Pledge/BAO/Pledge.php';
  //check that fields are in appropriate format. Dates will be formatted (within reason) by this function
  $error = _civicrm_pledge_format_params($params, $values, TRUE);
  if (civicrm_error($error)) {
    return $error;
  }

  $pledge = CRM_Pledge_BAO_Pledge::create($values);
  if (is_a($pledge, 'CRM_Core_Error')) {
    return civicrm_create_error($pledge->_errors[0]['message']);
  }
  else {
    _civicrm_object_to_array($pledge, $pledgeArray);
    $pledgeArray['is_error'] = 0;
  }
  _civicrm_object_to_array($pledge, $pledgeArray);

  return $pledgeArray;
}

/**
 * Delete a pledge
 *
 * @param  array   $params           array included 'pledge_id' of pledge to delete
 *
 * @return boolean        true if success, else false
 * @static void
 * @access public
 */
function civicrm_pledge_delete(&$params) {
  if (!empty($params['id'])) {
    //handle field name or unique db name
    $params['pledge_id'] = $params['id'];
  }

  $pledgeID = CRM_Utils_Array::value('pledge_id', $params);
  if (!$pledgeID) {
    return civicrm_create_error('Could not find pledge_id in input parameters');
  }

  require_once 'CRM/Pledge/BAO/Pledge.php';
  if (CRM_Pledge_BAO_Pledge::deletePledge($pledgeID)) {
    return civicrm_create_success();
  }
  else {
    return civicrm_create_error('Could not delete pledge');
  }
}

/**
 * Retrieve a set of pledges, given a set of input params
 *
 * @param  array   $params           (reference ) input parameters. Use interogate for possible fields
 *
 * @return array (reference )        array of pledges, if error an array with an error id and error message
 * @static void
 * @access public
 */
function &civicrm_pledge_get(&$params) {
  _civicrm_initialize();
  if (!is_array($params)) {
    return civicrm_create_error('Input parameters is not an array');
  }



  $inputParams      = array();
  $returnProperties = array();
  $otherVars        = array('sort', 'offset', 'rowCount');

  $sort     = NULL;
  $offset   = 0;
  $rowCount = 25;
  foreach ($params as $n => $v) {
    if (substr($n, 0, 7) == 'return.') {
      $returnProperties[substr($n, 7)] = $v;
    }
    elseif (in_array($n, $otherVars)) {
      $$n = $v;
    }
    else {
      $inputParams[$n] = $v;
    }
  }

  // add is_test to the clause if not present
  if (!array_key_exists('pledge_test', $inputParams)) {
    $inputParams['pledge_test'] = 0;
  }

  require_once 'CRM/Pledge/BAO/Query.php';
  require_once 'CRM/Contact/BAO/Query.php';
  if (empty($returnProperties)) {
    $returnProperties = CRM_Pledge_BAO_Query::defaultReturnProperties(CRM_Contact_BAO_Query::MODE_PLEDGE);
  }
  else {
    $returnProperties['pledge_id'] = 1;
  }

  $newParams = CRM_Contact_BAO_Query::convertFormValues($inputParams);

  $query = new CRM_Contact_BAO_Query($newParams, $returnProperties, NULL);
  list($select, $from, $where) = $query->query();


  $sql = "$select $from $where";

  if (!empty($sort)) {
    $sql .= " ORDER BY $sort ";
  }
  $sql .= " LIMIT $offset, $rowCount ";
  $dao = CRM_Core_DAO::executeQuery($sql);



  $pledge = array();
  while ($dao->fetch()) {
    if ($params['sequential']) {
      $pledge[] = $query->store($dao);
    }
    else {
      $pledge[$dao->pledge_id] = $query->store($dao);
    }
  }
  $dao->free();

  return $pledge;
}

/**
 * This function ensures that we have the required input pledge parameters
 *
 * We also run format the parameters with the format_params function
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new pledge.
 *
 * @return bool|CRM_Utils_Error
 * @access private
 */
function _civicrm_pledge_check_params(&$params) {
    static $required = array( 'contact_id', 'amount', 'financial_type_id' , 'installments','start_date');
  if ($params['pledge_amount']) {
    //can be in unique format or DB format but change to unique format here
    $params['amount'] = $params['pledge_amount'];
  }

  // cannot create a pledge with empty params
  if (empty($params)) {
    return civicrm_create_error('Input Parameters empty');
  }

  $valid = TRUE;
  $error = '';
  foreach ($required as $field) {
    if (!CRM_Utils_Array::value($field, $params)) {
      $valid = FALSE;
      $error .= $field;
      break;
    }
  }

  if (!$valid) {
    return civicrm_create_error("Required fields not found for pledge $error");
  }

  return array();
}

/**
 * This function returns possible values for this api
 *
 * @return array  $result       Associative array of possible values for the api
 *
 * @access public
 */
//having an 'interogate function to find what can be returned from an API would be SUPER useful. Ideally it would also advise which fields are required too. I
// imaging the most useful format would be to be like the $params array you need to pass in but the value for each field would be information about it. Ideally the
// function that sets which parameters are required would be accessible from this function to add them in
// function at the moment doesn't have custom fields
function civicrm_pledge_interogate($params) {
  require_once 'CRM/Pledge/DAO/Pledge.php';
  $fields = CRM_Pledge_DAO_Pledge::fields();
  $fields['sort'] = "(GET only)(Optional) Sort String in SQL format eg. 'display_name ASC'";
  $fields['rowCount'] = "(GET only)(Optional)(default =25) number of records to return";
  $fields['offset'] = "(GET only)(Optional)(default =0) result record to start from";
  $fields['return.display_name'] = "(GET only)(Optional)specify to return only display_name field (and contact_id). Substitute display_name for other field";
  $fields['version'] = "(Recommended - provide version -currently '3.0'";
  $fields['sequential'] = "(GET only)(Optional)(default =0). Return sequential array not id indexed array";
  $fields['scheduled_date'] = "(Add only)(Optional)(default= start date) next payment date";
  $fieldsarr = array_keys($fields);

  foreach ($fieldsarr as $field) {
    $result[$field] = $fields[$field]['type'];
    // todo change type to say what it is - e.g. integer
  }
  // and get the custom fields
  require_once 'CRM/Core/BAO/CustomField.php';
  $customDataType = 'Pledge';
  $customFields   = CRM_Core_BAO_CustomField::getFields($customDataType);
  $fieldIDs       = array_keys($customFields);
  foreach ($fieldIDs as $key) {
    $result['custom_' . $key] = $customFields[$key]['data_type'] . " : " . $customFields[$key]['label'];
  }

  $arrfields = db_query($sql);
  while ($field = db_fetch_array($arrfields)) {
    $result[$field['Field']] = $field['Field'];
  }
  return $result;
}

/**
 * take the input parameter list as specified in the data model and
 * convert it into the same format that we use in QF and BAO object
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new contact.
 * @param array  $values       The reformatted properties that we can use internally
 *                            '
 *
 * @return array|CRM_Error
 * @access public
 */
function _civicrm_pledge_format_params(&$params, &$values, $create = FALSE) {
  // based on contribution apis - copy all the pledge fields - this function filters out non -valid fields but unfortunately
  // means we have to put them back where there are 2 names for the field (name in table & unique name)
  // since there is no clear std to use one or the other. Generally either works ? but not for create date
  // perhaps we should just copy $params across rather than run it through the 'filter'?
  // but at least the filter forces anomalies into the open. In several cases it turned out the unique names wouldn't work
  // even though they are 'generally' what is returned in the GET - implying they should
  $fields = CRM_Pledge_DAO_Pledge::fields();
  _civicrm_store_values($fields, $params, $values);


  //add back the fields we know of that got dropped by the previous function
  if ($params['pledge_create_date']) {
    //pledge_create_date will not be formatted by the format params function so change back to create_date
    $values['create_date'] = $params['pledge_create_date'];
  }
  if ($params['create_date']) {
    //create_date may have been dropped by the $fields function so retrieve it
    $values['create_date'] = $params['create_date'];
  }
  if (array_key_exists('installment_amount', $params)) {
    //field has been renamed - don't lose it! Note that this must be called
    // installment amount not pledge_installment_amount, pledge_original_installment_amount
    // or original_installment_amount to avoid error
    // Division by zero in CRM\Pledge\BAO\PledgePayment.php:162
    // but we should accept the variant because they are all 'logical assumptions' based on the
    // 'standards'
    $values['installment_amount'] = $params['installment_amount'];
  }
  if (array_key_exists('original_installment_amount', $params)) {
    $values['installment_amount'] = $params['original_installment_amount'];
  }
  if (array_key_exists('pledge_original_installment_amount', $params)) {
    $values['installment_amount'] = $params['pledge_original_installment_amount'];
  }
  if (array_key_exists('status_id', $params)) {
    $values['pledge_status_id'] = $params['status_id'];
  }
  if ($params['contact_id']) {
    //this is validity checked further down to make sure the contact exists
    $values['pledge_contact_id'] = $params['contact_id'];
  }
  if (array_key_exists('id', $params)) {
    //retrieve the id key dropped from params. Note we can't use pledge_id because it
    //causes an error in CRM_Pledge_BAO_PledgePayment - approx line 302
    $values['id'] = $params['id'];
  }
  if (array_key_exists('pledge_id', $params)) {
    //retrieve the id key dropped from params. Note we can't use pledge_id because it
    //causes an error in CRM_Pledge_BAO_PledgePayment - approx line 302
    $values['id'] = $params['pledge_id'];
    unset($values['pledge_id']);
  }
  if (array_key_exists('status_id', $params)) {
    $values['pledge_status_id'] = $params['status_id'];
  }
  if (empty($values['id'])) {
    //at this point both should be the same so unset both if not set - passing in empty
    //value causes crash rather creating new - do it before next section as null values ignored in 'switch'
    unset($values['id']);
  }
  if (!empty($params['scheduled_date'])) {
    //scheduled date is required to set next payment date - defaults to start date
    $values['scheduled_date'] = $params['scheduled_date'];
  }
  elseif (array_key_exists('start_date', $params)) {
    $values['scheduled_date'] = $params['start_date'];
  }
    if( CRM_Utils_Array::value( 'financial_type_id', $params ) ) {
        $values['financial_type_id'] = $params['financial_type_id']; 
  }
  foreach ($values as $key => $value) {
    // ignore empty values or empty arrays etc
    if (CRM_Utils_System::isNull($value)) {
      continue;
    }
    switch ($key) {
      case 'pledge_contact_id':
        if (!CRM_Utils_Rule::integer($value)) {
          return civicrm_create_error("contact_id not valid: $value");
        }
        $dao     = new CRM_Core_DAO();
        $qParams = array();
        $svq     = $dao->singleValueQuery("SELECT id FROM civicrm_contact WHERE id = $value",
          $qParams
        );
        if (!$svq) {
          return civicrm_create_error("Invalid Contact ID: There is no contact record with contact_id = $value.");
        }

        $values['contact_id'] = $values['pledge_contact_id'];
        unset($values['pledge_contact_id']);
        break;

      case 'pledge_id':
        if (!CRM_Utils_Rule::integer($value)) {
          return civicrm_create_error("contact_id not valid: $value");
        }
        $dao     = new CRM_Core_DAO();
        $qParams = array();
        $svq     = $dao->singleValueQuery("SELECT id FROM civicrm_pledge WHERE id = $value",
          $qParams
        );
        if (!$svq) {
          return civicrm_create_error("Invalid Contact ID: There is no contact record with contact_id = $value.");
        }
        break;

      case 'create_date':
      case 'scheduled_date':
      case 'start_date':
        if (!CRM_Utils_Rule::datetime($value)) {
          return civicrm_create_error("$key not a valid date: $value");
        }
        break;

      case 'installment_amount':
      case 'amount':
        if (!CRM_Utils_Rule::money($value)) {
          return civicrm_create_error("$key not a valid amount: $value");
        }
        break;

      case 'currency':
        if (!CRM_Utils_Rule::currencyCode($value)) {
          return civicrm_create_error("currency not a valid code: $value");
        }
      case 'financial_type_id':
        require_once 'CRM/Contribute/PseudoConstant.php';
            $typeId = CRM_Contribute_PseudoConstant::financialType( $value );
        if (!CRM_Utils_Rule::integer($value) || !$typeId) {
                return civicrm_create_error( "financial type id is not valid: $value" );
        }
      default:
        break;
    }
  }

  //format the parameters
  _civicrm_custom_format_params($params, $values, 'Pledge');


  return array();
}

