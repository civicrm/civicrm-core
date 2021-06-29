<?php

/**
 * Class CRM_Case_Form_Task_PDFLetterCommonTest
 * @group headless
 */
class CRM_Case_Form_Task_PDFLetterCommonTest extends CiviCaseTestCase {

  protected $custom_group;

  /**
   * Set up for tests.
   */
  public function setUp(): void {
    parent::setUp();
    $this->custom_group = $this->customGroupCreate(['extends' => 'Case']);
    $this->custom_group = $this->custom_group['values'][$this->custom_group['id']];
  }

  public function testCaseTokenFunctionality(): void {
    // set up custom field, with any overrides from input params
    $custom_field = $this->callAPISuccess('custom_field', 'create', [
      'custom_group_id' => $this->custom_group['id'],
      'label' => 'What?',
      'data_type' => 'String',
      'html_type' => 'Text',
      'is_active' => 1,
    ]);
    $custom_field = $custom_field['values'][$custom_field['id']];

    // set up case and set the custom field initial value
    $client_id = $this->individualCreate([], 0, TRUE);
    $caseObj = $this->createCase($client_id, $this->_loggedInUser);
    $this->callAPISuccess('CustomValue', 'create', [
      "custom_{$custom_field['id']}" => 'I like it',
      'entity_id' => $caseObj->id,
    ]);

    $availableTokens = CRM_Case_Form_Task_PDFLetterCommon::listTokens();
    $expectedTokens = [
      '{case.case_type_id}',
      '{case.subject}',
      '{case.start_date}',
      '{case.end_date}',
      '{case.details}',
      '{case.status_id}',
      '{case.is_deleted}',
      '{case.created_date}',
      '{case.modified_date}',
      '{case.contact_id}',
      '{case.activity_id}',
      '{case.tag_id}',
      '{case.status}',
      '{case.type}',
      '{case.id}',
      '{case.custom_' . $custom_field['id'] . '}',
    ];
    foreach ($expectedTokens as $expectedToken) {
      $this->assertArrayKeyExists($expectedToken, $availableTokens);
    }
    $this->assertEquals('What?', $availableTokens['{case.custom_' . $custom_field['id'] . '}']);

    $expected_output = "Subject: Case Subject\nId: " . $caseObj->id . "\nCustom field: I like it";
    $html_message = "Subject: {case.subject}\nId: {case.id}\nCustom field: {case.custom_" . $custom_field['id'] . "}";
    $output = CRM_Case_Form_Task_PDFLetterCommon::createDocument([$caseObj->id], $html_message, ['is_unit_test' => TRUE]);

    $this->assertEquals($expected_output, $output[0]);
  }

}
