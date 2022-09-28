<?php

/**
 * Class CRM_Utils_TokenTest
 * @group headless
 */
class CRM_Utils_TokenTest extends CiviUnitTestCase {

  /**
   * Test for replaceGreetingTokens.
   *
   */
  public function testReplaceGreetingTokens(): void {
    $tokenString = 'First Name: {contact.first_name} Last Name: {contact.last_name} Birth Date: {contact.birth_date} Prefix: {contact.prefix_id:label} Suffix: {contact.individual_suffix}';
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
   * This is a basic test of the token processor (currently testing TokenCompatSubscriber)
   *   and makes sure that greeting + contact tokens are replaced.
   * This is a good example to copy/expand when creating additional tests for token processor
   *   in "real" situations.
   */
  public function testTokenProcessor(): void {
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
