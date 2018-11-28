<?php

/**
 * @file
 */

require_once 'CRM/Core/Form.php';

/**
 * Form controller class.
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_iATS_Form_IATSCustomerLink extends CRM_Core_Form {

  private $iats_result = array();

  /**
   * Get the field names and labels expected by iATS CustomerLink,
   * and the corresponding fields in CiviCRM.
   *
   * @return array
   */
  public function getFields() {
    $civicrm_fields = array(
      'firstName' => 'billing_first_name',
      'lastName' => 'billing_last_name',
      'address' => 'street_address',
      'city' => 'city',
      'state' => 'state_province',
      'zipCode' => 'postal_code',
      'creditCardNum' => 'credit_card_number',
      'creditCardExpiry' => 'credit_card_expiry',
      'mop' => 'credit_card_type',
    );
    // When querying using CustomerLink.
    $iats_fields = array(
    // FLN.
      'creditCardCustomerName' => 'CSTN',
      'address' => 'ADD',
      'city' => 'CTY',
      'state' => 'ST',
      'zipCode' => 'ZC',
      'creditCardNum' => 'CCN',
      'creditCardExpiry' => 'EXP',
      'mop' => 'MP',
    );
    $labels = array(
      // 'firstName' => 'First Name',
      // 'lastName' => 'Last Name',.
      'creditCardCustomerName' => 'Name on Card',
      'address' => 'Street Address',
      'city' => 'City',
      'state' => 'State or Province',
      'zipCode' => 'Postal Code or Zip Code',
      'creditCardNum' => 'Credit Card Number',
      'creditCardExpiry' => 'Credit Card Expiry Date',
      'mop' => 'Credit Card Type',
    );
    return array($civicrm_fields, $iats_fields, $labels);
  }

  /**
   *
   */
  protected function getCustomerCodeDetail($params) {
    require_once "CRM/iATS/iATSService.php";
    $credentials = iATS_Service_Request::credentials($params['paymentProcessorId'], $params['is_test']);
    $iats_service_params = array('type' => 'customer', 'iats_domain' => $credentials['domain'], 'method' => 'get_customer_code_detail');
    $iats = new iATS_Service_Request($iats_service_params);
    // print_r($iats); die();
    $request = array('customerCode' => $params['customerCode']);
    // Make the soap request.
    $response = $iats->request($credentials, $request);
    // note: don't log this to the iats_response table.
    $customer = $iats->result($response, FALSE);
    if (empty($customer['ac1'])) {
      $alert = ts('Unable to retrieve card details from iATS.<br />%1', array(1 => $customer['AUTHORIZATIONRESULT']));
      throw new Exception($alert);
    }
    // This is a SimpleXMLElement Object.
    $ac1 = $customer['ac1'];
    $card = get_object_vars($ac1->CC);
    return $customer + $card;
  }

  /**
   *
   */
  protected function updateCreditCardCustomer($params) {
    require_once "CRM/iATS/iATSService.php";
    $credentials = iATS_Service_Request::credentials($params['paymentProcessorId'], $params['is_test']);
    unset($params['paymentProcessorId']);
    unset($params['is_test']);
    unset($params['domain']);
    $iats_service_params = array('type' => 'customer', 'iats_domain' => $credentials['domain'], 'method' => 'update_credit_card_customer');
    $iats = new iATS_Service_Request($iats_service_params);
    // print_r($iats); die();
    $params['updateCreditCardNum'] = (0 < strlen($params['creditCardNum']) && (FALSE === strpos($params['creditCardNum'], '*'))) ? 1 : 0;
    if (empty($params['updateCreditCardNum'])) {
      unset($params['creditCardNum']);
      unset($params['updateCreditCardNum']);
    }
    $params['customerIPAddress'] = (function_exists('ip_address') ? ip_address() : $_SERVER['REMOTE_ADDR']);
    foreach (array('qfKey', 'entryURL', 'firstName', 'lastName', '_qf_default', '_qf_IATSCustomerLink_submit') as $key) {
      if (isset($params[$key])) {
        unset($params[$key]);
      }
    }
    // Make the soap request.
    $response = $iats->request($credentials, $params);
    // note: don't log this to the iats_response table.
    $this->iats_result = $iats->result($response, TRUE);
    return $this->iats_result;
  }

  /**
   *  Get an appropriate message for the user after an update is attempted.
   */
  protected function getResultMessage() {
    $message = array();
    foreach($this->iats_result as $key => $value) {
      $message[] = strtolower($key).": $value";
    }
    return '<pre>'.implode('<br />',$message).'</pre>';
  }

  /**
   *  Test whether the update was successful
   */
  public function getAuthorizationResult() {
    return $this->iats_result['AUTHORIZATIONRESULT'];
  }

  /**
   *
   */
  public function buildQuickForm() {

    list($civicrm_fields, $iats_fields, $labels) = $this->getFields();
    $cid = CRM_Utils_Request::retrieve('cid', 'Integer');
    $customerCode = CRM_Utils_Request::retrieve('customerCode', 'String');
    $paymentProcessorId = CRM_Utils_Request::retrieve('paymentProcessorId', 'Positive');
    $is_test = CRM_Utils_Request::retrieve('is_test', 'Integer');
    $defaults = array(
      'cid' => $cid,
      'customerCode' => $customerCode,
      'paymentProcessorId' => $paymentProcessorId,
      'is_test' => $is_test,
    );
    // Get my current values from iATS as defaults.
    if (empty($_POST)) {
      try {
        $customer = $this->getCustomerCodeDetail($defaults);
      }
      catch (Exception $e) {
        CRM_Core_Session::setStatus($e->getMessage(), ts('Warning'), 'alert');
        return;
      }
      foreach (array_keys($labels) as $name) {
        $iats_field = $iats_fields[$name];
        if (is_string($customer[$iats_field])) {
          $defaults[$name] = $customer[$iats_field];
        }
      }
    }
    // I don't need cid, but it allows the back button to work.
    $this->add('hidden', 'cid');
    foreach ($labels as $name => $label) {
      $this->add('text', $name, $label);
    }
    $this->add('hidden', 'customerCode');
    $this->add('hidden', 'paymentProcessorId');
    $this->add('hidden', 'is_test');
    $this->setDefaults($defaults);
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Back'),
      ),
    ));
    // Export form elements.
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  /**
   *
   */
  public function postProcess() {
    $values = $this->exportValues();
    // Send update to iATS
    $this->updateCreditCardCustomer($values);
    CRM_Core_Session::setStatus($this->getResultMessage(), 'Card Update Result');
    if ('OK' == $this->getAuthorizationResult()) {
      // Update my copy of the expiry date.
      list($month, $year) = explode('/', $values['creditCardExpiry']);
      $exp = sprintf('%02d%02d', $year, $month);
      $query_params = array(
        1 => array($values['customerCode'], 'String'),
        2 => array($exp, 'String'),
      );
      CRM_Core_DAO::executeQuery("UPDATE civicrm_iats_customer_codes SET expiry = %2 WHERE customer_code = %1", $query_params);
    }
    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
