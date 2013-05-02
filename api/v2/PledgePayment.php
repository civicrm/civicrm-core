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
*DRAFT CODE WRITTEN BY EILEEN still dev version (pre-ALPHA)
*Starting point was Contribute API & some portions are still just that with 
*contribute replaced by pledge & not yet tested
* have only been using create, delete functionality
*/

/**
 * File for the CiviCRM APIv2 Pledge functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Pledge_Payment
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: PledgePayment.php
 *
 */

/**
 * Include utility functions
 */
require_once 'api/v2/utils.php';
require_once 'CRM/Utils/Rule.php';
function &civicrm_pledge_payment_add(&$params) {
  $result = civicrm_pledge_create($params);
  return $result;
}

/**
 * Add or update a plege payment
 *
 * @param  array   $params           (reference ) input parameters
 *
 * @return array (reference )        pledge_id of created or updated record
 * @static void
 * @access public
 */
function &civicrm_pledge_payment_create(&$params) {
  _civicrm_initialize();
  //GAP - update doesn't recalculate payment dates on existing payment schedule  - not the sure the code is in Civi to leverage
  if (empty($params)) {
    return civicrm_create_error(ts('No input parameters present'));
  }

  if (!is_array($params)) {
    return civicrm_create_error(ts('Input parameters is not an array'));
  }

  $error = _civicrm_pledgepayment_check_params($params);
  if (civicrm_error($error)) {
    return $error;
  }

  $values = array();

  require_once 'CRM/Pledge/BAO/PledgePayment.php';
  $error = _civicrm_pledgepayment_format_params($params, $values);

  if (civicrm_error($error)) {
    return $error;
  }

  $pledge = CRM_Pledge_BAO_PledgePayment::getOldestPledgePayment($params['pledge_id']);
  $params['id'] = $pledge['id'];

  //params ID needs to be pledge payment ID
  // pledge payment isn't retrieved if only one exists - the status is not set correctly causing this so let's get it for now as a cludgey make it work
  // copied from getOldestPayment function
  if (!$params['id']) {
    $query = "
SELECT civicrm_pledge_payment.id id, civicrm_pledge_payment.scheduled_amount amount
FROM civicrm_pledge, civicrm_pledge_payment
WHERE civicrm_pledge.id = civicrm_pledge_payment.pledge_id
  AND civicrm_pledge.id = %1
LIMIT 0, 1  
";
    $params[1]      = array($params['pledge_id'], 'Integer');
    $payment        = CRM_Core_DAO::executeQuery($query, $params);
    $paymentDetails = NULL;
    if ($payment->fetch()) {
      $params['id'] = $payment->id;
    }
  }
  CRM_Pledge_BAO_PledgePayment::add($params);

  //update pledge status
  CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($params['pledge_id']);
  return $errors;
}

/**
 * Retrieve a specific pledge, given a set of input params
 * If more than one pledge exists, return an error, unless
 * the client has requested to return the first found contact
 *
 * @param  array   $params           (reference ) input parameters
 *
 * @return array (reference )        array of properties, if error an array with an error id and error message
 * @static void
 * @access public

 function &civicrm_pledge_payment_get( &$params ) {
 _civicrm_initialize( );
 // copied from contribute code - not touched at all to make work for pledge or tested
 $values = array( );
 if ( empty( $params ) ) {
 return civicrm_create_error( ts( 'No input parameters present' ) );
 }

 if ( ! is_array( $params ) ) {
 return civicrm_create_error( ts( 'Input parameters is not an array' ) );
 }

 $pledges =& civicrm_pledge_search( $params );
 if ( civicrm_error( $pledges ) ) {
 return $pledges;
 }

 if ( count( $pledges ) != 1 &&
 ! $params['returnFirst'] ) {
 return civicrm_create_error( ts( '%1 pledges matching input params', array( 1 => count( $pledges ) ) ),
 $pledges );
 }

 $payments = array_values( $pledges );
 return $pledges[0];
 }


 /**
 * Retrieve a set of pledges, given a set of input params
 *
 * @param  array   $params           (reference ) input parameters
 * @param array    $returnProperties Which properties should be included in the
 *                                   returned pledge object. If NULL, the default
 *                                   set of properties will be included.
 *
 * @return array (reference )        array of pledges, if error an array with an error id and error message
 * @static void
 * @access public
 */
function &civicrm_pledge_payment_search(&$params) {
  _civicrm_initialize();
  // copied from contribute code - not touched at all to make work for pledge or tested
  if (!is_array($params)) {
    return civicrm_create_error(ts('Input parameters is not an array'));
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
    $pledge[$dao->pledge_id] = $query->store($dao);
  }
  $dao->free();

  return $pledge;
}

/**
 *
 * @param <type> $params
 *
 * @return <type>
 */
function &civicrm_pledge_payment_format_create(&$params) {
  _civicrm_initialize();

  // return error if we have no params
  if (empty($params)) {
    return civicrm_create_error('Input Parameters empty');
  }

  $error = _civicrm_pledge_check_params($params);
  if (civicrm_error($error)) {
    return $error;
  }
  $values = array();
  $error = _civicrm_pledge_format_params($params, $values);
  if (civicrm_error($error)) {
    return $error;
  }

  $error = _civicrm_pledge_duplicate_check($params);
  if (civicrm_error($error)) {
    return $error;
  }
  $ids = array();

  CRM_Pledge_BAO_Pledge::resolveDefaults($params, TRUE);

  $pledge = CRM_Pledge_BAO_Pledge::create($params, $ids);
  _civicrm_object_to_array($pledge, $pledgeArray);
  return $pledgeArray;
}

/**
 * This function ensures that we have the right input pledge parameters
 *
 * We also need to make sure we run all the form rules on the params list
 * to ensure that the params are valid
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new pledge.
 *
 * @return bool|CRM_Utils_Error
 * @access private
 */
function _civicrm_pledgepayment_check_params(&$params) {
  static $required = array(
    'pledge_id',
  );

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
 * Check if there is a pledge with the same trxn_id or invoice_id
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new pledge.
 *
 * @return array|CRM_Error
 * @access private
 */


/* not yet looked at
 * function _civicrm_pledge_duplicate_check( &$params ) {
    require_once 'CRM/Pledge/BAO/Pledge.php';
    $duplicates = array( );
    $result = CRM_Pledge_BAO_Pledge::checkDuplicate( $params,$duplicates ); 
    if ( $result ) {
        $d = implode( ', ', $duplicates );
        $error = CRM_Core_Error::createError( "Duplicate error - existing pledge record(s) have a matching Transaction ID or Invoice ID. pledge record ID(s) are: $d", CRM_Core_Error::DUPLICATE_pledge, 'Fatal', $d);
        return civicrm_create_error( $error->pop( ),
                                     $d );
    } else {
        return array();
    }
}
*/

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
function _civicrm_pledgepayment_format_params(&$params, &$values, $create = FALSE) {
  // copy all the pledge fields as is
  require_once 'CRM/Pledge/BAO/PledgePayment.php';
  require_once 'CRM/Pledge/DAO/Pledge.php';
  $fields = CRM_Pledge_DAO_Pledge::fields();

  _civicrm_store_values($fields, $params, $values);

  foreach ($params as $key => $value) {
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

      case 'receive_date':
      case 'end_date':
      case 'pledge_create_date':
      case 'cancel_date':
      case 'receipt_date':
      case 'thankyou_date':
        if (!CRM_Utils_Rule::date($value)) {
          return civicrm_create_error("$key not a valid date: $value");
        }
        break;

      case 'non_deductible_amount':
      case 'total_amount':
      case 'fee_amount':
      case 'net_amount':
        if (!CRM_Utils_Rule::money($value)) {
          return civicrm_create_error("$key not a valid amount: $value");
        }
        break;

      case 'currency':
        if (!CRM_Utils_Rule::currencyCode($value)) {
          return civicrm_create_error("currency not a valid code: $value");
        }
        break;

      case 'pledge_type':
        $values['pledge_type_id'] = CRM_Utils_Array::key(ucfirst($value),
          CRM_Pledge_PseudoConstant::pledgeType()
        );
        break;

      case 'payment_instrument':
        require_once 'CRM/Core/OptionGroup.php';
        $values['payment_instrument_id'] = CRM_Core_OptionGroup::getValue('payment_instrument', $value);
        break;

      default:
        break;
    }
  }

  if (array_key_exists('note', $params)) {
    $values['note'] = $params['note'];
  }

  if (array_key_exists('installment_amount', $params)) {
    $values['installment_amount'] = $params['installment_amount'];
  }
  // testing testing - how do I make it take a create_date? It needs $values['create_date'] set but doesn't seem to like it because $fields calls it $pledge_create_date
  //ditto scheduled date. I don't know why this is needs to be done because I don't fully understand the code above
  if (array_key_exists('pledge_create_date', $params)) {
    $values['create_date'] = $params['pledge_create_date'];
  }
  if (array_key_exists('pledge_scheduled_date', $params)) {
    $values['scheduled_date'] = $params['pledge_scheduled_date'];
  }
  if (array_key_exists('pledge_create_date', $params)) {
    $values['create_date'] = $params['pledge_create_date'];
  }
  if (array_key_exists('status_id', $params)) {
    $values['status_id'] = $params['status_id'];
    $values['pledge_status_id'] = $params['status_id'];
  }

  _civicrm_custom_format_params($params, $values, 'Pledge');

  if ($create) {
    // CRM_pledge_BAO_Pledge::add() handles Pledge_source
    // So, if $values contains Pledge_source, convert it to source
    $changes = array('pledge_source' => 'source');

    foreach ($changes as $orgVal => $changeVal) {
      if (isset($values[$orgVal])) {
        $values[$changeVal] = $values[$orgVal];
        unset($values[$orgVal]);
      }
    }
  }

  return array();
}


//having an 'interogate function to find what can be returned from an API would be SUPER useful. Ideally it would also advise which fields are required too. I
// imaging the most useful format would be to be like the $params array you need to pass in but the value for each field would be information about it. Ideally the
// function that sets which parameters are required would be accessible from this function to add them in
// function at the moment doesn't have custom fields
function civicrm_pledge_payment_interogate($params) {
  $fields = CRM_Pledge_DAO_Pledge::fields();
  $fields['installment_amount'] = array(
    'name' => 'installment_amount',
    'title' => ts('Installment Amount'),
  );
  unset($fields['amount']);
  return $fields;
}

// this one should probably go in a pledge payment API
function updatePledgePayments($pledgeId, $paymentStatusId, $paymentIds) {
  require_once 'CRM/Pledge/BAO/Pledge.php';
  $result = updatePledgePayments($pledgeId, $paymentStatusId, $paymentIds = NULL);
  return $result;
}

