<?php

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
    $ids = $this->CustomGroupMultipleCreateWithFields(array('style' => 'Tab with table'));
    $params = array(
      'contact_type' => 'Individual',
      'first_name' => 'Test',
      'last_name' => 'Contact',
    );
    $customFields = $ids['custom_field_id'];
    $result = $this->callAPISuccess('contact', 'create', $params);
    $contactId = $result['id'];

    //enter values for custom fields
    $customParams = array(
      "custom_{$customFields[0]}_-1" => "test value {$customFields[0]} one",
      "custom_{$customFields[0]}_-2" => "test value {$customFields[0]} two",
      "custom_{$customFields[1]}_-1" => "test value {$customFields[1]} one",
      "custom_{$customFields[1]}_-2" => "test value {$customFields[1]} two",
      "custom_{$customFields[2]}_-1" => "test value {$customFields[2]} one",
      "custom_{$customFields[2]}_-2" => "test value {$customFields[2]} two",
    );
    CRM_Core_BAO_CustomValueTable::postProcess($customParams, "civicrm_contact", $contactId, NULL);

    $_GET = array(
      'cid' => $contactId,
      'cgid' => $ids['custom_group_id'],
      'is_unit_test' => TRUE,
    );
    $multiRecordFields = CRM_Custom_Page_AJAX::getMultiRecordFieldList();

    //check sorting
    foreach ($customFields as $fieldId) {
      $columnName = "field_{$fieldId}{$ids['custom_group_id']}_{$fieldId}";
      $_GET['columns'][] = array(
        'data' => $columnName,
      );
    }
    // get the results in descending order
    $_GET['order'] = array(
      '0' => array(
        'column' => 0,
        'dir' => 'desc',
      ),
    );
    $sortedRecords = CRM_Custom_Page_AJAX::getMultiRecordFieldList();

    $this->assertEquals(2, $sortedRecords['recordsTotal']);
    $this->assertEquals(2, $multiRecordFields['recordsTotal']);
    foreach ($customFields as $fieldId) {
      $columnName = "field_{$fieldId}{$ids['custom_group_id']}_{$fieldId}";
      $this->assertEquals("test value {$fieldId} one", $multiRecordFields['data'][0][$columnName]['data']);
      $this->assertEquals("test value {$fieldId} two", $multiRecordFields['data'][1][$columnName]['data']);

      // this should be sorted in descending order.
      $this->assertEquals("test value {$fieldId} two", $sortedRecords['data'][0][$columnName]['data']);
      $this->assertEquals("test value {$fieldId} one", $sortedRecords['data'][1][$columnName]['data']);
    }

    //check the links
    $sorted = FALSE;
    foreach (array($multiRecordFields, $sortedRecords) as $records) {
      $count = 1;
      //check links for result sorted in descending order
      if ($sorted) {
        $count = 2;
      }
      foreach ($records['data'] as $key => $val) {
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
              $this->assertEquals(3, $query['cgcount']);
              break;
          }
        }

        //decrement the count as we're sorting in descending order.
        if ($sorted) {
          $count--;
        }
        else {
          $count++;
        }
      }
      $sorted = TRUE;
    }
  }

}
