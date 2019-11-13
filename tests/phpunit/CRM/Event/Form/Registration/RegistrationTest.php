<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Event_Form_RegistrationTest
 * @group headless
 */
class CRM_Event_Form_Registration_RegistrationTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * CRM-19626 - Test minimum value configured for priceset.
   */
  public function testMinValueForPriceSet() {
    $form = new CRM_Event_Form_Registration();
    $form->controller = new CRM_Core_Controller();

    $minAmt = 100;
    $feeAmt = 1000;
    $event = $this->eventCreate();
    $priceSetId = $this->eventPriceSetCreate($feeAmt, $minAmt);
    $priceSet = current(CRM_Price_BAO_PriceSet::getSetDetail($priceSetId));
    $form->_values['fee'] = $form->_feeBlock = $priceSet['fields'];
    $form->_values['event'] = $event['values'][$event['id']];
    $form->_skipDupeRegistrationCheck = 1;

    $priceField = $this->callAPISuccess('PriceField', 'get', ['price_set_id' => $priceSetId]);
    $params = [
      'email-Primary' => 'someone@example.com',
      'priceSetId' => $priceSetId,
    ];
    // Check empty values for price fields.
    foreach (array_keys($priceField['values']) as $fieldId) {
      $params['price_' . $fieldId] = 0;
    }
    $form->set('priceSetId', $priceSetId);
    $form->set('priceSet', $priceSet);
    $form->set('name', 'CRM_Event_Form_Registration_Register');
    $files = [];
    $errors = CRM_Event_Form_Registration_Register::formRule($params, $files, $form);

    //Assert the validation Error.
    $expectedResult = [
      '_qf_default' => ts('A minimum amount of %1 should be selected from Event Fee(s).', [1 => CRM_Utils_Money::format($minAmt)]),
    ];
    $this->checkArrayEquals($expectedResult, $errors);
  }

}
