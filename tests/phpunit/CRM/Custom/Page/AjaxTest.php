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
 * Class CRM_Custom_Page_AJAXTest
 * @group headless
 */
class CRM_Custom_Page_AJAXTest extends CiviUnitTestCase {

  /**
   * Set up function.
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Test multi-record custom fields
   */
  public function testMultiRecordFieldList() {
    //create multi record custom group
    $ids = $this->CustomGroupMultipleCreateWithFields(['style' => 'Tab with table']);
    $params = [
      'contact_type' => 'Individual',
      'first_name' => 'Test',
      'last_name' => 'Contact',
    ];
    $customFields = $ids['custom_field_id'];
    $result = $this->callAPISuccess('contact', 'create', $params);
    $contactId = $result['id'];

    //enter values for custom fields
    $customParams = [
      "custom_{$customFields[0]}_-1" => "test value {$customFields[0]} one",
      "custom_{$customFields[0]}_-2" => "test value {$customFields[0]} two",
      "custom_{$customFields[0]}_-3" => "test value {$customFields[0]} three",
      "custom_{$customFields[1]}_-1" => "test value {$customFields[1]} one",
      "custom_{$customFields[1]}_-2" => "test value {$customFields[1]} two",
      "custom_{$customFields[1]}_-3" => "test value {$customFields[1]} three",
      "custom_{$customFields[2]}_-1" => "test value {$customFields[2]} one",
      "custom_{$customFields[2]}_-2" => "test value {$customFields[2]} two",
      "custom_{$customFields[2]}_-3" => "test value {$customFields[2]} three",
    ];
    CRM_Core_BAO_CustomValueTable::postProcess($customParams, "civicrm_contact", $contactId, NULL);

    $_GET = [
      'cid' => $contactId,
      'cgid' => $ids['custom_group_id'],
      'is_unit_test' => TRUE,
    ];
    $multiRecordFields = CRM_Custom_Page_AJAX::getMultiRecordFieldList();

    //check sorting
    foreach ($customFields as $fieldId) {
      $columnName = "field_{$fieldId}{$ids['custom_group_id']}_{$fieldId}";
      $_GET['columns'][] = [
        'data' => $columnName,
      ];
    }
    // get the results in descending order
    $_GET['order'] = [
      '0' => [
        'column' => 0,
        'dir' => 'desc',
      ],
    ];
    $sortedRecords = CRM_Custom_Page_AJAX::getMultiRecordFieldList();

    $this->assertEquals(3, $sortedRecords['recordsTotal']);
    $this->assertEquals(3, $multiRecordFields['recordsTotal']);
    foreach ($customFields as $fieldId) {
      $columnName = "field_{$fieldId}{$ids['custom_group_id']}_{$fieldId}";
      $this->assertEquals("test value {$fieldId} one", $multiRecordFields['data'][0][$columnName]['data']);
      $this->assertEquals("test value {$fieldId} two", $multiRecordFields['data'][1][$columnName]['data']);
      $this->assertEquals("test value {$fieldId} three", $multiRecordFields['data'][2][$columnName]['data']);

      // this should be sorted in descending order.
      $this->assertEquals("test value {$fieldId} two", $sortedRecords['data'][0][$columnName]['data']);
      $this->assertEquals("test value {$fieldId} three", $sortedRecords['data'][1][$columnName]['data']);
      $this->assertEquals("test value {$fieldId} one", $sortedRecords['data'][2][$columnName]['data']);
    }

    $sorted = FALSE;
    // sorted order result should be two, three, one
    $sortedCount = [1 => 2, 2 => 3, 3 => 1];
    foreach ([$multiRecordFields, $sortedRecords] as $records) {
      $count = 1;
      foreach ($records['data'] as $key => $val) {
        //check links for result sorted in descending order
        if ($sorted) {
          $initialCount = $count;
          $count = $sortedCount[$count];
        }
        // extract view, edit, copy links and assert the recId, cgcount.
        preg_match_all('!https?://\S+!', $val['action'], $matches);
        foreach ($matches[0] as $match) {
          $parts = parse_url($match);
          $parts['query'] = str_replace('&amp;', '&', $parts['query']);
          parse_str($parts['query'], $query);

          switch (trim($query['mode'], '"')) {
            case 'view':
              $this->assertEquals($count, $query['recId']);
              break;

            case 'edit':
              $this->assertEquals($count, $query['cgcount']);
              break;

            case 'copy':
              $this->assertEquals(4, $query['cgcount']);
              break;
          }
        }
        if (!empty($initialCount)) {
          $count = $initialCount;
        }

        $count++;
      }
      $sorted = TRUE;
    }
  }

}
