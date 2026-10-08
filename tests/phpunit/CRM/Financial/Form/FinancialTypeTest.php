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

use Civi\Test\FormWrapper;

/**
 * @group headless
 */
class CRM_Financial_Form_FinancialTypeTest extends CiviUnitTestCase {

  public function tearDown(): void {
    \Civi\Api4\FinancialType::delete(FALSE)
      ->addWhere('name', '=', 'Test Financial Type')
      ->execute();
    parent::tearDown();
  }

  /**
   * Editing a Financial Type must not error.
   *
   * CRM_Financial_BAO_FinancialType has no `retrieve()` method - it is one
   * of the entities that has moved to being defined purely via the schema /
   * APIv4, with CRM_Financial_DAO_FinancialType retained only as a property
   * placeholder (dev/core#6835).
   *
   * @throws \CRM_Core_Exception
   */
  public function testEditDoesNotError(): void {
    $financialType = $this->createTestEntity('FinancialType', [
      'name' => 'Test Financial Type',
      'label' => 'Test Financial Type',
      'is_active' => TRUE,
    ], 'test6835');

    $form = $this->getTestForm('CRM_Financial_Form_FinancialType', [], [
      'id' => $financialType['id'],
      'action' => 'update',
    ]);
    $form->processForm(FormWrapper::BUILT);

    $this->assertEquals('Test Financial Type', $form->getDefaultValues()['label']);
  }

}
