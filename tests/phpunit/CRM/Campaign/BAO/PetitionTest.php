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

use Civi\Api4\MessageTemplate;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Campaign_BAO_PetitionTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
  }

  public function tearDown(): void {
    $this->revertTemplateToReservedTemplate();
    $this->quickCleanup(['civicrm_campaign', 'civicrm_survey']);
    CRM_Core_BAO_ConfigSetting::disableComponent('CiviCampaign');
    parent::tearDown();
  }

  /**
   * Test Petition Email Sending using Domain tokens
   *
   * @throws \CRM_Core_Exception
   */
  public function testPetitionEmailWithDomainTokens(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $domain = $this->callAPISuccessGetSingle('Domain', ['id' => CRM_Core_Config::domainID()]);
    $this->createTestEntity('Address', [
      'contact_id' => $domain['contact_id'],
      'location_type_id' => 'Billing',
      'street_address' => '1600 Pennsylvania Avenue',
      'city' => 'Washington',
      'state_province_id' => 'District of Columbia',
      'country_id' => 'US',
      'postal_code' => '20500',
    ]);
    $templateContent = CRM_Core_DAO::singleValueQuery("SELECT msg_html FROM civicrm_msg_template WHERE workflow_name = 'petition_sign' AND is_default = 1");
    MessageTemplate::update()->addWhere('workflow_name', '=', 'petition_sign')
      ->addWhere('is_default', '=', 1)
      ->setValues(['msg_html' => $templateContent . '{domain.address}'])->execute();
    $contactID = $this->individualCreate();
    $this->callAPISuccess('Email', 'create', [
      'contact_id' => $contactID,
      'email' => 'testpetitioncontact@civicrm.org',
    ]);
    $survey = $this->createTestEntity('Survey', [
      'title' => 'Test Petition',
      'activity_type_id:name' => 'Petition',
      'bypass_confirm' => 1,
    ]);
    CRM_Campaign_BAO_Petition::sendEmail([
      'sid' => $survey['id'],
      'contactId' => $contactID,
      'email-Primary' => 'testpetitioncontact@civicrm.org',
    ], CRM_Campaign_Form_Petition_Signature::EMAIL_THANK);
    $mut->checkMailLog([
      '1600 Pennsylvania Avenue',
      'Washington',
      'Dear Anthony,',
      'Thank you for signing Test Petition.',
    ]);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testCreateAndConfirmSignatures(): void {
    $this->createTestEntity('Campaign', [
      'title' => 'Test Petition Campaign',
    ], 'petition')['id'];
    $this->createTestEntity('Survey', [
      'title' => 'Test Create And Confirm Signatures Petition',
      'activity_type_id:name' => 'Petition',
    ], 'petition')['id'];
    $tag = $this->createTestEntity('Tag', [
      'name' => Civi::settings()->get('tag_unconfirmed'),
      'used_for' => 'civicrm_contact',
    ], 'unconfirmed');

    // Add Unconfirmed tag
    $this->createTestEntity('EntityTag', [
      'tag_id' => $this->ids['Tag']['unconfirmed'],
      'entity_table' => 'civicrm_contact',
      'entity_id' => $this->individualCreate([], 'unconfirmed'),
    ]);

    $bao = new CRM_Campaign_BAO_Petition();

    // Test create signature
    $params = [
      'sid' => $this->ids['Survey']['petition'],
      'contactId' => $this->ids['Contact']['unconfirmed'],
      'statusId' => '1',
      'activity_campaign_id' => $this->ids['Campaign']['petition'],
    ];
    $activity = $bao->createSignature($params);
    $this->callAPISuccessGetCount('Activity', [
      'source_contact_id' => $this->ids['Contact']['unconfirmed'],
      'target_contact_id' => $this->ids['Contact']['unconfirmed'],
      'source_record_id' => $this->ids['Survey']['petition'],
      'subject' => 'Test Create And Confirm Signatures Petition',
      'status_id' => $params['statusId'],
      'activity_campaign_id' => $this->ids['Campaign']['petition'],
    ], 1);

    // Test confirm signature
    $this->assertTrue($bao->confirmSignature($activity->id, $this->ids['Contact']['unconfirmed'], $this->ids['Survey']['petition']), 'Signature not confirmed');
    $this->assertEquals(2, $this->callAPISuccessGetValue('Activity', ['id' => $activity->id, 'return' => 'status_id']), 'Activity status not changed');
    // Check Unconfirmed tag removed
    $this->callAPISuccessGetCount('EntityTag', [
      'tag_id' => $tag['id'],
      'entity_table' => 'civicrm_contact',
      'entity_id' => $this->ids['Contact']['unconfirmed'],
    ], 0);
  }

}
