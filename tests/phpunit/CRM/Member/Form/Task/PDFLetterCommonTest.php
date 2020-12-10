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
class CRM_Member_Form_Task_PDFLetterCommonTest extends CiviUnitTestCase {

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    CRM_Utils_Hook::singleton()->reset();
  }

  /**
   * Test token replacement for Print/Merge Task
   */
  public function testMembershipTokenReplacementInPDF() {
    $membershipIds = $returnProperties = $categories = $expected = [];
    list($tokens, $htmlMessage) = self::getSampleHTML();

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
      $membershipIds[] = $result['id'];
      $params = array_merge($params,
        [
          'fee' => '100.00',
          'type' => 'General',
          'status' => 'New',
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
    $messageToken = CRM_Utils_Token::getTokens($htmlMessage);
    $testHTML = CRM_Member_Form_Task_PDFLetterCommon::generateHTML($membershipIds,
      $returnProperties,
      NULL,
      NULL,
      $messageToken,
      $htmlMessage,
      $categories
    );

    // Assert all membership tokens are replaced correctly.
    $expected = array_values($expected);
    foreach ($expected as $key => $dateVal) {
      foreach ($tokens as $text => $token) {
        $this->assertContains($dateVal[$token], $testHTML[$key]);
      }
    }
  }

  /**
   * Generate sample HTML for testing.
   */
  public static function getSampleHTML() {
    $tokens = [
      'Test Fee' => 'fee',
      'Test Type' => 'type',
      'Test Status' => 'status',
      'Test Join Date' => 'join_date',
      'Test Start Date' => 'start_date',
      'Test End Date' => 'end_date',
    ];

    $html = '';
    foreach ($tokens as $key => $val) {
      $html .= "<p>{$key} - {membership.{$val}}</p>";
    }
    return [$tokens, $html];
  }

}
