<?php

use Civi\Api4\Address;
use Civi\Api4\Contact;

/**
 * Class CRM_Core_BAO_MessageTemplateTest
 * @group headless
 */
class CRM_Core_BAO_MessageTemplateTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;

  /**
   * Post test cleanup.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown():void {
    $this->quickCleanup(['civicrm_address', 'civicrm_phone', 'civicrm_im', 'civicrm_website', 'civicrm_openid', 'civicrm_email']);
    parent::tearDown();
  }

  /**
   * Test message template send.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testCaseActivityCopyTemplate():void {
    $client_id = $this->individualCreate();
    $contact_id = $this->individualCreate();

    $tplParams = [
      'isCaseActivity' => 1,
      'client_id' => $client_id,
      // activityTypeName means label here not name, but it's ok because label is desired here (dev/core#1116-ok-label)
      'activityTypeName' => 'Follow up',
      'activity' => [
        'fields' => [
          [
            'label' => 'Case ID',
            'type' => 'String',
            'value' => '1234',
          ],
        ],
      ],
      'activitySubject' => 'Test 123',
      'idHash' => substr(sha1(CIVICRM_SITE_KEY . '1234'), 0, 7),
    ];

    [$sent, $subject, $message] = CRM_Core_BAO_MessageTemplate::sendTemplate(
      [
        'valueName' => 'case_activity',
        'contactId' => $contact_id,
        'tplParams' => $tplParams,
        'from' => 'admin@example.com',
        'toName' => 'Demo',
        'toEmail' => 'admin@example.com',
        'attachments' => NULL,
      ]
    );

    $this->assertEquals('[case #' . $tplParams['idHash'] . '] Test 123', $subject);
    $this->assertContains('Your Case Role', $message);
    $this->assertContains('Case ID : 1234', $message);
  }

  /**
   * Test rendering of domain tokens.
   *
   * @throws \CRM_Core_Exception
   */
  public function testDomainTokens(): void {
    $values = $this->getDomainTokenData();
    $this->callAPISuccess('Domain', 'create', [
      'id' => CRM_Core_Config::domainID(),
      'description' => $values['description'],
    ]);
    $this->callAPISuccess('Address', 'create', array_merge($values['address'], ['contact_id' => 1]));
    $this->callAPISuccess('Email', 'create', array_merge(['email' => $values['email']], ['contact_id' => 1, 'is_primary' => 1]));
    $tokenString = '{domain.' . implode('} ~ {domain.', array_keys($values)) . '}';
    $messageContent = CRM_Core_BAO_MessageTemplate::renderMessageTemplate([
      'html' => $tokenString,
      // Check the space is stripped.
      'subject' => $tokenString . ' ',
      'text' => $tokenString,
    ], FALSE, FALSE, []);
    $this->assertEquals('Default Domain Name ~  ~ <div class="location vcard"><span class="adr"><span class="street-address">Buckingham palace</span><br /><span class="extended-address">Up the road</span><br /><span class="locality">London</span>, <span class="postal-code">90210</span><br /></span></div> ~ crown@example.com ~ 1 ~ rather nice', $messageContent['html']);
    $this->assertEquals('Default Domain Name ~  ~ Buckingham palace
Up the road
London, 90210
 ~ crown@example.com ~ 1 ~ rather nice', $messageContent['text']);
    $this->assertEquals('Default Domain Name ~  ~ Buckingham palace Up the road London, 90210  ~ crown@example.com ~ 1 ~ rather nice', $messageContent['subject']);
  }

  /**
   * Test rendering of smarty tokens.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRenderMessageTemplateSmarty(): void {
    $messageContent = CRM_Core_BAO_MessageTemplate::renderMessageTemplate([
      'html' => '{$tokenString}',
      // Check the space is stripped.
      'subject' => '{$tokenString} ',
      'text' => '{$tokenString}',
    ], FALSE, FALSE, ['tokenString' => 'Something really witty']);

    $this->assertEquals('Something really witty', $messageContent['text']);
    $this->assertEquals('Something really witty', $messageContent['html']);
    $this->assertEquals('Something really witty', $messageContent['subject']);
  }

  /**
   * Test rendering of smarty tokens.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRenderMessageTemplateIgnoreSmarty(): void {
    $messageContent = CRM_Core_BAO_MessageTemplate::renderMessageTemplate([
      'html' => '{$tokenString}',
      // Check the space is stripped.
      'subject' => '{$tokenString} ',
      'text' => '{$tokenString}',
    ], TRUE, FALSE, ['tokenString' => 'Something really witty']);

    $this->assertEquals('{$tokenString}', $messageContent['text']);
    $this->assertEquals('{$tokenString}', $messageContent['html']);
    $this->assertEquals('{$tokenString}', $messageContent['subject']);
  }

  /**
   * Test rendering of domain tokens.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testContactTokens(): void {
    $this->createCustomGroupWithFieldOfType([], 'contact_reference');
    $tokenData = $this->getAllContactTokens();
    $this->callAPISuccess('Contact', 'create', $tokenData);
    $address = $this->callAPISuccess('Address', 'create', array_merge($tokenData, ['is_primary' => TRUE]));
    $this->callAPISuccess('Phone', 'create', array_merge($tokenData, ['is_primary' => TRUE]));
    $this->callAPISuccess('Email', 'create', array_merge($tokenData, ['is_primary' => TRUE]));
    $this->callAPISuccess('Website', 'create', array_merge($tokenData, ['is_primary' => TRUE]));
    $this->callAPISuccess('Im', 'create', ['is_primary' => TRUE, 'name' => $tokenData['im'], 'provider_id' => $tokenData['im_provider'], 'contact_id' => $tokenData['contact_id']]);
    $this->callAPISuccess('OpenID', 'create', array_merge($tokenData, ['is_primary' => TRUE, 'contact_id' => $tokenData['contact_id'], 'openid' => $tokenData['openid']]));

    CRM_Core_Smarty::singleton()->assign('pre_assigned_smarty', 'wee');
    // This string contains the 4 types of possible replaces just to be sure they
    // work in combination.
    $tokenString = '{$pre_assigned_smarty}{$passed_smarty}
{domain.name}
';
    foreach (array_keys($tokenData) as $key) {
      $tokenString .= "{$key}:{contact.{$key}}\n";
    }
    $messageContent = CRM_Core_BAO_MessageTemplate::renderMessageTemplate([
      'html' => $tokenString,
      // Check the space is stripped.
      'subject' => $tokenString . ' ',
      'text' => $tokenString,
    ], FALSE, $tokenData['contact_id'], ['passed_smarty' => 'whoa']);
    $checksum = substr($messageContent['html'], (strpos($messageContent['html'], 'cs=') + 3), 47);
    $contact = Contact::get(FALSE)->addWhere('id', '=', $tokenData['contact_id'])->setSelect(['modified_date', 'employer_id'])->execute()->first();
    $expected = 'weewhoa
Default Domain Name
contact_type:Individual
do_not_email:1
do_not_phone:
do_not_mail:1
do_not_sms:1
do_not_trade:1
is_opt_out:1
external_identifier:blah
sort_name:Smith, Robert
display_name:Mr. Robert Smith II
nick_name:Bob
image_URL:https://example.com
preferred_communication_method:
preferred_language:fr_CA
preferred_mail_format:Both
hash:xyz
contact_source:Contact Source
first_name:Robert
middle_name:Frank
last_name:Smith
individual_prefix:Mr.
individual_suffix:II
formal_title:Dogsbody
communication_style:Formal
email_greeting_id:Dear {contact.first_name}
postal_greeting_id:Dear {contact.first_name}
addressee_id:{contact.individual_prefix}{ } {contact.first_name}{ }{contact.middle_name}{ }{contact.last_name}{ }{contact.individual_suffix}
job_title:Busy person
gender:Female
birth_date:December 31st, 1998
current_employer_id:' . $contact['employer_id'] . '
contact_is_deleted:
created_date:January 1st, 2020 12:00 AM
modified_date:' . CRM_Utils_Date::customFormat($contact['modified_date']) . '
addressee:Mr. Robert Frank Smith II
email_greeting:Dear Robert
postal_greeting:Dear Robert
current_employer:Unit Test Organization
location_type:Home
address_id:' . $address['id'] . '
street_address:Street Address
street_number:123
street_number_suffix:S
street_name:Main St
street_unit:45B
supplemental_address_1:Round the corner
supplemental_address_2:Up the road
supplemental_address_3:By the big tree
city:New York
postal_code_suffix:4578
postal_code:90210
geo_code_1:48.858093
geo_code_2:2.294694
manual_geo_code:1
address_name:The white house
master_id:' . $tokenData['master_id'] . '
county:
state_province:TX
country:United States
phone:123-456
phone_ext:77
phone_type_id:
phone_type:Mobile
email:anthony_anderson@civicrm.org
on_hold:
signature_text:Yours sincerely
signature_html:&lt;p&gt;Yours&lt;/p&gt;
im_provider:1
im:IM Screen Name
openid:OpenID
world_region:America South, Central, North and Caribbean
url:http://civicrm.org
custom_1:Mr. Spider Man II
checksum:cs=' . $checksum . '
contact_id:' . $tokenData['contact_id'] . '
';
    $this->assertEquals($expected, $messageContent['html']);
    $this->assertEquals($expected, $messageContent['text']);
    $this->assertEquals(rtrim(str_replace("\n", ' ', $expected)), $messageContent['subject']);
  }

  /**
   * Gets the values needed to render domain tokens.
   *
   * This is keyed by all the available tokens and fills
   * them with sample data.
   *
   * @return array
   */
  protected function getDomainTokenData(): array {
    return [
      'name' => 'Default Domain Name',
      'phone' => 123,
      'address' => [
        'street_address' => 'Buckingham palace',
        'supplemental_address_1' => 'Up the road',
        'postal_code' => 90210,
        'geocode_1' => 789,
        'geocode_2' => 890,
        'city' => 'London',
      ],
      'email' => 'crown@example.com',
      'id' => CRM_Core_Config::domainID(),
      'description' => 'rather nice',
    ];
  }

  /**
   * Get all the available tokens with usable data.
   *
   * This is the result rendered from CRM_Core_SelectValues::contactTokens();
   * and has been gathered by calling that function.
   *
   * I have hard-coded them so we have a record of what we have
   * seemingly committed to support.
   *
   * Note it will render additional custom fields if they exist.
   *
   * @return array
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function getAllContactTokens(): array {
    return [
      'contact_type' => 'Individual',
      'do_not_email' => 1,
      'do_not_phone' => 0,
      'do_not_mail' => 1,
      'do_not_sms' => 1,
      'do_not_trade' => 1,
      'is_opt_out' => 1,
      'external_identifier' => 'blah',
      'sort_name' => 'Smith, Robert',
      'display_name' => 'Robert Smith',
      'nick_name' => 'Bob',
      'image_URL' => 'https://example.com',
      'preferred_communication_method' => 'Phone',
      'preferred_language' => 'fr_CA',
      'preferred_mail_format' => 'Both',
      'hash' => 'xyz',
      'contact_source' => 'Contact Source',
      'first_name' => 'Robert',
      'middle_name' => 'Frank',
      'last_name' => 'Smith',
      'individual_prefix' => 'Mr.',
      'individual_suffix' => 'II',
      'formal_title' => 'Dogsbody',
      'communication_style' => 'Formal',
      'email_greeting_id' => 1,
      'postal_greeting_id' => 1,
      'addressee_id' => 1,
      'job_title' => 'Busy person',
      'gender' => 'Female',
      'birth_date' => '1998-12-31',
      'current_employer_id' => $this->organizationCreate(),
      'contact_is_deleted' => 0,
      'created_date' => '2020-01-01',
      'modified_date' => '2020-01-01',
      'addressee' => '{contact.individual_prefix}{ } {contact.first_name}{ }{contact.middle_name}{ }{contact.last_name}{ }{contact.individual_suffix}',
      'email_greeting' => 'Dear {contact.first_name}',
      'postal_greeting' => 'Dear {contact.first_name}',
      'current_employer' => 'Unit Test Organization',
      'location_type' => 'Main',
      'address_id' => Address::create(FALSE)->setValues(['street_address' => 'Street Address'])->execute()->first()['id'],
      'street_address' => 'Street Address',
      'street_number' => '123',
      'street_number_suffix' => 'S',
      'street_name' => 'Main St',
      'street_unit' => '45B',
      'supplemental_address_1' => 'Round the corner',
      'supplemental_address_2' => 'Up the road',
      'supplemental_address_3' => 'By the big tree',
      'city' => 'New York',
      'postal_code_suffix' => '4578',
      'postal_code' => '90210',
      'geo_code_1' => '48.858093',
      'geo_code_2' => '2.294694',
      'manual_geo_code' => TRUE,
      'address_name' => 'The white house',
      'master_id' => $this->callAPISuccess('Address', 'create', [
        'contact_id' => $this->individualCreate(),
        'street_address' => 'Street Address',
        'street_number' => '123',
        'street_number_suffix' => 'S',
        'street_name' => 'Main St',
        'street_unit' => '45B',
        'supplemental_address_1' => 'Round the corner',
        'supplemental_address_2' => 'Up the road',
        'supplemental_address_3' => 'By the big tree',
        'city' => 'New York',
        'postal_code_suffix' => '4578',
        'postal_code' => '90210',
        'location_type' => 'Main',
      ])['id'],
      'county' => 'Harris County',
      'state_province' => 'Texas',
      'country' => 'United States',
      'phone' => '123-456',
      'phone_ext' => '77',
      'phone_type_id' => 'Mobile',
      'phone_type' => 'Mobile',
      'email' => 'anthony_anderson@civicrm.org',
      'on_hold' => FALSE,
      'signature_text' => 'Yours sincerely',
      'signature_html' => '<p>Yours</p>',
      'im_provider' => 'Yahoo',
      'im' => 'IM Screen Name',
      'openid' => 'OpenID',
      'world_region' => 'World Region',
      'url' => 'http://civicrm.org',
      $this->getCustomFieldName('contact_reference') => $this->individualCreate(['first_name' => 'Spider', 'last_name' => 'Man']),
      'checksum' => 'Checksum',
      'contact_id' => $this->individualCreate(['first_name' => 'Peter', 'last_name' => 'Parker']),
    ];
  }

}
