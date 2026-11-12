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
 * Test class for CRM_Core_BAO_Query.
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Core_BAO_QueryTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    foreach (['viewable', 'hidden'] as $identifier) {
      $customGroup = $this->createTestEntity('CustomGroup', [
        'title' => "Activity $identifier group",
        'extends' => 'Activity',
        'style' => 'Inline',
      ], $identifier);
      $this->createTestEntity('CustomField', [
        'custom_group_id' => $customGroup['id'],
        'label' => "Activity $identifier field",
        'html_type' => 'Text',
        'data_type' => 'String',
        'is_searchable' => TRUE,
      ], $identifier);
    }
    $this->createLoggedInUser();
  }

  public function tearDown(): void {
    $this->quickCleanup([], TRUE);
    parent::tearDown();
  }

  /**
   * Search panes only offer the custom groups the user may view.
   */
  public function testAddCustomFormFieldsOnlyAddsViewableGroups(): void {
    $this->setPermissions(['access CiviCRM']);
    $this->hookClass->setHook('civicrm_aclGroup', [$this, 'aclGroupHookViewOnlyViewableGroup']);

    $form = $this->getFormWithActivityCustomFields();

    $this->assertSame([$this->getTestEntityID('CustomGroup', 'viewable')], array_keys($form->getTemplateVars('activityGroupTree')));
    $this->assertTrue($form->elementExists('custom_' . $this->getTestEntityID('CustomField', 'viewable')));
    $this->assertFalse($form->elementExists('custom_' . $this->getTestEntityID('CustomField', 'hidden')));
  }

  /**
   * A user with 'access all custom data' is offered every custom group.
   */
  public function testAddCustomFormFieldsAddsAllGroupsWithAccessAllCustomData(): void {
    $this->setPermissions(['access CiviCRM', 'access all custom data']);

    $form = $this->getFormWithActivityCustomFields();

    $expectedGroupIDs = [
      $this->getTestEntityID('CustomGroup', 'viewable'),
      $this->getTestEntityID('CustomGroup', 'hidden'),
    ];
    $this->assertEqualsCanonicalizing($expectedGroupIDs, array_keys($form->getTemplateVars('activityGroupTree')));
    $this->assertTrue($form->elementExists('custom_' . $this->getTestEntityID('CustomField', 'viewable')));
    $this->assertTrue($form->elementExists('custom_' . $this->getTestEntityID('CustomField', 'hidden')));
  }

  /**
   * Implements hook_civicrm_aclGroup().
   *
   * Lets the user view, but not edit, the 'viewable' custom group.
   */
  public function aclGroupHookViewOnlyViewableGroup($type, $contactID, $tableName, &$allGroups, &$currentGroups): void {
    if ($tableName === 'civicrm_custom_group' && (int) $type === CRM_Core_Permission::VIEW) {
      $currentGroups = [$this->getTestEntityID('CustomGroup', 'viewable')];
    }
  }

  private function getFormWithActivityCustomFields(): CRM_Activity_Form_Search {
    Civi::$statics['CRM_ACL_BAO_ACL'] = [];
    /** @var CRM_Activity_Form_Search $form */
    $form = $this->getFormObject('CRM_Activity_Form_Search');
    CRM_Core_BAO_Query::addCustomFormFields($form, ['Activity']);
    return $form;
  }

}
