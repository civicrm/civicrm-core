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
 * Test class for CRM_PCP_BAO_PCPTest BAO
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_PCP_BAO_PCPTest extends CiviUnitTestCase {

  use CRMTraits_PCP_PCPTestTrait;

  /**
   * Clean up after test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  public function testAddPCPBlock() {

    $params = $this->pcpBlockParams();
    $pcpBlock = CRM_PCP_BAO_PCPBlock::create($params);

    $this->assertInstanceOf('CRM_PCP_DAO_PCPBlock', $pcpBlock, 'Check for created object');
    $this->assertEquals($params['entity_table'], $pcpBlock->entity_table, 'Check for entity table.');
    $this->assertEquals($params['entity_id'], $pcpBlock->entity_id, 'Check for entity id.');
    $this->assertEquals($params['supporter_profile_id'], $pcpBlock->supporter_profile_id, 'Check for profile id .');
    $this->assertEquals($params['is_approval_needed'], $pcpBlock->is_approval_needed, 'Check for approval needed .');
    $this->assertEquals($params['is_tellfriend_enabled'], $pcpBlock->is_tellfriend_enabled, 'Check for tell friend on.');
    $this->assertEquals($params['tellfriend_limit'], $pcpBlock->tellfriend_limit, 'Check for tell friend limit .');
    $this->assertEquals($params['link_text'], $pcpBlock->link_text, 'Check for link text.');
    $this->assertEquals($params['is_active'], $pcpBlock->is_active, 'Check for is_active.');
  }

  /**
   * Basic create test.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreatePCP() {
    $params = $this->pcpParams();
    $pcpID = $this->createPCPBlock($params);
    $this->getAndCheck($params, $pcpID, 'Pcp');
  }

  public function testAddPCPNoStatus() {
    $blockParams = $this->pcpBlockParams();
    $pcpBlock = CRM_PCP_BAO_PCPBlock::create($blockParams);

    $params = $this->pcpParams();
    $params['pcp_block_id'] = $pcpBlock->id;
    unset($params['status_id']);

    $pcp = CRM_PCP_BAO_PCP::create($params);

    $this->assertInstanceOf('CRM_PCP_DAO_PCP', $pcp, 'Check for created object');
    $this->assertEquals($params['contact_id'], $pcp->contact_id, 'Check for entity table.');
    $this->assertEquals(0, $pcp->status_id, 'Check for zero status when no status_id passed.');
    $this->assertEquals($params['title'], $pcp->title, 'Check for title.');
    $this->assertEquals($params['intro_text'], $pcp->intro_text, 'Check for intro_text.');
    $this->assertEquals($params['page_text'], $pcp->page_text, 'Check for page_text.');
    $this->assertEquals($params['donate_link_text'], $pcp->donate_link_text, 'Check for donate_link_text.');
    $this->assertEquals($params['is_thermometer'], $pcp->is_thermometer, 'Check for is_thermometer.');
    $this->assertEquals($params['is_honor_roll'], $pcp->is_honor_roll, 'Check for is_honor_roll.');
    $this->assertEquals($params['goal_amount'], $pcp->goal_amount, 'Check for goal_amount.');
    $this->assertEquals($params['is_active'], $pcp->is_active, 'Check for is_active.');
  }

  public function testDeletePCP() {

    $pcp = CRM_Core_DAO::createTestObject('CRM_PCP_DAO_PCP');
    $pcpId = $pcp->id;
    CRM_PCP_BAO_PCP::deleteById($pcpId);
    $this->assertDBRowNotExist('CRM_PCP_DAO_PCP', $pcpId, 'Database check PCP deleted successfully.');
  }

  /**
   * Get getPCPDashboard info function.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetPcpDashboardInfo() {
    $block = CRM_PCP_BAO_PCPBlock::create($this->pcpBlockParams());
    $contactID = $this->individualCreate();
    $contributionPage = $this->callAPISuccessGetSingle('ContributionPage', []);
    $this->callAPISuccess('Pcp', 'create', ['contact_id' => $contactID, 'title' => 'pcp', 'page_id' => $contributionPage['id'], 'pcp_block_id' => $block->id, 'is_active' => TRUE, 'status_id' => 'Approved']);
    $this->assertEquals([
      [],
      [
        [
          'pageTitle' => $contributionPage['title'],
          'action' => '<span><a href="/index.php?q=civicrm/pcp/info&amp;action=update&amp;reset=1&amp;id=' . $contributionPage['id'] . '&amp;component=contribute" class="action-item crm-hover-button" title=\'Configure\' >Edit Your Page</a><a href="/index.php?q=civicrm/friend&amp;eid=1&amp;blockId=1&amp;reset=1&amp;pcomponent=pcp&amp;component=contribute" class="action-item crm-hover-button" title=\'Tell Friends\' >Tell Friends</a></span><span class=\'btn-slide crm-hover-button\'>more<ul class=\'panel\'><li><a href="/index.php?q=civicrm/pcp/info&amp;reset=1&amp;id=1&amp;component=contribute" class="action-item crm-hover-button" title=\'URL for this Page\' >URL for this Page</a></li><li><a href="/index.php?q=civicrm/pcp/info&amp;action=browse&amp;reset=1&amp;id=1&amp;component=contribute" class="action-item crm-hover-button" title=\'Update Contact Information\' >Update Contact Information</a></li><li><a href="/index.php?q=civicrm/pcp&amp;action=disable&amp;reset=1&amp;id=1&amp;component=contribute" class="action-item crm-hover-button" title=\'Disable\' >Disable</a></li><li><a href="/index.php?q=civicrm/pcp&amp;action=delete&amp;reset=1&amp;id=1&amp;component=contribute" class="action-item crm-hover-button small-popup" title=\'Delete\' onclick = "return confirm(\'Are you sure you want to delete this Personal Campaign Page?\nThis action cannot be undone.\');">Delete</a></li></ul></span>',
          'pcpId' => 1,
          'pcpTitle' => 'pcp',
          'pcpStatus' => 'Approved',
          'class' => '',
        ],
      ],
    ], CRM_PCP_BAO_PCP::getPcpDashboardInfo($contactID));
  }

  /**
   * Test that hook_civicrm_links is called.
   */
  public function testPcpInfoLinksHook() {
    Civi::dispatcher()->addListener('hook_civicrm_links', [$this, 'hookLinks']);

    // Reset the cache otherwise our hook will not be called
    CRM_PCP_BAO_PCP::$_pcpLinks = NULL;

    $block = CRM_PCP_BAO_PCPBlock::create($this->pcpBlockParams());
    $contactID = $this->individualCreate();
    $contributionPage = $this->callAPISuccessGetSingle('ContributionPage', []);
    $pcp = $this->callAPISuccess('Pcp', 'create', ['contact_id' => $contactID, 'title' => 'pcp', 'page_id' => $contributionPage['id'], 'pcp_block_id' => $block->id, 'is_active' => TRUE, 'status_id' => 'Approved']);

    $links = CRM_PCP_BAO_PCP::pcplinks($pcp['id']);

    foreach ($links['all'] as $link) {
      if ($link['name'] == 'URL for this Page') {
        $found = TRUE;
        $this->assertEquals($link['url'], 'https://civicrm.org/mih');
      }
    }

    $this->assertEquals($found, TRUE);
  }

  /**
   * This is the listener for hook_civicrm_links
   *
   * Replaces the "URL for this Page" link by a hardcoded link.
   * This is the listener for hook_civicrm_alterReportVar
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   *   Should contain 'op', 'links', and other members corresponding
   *   to the hook parameters.
   */
  public function hookLinks(\Civi\Core\Event\GenericHookEvent $e) {
    if ($e->op == 'pcp.user.actions') {
      foreach ($e->links['all'] as $key => &$link) {
        if ($link['name'] == 'URL for this Page') {
          $e->links['all'][$key]['url'] = 'https://civicrm.org/mih';
        }
      }
    }
  }

  /**
   * Test that CRM_Contribute_BAO_Contribution::_gatherMessageValues() works
   * with PCP.
   */
  public function testGatherMessageValuesForPCP() {
    // set up a pcp page
    $block = CRM_PCP_BAO_PCPBlock::create($this->pcpBlockParams());
    // The owner of the pcp, who gets the soft credit
    $contact_owner = $this->individualCreate([], 0, TRUE);
    $contributionPage = $this->callAPISuccessGetSingle('ContributionPage', []);
    $pcp = $this->callAPISuccess('Pcp', 'create', [
      'contact_id' => $contact_owner,
      'title' => 'pcp',
      'page_id' => $contributionPage['id'],
      'pcp_block_id' => $block->id,
      'is_active' => TRUE,
      'status_id' => 'Approved',
    ]);

    // set up a payment processor
    $payment_processor_type = $this->callAPISuccess('PaymentProcessorType', 'get', ['name' => 'Dummy']);
    $payment_processor = $this->callAPISuccess('PaymentProcessor', 'create', [
      'name' => 'Dummy PP',
      'payment_processor_type_id' => $payment_processor_type['id'],
      'class_name' => $payment_processor_type['values'][$payment_processor_type['id']]['class_name'],
    ]);

    // create a contribution with the pcp soft credit
    $contact_contributor = $this->individualCreate([], 1, TRUE);
    $address = $this->callAPISuccess('address', 'create', [
      'address_name' => "Giver {$contact_contributor}",
      'street_address' => '123 Main St.',
      'location_type_id' => 'Billing',
      'is_billing' => 1,
      'contact_id' => $contact_contributor,
    ]);
    $contribution = $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $contact_contributor,
      'address_id' => $address['id'],
      'total_amount' => 10,
      'receive_date' => date('YmdHis'),
      'financial_type_id' => 'Donation',
      'payment_processor' => $payment_processor['id'],
      'payment_instrument_id' => 'Credit Card',
    ]);
    $contribution_soft = $this->callAPISuccess('ContributionSoft', 'create', [
      'contribution_id' => $contribution['id'],
      'amount' => 10,
      'contact_id' => $contact_owner,
      'pcp_id' => $pcp['id'],
      'pcp_display_in_roll' => 1,
      'pcp_roll_nickname' => "Giver {$contact_contributor}",
      'soft_credit_type_id' => 'pcp',
    ]);

    // Retrieve it using BAO so we can call gatherMessageValues
    $contribution_bao = new CRM_Contribute_BAO_Contribution();
    $contribution_bao->id = $contribution['id'];
    $contribution_bao->find(TRUE);

    $contribution_bao->_component = 'contribute';

    // call and check result. $values has to be defined since it's pass-by-ref.
    $values = [
      'receipt_from_name' => 'CiviCRM Fundraising Dept.',
      'receipt_from_email' => 'donationFake@civicrm.org',
      'contribution_status' => 'Completed',
    ];
    $gathered_values = $contribution_bao->_gatherMessageValues(
      [
        'payment_processor_id' => $payment_processor['id'],
        'is_email_receipt' => TRUE,
      ],
      $values,
      [
        'component' => 'contribute',
        'contact_id' => $contact_contributor,
        'contact' => $contact_contributor,
        'financialType' => $contribution['values'][$contribution['id']]['financial_type_id'],
        'contributionType' => $contribution['values'][$contribution['id']]['contribution_type_id'],
        'contributionPage' => $contributionPage['id'],
        'membership' => [],
        'paymentProcessor' => $payment_processor['id'],
        'contribution' => $contribution['id'],
      ]
    );

    $this->assertEquals([
      'receipt_from_name' => 'CiviCRM Fundraising Dept.',
      'receipt_from_email' => 'donationFake@civicrm.org',
      'contribution_status' => 'Completed',
      'billingName' => "Giver {$contact_contributor}",
      'address' => "Giver {$contact_contributor}\n123 Main St.\n",
      'softContributions' => NULL,
      'title' => 'Contribution',
      'priceSetID' => '1',
      'useForMember' => FALSE,
      'lineItem' => [
        0 => [
          1 => [
            'qty' => 1.0,
            'label' => 'Contribution Amount',
            'unit_price' => '10.00',
            'line_total' => '10.00',
            'price_field_id' => '1',
            'participant_count' => NULL,
            'price_field_value_id' => '1',
            'field_title' => 'Contribution Amount',
            'html_type' => 'Text',
            'description' => NULL,
            'entity_id' => '1',
            'entity_table' => 'civicrm_contribution',
            'contribution_id' => '1',
            'financial_type_id' => '1',
            'financial_type' => 'Donation',
            'membership_type_id' => NULL,
            'membership_num_terms' => NULL,
            'tax_amount' => 0.0,
            'price_set_id' => '1',
            'tax_rate' => FALSE,
            'subTotal' => 10.0,
          ],
        ],
      ],
      'customGroup' => [],
      'is_pay_later' => '0',
    ], $gathered_values);
  }

}
