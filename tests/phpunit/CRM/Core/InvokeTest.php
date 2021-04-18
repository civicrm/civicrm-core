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
 * @group headless
 */
class CRM_Core_InvokeTest extends CiviUnitTestCase {

  public function tearDown(): void {
    $this->quickCleanup([
      'civicrm_contact',
      'civicrm_activity',
      'civicrm_activity_contact',
    ]);
    parent::tearDown();
  }

  /**
   * Test that no php errors come up invoking dashboard url for non-admins
   * Motivation: This currently fails on php 7.4 because of IDS and magicquotes.
   */
  public function testInvokeDashboardForNonAdmin(): void {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];

    $_SERVER['REQUEST_URI'] = 'civicrm/dashboard?reset=1';
    $_GET['q'] = 'civicrm/dashboard';

    $item = CRM_Core_Invoke::getItem(['civicrm/dashboard?reset=1']);
    ob_start();
    CRM_Core_Invoke::runItem($item);
    ob_end_clean();
  }

  /**
   * Test activity url rewrites
   * See also CRM_Case_BAO_CaseTest::testInvokeCaseActivity
   * @todo add test that does something similar but where user doesn't have
   * permission for the contact.
   * @dataProvider activityProvider
   * @param array $input
   * @param array $expected
   */
  public function testInvokeNonCaseActivity(array $input, array $expected) {
    $contact_id = $this->createLoggedInUser();
    if ($input['url_params']['action'] != 'add') {
      $activity = $this->callAPISuccess('Activity', 'create', [
        'source_contact_id' => $contact_id,
        'subject' => 'a subject',
        'activity_type_id' => 'Meeting',
      ]);
      $_GET['id'] = $_REQUEST['id'] = $activity['id'];
    }

    // set any other url params
    $_GET['reset'] = $_REQUEST['reset'] = '1';
    foreach ($input['url_params'] as $param => $value) {
      $_GET[$param] = $_REQUEST[$param] = $value;
    }

    $item = CRM_Core_Invoke::getItem([$input['q']]);

    // check the subset we care about
    $this->assertEquals($expected['item'], array_intersect_key($item, $expected['item']));

    // For regular activities there's just contact id to check
    if ($expected['check_contact']) {
      $this->assertEquals($contact_id, CRM_Utils_Request::retrieve('cid', 'String'));
    }
    else {
      $this->assertNull(CRM_Utils_Request::retrieve('cid', 'String'));
    }
  }

  /**
   * dataprovider for testInvokeNonCaseActivity
   * @return array
   */
  public function activityProvider(): array {
    // It turns out this is always the same for all actions
    $expectedItem = [
      'path' => 'civicrm/activity',
      'path_arguments' => 'action=add&context=standalone',
      'title' => 'New Activity',
      'page_callback' => 'CRM_Activity_Form_Activity',
    ];
    return [
      0 => [
        'input' => [
          'q' => 'civicrm/activity',
          'url_params' => ['action' => 'view'],
        ],
        'expected' => [
          'item' => $expectedItem,
          'check_contact' => TRUE,
        ],
      ],
      1 => [
        'input' => [
          'q' => 'civicrm/activity',
          'url_params' => ['action' => 'update'],
        ],
        'expected' => [
          'item' => $expectedItem,
          'check_contact' => TRUE,
        ],
      ],
      2 => [
        'input' => [
          'q' => 'civicrm/activity',
          'url_params' => ['action' => 'delete'],
        ],
        'expected' => [
          'item' => $expectedItem,
          'check_contact' => TRUE,
        ],
      ],
      3 => [
        'input' => [
          'q' => 'civicrm/activity',
          'url_params' => ['action' => 'add', 'context' => 'standalone'],
        ],
        'expected' => [
          'item' => $expectedItem,
          'check_contact' => FALSE,
        ],
      ],
    ];
  }

}
