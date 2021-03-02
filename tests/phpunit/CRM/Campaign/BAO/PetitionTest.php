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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Campaign_BAO_PetitionTest extends CiviUnitTestCase {

  /**
   * Test Petition Email Sending using Domain tokens
   */
  public function testPetitionEmailWithDomainTokens() {
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
    $template_contact = CRM_Core_DAO::singleValueQuery("SELECT msg_html FROM civicrm_msg_template WHERE workflow_name = 'petition_sign' AND is_default = 1");
    $template_contact .= '
      {domain.address}';
    CRM_Core_DAO::executeQuery("UPDATE civicrm_msg_template SET msg_html = '{$template_contact}' WHERE workflow_name = 'petition_sign' AND is_default = 1");
    $contact = $this->individualCreate();
    $email = $this->callAPISuccess('email', 'create', [
      'contact_id' => $contact,
      'email' => 'testpetitioncontact@civicrm.org',
    ]);
    $survey = $this->callAPISuccess('Survey', 'create', [
      'title' => 'Test Petition',
      'activity_type_id' => 'Petition',
      'bypass_confirm' => 1,
    ]);
    $params = [
      'sid' => $survey['id'],
      'contactId' => $contact,
      'email-Primary' => 'testpetitioncontact@civicrm.org',
    ];
    CRM_Campaign_BAO_Petition::sendEmail($params, CRM_Campaign_Form_Petition_Signature::EMAIL_THANK);
    $mut->checkMailLog([
      '1600 Pennsylvania Avenue',
      'Washington',
    ]);
    $mut->stop();
  }

}
