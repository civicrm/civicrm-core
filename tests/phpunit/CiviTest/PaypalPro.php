<?php
require_once "CRM/Financial/DAO/PaymentProcessor.php";
class PaypalPro extends PHPUnit_Framework_Testcase {
  /**
   * Helper function to create
   * a payment processor of type Paypal Pro
   *
   * @return int $paymentProcessor id of created payment processor@todo this appears not to be working but because it doesn't extend the test class
   * callAPISuccess won't work
   * I have duplicated this on the main test class as a work around
   */
  static function create() {

    $paymentProcessor = new CRM_Financial_DAO_PaymentProcessor();
    $paymentParams = array(
      'name' => 'demo',
      'domain_id' => CRM_Core_Config::domainID(),
      'payment_processor_type' => 'PayPal',
      'is_active' => 1,
      'is_default' => 0,
      'is_test' => 1,
      'user_name' => 'sunil._1183377782_biz_api1.webaccess.co.in',
      'password' => '1183377788',
      'signature' => 'APixCoQ-Zsaj-u3IH7mD5Do-7HUqA9loGnLSzsZga9Zr-aNmaJa3WGPH',
      'url_site' => 'https://www.sandbox.paypal.com/',
      'url_api' => 'https://api-3t.sandbox.paypal.com/',
      'url_button' => 'https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif',
      'class_name' => 'Payment_PayPalImpl',
      'billing_mode' => 3,
      'financial_type_id' => 1,
    );
    $paymentProcessor->copyValues($paymentParams);
    $paymentProcessor->save();
    return $paymentProcessor->id;
  }

  /**
   * Helper function to delete a PayPal Pro
   * payment processor
   * @param  int $id - id of the PayPal Pro payment processor
   * to be deleted
   * @return boolean true if payment processor deleted, false otherwise
   */
  static function delete($id) {
    $pp = new CRM_Financial_DAO_PaymentProcessor();
    $pp->id = $id;
    if ($pp->find(TRUE)) {
      $result = $pp->delete();
    }
    return $result;
  }
}
