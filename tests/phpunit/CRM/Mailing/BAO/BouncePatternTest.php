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
 * Class CRM_Mailing_BAO_BouncePatternTest
 */
class CRM_Mailing_BAO_BouncePatternTest extends CiviUnitTestCase {

  public static function patternExamples(): array {
    return [
      'Gmail Unsolicited Mail' => ['smtp; 550-5.7.1 Gmail has detected that this message is likely 550-5.7.1 unsolicited mail', 'Spam'],
      'SES complaint' => ['Complaint via SES', 'Spam'],
    ];
  }

  /**
   * Test Pattern Matching
   * @dataProvider patternExamples
   * @param string $bounce_message
   * @param string $expectedBounceType
   */
  public function testPatternMatching(string $bounce_message, string $expectedBounceType): void {
    $match = CRM_Mailing_BAO_BouncePattern::match($bounce_message);
    if ($match['bounce_type_id']) {
      $returnedBounceType = CRM_Core_DAO::singleValueQuery("SELECT name FROM civicrm_mailing_bounce_type WHERE id = %1", [1 => [$match['bounce_type_id'], 'Positive']]);
    }
    else {
      $returnedBounceType = 'Syntax';
    }
    $this->assertSame($expectedBounceType, $returnedBounceType);
  }

}
