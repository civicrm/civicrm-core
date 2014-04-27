<?php
require_once 'CiviTest/CiviUnitTestCase.php';
class CRM_Utils_RestTest extends CiviUnitTestCase {
  function get_info() {
    return array(
      'name' => 'Rest Test',
      'description' => 'Test Rest Interface Utilities',
      'group' => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();
  }

  function testProcessMultiple() {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $input = array(
      'cow' => array(
        'contact',
        'create',
        array(
          'contact_type' => 'Individual',
          'first_name' => 'Cow',
        ),
      ),
      'sheep' => array(
        'contact',
        'create',
        array(
          'contact_type' => 'Individual',
          'first_name' => 'Sheep',
        ),
      ),
    );
    $_REQUEST['json'] = json_encode($input);
    $output = CRM_Utils_REST::processMultiple();
    $this->assertGreaterThan(0, $output['cow']['id']);
    $this->assertGreaterThan($output['cow']['id'], $output['sheep']['id']);
  }

}
