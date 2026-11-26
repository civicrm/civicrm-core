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

namespace Civi\Tellafriend;

use Civi\Test;
use Civi\Test\Api4TestTrait;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class LinksTest extends TestCase implements HeadlessInterface {

  use Api4TestTrait;

  public function setUpHeadless(): CiviEnvBuilder {
    return Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function tearDown(): void {
    $this->deleteTestRecords();
    parent::tearDown();
  }

  /**
   * An approved page whose block has Tell a Friend enabled gets the Tell Friends link.
   */
  public function testPcpLinks(): void {
    $links = \CRM_PCP_BAO_PCP::pcpLinks($this->createPcp(TRUE, 'Approved'));
    $this->assertArrayHasKey(\CRM_Core_Action::DETACH, $links['all']);
    $this->assertSame('civicrm/friend', $links['all'][\CRM_Core_Action::DETACH]['url']);
  }

  /**
   * A block without Tell a Friend gets no Tell Friends link.
   */
  public function testPcpLinksTellFriendDisabled(): void {
    $links = \CRM_PCP_BAO_PCP::pcpLinks($this->createPcp(FALSE, 'Approved'));
    $this->assertArrayNotHasKey(\CRM_Core_Action::DETACH, $links['all']);
  }

  /**
   * A page awaiting approval gets no Tell Friends link.
   */
  public function testPcpLinksNotApproved(): void {
    $links = \CRM_PCP_BAO_PCP::pcpLinks($this->createPcp(TRUE, 'Waiting Review'));
    $this->assertArrayNotHasKey(\CRM_Core_Action::DETACH, $links['all']);
  }

  /**
   * Create a personal campaign page on a contribution page.
   *
   * @return int
   *   The PCP ID.
   */
  private function createPcp(bool $isTellFriendEnabled, string $status): int {
    $contributionPageID = $this->createTestRecord('ContributionPage')['id'];
    $pcpBlockID = $this->createTestRecord('PCPBlock', [
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $contributionPageID,
      'target_entity_type' => 'contribute',
      'target_entity_id' => $contributionPageID,
      'is_tellfriend_enabled' => $isTellFriendEnabled,
    ])['id'];
    return $this->createTestRecord('PCP', [
      'contact_id' => $this->createTestRecord('Contact')['id'],
      'page_id' => $contributionPageID,
      'page_type' => 'contribute',
      'pcp_block_id' => $pcpBlockID,
      'status_id:name' => $status,
      'title' => 'My campaign',
    ])['id'];
  }

}
