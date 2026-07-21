<?php

/**
 * @group headless
 */
class CRM_Mailing_TokensTest extends \CiviUnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    $this->useTransaction();
    $this->callAPISuccess('mail_settings', 'get',
      ['api.mail_settings.create' => ['domain' => 'chaos.org']]);
  }

  public static function getExampleTokens() {
    $cases = [];

    $cases[] = ['text/plain', 'The {mailing.id}!', ';The [0-9]+!;'];
    $cases[] = ['text/plain', 'The {mailing.name}!', ';The Example Name!;'];
    $cases[] = ['text/plain', 'The {mailing.editUrl}!', ';The http.*civicrm/mailing/send.*!;'];
    $cases[] = ['text/plain', 'To subscribe: {action.subscribeUrl}!', ';To subscribe: http.*civicrm/mailing/subscribe.*!;'];
    $cases[] = ['text/plain', 'To optout: {action.optOutUrl}!', ';To optout: http.*civicrm/mailing/optout.*!;'];
    $cases[] = ['text/plain', 'To unsubscribe: {action.unsubscribe}!', ';To unsubscribe: u\.123\.456\.abcd1234@chaos.org!;'];

    // TODO: Think about supporting dynamic tokens like "{action.subscribe.\d+}"

    return $cases;
  }

  /**
   * Check that mailing-tokens are generated (given a mailing_id as input).
   *
   * @param string $inputTemplateFormat
   *   Ex: 'text/plain' or 'text/html'
   * @param string $inputTemplate
   *   Ex: 'Hello, {contact.first_name}'.
   * @param string $expectRegex
   * @dataProvider getExampleTokens
   */
  public function testTokensWithMailingId($inputTemplateFormat, $inputTemplate, $expectRegex) {
    $mailing = CRM_Core_DAO::createTestObject('CRM_Mailing_DAO_Mailing', [
      'name' => 'Example Name',
    ]);
    $contact = CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact');

    $p = new \Civi\Token\TokenProcessor(Civi::dispatcher(), [
      'mailingId' => $mailing->id,
    ]);
    $p->addMessage('example', $inputTemplate, $inputTemplateFormat);
    $p->addRow()->context([
      'contactId' => $contact->id,
      'mailingJobId' => 123,
      'mailingActionTarget' => [
        'id' => 456,
        'hash' => 'abcd1234',
        'email' => 'someone@example.com',
      ],
    ]);
    $p->evaluate();
    $count = 0;
    foreach ($p->getRows() as $row) {
      $this->assertMatchesRegularExpression($expectRegex, $row->render('example'));
      $count++;
    }
    $this->assertEquals(1, $count);
  }

  /**
   * Check that mailing-tokens are generated (given a mailing DAO as input).
   */
  public function testTokensWithMailingObject(): void {
    // We only need one case to see that the mailing-object works as
    // an alternative to the mailing-id.
    $inputTemplateFormat = 'text/plain';
    $inputTemplate = 'To optout: {action.optOutUrl}!';
    $expectRegex = ';To optout: http.*civicrm/mailing/optout.*!;';

    $mailing = CRM_Core_DAO::createTestObject('CRM_Mailing_DAO_Mailing', [
      'name' => 'Example Name',
    ]);
    $contact = CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact');

    $p = new \Civi\Token\TokenProcessor(Civi::dispatcher(), [
      'mailing' => $mailing,
    ]);
    $p->addMessage('example', $inputTemplate, $inputTemplateFormat);
    $p->addRow()->context([
      'contactId' => $contact->id,
      'mailingJobId' => 123,
      'mailingActionTarget' => [
        'id' => 456,
        'hash' => 'abcd1234',
        'email' => 'someone@example.com',
      ],
    ]);
    $p->evaluate();
    $count = 0;
    foreach ($p->getRows() as $row) {
      $this->assertMatchesRegularExpression($expectRegex, $row->render('example'));
      $count++;
    }
    $this->assertEquals(1, $count);
  }

  public static function getExampleTokensForUseWithoutMailingJob() {
    $cases = [];
    $cases[] = ['text/plain', 'To opt out: {action.optOutUrl}!', '@To opt out: .*civicrm/mailing/optout.*&jid=&qid=@'];
    $cases[] = ['text/html', 'To opt out: <a href="{action.optOutUrl}">click here</a>!', '@To opt out: <a href=".*civicrm/mailing/optout.*&amp;jid=&amp;qid=.*">click@'];
    return $cases;
  }

  /**
   * When previewing a mailing, there is no active mailing job, so one cannot
   * generate fully formed URLs which reference the job. The current behavior
   * is to link to a placeholder URL which has blank values for key fields
   * like `jid` and `qid`.
   *
   * This current behavior may be wise or unwise - either way, having ensures
   * that changes are intentional.
   *
   * @dataProvider getExampleTokensForUseWithoutMailingJob
   */
  public function testTokensWithoutMailingJob($inputTemplateFormat, $inputTemplateText, $expectRegex) {
    $mailing = CRM_Core_DAO::createTestObject('CRM_Mailing_DAO_Mailing', [
      'name' => 'Example Name',
    ]);
    $contact = CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact');

    $p = new \Civi\Token\TokenProcessor(Civi::dispatcher(), [
      'mailing' => $mailing,
    ]);
    $p->addMessage('example', $inputTemplateText, $inputTemplateFormat);
    $p->addRow()->context([
      'contactId' => $contact->id,
    ]);
    //    try {
    //      $p->evaluate();
    //      $this->fail('TokenProcessor::evaluate() should have thrown an exception');
    //    }
    //    catch (CRM_Core_Exception $e) {
    //      $this->assertMatchesRegularExpression(';Cannot use action tokens unless context defines mailingJobId and mailingActionTarget;', $e->getMessage());
    //    }

    $p->evaluate();

    // FIXME: For compatibility with
    $actual = $p->getRow(0)->render('example');
    $this->assertMatchesRegularExpression($expectRegex, $actual);
  }

  /**
   * Test Api4-style custom field tokens, option labels, and chained entity reference tokens for mailings.
   */
  public function testApi4CustomFieldTokens(): void {
    $targetContact = $this->callAPISuccess('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'Jane',
      'last_name' => 'Author',
    ]);

    $customGroup = $this->callAPISuccess('CustomGroup', 'create', [
      'title' => 'Mailing Extra Info',
      'name' => 'mailing_extra_info',
      'extends' => 'Mailing',
    ]);

    $this->callAPISuccess('CustomField', 'create', [
      'custom_group_id' => $customGroup['id'],
      'label' => 'Special Note',
      'name' => 'special_note',
      'html_type' => 'Text',
      'data_type' => 'String',
    ]);

    $this->callAPISuccess('CustomField', 'create', [
      'custom_group_id' => $customGroup['id'],
      'label' => 'Priority Level',
      'name' => 'priority_level',
      'html_type' => 'Select',
      'data_type' => 'String',
      'option_values' => [
        'high_prio' => 'High Priority',
        'low_prio' => 'Low Priority',
      ],
    ]);

    $this->callAPISuccess('CustomField', 'create', [
      'custom_group_id' => $customGroup['id'],
      'label' => 'Assigned Author',
      'name' => 'assigned_author',
      'html_type' => 'Autocomplete-Select',
      'data_type' => 'ContactReference',
    ]);

    Civi::cache('metadata')->flush();

    $mailing = \Civi\Api4\Mailing::create(FALSE)
      ->setValues([
        'name' => 'Custom Token Mailing',
        'subject' => 'Test Subject',
        'mailing_extra_info.special_note' => 'Custom Note Value',
        'mailing_extra_info.priority_level' => 'high_prio',
        'mailing_extra_info.assigned_author' => $targetContact['id'],
      ])->execute()->first();

    $p = new \Civi\Token\TokenProcessor(Civi::dispatcher(), [
      'mailingId' => $mailing['id'],
    ]);

    $template = 'Note: {mailing.mailing_extra_info.special_note} | Code: {mailing.mailing_extra_info.priority_level} | Label: {mailing.mailing_extra_info.priority_level:label} | Author: {mailing.mailing_extra_info.assigned_author.display_name}';
    $p->addMessage('test_msg', $template, 'text/plain');
    $p->addRow()->context(['contactId' => $targetContact['id']]);
    $p->evaluate();

    $rendered = $p->getRow(0)->render('test_msg');
    $this->assertEquals(
      'Note: Custom Note Value | Code: high_prio | Label: High Priority | Author: Jane Author',
      $rendered
    );
  }

}
