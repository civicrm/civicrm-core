<?php
require_once 'CiviTest/CiviCaseTestCase.php';

/**
 * Class CRM_Case_Form_TaskTest
 * @group headless
 */
class CRM_Case_Form_TaskTest extends CiviCaseTestCase {

  /**
   * Test the order of the corresponding ids in the output matches the order
   * of the ids in the input, i.e. case_contacts matches cases.
   *
   * @param $input array
   * @param $selected_search_results array
   * @param $expected array
   *
   * @dataProvider contactIDProvider
   */
  public function testSetContactIDs($input, $selected_search_results, $expected): void {
    $this->createCaseContacts($input);
    $task = $this->getFormObject('CRM_Case_Form_Task');

    // This simulates the selection from the search results list. What we're
    // testing is that no matter what order the cases were created or what
    // mysql feels like doing today, the order of the retrieved contacts ends
    // up matching the order that the cases came in from search results.
    $task->_entityIds = $selected_search_results;

    $task->setContactIDs();
    $this->assertEquals($expected, $task->_contactIds);
  }

  private function createCaseContacts($caseContacts) {
    foreach ($caseContacts as $caseContact) {
      // The corresponding case needs to exist. We don't care about most of
      // its values, just the case_id.
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_case (id, start_date, case_type_id, details, status_id, created_date) VALUES ({$caseContact['case_id']}, '2019-01-01', 1, '', 1, NOW())");
      // Ditto the contact
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_contact (id, contact_type, created_date, image_URL, sort_name) VALUES ({$caseContact['contact_id']}, 'Individual', NOW(), '', 'Contact {$caseContact['contact_id']}')");
      // And now case_contact
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_case_contact (contact_id, case_id) VALUES ({$caseContact['contact_id']}, {$caseContact['case_id']})");
    }
  }

  /**
   * Data provider for testSetContactIDs
   * @return array
   */
  public function contactIDProvider() {
    return [
      // empty input
      [[], [], []],
      // one input
      [
        [
          ['contact_id' => 7, 'case_id' => 8],
        ],
        // the case id's in the order they were passed in from search results
        [8],
        // the retrieved contacts listed in the expected order
        [7],
      ],
      // some input
      [
        [
          ['contact_id' => 7, 'case_id' => 8],
          ['contact_id' => 9, 'case_id' => 4],
          ['contact_id' => 5, 'case_id' => 6],
        ],
        [8, 4, 6],
        [7, 9, 5],
      ],
      [
        [
          ['contact_id' => 7, 'case_id' => 8],
          ['contact_id' => 9, 'case_id' => 4],
          ['contact_id' => 5, 'case_id' => 6],
        ],
        [4, 8, 6],
        [9, 7, 5],
      ],

      // some more input
      [
        [
          ['contact_id' => 19, 'case_id' => 12],
          ['contact_id' => 9, 'case_id' => 8],
          ['contact_id' => 25, 'case_id' => 45],
          ['contact_id' => 20, 'case_id' => 33],
        ],
        [12, 8, 45, 33],
        [19, 9, 25, 20],
      ],
      [
        [
          ['contact_id' => 19, 'case_id' => 12],
          ['contact_id' => 9, 'case_id' => 8],
          ['contact_id' => 25, 'case_id' => 45],
          ['contact_id' => 20, 'case_id' => 33],
        ],
        [45, 8, 33, 12],
        [25, 9, 20, 19],
      ],
      [
        [
          ['contact_id' => 19, 'case_id' => 12],
          ['contact_id' => 9, 'case_id' => 8],
          ['contact_id' => 25, 'case_id' => 45],
          ['contact_id' => 20, 'case_id' => 33],
        ],
        [12, 33, 45, 8],
        [19, 20, 25, 9],
      ],
      [
        [
          ['contact_id' => 19, 'case_id' => 12],
          ['contact_id' => 9, 'case_id' => 8],
          ['contact_id' => 25, 'case_id' => 45],
          ['contact_id' => 20, 'case_id' => 33],
        ],
        [8, 33, 12, 45],
        [9, 20, 19, 25],
      ],
    ];
  }

  /**
   * Test that File On Case and its friends open the form without errors.
   *
   * @dataProvider fileOnCaseVariationProvider
   *
   * @param array $input
   */
  public function testOpenFileOnCaseForm($input) {
    // Create a case and an activity to use
    $client_id = $this->individualCreate([], 0, TRUE);
    $case = $this->createCase($client_id, $this->_loggedInUser);
    // create 2 cases since "move"/"copy" aren't available actions otherwise
    $case = $this->createCase($this->individualCreate([], 1, TRUE), $this->_loggedInUser);
    $activity_params = [
      'activity_type_id' => 'Inbound Email',
      'source_contact_id' => $client_id,
      'target_id' => $this->_loggedInUser,
      'subject' => $input['subject'] ?? NULL,
      'details' => 'test test test',
      'activity_date_time' => date('Ymdhis'),
    ];
    if ($input['variation_type'] !== 'file') {
      // For copy and move it doesn't make sense if the activity isn't a case
      // activity.
      $activity_params['case_id'] = $case->id;
    }
    $activity = $this->callAPISuccess('Activity', 'create', $activity_params);

    $form = new CRM_Case_Form_ActivityToCase();
    $form->controller = new CRM_Core_Controller_Simple('CRM_Case_Form_ActivityToCase', 'Case Thing');
    $_REQUEST['activityId'] = $activity['id'];
    $_REQUEST['fileOnCaseAction'] = $input['variation_type'];

    ob_start();
    $form->controller->_actions['display']->perform($form, 'display');
    $contents = ob_get_contents();
    ob_end_clean();

    // We're really just checking for form opening errors, but let's check
    // something while we're here.
    $this->assertStringContainsString('<tr class="crm-case-activitytocase-form-block-file_on_case_unclosed_case_id">', $contents);

    unset($_REQUEST['activityId']);
    unset($_REQUEST['fileOnCaseAction']);
  }

  /**
   * data provider for testFileOnCaseForm
   *
   * File On Case, copy to case, and move to case are all variations of the
   * same thing.
   */
  public function fileOnCaseVariationProvider() {
    return [
      'File On Case with subject' => [
        [
          'variation_type' => 'file',
          'subject' => 'test test test',
        ],
      ],
      'Copy To Case with subject' => [
        [
          'variation_type' => 'copy',
          'subject' => 'test test test',
        ],
      ],
      'Move To Case with subject' => [
        [
          'variation_type' => 'move',
          'subject' => 'test test test',
        ],
      ],
      'File On Case no subject' => [
        [
          'variation_type' => 'file',
          'subject' => NULL,
        ],
      ],
      'Copy To Case no subject' => [
        [
          'variation_type' => 'copy',
          'subject' => NULL,
        ],
      ],
      'Move To Case no subject' => [
        [
          'variation_type' => 'move',
          'subject' => NULL,
        ],
      ],
    ];
  }

}
