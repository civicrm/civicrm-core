<?php

/**
 * Class CRM_Utils_RestTest
 * @group headless
 */
class CRM_Utils_RestTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function testProcessMultiple() {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $input = [
      'cow' => [
        'contact',
        'create',
        [
          'contact_type' => 'Individual',
          'first_name' => 'Cow',
        ],
      ],
      'sheep' => [
        'contact',
        'create',
        [
          'contact_type' => 'Individual',
          'first_name' => 'Sheep',
        ],
      ],
    ];
    $_REQUEST['json'] = json_encode($input);
    $output = CRM_Utils_REST::processMultiple();
    $this->assertGreaterThan(0, $output['cow']['id']);
    $this->assertGreaterThan($output['cow']['id'], $output['sheep']['id']);
  }

}
