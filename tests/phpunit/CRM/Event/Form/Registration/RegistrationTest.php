<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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

    $priceField = $this->callAPISuccess('PriceField', 'get', array('price_set_id' => $priceSetId));
    $params = array(
      'email-Primary' => 'someone@example.com',
      'priceSetId' => $priceSetId,
    );
    // Check empty values for price fields.
    foreach (array_keys($priceField['values']) as $fieldId) {
      $params['price_' . $fieldId] = 0;
    }
    $form->set('priceSetId', $priceSetId);
    $form->set('priceSet', $priceSet);
    $form->set('name', 'CRM_Event_Form_Registration_Register');
    $files = array();
    $errors = CRM_Event_Form_Registration_Register::formRule($params, $files, $form);

    //Assert the validation Error.
    $expectedResult = array(
      '_qf_default' => ts('A minimum amount of %1 should be selected from Event Fee(s).', array(1 => CRM_Utils_Money::format($minAmt))),
    );
    $this->checkArrayEquals($expectedResult, $errors);
  }

}
