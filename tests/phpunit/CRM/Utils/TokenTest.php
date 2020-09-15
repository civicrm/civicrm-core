<?php

/**
 * Class CRM_Utils_TokenTest
 * @group headless
 */
class CRM_Utils_TokenTest extends CiviUnitTestCase {

  /**
   * Basic test on getTokenDetails function.
   */
  public function testGetTokenDetails() {
    $contactID = $this->individualCreate(['preferred_communication_method' => ['Phone', 'Fax']]);
    $resolvedTokens = CRM_Utils_Token::getTokenDetails([$contactID]);
    $this->assertEquals('Phone, Fax', $resolvedTokens[0][$contactID]['preferred_communication_method']);
  }

  /**
   * Test getting contacts w/o primary location type
   *
   * Check for situation described in CRM-19876.
   */
  public function testSearchByPrimaryLocation() {
    // Disable searchPrimaryDetailsOnly civi settings so we could test the functionality without it.
    Civi::settings()->set('searchPrimaryDetailsOnly', '0');

    // create a contact with multiple email address and among which one is primary
    $contactID = $this->individualCreate();
    $primaryEmail = uniqid() . '@primary.com';
    $this->callAPISuccess('Email', 'create', [
      'contact_id' => $contactID,
      'email' => $primaryEmail,
      'location_type_id' => 'Other',
      'is_primary' => 1,
    ]);
    $this->callAPISuccess('Email', 'create', [
      'contact_id' => $contactID,
      'email' => uniqid() . '@galaxy.com',
      'location_type_id' => 'Work',
      'is_primary' => 0,
    ]);
    $this->callAPISuccess('Email', 'create', [
      'contact_id' => $contactID,
      'email' => uniqid() . '@galaxy.com',
      'location_type_id' => 'Work',
      'is_primary' => 0,
    ]);

    $contactIDs = [$contactID];

    // when we are fetching contact details ON basis of primary address fields
    $contactDetails = CRM_Utils_Token::getTokenDetails($contactIDs);
    $this->assertEquals($primaryEmail, $contactDetails[0][$contactID]['email']);

    // restore setting
    Civi::settings()->set('searchPrimaryDetailsOnly', '1');
  }

  /**
   * Test for replaceGreetingTokens.
   *
   */
  public function testReplaceGreetingTokens() {
    $tokenString = 'First Name: {contact.first_name} Last Name: {contact.last_name} Birth Date: {contact.birth_date} Prefix: {contact.prefix_id} Suffix: {contact.individual_suffix}';
    $contactDetails = [
      [
        2811 => [
          'id' => '2811',
          'contact_type' => 'Individual',
          'first_name' => 'Morticia',
          'last_name' => 'Addams',
          'prefix_id' => 2,
        ],
      ],
    ];
    $contactId = 2811;
    $className = 'CRM_Contact_BAO_Contact';
    $escapeSmarty = TRUE;
    CRM_Utils_Token::replaceGreetingTokens($tokenString, $contactDetails, $contactId, $className, $escapeSmarty);
    $this->assertEquals($tokenString, 'First Name: Morticia Last Name: Addams Birth Date:  Prefix: Ms. Suffix: ');

    // Test compatibility with custom tokens (#14943)
    $tokenString = 'Custom {custom.custom}';
    CRM_Utils_Token::replaceGreetingTokens($tokenString, $contactDetails, $contactId, $className, $escapeSmarty);
    $this->assertEquals($tokenString, 'Custom ');
  }

  /**
   * Test getting multiple contacts.
   *
   * Check for situation described in CRM-19876.
   */
  public function testGetTokenDetailsMultipleEmails() {
    $i = 0;

    $params = [
      'do_not_phone' => 1,
      'do_not_email' => 0,
      'do_not_mail' => 1,
      'do_not_sms' => 1,
      'do_not_trade' => 1,
      'is_opt_out' => 0,
      'email' => 'guardians@galaxy.com',
      'legal_identifier' => 'convict 56',
      'nick_name' => 'bob',
      'contact_source' => 'bargain basement',
      'formal_title' => 'Your silliness',
      'job_title' => 'World Saviour',
      'gender_id' => '1',
      'birth_date' => '2017-01-01',
      // 'city' => 'Metropolis',
    ];
    $contactIDs = [];
    while ($i < 27) {
      $contactIDs[] = $contactID = $this->individualCreate($params);
      $this->callAPISuccess('Email', 'create', [
        'contact_id' => $contactID,
        'email' => 'goodguy@galaxy.com',
        'location_type_id' => 'Other',
        'is_primary' => 0,
      ]);
      $this->callAPISuccess('Email', 'create', [
        'contact_id' => $contactID,
        'email' => 'villain@galaxy.com',
        'location_type_id' => 'Work',
        'is_primary' => 1,
      ]);
      $i++;
    }
    unset($params['email']);

    $resolvedTokens = CRM_Utils_Token::getTokenDetails($contactIDs);
    foreach ($contactIDs as $contactID) {
      $resolvedContactTokens = $resolvedTokens[0][$contactID];
      $this->assertEquals('Individual', $resolvedContactTokens['contact_type']);
      $this->assertEquals('Anderson, Anthony', $resolvedContactTokens['sort_name']);
      $this->assertEquals('en_US', $resolvedContactTokens['preferred_language']);
      $this->assertEquals('Both', $resolvedContactTokens['preferred_mail_format']);
      $this->assertEquals(3, $resolvedContactTokens['prefix_id']);
      $this->assertEquals(3, $resolvedContactTokens['suffix_id']);
      $this->assertEquals('Mr. Anthony J. Anderson II', $resolvedContactTokens['addressee_display']);
      $this->assertEquals('villain@galaxy.com', $resolvedContactTokens['email']);

      foreach ($params as $key => $value) {
        $this->assertEquals($value, $resolvedContactTokens[$key]);
      }
    }
  }

  /**
   * This is a basic test of the token processor (currently testing TokenCompatSubscriber)
   *   and makes sure that greeting + contact tokens are replaced.
   * This is a good example to copy/expand when creating additional tests for token processor
   *   in "real" situations.
   *
   * @throws \CRM_Core_Exception
   */
  public function testTokenProcessor() {
    $params['contact_id'] = $this->individualCreate();

    // Prepare the processor and general context.
    $tokenProc = new \Civi\Token\TokenProcessor(\Civi::dispatcher(), [
      // Unique(ish) identifier for our controller/use-case.
      'controller' => 'civicrm_tokentest',

      // Provide hints about what data will be available for each row.
      // Ex: 'schema' => ['contactId', 'activityId', 'caseId'],
      'schema' => ['contactId'],

      // Whether to enable Smarty evaluation.
      'smarty' => (defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY),
    ]);

    // Define message templates.
    $tokenProc->addMessage('body_html', 'Good morning, <p>{contact.email_greeting} {contact.display_name}</p>. {custom.foobar} Bye!', 'text/html');
    $tokenProc->addMessage('body_text', 'Good morning, {contact.email_greeting} {contact.display_name} Bye!', 'text/plain');

    $expect[$params['contact_id']]['html'] = 'Good morning, <p>Dear Anthony Mr. Anthony Anderson II</p>.  Bye!';
    $expect[$params['contact_id']]['text'] = 'Good morning, Dear Anthony Mr. Anthony Anderson II Bye!';

    // Define row data.
    foreach (explode(',', $params['contact_id']) as $contactId) {
      $context = ['contactId' => $contactId];
      $tokenProc->addRow()->context($context);
    }

    $tokenProc->evaluate();

    $this->assertNotEmpty($tokenProc->getRows());
    foreach ($tokenProc->getRows() as $tokenRow) {
      /** @var \Civi\Token\TokenRow $tokenRow */
      $html = $tokenRow->render('body_html');
      $text = $tokenRow->render('body_text');
      $this->assertEquals($expect[$params['contact_id']]['html'], $html);
      $this->assertEquals($expect[$params['contact_id']]['text'], $text);
    }
  }

}
