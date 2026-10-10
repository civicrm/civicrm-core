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

namespace Civi\WorkflowMessage;

use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * The entity a workflow model property points at has to be declared, or
 * nothing downstream can tell that `participantID` means a Participant.
 *
 * @group headless
 * @group msgtpl
 */
class ForeignKeyAnnotationTest extends TestCase implements HeadlessInterface {

  /**
   * @return \Civi\Test\CiviEnvBuilder
   */
  public function setUpHeadless() {
    return \Civi\Test::headless()->apply();
  }

  /**
   * @return array
   */
  public static function getForeignKeys(): array {
    return [
      ['event_online_receipt', 'participantID', 'Participant'],
      ['event_online_receipt', 'eventID', 'Event'],
      ['event_online_receipt', 'contributionID', 'Contribution'],
      ['event_online_receipt', 'contactID', 'Contact'],
      ['membership_online_receipt', 'membershipID', 'Membership'],
      ['contribution_invoice_receipt', 'contributionID', 'Contribution'],
      ['contribution_online_receipt', 'contributionProductID', 'ContributionProduct'],
      ['contribution_online_receipt', 'financialTrxnID', 'FinancialTrxn'],
    ];
  }

  /**
   * @param string $workflow
   * @param string $field
   * @param string $expected
   *
   * @dataProvider getForeignKeys
   */
  public function testForeignKeyIsDeclared(string $workflow, string $field, string $expected): void {
    $fields = WorkflowMessage::create($workflow)->getFields();
    $this->assertArrayHasKey($field, $fields);
    $this->assertEquals($expected, $fields[$field]->getFkEntity());
  }

}
