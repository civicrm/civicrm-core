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

namespace Civi;

use Civi\Test\EntityTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use Civi\Token\TokenProcessor;
use PHPUnit\Framework\TestCase;

/**
 *  Test APIv3 civicrm_grant* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Grant
 * @group headless
 */
class TokenTest extends TestCase implements HeadlessInterface, TransactionalInterface {

  use EntityTrait;

  /**
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless(): Test\CiviEnvBuilder {
    return Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testTokens(): void {
    $this->createTestEntity('OptionValue', [
      'option_group_id:name' => 'grant_type',
      'value' => 1,
      'name' => 'community_grant',
      'label' => 'Community Grant',
    ]);
    $this->createTestEntity('Grant', [
      'contact_id' => $this->createTestEntity('Contact', ['contact_type' => 'organization', 'organization_name' => 'The Firm'])['id'],
      'application_received_date' => '2024-05-07',
      'decision_date' => '2024-06-07',
      'amount_total' => '500.00',
      'status_id' => 1,
      'rationale' => 'Just Because',
      'currency' => 'USD',
      'grant_type_id:name' => 'community_grant',
    ]);
    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), ['schema' => ['grantId']]);
    $tokens = $tokenProcessor->listTokens();
    $this->assertEquals('Grant Report Due Date', $tokens['{grant.grant_due_date}']);
    $tokenProcessor->addMessage('html', '{grant.application_received_date} {grant.status_id:label} {grant.rationale}', 'text/html');
    $tokenProcessor->addRow(['grantId' => $this->ids['Grant']['default']]);
    $tokenProcessor->evaluate();
    $row = $tokenProcessor->getRow(0);
    $this->assertEquals('May 7th, 2024 Submitted Just Because', $text = $row->render('html'));
  }

}
