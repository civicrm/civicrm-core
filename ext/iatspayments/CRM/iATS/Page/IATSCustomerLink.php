<?php

/**
 * @file
 */

require_once 'CRM/Core/Page.php';
/**
 *
 */
class CRM_iATS_Page_IATSCustomerLink extends CRM_Core_Page {

  /**
   *
   */
  public function run() {
    // TODO: use the cid value to put the customer name in the title?
    // CRM_Utils_System::setTitle(ts('iATS CustomerLink'));.
    $customerCode = CRM_Utils_Request::retrieve('customerCode', 'String');
    $paymentProcessorId = CRM_Utils_Request::retrieve('paymentProcessorId', 'Positive');
    $is_test = CRM_Utils_Request::retrieve('is_test', 'Integer');
    $this->assign('customerCode', $customerCode);
    require_once "CRM/iATS/iATSService.php";
    $credentials = iATS_Service_Request::credentials($paymentProcessorId, $is_test);
    $iats_service_params = array('type' => 'customer', 'iats_domain' => $credentials['domain'], 'method' => 'get_customer_code_detail');
    $iats = new iATS_Service_Request($iats_service_params);
    // print_r($iats); die();
    $request = array('customerCode' => $customerCode);
    // Make the soap request.
    $response = $iats->request($credentials, $request);
    // note: don't log this to the iats_response table.
    $customer = $iats->result($response, FALSE);
    if (empty($customer['ac1'])) {
      $alert = ts('Unable to retrieve card details from iATS.<br />%1', array(1 => $customer['AUTHORIZATIONRESULT']));
      CRM_Core_Session::setStatus($alert, ts('Warning'), 'alert');
    }
    else {
      // This is a SimpleXMLElement Object.
      $ac1 = $customer['ac1'];
      $attributes = $ac1->attributes();
      $type = $attributes['type'];
      $card = get_object_vars($ac1->$type);
      $card['type'] = $type;
      foreach (array('ac1', 'status', 'remote_id', 'auth_result') as $key) {
        if (isset($customer[$key])) {
          unset($customer[$key]);
        }
      }
      $this->assign('customer', $customer);
      $this->assign('card', $card);
    }
    parent::run();
  }

}
