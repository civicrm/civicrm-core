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
 * File for the CiviCRM APIv3 job functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Job
 *
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * @version $Id: Job.php 30879 2010-11-22 15:45:55Z shot $
 *
 */

/**
 * Tests for job api where custom data is involved.
 *
 * Set up for custom data won't work with useTransaction so these are not
 * compatible with the other job test class.
 *
 * @group headless
 */
class api_v3_JobTestCustomDataTest extends CiviUnitTestCase {
  protected $_apiversion = 3;

  public $_entity = 'Job';

  /**
   * Custom group ID.
   *
   * @var int
   */
  public $customFieldID = NULL;

  /**
   * ID of a custom field of integer type.
   *
   * @var int
   */
  public $customIntFieldID = NULL;

  /**
   * ID of a custom field of integer type.
   *
   * @var int
   */
  public $customBoolFieldID = NULL;

  /**
   * ID of a custom field of integer type.
   *
   * @var int
   */
  public $customStringCheckboxID = NULL;

  /**
   * Custom Field ID.
   *
   * @var int
   */
  public $customGroupID = NULL;

  public function setUp(): void {
    parent::setUp();
    $customGroup = $this->customGroupCreate();
    $this->customGroupID = $customGroup['id'];
    $customField = $this->customFieldCreate([
      'custom_group_id' => $this->customGroupID,
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'default_value' => '',
    ]);
    $this->customFieldID = $customField['id'];
    $customField = $this->customFieldCreate([
      'custom_group_id' => $this->customGroupID,
      'data_type' => 'Integer',
      'html_type' => 'Text',
      'default_value' => '',
      'label' => 'Int Field',
    ]);
    $this->customIntFieldID = $customField['id'];
    $customField = $this->customFieldCreate([
      'custom_group_id' => $this->customGroupID,
      'data_type' => 'Boolean',
      'html_type' => 'Radio',
      'default_value' => '',
      'label' => 'Radio Field',
    ]);
    $this->customBoolFieldID = $customField['id'];
    $customField = $this->customFieldCreate([
      'custom_group_id' => $this->customGroupID,
      'data_type' => 'String',
      'html_type' => 'CheckBox',
      'default_value' => NULL,
      'label' => 'checkbox Field',
      'option_values' => ['black' => 'black', 'white' => 'white'],
    ]);
    $this->customStringCheckboxID = $customField['id'];
  }

  /**
   * Cleanup after tests.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $this->quickCleanup(['civicrm_contact'], TRUE);
    parent::tearDown();
  }

  /**
   * Test the batch merge does not bork on custom date fields.
   *
   * @dataProvider getCheckboxData
   *
   * Test CRM-19074 checkbox field handling.
   *
   * Given a custom field with 2 checkboxes (black & white) possible values are:
   * 1) SEPARATOR + black + SEPARATOR
   * 2) SEPARATOR + white + SEPARATOR
   * 3) SEPARATOR + black + SEPARATOR + white + SEPARATOR
   * 3) '' (ie empty string means both set to 0)
   * 4) NULL (ie not set)
   *  - in safe mode NULL is not a conflict with any option but the other
   *   combos are a conflict.
   *
   * @param array $dataSet
   *
   */
  public function testBatchMergeCheckboxCustomFieldHandling(array $dataSet): void {
    $customFieldLabel = 'custom_' . $this->customStringCheckboxID;
    $contact1Params = is_array($dataSet['contacts'][0]) ? [$customFieldLabel => $dataSet['contacts'][0]] : [];
    $contact2Params = is_array($dataSet['contacts'][1]) ? [$customFieldLabel => $dataSet['contacts'][1]] : [];
    $contactID = $this->individualCreate($contact1Params);
    $this->individualCreate($contact2Params);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', ['mode' => $dataSet['mode']]);
    $this->assertCount($dataSet['merged'], $result['values']['merged']);
    $this->assertCount($dataSet['skipped'], $result['values']['skipped']);
    $contact = $this->callAPISuccess('Contact', 'getsingle', ['id' => $contactID, 'return' => $customFieldLabel]);
    $this->assertEquals($dataSet['expected'], $contact[$customFieldLabel]);
  }

  /**
   * Get the various data combinations for a checkbox field.
   *
   * @return array
   */
  public static function getCheckboxData() {
    $data = [
      [
        'null_merges_with_set' => [
          'mode' => 'safe',
          'contacts' => [
            NULL,
            ['black'],
          ],
          'skipped' => 0,
          'merged' => 1,
          'expected' => ['black'],
        ],
      ],
      [
        'null_merges_with_set_reverse' => [
          'mode' => 'safe',
          'contacts' => [
            ['black'],
            NULL,
          ],
          'skipped' => 0,
          'merged' => 1,
          'expected' => ['black'],

        ],
      ],
      [
        'empty_conflicts_with_set' => [
          'mode' => 'safe',
          'contacts' => [
            ['white'],
            ['black'],
          ],
          'skipped' => 1,
          'merged' => 0,
          'expected' => ['white'],
        ],
      ],
      [
        'empty_conflicts_with_set' => [
          'mode' => 'aggressive',
          'contacts' => [
            ['white'],
            ['black'],
          ],
          'skipped' => 0,
          'merged' => 1,
          'expected' => ['white'],
        ],
      ],
    ];
    return $data;
  }

  /**
   * Test the batch merge does not bork on custom date fields.
   *
   * Test CRM-18674 date custom field handling.
   */
  public function testBatchMergeDateCustomFieldHandling(): void {
    $customFieldLabel = 'custom_' . $this->customFieldID;
    $contactID = $this->individualCreate();
    $this->individualCreate([$customFieldLabel => '2012-12-03']);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', []);
    $this->assertEquals(1, count($result['values']['merged']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', ['id' => $contactID, 'return' => $customFieldLabel]);
    $this->assertEquals('2012-12-03 00:00:00', $contact[$customFieldLabel]);
  }

  /**
   * Test the batch merge does not bork on custom date fields.
   *
   * Test CRM-18674 date custom field handling.
   */
  public function testBatchMergeDateCustomFieldHandlingIsView(): void {
    $this->customFieldCreate([
      'label' => 'OnlyView',
      'custom_group_id' => $this->customGroupID,
      'is_view' => 1,
    ]);
    $customFieldLabel = 'custom_' . $this->customFieldID;
    $contactID = $this->individualCreate();
    $this->individualCreate([$customFieldLabel => '2012-11-03']);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', []);
    $this->assertEquals(1, count($result['values']['merged']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', ['id' => $contactID, 'return' => $customFieldLabel]);
    $this->assertEquals('2012-11-03 00:00:00', $contact[$customFieldLabel]);
  }

  /**
   * Check we get a conflict on the custom field.
   */
  public function testBatchMergeDateCustomFieldConflict(): void {
    $customFieldLabel = 'custom_' . $this->customFieldID;
    $contactID = $this->individualCreate([$customFieldLabel => '2012-11-03']);
    $this->individualCreate([$customFieldLabel => '2013-11-03']);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', []);
    $this->assertEquals(0, count($result['values']['merged']));
    $this->assertEquals(1, count($result['values']['skipped']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', ['id' => $contactID, 'return' => $customFieldLabel]);
    $this->assertEquals('2012-11-03 00:00:00', $contact[$customFieldLabel]);
  }

  /**
   * Check we get a conflict on the custom field.
   */
  public function testBatchMergeDateCustomFieldNoConflict(): void {
    $customFieldLabel = 'custom_' . $this->customFieldID;
    $contactID = $this->individualCreate([$customFieldLabel => '2012-11-03']);
    $this->individualCreate([$customFieldLabel => '2012-11-03']);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', []);
    $this->assertEquals(1, count($result['values']['merged']));
    $this->assertEquals(0, count($result['values']['skipped']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', ['id' => $contactID, 'return' => $customFieldLabel]);
    $this->assertEquals('2012-11-03 00:00:00', $contact[$customFieldLabel]);
  }

  /**
   * Check we get a no conflict on the custom field & integer merges.
   */
  public function testBatchMergeIntCustomFieldNoConflict(): void {
    $customFieldLabel = 'custom_' . $this->customIntFieldID;
    $contactID = $this->individualCreate([]);
    $this->individualCreate([$customFieldLabel => 20]);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', []);
    $this->assertEquals(1, count($result['values']['merged']));
    $this->assertEquals(0, count($result['values']['skipped']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', ['id' => $contactID, 'return' => $customFieldLabel]);
    $this->assertEquals(20, $contact[$customFieldLabel]);
  }

  /**
   * Check we get a conflict on the integer custom field.
   */
  public function testBatchMergeIntCustomFieldConflict(): void {
    $customFieldLabel = 'custom_' . $this->customIntFieldID;
    $contactID = $this->individualCreate([$customFieldLabel => 20]);
    $this->individualCreate([$customFieldLabel => 1]);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', []);
    $this->assertEquals(0, count($result['values']['merged']));
    $this->assertEquals(1, count($result['values']['skipped']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', ['id' => $contactID, 'return' => $customFieldLabel]);
    $this->assertEquals(20, $contact[$customFieldLabel]);
  }

  /**
   * Check we get a conflict on the integer custom field when the conflicted field is 0.
   */
  public function testBatchMergeIntCustomFieldConflictZero(): void {
    $customFieldLabel = 'custom_' . $this->customIntFieldID;
    $contactID = $this->individualCreate([$customFieldLabel => 0]);
    $this->individualCreate([$customFieldLabel => 20]);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', []);
    $this->assertEquals(0, count($result['values']['merged']));
    $this->assertEquals(1, count($result['values']['skipped']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', ['id' => $contactID, 'return' => $customFieldLabel]);
    $this->assertEquals(0, $contact[$customFieldLabel]);
  }

  /**
   * Using the api with check perms set to off, make sure custom data is merged.
   *
   * Test CRM-18674 date custom field handling.
   */
  public function testBatchMergeDateCustomFieldConflictAndNoCheckPerms(): void {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'edit my contact'];
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_cache");
    Civi::rebuild(['system' => TRUE])->execute();
    $customFieldLabel = 'custom_' . $this->customFieldID;
    $contactID = $this->individualCreate([$customFieldLabel => '2012-11-03']);
    $this->individualCreate([$customFieldLabel => '2013-11-03']);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', ['check_permissions' => 0]);
    $this->assertEquals(0, count($result['values']['merged']));
    $this->assertEquals(1, count($result['values']['skipped']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', ['id' => $contactID, 'return' => $customFieldLabel]);
    $this->assertEquals('2012-11-03 00:00:00', $contact[$customFieldLabel]);
  }

  /**
   * Using the api with check perms set to off, make sure custom data is merged.
   *
   * Test CRM-18674 date custom field handling.
   */
  public function testBatchMergeDateCustomFieldNoConflictAndNoCheckPerms(): void {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'edit my contact'];
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_cache");
    Civi::rebuild(['system' => TRUE])->execute();
    $customFieldLabel = 'custom_' . $this->customFieldID;
    $contactID = $this->individualCreate();
    $this->individualCreate([$customFieldLabel => '2013-11-03']);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', ['check_permissions' => 0]);
    $this->assertEquals(1, count($result['values']['merged']));
    $this->assertEquals(0, count($result['values']['skipped']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', ['id' => $contactID, 'return' => $customFieldLabel]);
    $this->assertEquals('2013-11-03 00:00:00', $contact[$customFieldLabel]);
  }

  /**
   * Using the api with check perms set to off, make sure custom data is merged.
   *
   * Test CRM-19113 custom data lost when permissions in play.
   */
  public function testBatchMergeIntCustomFieldNoConflictAndNoCheckPerms(): void {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'edit my contact'];
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_cache");
    Civi::rebuild(['system' => TRUE])->execute();
    $customFieldLabel = 'custom_' . $this->customIntFieldID;
    $contactID = $this->individualCreate(['custom_' . $this->customBoolFieldID => 1]);
    $this->individualCreate([$customFieldLabel => 1, 'custom_' . $this->customBoolFieldID => 1]);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', ['check_permissions' => 0]);
    $this->assertEquals(1, count($result['values']['merged']));
    $this->assertEquals(0, count($result['values']['skipped']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', [
      'id' => $contactID,
      'return' => [$customFieldLabel, 'custom_' . $this->customBoolFieldID],
    ]);
    $this->assertEquals(1, $contact[$customFieldLabel]);
    $this->assertEquals(1, $contact['custom_' . $this->customBoolFieldID]);
  }

  /**
   * Check we get a conflict on the customs field when the data conflicts for booleans.
   */
  public function testBatchMergeCustomFieldConflicts(): void {
    $this->individualCreate(['custom_' . $this->customBoolFieldID => 0]);
    $this->individualCreate(['custom_' . $this->customBoolFieldID => 1]);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', []);
    $this->assertEquals(0, count($result['values']['merged']));
    $this->assertEquals(1, count($result['values']['skipped']));
  }

  /**
   * Check we get a conflict on the customs field when the data conflicts for booleans (reverse).
   */
  public function testBatchMergeCustomFieldConflictsReverse(): void {
    $this->individualCreate(['custom_' . $this->customBoolFieldID => 1]);
    $this->individualCreate(['custom_' . $this->customBoolFieldID => 0]);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', []);
    $this->assertEquals(0, count($result['values']['merged']));
    $this->assertEquals(1, count($result['values']['skipped']));
  }

  /**
   * Check we get a conflict on the customs field when the data conflicts for booleans (reverse).
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchMergeCustomFieldNoConflictsOneBlank(): void {
    $this->individualCreate(['custom_' . $this->customBoolFieldID => 1]);
    $this->individualCreate();
    $result = $this->callAPISuccess('Job', 'process_batch_merge', []);
    $this->assertCount(1, $result['values']['merged']);
    $this->assertCount(0, $result['values']['skipped']);
  }

  /**
   * Check we get a conflict on the customs field when the data conflicts for booleans (reverse).
   *
   * @throws \CRM_Core_Exception
   */
  public function testBatchMergeCustomFieldNoConflictsOneBlankReverse(): void {
    $contactID = $this->individualCreate();
    $this->individualCreate(['custom_' . $this->customBoolFieldID => 1]);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', []);
    $this->assertCount(1, $result['values']['merged']);
    $this->assertCount(0, $result['values']['skipped']);
    $this->assertEquals(1, $this->callAPISuccessGetValue('Contact', ['id' => $contactID, 'return' => 'custom_' . $this->customBoolFieldID]));
  }

}
