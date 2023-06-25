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

  public function tearDown(): void {
    $this->revertTemplateToReservedTemplate();
    parent::tearDown();
  }

  /**
   * Test Petition Email Sending using Domain tokens
   *
   * @throws \CRM_Core_Exception
   */
  public function testPetitionEmailWithDomainTokens(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $domain = $this->callAPISuccess('Domain', 'getsingle', ['id' => CRM_Core_Config::domainID()]);
    $this->callAPISuccess('Address', 'create', [
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
    $survey = $this->callAPISuccess('Survey', 'create', [
      'title' => 'Test Petition',
      'activity_type_id' => 'Petition',
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
      'Dear Anthony,
Thank you for signing Test Petition.
',
    ]);
    $mut->stop();
  }

}
