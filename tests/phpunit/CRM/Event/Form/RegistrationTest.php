<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
class CRM_Event_Form_RegistrationTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }


  /**
   * CRM-19626 - Test validatePriceSet() function
   */
  public function testValidatePriceSet() {
    $form = new CRM_Event_Form_Registration();
    $form->controller = new CRM_Core_Controller();

    $feeAmt = 100;
    $priceSetId = $this->eventPriceSetCreate($feeAmt);
    $priceSet = current(CRM_Price_BAO_PriceSet::getSetDetail($priceSetId));
    $form->_feeBlock = $priceSet['fields'];
    $priceField = $this->callAPISuccess('PriceField', 'get', array('price_set_id' => $priceSetId));
    $params = array(
      array(
        'priceSetId' => $priceSetId,
      ),
    );
    // Check empty values for price fields.
    foreach (array_keys($priceField['values']) as $fieldId) {
      $params[0]['price_' . $fieldId] = NULL;
    }
    $form->set('priceSetId', $priceSetId);
    $form->set('priceSet', $priceSet);
    $form->set('name', 'CRM_Event_Form_Registration');
    $errors = CRM_Event_Form_Registration::validatePriceSet($form, $params);

    //Assert the validation Error.
    $expectedResult = array(
      array(
        '_qf_default' => 'Select at least one option from Event Fee(s).',
      ),
    );
    $this->checkArrayEquals($expectedResult, $errors);
  }

}
