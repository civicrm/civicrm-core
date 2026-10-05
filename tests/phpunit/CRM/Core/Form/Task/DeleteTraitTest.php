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

use Civi\Test\Api4TestTrait;

require_once 'CiviTest/CiviCaseTestCase.php';

/**
 * Test class for CRM_Core_Form_Task_DeleteTrait.
 *
 * @group headless
 */
class CRM_Core_Form_Task_DeleteTraitTest extends CiviCaseTestCase {

  use Api4TestTrait;

  public function setUp(): void {
    \CRM_Core_BAO_ConfigSetting::enableAllComponents();
    parent::setUp();
  }

  /**
   * Data provider for task delete forms using DeleteTrait.
   *
   * @return array
   */
  public static function deleteTaskFormDataProvider(): array {
    return [
      'Membership' => [
        'formClass' => 'CRM_Member_Form_Task_Delete',
        'entityName' => 'Membership',
        'property' => '_memberIds',
      ],
      'Pledge' => [
        'formClass' => 'CRM_Pledge_Form_Task_Delete',
        'entityName' => 'Pledge',
        'property' => '_pledgeIds',
      ],
      'Activity' => [
        'formClass' => 'CRM_Activity_Form_Task_Delete',
        'entityName' => 'Activity',
        'property' => '_activityHolderIds',
      ],
      'Case' => [
        'formClass' => 'CRM_Case_Form_Task_Delete',
        'entityName' => 'Case',
        'property' => '_entityIds',
      ],
    ];
  }

  /**
   * Test delete task form functionality across entity forms using DeleteTrait.
   *
   * @dataProvider deleteTaskFormDataProvider
   */
  public function testDeleteTaskForm(string $formClass, string $entityName, string $property, array $createValues = []): void {
    $record = $this->createTestRecord($entityName, $createValues);
    $recordId = $record['id'];

    /** @var CRM_Core_Form $form */
    $form = $this->getFormObject($formClass);
    if (property_exists($form, '_moveToTrash')) {
      $form->_moveToTrash = FALSE;
    }
    \Civi\Test\Invasive::set([$form, $property], [$recordId]);
    $form->postProcess();

    $check = civicrm_api4($entityName, 'get', ['where' => [['id', '=', $recordId]]]);
    $this->assertCount(0, $check);
  }

}
