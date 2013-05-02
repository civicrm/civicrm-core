<?php
class AuthorizeNet extends PHPUnit_Framework_Testcase {
  /*
     * Helper function to create
     * a payment processor of type Authorize.net
     *
     * @return $paymentProcessor id of created payment processor
     */
  function create() {

    $paymentProcessor = new CRM_Financial_DAO_PaymentProcessor();
    $paymentParams = array(
      'name' => 'Authorize',
      'domain_id' => CRM_Core_Config::domainID(),
      'payment_processor_type' => 'AuthNet',
      'is_active' => 1,
      'is_default' => 0,
      'is_test' => 1,
      'user_name' => '4y5BfuW7jm',
      'password' => '4cAmW927n8uLf5J8',
      'url_site' => 'https://test.authorize.net/gateway/transact.dll',
      'url_recur' => 'https://apitest.authorize.net/xml/v1/request.api',
      'class_name' => 'Payment_AuthorizeNet',
      'billing_mode' => 1,
    );
    $paymentProcessor->copyValues($paymentParams);
    $paymentProcessor->save();
    return $paymentProcessor;
  }

  /*
     * Helper function to delete a PayPal Pro 
     * payment processor
     * @param  int $id - id of the PayPal Pro payment processor
     * to be deleted
     * @return boolean true if payment processor deleted, false otherwise
     * 
     */
  function delete($id) {
    $paymentProcessor = new CRM_Financial_DAO_PaymentProcessor();
    $paymentProcessor->id = $id;
    if ($paymentProcessor->find(TRUE)) {
      $result = $paymentProcessor->delete();
    }
    return $result;
  }
}



