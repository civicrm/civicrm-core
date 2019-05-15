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
 * File for the CiviCRM APIv3 job functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Job
 *
 * @copyright CiviCRM LLC (c) 2004-2019
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

  public function setUp() {
    parent::setUp();
    $customGroup = $this->customGroupCreate();
    $this->customGroupID = $customGroup['id'];
    $customField = $this->customFieldCreate(array(
      'custom_group_id' => $this->customGroupID,
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'default_value' => '',
    ));
    $this->customFieldID = $customField['id'];
    $customField = $this->customFieldCreate(array(
      'custom_group_id' => $this->customGroupID,
      'data_type' => 'Integer',
      'html_type' => 'Text',
      'default_value' => '',
      'label' => 'Int Field',
    ));
    $this->customIntFieldID = $customField['id'];
    $customField = $this->customFieldCreate(array(
      'custom_group_id' => $this->customGroupID,
      'data_type' => 'Boolean',
      'html_type' => 'Radio',
      'default_value' => '',
      'label' => 'Radio Field',
    ));
    $this->customBoolFieldID = $customField['id'];
    $customField = $this->customFieldCreate(array(
      'custom_group_id' => $this->customGroupID,
      'data_type' => 'String',
      'html_type' => 'CheckBox',
      'default_value' => NULL,
      'label' => 'checkbox Field',
      'option_values' => array('black' => 'black', 'white' => 'white'),
    ));
    $this->customStringCheckboxID = $customField['id'];
  }

  /**
   * Cleanup after tests.
   */
  public function tearDown() {
    $this->quickCleanup(array('civicrm_contact'), TRUE);
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
   */
  public function testBatchMergeCheckboxCustomFieldHandling($dataSet) {
    $customFieldLabel = 'custom_' . $this->customStringCheckboxID;
    $contact1Params = is_array($dataSet['contacts'][0]) ? array($customFieldLabel => $dataSet['contacts'][0]) : array();
    $contact2Params = is_array($dataSet['contacts'][1]) ? array($customFieldLabel => $dataSet['contacts'][1]) : array();
    $contactID = $this->individualCreate($contact1Params);
    $this->individualCreate($contact2Params);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array('mode' => $dataSet['mode']));
    $this->assertEquals($dataSet['merged'], count($result['values']['merged']));
    $this->assertEquals($dataSet['skipped'], count($result['values']['skipped']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', array('id' => $contactID, 'return' => $customFieldLabel));
    $this->assertEquals($dataSet['expected'], $contact[$customFieldLabel]);
  }

  /**
   * Get the various data combinations for a checkbox field.
   *
   * @return array
   */
  public function getCheckboxData() {
    $data = array(
      array(
        'null_merges_with_set' => array(
          'mode' => 'safe',
          'contacts' => array(
            NULL,
            array('black'),
          ),
          'skipped' => 0,
          'merged' => 1,
          'expected' => array('black'),
        ),
      ),
      array(
        'null_merges_with_set_reverse' => array(
          'mode' => 'safe',
          'contacts' => array(
            array('black'),
            NULL,
          ),
          'skipped' => 0,
          'merged' => 1,
          'expected' => array('black'),

        ),
      ),
      array(
        'empty_conflicts_with_set' => array(
          'mode' => 'safe',
          'contacts' => array(
            array('white'),
            array('black'),
          ),
          'skipped' => 1,
          'merged' => 0,
          'expected' => array('white'),
        ),
      ),
      array(
        'empty_conflicts_with_set' => array(
          'mode' => 'aggressive',
          'contacts' => array(
            array('white'),
            array('black'),
          ),
          'skipped' => 0,
          'merged' => 1,
          'expected' => array('white'),
        ),
      ),
    );
    return $data;
  }

  /**
   * Test the batch merge does not bork on custom date fields.
   *
   * Test CRM-18674 date custom field handling.
   */
  public function testBatchMergeDateCustomFieldHandling() {
    $customFieldLabel = 'custom_' . $this->customFieldID;
    $contactID = $this->individualCreate();
    $this->individualCreate(array($customFieldLabel => '2012-12-03'));
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array());
    $this->assertEquals(1, count($result['values']['merged']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', array('id' => $contactID, 'return' => $customFieldLabel));
    $this->assertEquals('2012-12-03 00:00:00', $contact[$customFieldLabel]);
  }

  /**
   * Test the batch merge does not bork on custom date fields.
   *
   * Test CRM-18674 date custom field handling.
   */
  public function testBatchMergeDateCustomFieldHandlingIsView() {
    $this->customFieldCreate(array(
      'label' => 'OnlyView',
      'custom_group_id' => $this->customGroupID,
      'is_view' => 1,
    ));
    $customFieldLabel = 'custom_' . $this->customFieldID;
    $contactID = $this->individualCreate();
    $this->individualCreate(array($customFieldLabel => '2012-11-03'));
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array());
    $this->assertEquals(1, count($result['values']['merged']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', array('id' => $contactID, 'return' => $customFieldLabel));
    $this->assertEquals('2012-11-03 00:00:00', $contact[$customFieldLabel]);
  }

  /**
   * Check we get a conflict on the custom field.
   */
  public function testBatchMergeDateCustomFieldConflict() {
    $customFieldLabel = 'custom_' . $this->customFieldID;
    $contactID = $this->individualCreate(array($customFieldLabel => '2012-11-03'));
    $this->individualCreate(array($customFieldLabel => '2013-11-03'));
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array());
    $this->assertEquals(0, count($result['values']['merged']));
    $this->assertEquals(1, count($result['values']['skipped']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', array('id' => $contactID, 'return' => $customFieldLabel));
    $this->assertEquals('2012-11-03 00:00:00', $contact[$customFieldLabel]);
  }

  /**
   * Check we get a conflict on the custom field.
   */
  public function testBatchMergeDateCustomFieldNoConflict() {
    $customFieldLabel = 'custom_' . $this->customFieldID;
    $contactID = $this->individualCreate(array($customFieldLabel => '2012-11-03'));
    $this->individualCreate(array($customFieldLabel => '2012-11-03'));
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array());
    $this->assertEquals(1, count($result['values']['merged']));
    $this->assertEquals(0, count($result['values']['skipped']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', array('id' => $contactID, 'return' => $customFieldLabel));
    $this->assertEquals('2012-11-03 00:00:00', $contact[$customFieldLabel]);
  }

  /**
   * Check we get a no conflict on the custom field & integer merges.
   */
  public function testBatchMergeIntCustomFieldNoConflict() {
    $customFieldLabel = 'custom_' . $this->customIntFieldID;
    $contactID = $this->individualCreate(array());
    $this->individualCreate(array($customFieldLabel => 20));
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array());
    $this->assertEquals(1, count($result['values']['merged']));
    $this->assertEquals(0, count($result['values']['skipped']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', array('id' => $contactID, 'return' => $customFieldLabel));
    $this->assertEquals(20, $contact[$customFieldLabel]);
  }

  /**
   * Check we get a conflict on the integer custom field.
   */
  public function testBatchMergeIntCustomFieldConflict() {
    $customFieldLabel = 'custom_' . $this->customIntFieldID;
    $contactID = $this->individualCreate(array($customFieldLabel => 20));
    $this->individualCreate(array($customFieldLabel => 1));
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array());
    $this->assertEquals(0, count($result['values']['merged']));
    $this->assertEquals(1, count($result['values']['skipped']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', array('id' => $contactID, 'return' => $customFieldLabel));
    $this->assertEquals(20, $contact[$customFieldLabel]);
  }

  /**
   * Check we get a conflict on the integer custom field when the conflicted field is 0.
   */
  public function testBatchMergeIntCustomFieldConflictZero() {
    $customFieldLabel = 'custom_' . $this->customIntFieldID;
    $contactID = $this->individualCreate(array($customFieldLabel => 0));
    $this->individualCreate(array($customFieldLabel => 20));
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array());
    $this->assertEquals(0, count($result['values']['merged']));
    $this->assertEquals(1, count($result['values']['skipped']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', array('id' => $contactID, 'return' => $customFieldLabel));
    $this->assertEquals(0, $contact[$customFieldLabel]);
  }

  /**
   * Using the api with check perms set to off, make sure custom data is merged.
   *
   * Test CRM-18674 date custom field handling.
   */
  public function testBatchMergeDateCustomFieldConflictAndNoCheckPerms() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM', 'edit my contact');
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_cache");
    CRM_Utils_System::flushCache();
    $customFieldLabel = 'custom_' . $this->customFieldID;
    $contactID = $this->individualCreate(array($customFieldLabel => '2012-11-03'));
    $this->individualCreate(array($customFieldLabel => '2013-11-03'));
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array('check_permissions' => 0));
    $this->assertEquals(0, count($result['values']['merged']));
    $this->assertEquals(1, count($result['values']['skipped']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', array('id' => $contactID, 'return' => $customFieldLabel));
    $this->assertEquals('2012-11-03 00:00:00', $contact[$customFieldLabel]);
  }

  /**
   * Using the api with check perms set to off, make sure custom data is merged.
   *
   * Test CRM-18674 date custom field handling.
   */
  public function testBatchMergeDateCustomFieldNoConflictAndNoCheckPerms() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM', 'edit my contact');
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_cache");
    CRM_Utils_System::flushCache();
    $customFieldLabel = 'custom_' . $this->customFieldID;
    $contactID = $this->individualCreate();
    $this->individualCreate(array($customFieldLabel => '2013-11-03'));
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array('check_permissions' => 0));
    $this->assertEquals(1, count($result['values']['merged']));
    $this->assertEquals(0, count($result['values']['skipped']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', array('id' => $contactID, 'return' => $customFieldLabel));
    $this->assertEquals('2013-11-03 00:00:00', $contact[$customFieldLabel]);
  }

  /**
   * Using the api with check perms set to off, make sure custom data is merged.
   *
   * Test CRM-19113 custom data lost when permissions in play.
   */
  public function testBatchMergeIntCustomFieldNoConflictAndNoCheckPerms() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM', 'edit my contact');
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_cache");
    CRM_Utils_System::flushCache();
    $customFieldLabel = 'custom_' . $this->customIntFieldID;
    $contactID = $this->individualCreate(array('custom_' . $this->customBoolFieldID => 1));
    $this->individualCreate(array($customFieldLabel => 1, 'custom_' . $this->customBoolFieldID => 1));
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array('check_permissions' => 0));
    $this->assertEquals(1, count($result['values']['merged']));
    $this->assertEquals(0, count($result['values']['skipped']));
    $contact = $this->callAPISuccess('Contact', 'getsingle', array(
      'id' => $contactID,
      'return' => array($customFieldLabel, 'custom_' . $this->customBoolFieldID),
    ));
    $this->assertEquals(1, $contact[$customFieldLabel]);
    $this->assertEquals(1, $contact['custom_' . $this->customBoolFieldID]);
  }

  /**
   * Check we get a conflict on the customs field when the data conflicts for booleans.
   */
  public function testBatchMergeCustomFieldConflicts() {
    $this->individualCreate(array('custom_' . $this->customBoolFieldID => 0));
    $this->individualCreate(array('custom_' . $this->customBoolFieldID => 1));
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array());
    $this->assertEquals(0, count($result['values']['merged']));
    $this->assertEquals(1, count($result['values']['skipped']));
  }

  /**
   * Check we get a conflict on the customs field when the data conflicts for booleans (reverse).
   */
  public function testBatchMergeCustomFieldConflictsReverse() {
    $this->individualCreate(array('custom_' . $this->customBoolFieldID => 1));
    $this->individualCreate(array('custom_' . $this->customBoolFieldID => 0));
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array());
    $this->assertEquals(0, count($result['values']['merged']));
    $this->assertEquals(1, count($result['values']['skipped']));
  }

  /**
   * Check we get a conflict on the customs field when the data conflicts for booleans (reverse).
   */
  public function testBatchMergeCustomFieldConflictsOneBlank() {
    $this->individualCreate(array('custom_' . $this->customBoolFieldID => 1));
    $this->individualCreate();
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array());
    $this->assertEquals(1, count($result['values']['merged']));
    $this->assertEquals(0, count($result['values']['skipped']));
  }

  /**
   * Check we get a conflict on the customs field when the data conflicts for booleans (reverse).
   */
  public function testBatchMergeCustomFieldConflictsOneBlankReverse() {
    $this->individualCreate();
    $this->individualCreate(array('custom_' . $this->customBoolFieldID => 1));
    $result = $this->callAPISuccess('Job', 'process_batch_merge', array());
    $this->assertEquals(1, count($result['values']['merged']));
    $this->assertEquals(0, count($result['values']['skipped']));
  }

}
