<?php
use CRM_Iats_ExtensionUtil as E;

/**
 * FapsTransaction.Get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_faps_transaction_Get_spec(&$spec) {
  $spec['payment_processor_id']['api.required'] = 1;
  $spec['transactionId']['api.required'] = 1;
}

/**
 * FapsTransaction.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_faps_transaction_Get($params) {
  $paymentProcessor = civicrm_api3('PaymentProcessor', 'getsingle', ['return' => ['password','user_name','signature'], 'id' => $params['payment_processor_id'], 'is_test' => 0]);
  $credentials = array(
    'merchantKey' => $paymentProcessor['signature'],
    'processorId' => $paymentProcessor['user_name']
  );
  $service_params = array('action' => 'Query');
  $faps = new CRM_Iats_FapsRequest($service_params);
  $request = array(
    'referenceNumber' => '182668',
    // 'transactionId' => $params['transactionId'],
  );
  $result = $faps->request($credentials, $request);
  return civicrm_api3_create_success($result, $params, 'FapsTransaction', 'Get');
}
