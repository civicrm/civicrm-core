<?php

use Civi\Api4\Address;
use Civi\Api4\Contact;
use Civi\Token\TokenProcessor;

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
    $this->quickCleanup(['civicrm_address', 'civicrm_phone', 'civicrm_im', 'civicrm_website', 'civicrm_openid', 'civicrm_email'], TRUE);
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

    [, $subject, $message] = CRM_Core_BAO_MessageTemplate::sendTemplate(
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
    $this->assertStringContainsString('Your Case Role', $message);
    $this->assertStringContainsString('Case ID : 1234', $message);
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
    // Freeze the time at the start of the test, so checksums don't suffer from second rollovers.
    putenv('TIME_FUNC=frozen');
    CRM_Utils_Time::setTime(date('Y-m-d H:i:s'));

    $this->createCustomGroupWithFieldsOfAllTypes([]);
    $tokenData = $this->getAllContactTokens();
    $address = $this->setupContactFromTokeData($tokenData);

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
    $expected = 'weewhoa
Default Domain Name
';
    $expected .= $this->getExpectedContactOutput($address['id'], $tokenData, $messageContent['html']);
    $this->assertEquals($expected, $messageContent['html']);
    $this->assertEquals($expected, $messageContent['text']);
    $checksum_position = strpos($messageContent['subject'], 'cs=');
    $this->assertTrue($checksum_position !== FALSE);
    $fixedExpected = rtrim(str_replace("\n", ' ', $expected));
    $this->assertEquals(substr($fixedExpected, 0, $checksum_position), substr($messageContent['subject'], 0, $checksum_position));
    $returned_parts = explode('_', substr($messageContent['subject'], $checksum_position));
    $expected_parts = explode('_', substr($fixedExpected, $checksum_position));
    $this->assertEquals($expected_parts[0], $returned_parts[0]);
    $this->assertApproxEquals($expected_parts[1], $returned_parts[1], 2);
    $this->assertEquals($expected_parts[2], $returned_parts[2]);

    // reset time
    putenv('TIME_FUNC');
    CRM_Utils_Time::resetTime();
  }

  /**
   * Test the contact tokens rendered by the token processor.
   *
   * This test will be obsolete once the renderMessageTemplate
   * function uses the token processor - at that point the test above
   * will be testing the same thing.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testContactTokensRenderedByTokenProcessor(): void {
    $this->createCustomGroupWithFieldsOfAllTypes([]);
    $tokenData = $this->getAllContactTokens();
    $address = $this->setupContactFromTokeData($tokenData);
    $tokenString = '';
    foreach (array_keys($tokenData) as $key) {
      $tokenString .= "{$key}:{contact.{$key}}\n";
    }
    $tokenProcessor = new TokenProcessor(Civi::dispatcher(), []);
    $tokenProcessor->addMessage('html', $tokenString, 'text/html');
    $tokenProcessor->addRow(['contactId' => $tokenData['contact_id']]);
    $tokenProcessor->evaluate();
    $rendered = '';
    foreach ($tokenProcessor->getRows() as $row) {
      $rendered = (string) $row->render('html');
    }
    $expected = $this->getExpectedContactOutput($address['id'], $tokenData, $rendered);
    // @todo - this works better in token processor than in CRM_Core_Token.
    // once synced we can fix $this->getExpectedContactOutput to return the right thing.
    $expected = str_replace("preferred_communication_method:\n", "preferred_communication_method:Phone\n", $expected);
    $this->assertEquals($expected, $rendered);
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
      $this->getCustomFieldName('text') => 'Bobsled',
      $this->getCustomFieldName('select_string') => 'R',
      $this->getCustomFieldName('select_date') => '2021-01-20',
      $this->getCustomFieldName('int') => 999,
      $this->getCustomFieldName('link') => 'http://civicrm.org',
      $this->getCustomFieldName('country') => 'New Zealand',
      $this->getCustomFieldName('multi_country') => ['France', 'Canada'],
      $this->getCustomFieldName('contact_reference') => $this->individualCreate(['first_name' => 'Spider', 'last_name' => 'Man']),
      $this->getCustomFieldName('state') => 'Queensland',
      $this->getCustomFieldName('multi_state') => ['Victoria', 'New South Wales'],
      $this->getCustomFieldName('boolean') => TRUE,
      $this->getCustomFieldName('checkbox') => 'P',
      $this->getCustomFieldName('contact_reference') => $this->individualCreate(['first_name' => 'Spider', 'last_name' => 'Man']),
      'checksum' => 'Checksum',
      'contact_id' => $this->individualCreate(['first_name' => 'Peter', 'last_name' => 'Parker']),
    ];
  }

  /**
   * @param array $tokenData
   *
   * @return array|int
   * @throws \CRM_Core_Exception
   */
  protected function setupContactFromTokeData(array $tokenData) {
    $this->callAPISuccess('Contact', 'create', $tokenData);
    $address = $this->callAPISuccess('Address', 'create', array_merge($tokenData, ['is_primary' => TRUE]));
    $this->callAPISuccess('Phone', 'create', array_merge($tokenData, ['is_primary' => TRUE]));
    $this->callAPISuccess('Email', 'create', array_merge($tokenData, ['is_primary' => TRUE]));
    $this->callAPISuccess('Website', 'create', array_merge($tokenData, ['is_primary' => TRUE]));
    $this->callAPISuccess('Im', 'create', [
      'is_primary' => TRUE,
      'name' => $tokenData['im'],
      'provider_id' => $tokenData['im_provider'],
      'contact_id' => $tokenData['contact_id'],
    ]);
    $this->callAPISuccess('OpenID', 'create', array_merge($tokenData, [
      'is_primary' => TRUE,
      'contact_id' => $tokenData['contact_id'],
      'openid' => $tokenData['openid'],
    ]));
    return $address;
  }

  /**
   * Get the expected rendered string.
   *
   * @param int $id
   * @param array $tokenData
   * @param string $actualOutput
   *
   * @return string
   * @throws \API_Exception
   */
  protected function getExpectedContactOutput($id, array $tokenData, string $actualOutput): string {
    $checksum = substr($actualOutput, (strpos($actualOutput, 'cs=') + 3), 47);
    $contact = Contact::get(FALSE)->addWhere('id', '=', $tokenData['contact_id'])->setSelect(['modified_date', 'employer_id'])->execute()->first();
    $expected = 'contact_type:Individual
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
preferred_communication_method:Phone
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
address_id:' . $id . '
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
custom_1:Bobsled
custom_2:Red
custom_3:01/20/2021 12:00AM
custom_4:999
custom_5:<a href="http://civicrm.org" target="_blank">http://civicrm.org</a>
custom_7:New Zealand
custom_8:France, Canada
custom_9:Mr. Spider Man II
custom_10:Queensland
custom_11:Victoria, New South Wales
custom_12:Yes
custom_13:Purple
checksum:cs=' . $checksum . '
contact_id:' . $tokenData['contact_id'] . '
';
    return $expected;
  }

}
