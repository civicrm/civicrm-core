<?php
require_once 'CiviTest/CiviCaseTestCase.php';

/**
 * Class CRM_Case_Form_TaskTest
 * @group headless
 */
class CRM_Case_Form_TaskTest extends CiviCaseTestCase {

  public function setUp() {
    parent::setUp();
    $this->quickCleanup(['civicrm_case_contact', 'civicrm_case', 'civicrm_contact']);
  }

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
  public function testSetContactIDs($input, $selected_search_results, $expected) {
    $this->createCaseContacts($input);
    $task = new CRM_Case_Form_Task();

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
          ['contact_id' => 1, 'case_id' => 2],
        ],
        // the case id's in the order they were passed in from search results
        [2],
        // the retrieved contacts listed in the expected order
        [1],
      ],
      // some input
      [
        [
          ['contact_id' => 1, 'case_id' => 2],
          ['contact_id' => 3, 'case_id' => 4],
          ['contact_id' => 5, 'case_id' => 6],
        ],
        [2, 4, 6],
        [1, 3, 5],
      ],
      [
        [
          ['contact_id' => 1, 'case_id' => 2],
          ['contact_id' => 3, 'case_id' => 4],
          ['contact_id' => 5, 'case_id' => 6],
        ],
        [4, 2, 6],
        [3, 1, 5],
      ],

      // some more input
      [
        [
          ['contact_id' => 17, 'case_id' => 12],
          ['contact_id' => 3, 'case_id' => 8],
          ['contact_id' => 25, 'case_id' => 45],
          ['contact_id' => 18, 'case_id' => 33],
        ],
        [12, 8, 45, 33],
        [17, 3, 25, 18],
      ],
      [
        [
          ['contact_id' => 17, 'case_id' => 12],
          ['contact_id' => 3, 'case_id' => 8],
          ['contact_id' => 25, 'case_id' => 45],
          ['contact_id' => 18, 'case_id' => 33],
        ],
        [45, 8, 33, 12],
        [25, 3, 18, 17],
      ],
      [
        [
          ['contact_id' => 17, 'case_id' => 12],
          ['contact_id' => 3, 'case_id' => 8],
          ['contact_id' => 25, 'case_id' => 45],
          ['contact_id' => 18, 'case_id' => 33],
        ],
        [12, 33, 45, 8],
        [17, 18, 25, 3],
      ],
      [
        [
          ['contact_id' => 17, 'case_id' => 12],
          ['contact_id' => 3, 'case_id' => 8],
          ['contact_id' => 25, 'case_id' => 45],
          ['contact_id' => 18, 'case_id' => 33],
        ],
        [8, 33, 12, 45],
        [3, 18, 17, 25],
      ],
    ];
  }

}
