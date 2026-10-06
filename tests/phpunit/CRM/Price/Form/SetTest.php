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
 * Test class for CRM_Price_Form_Set.
 * @group headless
 */
class CRM_Price_Form_SetTest extends CiviUnitTestCase {

  /**
   * Price Set titles are stored html-ish-encoded (CRM_Utils_API_HTMLInputCoder),
   * so the form heading must decode them once rather than displaying the
   * encoded value, which downstream rendering would otherwise escape a
   * second time (dev/core#6812).
   */
  public function testEditTitleIsNotHtmlEncoded(): void {
    $priceSetID = $this->createTestEntity('PriceSet', [
      'title' => 'Test-Small (< 20 employees)',
      'name' => 'test_small_price_set',
      'extends' => CRM_Core_Component::getComponentID('CiviMember'),
    ])['id'];

    $form = $this->getTestForm('CRM_Price_Form_Set', [], [
      'sid' => $priceSetID,
      'action' => CRM_Core_Action::UPDATE,
    ])->processForm(\Civi\Test\FormWrapper::PREPROCESSED);

    $this->assertEquals('Edit Test-Small (< 20 employees)', $form->getTitle());
  }

}
