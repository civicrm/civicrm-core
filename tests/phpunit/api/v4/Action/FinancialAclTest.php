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

namespace api\v4\Action;

use api\v4\Api4TestBase;
use Civi\Api4\ContributionProduct;
use Civi\Api4\PledgePayment;
use Civi\Core\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class FinancialAclTest extends Api4TestBase implements TransactionalInterface, HookInterface {

  use \Civi\Test\ACLPermissionTrait;

  public function setUpHeadless(): \Civi\Test\CiviEnvBuilder {
    return \Civi\Test::headless()->install([
      'civi_contribute',
      'civi_member',
      'civi_event',
      'civi_pledge',
    ])->apply();
  }

  public function setUp(): void {
    \CRM_Core_BAO_ConfigSetting::enableAllComponents();
    parent::setUp();
  }

  public function testFinancialRowAcl(): void {
    // 1. Create contacts: contact 0 (disallowed) and contact 1 (allowed)
    $cids = $this->saveTestRecords('Contact', ['records' => 2])->column('id');
    $disallowedContactId = $cids[0];
    $allowedContactId = $cids[1];

    // 2. Setup ACL: only allowedContactId is permitted
    $this->allowedContactId = $allowedContactId;
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view debug output',
      'access CiviPledge',
      'access CiviContribute',
      'access CiviMember',
      'access CiviEvent',
      'view event participants',
    ];
    \CRM_Utils_Hook::singleton()->setHook('civicrm_aclWhereClause', [$this, 'aclWhereOnlyOne']);

    // --- PledgePayment Test ---
    // Create Pledges
    $disallowedPledgeId = $this->createTestRecord('Pledge', [
      'contact_id' => $disallowedContactId,
      'amount' => 100,
      'installments' => 1,
      'financial_type_id:name' => 'Donation',
      'start_date' => 'now',
    ])['id'];
    $allowedPledgeId = $this->createTestRecord('Pledge', [
      'contact_id' => $allowedContactId,
      'amount' => 100,
      'installments' => 1,
      'financial_type_id:name' => 'Donation',
      'start_date' => 'now',
    ])['id'];

    // Create PledgePayments
    $disallowedPledgePaymentId = $this->createTestRecord('PledgePayment', [
      'pledge_id' => $disallowedPledgeId,
      'scheduled_amount' => 100,
      // Pending
      'status_id' => 2,
    ])['id'];
    $allowedPledgePaymentId = $this->createTestRecord('PledgePayment', [
      'pledge_id' => $allowedPledgeId,
      'scheduled_amount' => 100,
      // Pending
      'status_id' => 2,
    ])['id'];

    // Retrieve PledgePayments under ACL
    $pledgePayments = PledgePayment::get()->execute();
    $pledgePaymentIds = $pledgePayments->column('id');
    $this->assertNotContains($disallowedPledgePaymentId, $pledgePaymentIds);
    $this->assertContains($allowedPledgePaymentId, $pledgePaymentIds);

    // --- Contributions Setup ---
    $disallowedContributionId = $this->createTestRecord('Contribution', [
      'contact_id' => $disallowedContactId,
      'financial_type_id:name' => 'Donation',
      'total_amount' => 50,
    ])['id'];
    $allowedContributionId = $this->createTestRecord('Contribution', [
      'contact_id' => $allowedContactId,
      'financial_type_id:name' => 'Donation',
      'total_amount' => 50,
    ])['id'];

    // --- ContributionProduct Test ---
    // Create Products
    $productId = $this->createTestRecord('Product', [
      'name' => 'Test Product',
      'price' => 10,
    ])['id'];

    // Create ContributionProducts
    $disallowedContributionProductId = $this->createTestRecord('ContributionProduct', [
      'contribution_id' => $disallowedContributionId,
      'product_id' => $productId,
    ])['id'];
    $allowedContributionProductId = $this->createTestRecord('ContributionProduct', [
      'contribution_id' => $allowedContributionId,
      'product_id' => $productId,
    ])['id'];

    // Retrieve ContributionProducts under ACL
    $contributionProducts = ContributionProduct::get()->execute();
    $contributionProductIds = $contributionProducts->column('id');
    $this->assertNotContains($disallowedContributionProductId, $contributionProductIds);
    $this->assertContains($allowedContributionProductId, $contributionProductIds);
  }

}
