<?php

/**
 * @file Copyright iATS Payments (c) 2014.
 * @author Alan Dixon
 *
 * This file is a part of CiviCRM published extension.
 *
 * This extension is free software; you can copy, modify, and distribute it
 * under the terms of the GNU Affero General Public License
 * Version 3, 19 November 2007.
 *
 * It is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License with this program; if not, see http://www.gnu.org/licenses/
 *
 * This code provides glue between CiviCRM payment model and the iATS Payment model encapsulated in the CRM_Iats_iATSServiceRequest object
 */

/**
 *
 */
class CRM_Core_Payment_iATSServiceSWIPE extends CRM_Core_Payment_iATSService {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable.
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * Constructor.
   *
   * @param string $mode
   *   the mode of operation: live or test.
   *
   * @return void
   */
  public function __construct($mode, &$paymentProcessor) {
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('iATS Payments SWIPE');

    // Get merchant data from config.
    $config = CRM_Core_Config::singleton();
    // Live or test.
    $this->_profile['mode'] = $mode;
    // We only use the domain of the configured url, which is different for NA vs. UK.
    $this->_profile['iats_domain'] = parse_url($this->_paymentProcessor['url_site'], PHP_URL_HOST);
  }

  /**
   *
   */
  static public function &singleton($mode, &$paymentProcessor, &$paymentForm = NULL, $force = FALSE) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_iATSServiceSWIPE($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   *
   */
  public function validatePaymentInstrument($values, &$errors) {
    // Override the default and don't do any validation because my values are encrypted.
  }

  /**
   * Get array of fields that should be displayed on the payment form for credit cards.
   * Replace cvv and card type fields with (hidden) swipe field.
   *
   * @return array
   */

  protected function getCreditCardFormFields() {
    return [
      'credit_card_number',
      'credit_card_exp_date',
      'encrypted_credit_card_number'
    ];
  }

  /**
   * Return an array of all the details about the fields potentially required for payment fields.
   *
   * Only those determined by getPaymentFormFields will actually be assigned to the form
   *
   * @return array
   *   field metadata
   */
  public function getPaymentFormFieldsMetadata() {
    $metadata = parent::getPaymentFormFieldsMetadata();
    $metadata['encrypted_credit_card_number'] = [
        'htmlType' => 'textarea',
        'name' => 'encrypted_credit_card_number',
        'title' => ts('Encrypted Credit Card Details'),
        'is_required' => TRUE,
        'attributes' => [
          'cols' => 80,
          'rows' => 8,
          'autocomplete' => 'off',
          'id' => 'encrypted_credit_card_number',
        ],
      ];
    return $metadata;
  }


  /**
   * Opportunity for the payment processor to override the entire form build.
   *
   * @param CRM_Core_Form $form
   *
   * @return bool
   *   Should form building stop at this point?
   *
   * Add SWIPE instructions, also do parent (non-swipe) form building.
   *
   * return (!empty($form->_paymentFields));
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function buildForm(&$form) {
    CRM_Core_Resources::singleton()->addScriptFile('com.iatspayments.civicrm', 'js/swipe.js', 10);
    CRM_Core_Region::instance('billing-block')->add(array(
      'template' => 'CRM/Iats/BillingBlockSwipe.tpl',
    ));
    return parent::buildForm($form);
  }


}
