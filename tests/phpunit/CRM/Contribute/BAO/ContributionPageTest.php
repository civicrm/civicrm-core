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
 * Class CRM_Contribute_BAO_ContributionPageTest
 * @group headless
 */
class CRM_Contribute_BAO_ContributionPageTest extends CiviUnitTestCase {

  /**
   * Create() method (create Contribution Page)
   */
  public function testCreate(): void {

    $params = [
      'qfkey' => '9a3ef3c08879ad4c8c109b21c583400e',
      'title' => 'Test Contribution Page',
      'financial_type_id' => 1,
      'intro_text' => '',
      'footer_text' => 'Thanks',
      'is_for_organization' => 0,
      'for_organization' => ' I am contributing on behalf of an organization',
      'goal_amount' => '400',
      'is_active' => 1,
      'honor_block_title' => '',
      'honor_block_text' => '',
      'start_date' => '20091022105900',
      'start_date_time' => '10:59AM',
      'end_date' => '19700101000000',
      'end_date_time' => '',
      'is_credit_card_only' => '',
    ];

    $contributionPageID = CRM_Contribute_BAO_ContributionPage::create($params)->id;
    $this->assertIsInt($contributionPageID);
    $this->callAPISuccess('ContributionPage', 'delete', ['id' => $contributionPageID]);
  }

  /**
   * Test setValues() method
   */
  public function testSetValues(): void {

    $params = [
      'title' => 'Test Contribution Page',
      'financial_type_id' => 1,
      'is_active' => 1,
    ];

    $contributionPage = CRM_Contribute_BAO_ContributionPage::create($params);

    $id = $contributionPage->id;
    $values = [];
    CRM_Contribute_BAO_ContributionPage::setValues($id, $values);

    $this->assertEquals($params['title'], $values['title'], 'Verify contribution title.');
    $this->assertEquals(1, $values['financial_type_id'], 'Verify financial types id.');
    $this->assertEquals(1, $values['is_active'], 'Verify contribution is_active value.');
    $this->callAPISuccess('ContributionPage', 'delete', ['id' => $contributionPage->id]);
  }

  /**
   * Test copy() method
   */
  public function testcopy(): void {
    $params = [
      'qfkey' => '9a3ef3c08879ad4c8c109b21c583400e',
      'title' => 'Test Contribution Page',
      'financial_type_id' => 1,
      'intro_text' => '',
      'footer_text' => 'Thanks',
      'is_for_organization' => 0,
      'for_organization' => ' I am contributing on behalf of an organization',
      'goal_amount' => '400',
      'is_active' => 1,
      'honor_block_title' => '',
      'honor_block_text' => '',
      'start_date' => '20091022105900',
      'start_date_time' => '10:59AM',
      'end_date' => '19700101000000',
      'end_date_time' => '',
      'is_credit_card_only' => '',
    ];

    $contributionPage = CRM_Contribute_BAO_ContributionPage::create($params);
    $copyContributionPage = CRM_Contribute_BAO_ContributionPage::copy($contributionPage->id);
    $this->assertEquals(1, $copyContributionPage->financial_type_id, 'Check for Financial type id.');
    $this->assertEquals(400, $copyContributionPage->goal_amount, 'Check for goal amount.');
    $this->callAPISuccess('ContributionPage', 'delete', ['id' => $contributionPage->id]);
    $this->callAPISuccess('ContributionPage', 'delete', ['id' => $copyContributionPage->id]);
  }

}
