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
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class CRM_Member_Form_Task_PDFLetterTest extends CiviUnitTestCase {

  /**
   * Clean up after each test.
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    CRM_Utils_Hook::singleton()->reset();
  }

  /**
   * Test token replacement for Print/Merge Task
   */
  public function testMembershipTokenReplacementInPDF(): void {
    $this->createLoggedInUser();
    $expected = [];
    [$tokens, $htmlMessage] = self::getSampleHTML();

    $searchFormValues = [
      'radio_ts' => 'ts_sel',
      'task' => CRM_Member_Task::PDF_LETTER,
    ];

    $membershipDates = [
      date('Y-m-d'),
      date('Y-m-d', strtotime('-1 month')),
    ];
    // Create sample memberships with different dates.
    foreach ($membershipDates as $date) {
      $contactId = $this->individualCreate();
      $membershipTypeID = $this->membershipTypeCreate([
        'minimum_fee' => '100.00',
        'member_of_contact_id' => $contactId,
      ]);
      $params = [
        'contact_id' => $contactId,
        'membership_type_id' => $membershipTypeID,
        'join_date' => $date,
        'start_date' => $date,
        'end_date' => date('Y-m-d', strtotime("{$date} +1 year")),
      ];
      $result = $this->callAPISuccess('membership', 'create', $params);
      $searchFormValues['mark_x_' . $result['id']] = 1;
      $params = array_merge($params,
        [
          'fee' => '100.00',
          'membership_type_id:label' => 'General',
          'status_id:label' => 'New',
        ]
      );

      // Form an expected array replacing tokens for each contact.
      foreach ($tokens as $key => $val) {
        if (CRM_Utils_String::endsWith($val, '_date')) {
          $formattedDate = CRM_Utils_Date::customFormat($params[$val]);
          $expected[$contactId][$val] = "{$key} - {$formattedDate}";
        }
        else {
          $expected[$contactId][$val] = $params[$val];
        }
      }
    }

    /* @var CRM_Member_Form_Task_PDFLetter $form */
    $form = $this->getFormObject('CRM_Member_Form_Task_PDFLetter', [
      'subject' => '{contact.first_name} {membership.source}',
      'html_message' => $htmlMessage,
    ], NULL, $searchFormValues);
    $form->buildForm();
    try {
      $form->postProcess();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $testHTML = $e->errorData['html'];
    }

    // Assert all membership tokens are replaced correctly.
    $expected = array_values($expected);
    foreach ($expected as $key => $dateVal) {
      $this->assertStringContainsString('Anthony', $testHTML);
      foreach ($tokens as $token) {
        $this->assertStringContainsString($dateVal[$token], $testHTML);
      }
    }
  }

  /**
   * Generate sample HTML for testing.
   */
  public static function getSampleHTML() {
    $tokens = [
      'Test Fee' => 'fee',
      'Test Type' => 'membership_type_id:label',
      'Test Status' => 'status_id:label',
      'Test Join Date' => 'join_date',
      'Test Start Date' => 'start_date',
      'Test End Date' => 'end_date',
    ];

    $html = '';
    foreach ($tokens as $key => $val) {
      $html .= "<p>{$key} - {membership.{$val}}</p>";
    }
    $html .= '{contact.first_name}';
    return [$tokens, $html];
  }

}
