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
 * Test ContributionPage translation features.
 *
 * @group headless
 */
class CRM_Contribute_Form_ContributionPageTranslationTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->_financialTypeID = 1;
    $this->enableMultilingual();
    CRM_Core_I18n_Schema::addLocale('fr_FR', 'en_US');
  }

  public function tearDown(): void {
    global $dbLocale;
    if ($dbLocale) {
      CRM_Core_I18n_Schema::makeSinglelingual('en_US');
    }
  }

  /**
   * Create() method (create Contribution Page with Honor block)
   */
  public function testCreateHonor() {
    CRM_Core_I18n::singleton()->setLocale('en_US');

    $params = [
      'title' => 'Test Contribution Page',
      'financial_type_id' => $this->_financialTypeID,
      'is_for_organization' => 0,
      'for_organization' => ' I am contributing on behalf of an organization',
      'goal_amount' => '400',
      'is_active' => 1,
      'honor_block_is_active' => 1,
      'honor_block_title' => 'In Honor Title EN',
      'honor_block_text' => 'In Honor Text EN',
      // Honoree Individual
      'honoree_profile' => 13,
      // In Honor Of
      'soft_credit_types' => 1,
      'start_date' => '20091022105900',
      'start_date_time' => '10:59AM',
      'end_date' => '19700101000000',
      'end_date_time' => '',
      'is_credit_card_only' => '',
    ];

    $contributionpage = CRM_Contribute_BAO_ContributionPage::create($params);

    // The BAO does not save these
    $params['id'] = $contributionpage->id;
    $params['honor_block_title'] = 'In Honor Title EN';
    $params['honor_block_text'] = 'In Honor Text EN';

    $form = $this->getFormObject('CRM_Contribute_Form_ContributionPage_Settings', $params, 'Settings');
    $form->postProcess();

    // Now update the page with In Honor (soft credit) text in French
    CRM_Core_I18n::singleton()->setLocale('fr_FR');

    $params['honor_block_title'] = 'In Honor Title FR';
    $params['honor_block_text'] = 'In Honor Text FR';

    $form = $this->getFormObject('CRM_Contribute_Form_ContributionPage_Settings', $params, 'Settings');
    $form->postProcess();

    $uf = $this->callAPISuccess('UFJoin', 'getsingle', [
      'entity_id' => $contributionpage->id,
      'module' => 'soft_credit',
    ]);

    $json = json_decode($uf['module_data'], TRUE);

    $this->assertEquals('In Honor Title EN', $json['soft_credit']['en_US']['honor_block_title']);
    $this->assertEquals('In Honor Text EN', $json['soft_credit']['en_US']['honor_block_text']);
    $this->assertEquals('In Honor Title FR', $json['soft_credit']['fr_FR']['honor_block_title']);
    $this->assertEquals('In Honor Text FR', $json['soft_credit']['fr_FR']['honor_block_text']);

    $this->callAPISuccess('ContributionPage', 'delete', ['id' => $contributionpage->id]);
  }

}
