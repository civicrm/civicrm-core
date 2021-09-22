<?php

/**
 * The Token-Smarty template notation is a hybrid notation that combines `{entity.field}` tokens
 * and `{$smarty.expressions}`. At time of writing, this notation is commonly used with MessageTemplates.
 *
 * @group headless
 */
class CRM_Core_TokenSmartyTest extends CiviUnitTestCase {

  /**
   * @var int
   */
  protected $contactId;

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
    $this->contactId = $this->individualCreate([
      'first_name' => 'Bob',
      'last_name' => 'Roberts',
    ]);
  }

  /**
   * A template which uses both token-data and Smarty-data.
   */
  public function testMixedData() {
    $rendered = CRM_Core_TokenSmarty::render(
      ['msg_subject' => 'First name is {contact.first_name}. ExtraFoo is {$extra.foo}.'],
      ['contactId' => $this->contactId],
      ['extra' => ['foo' => 'foobar']]
    );
    $this->assertEquals('First name is Bob. ExtraFoo is foobar.', $rendered['msg_subject']);

    try {
      $modifiers = [
        '|crmDate:"shortdate"' => '02/01/2020',
        '|crmDate:"%B %Y"' => 'February 2020',
        '|crmDate' => 'February 1st, 2020  3:04 AM',
      ];
      foreach ($modifiers as $modifier => $expected) {
        CRM_Utils_Time::setTime('2020-02-01 03:04:05');
        $rendered = CRM_Core_TokenSmarty::render(
          ['msg_subject' => "Now is the token, {domain.now$modifier}! No, now is the smarty-pants, {\$extra.now$modifier}!"],
          ['contactId' => $this->contactId],
          ['extra' => ['now' => '2020-02-01 03:04:05']]
        );
        $this->assertEquals("Now is the token, $expected! No, now is the smarty-pants, $expected!", $rendered['msg_subject']);
      }
    }
    finally {
      \CRM_Utils_Time::resetTime();
    }
  }

  /**
   * A template which uses token-data as part of a Smarty expression.
   */
  public function testTokenInSmarty() {
    \CRM_Utils_Time::setTime('2022-04-08 16:32:04');
    $resetTime = \CRM_Utils_AutoClean::with(['CRM_Utils_Time', 'resetTime']);

    $rendered = CRM_Core_TokenSmarty::render(
      ['msg_html' => '<p>{assign var="greeting" value="{contact.email_greeting}"}Greeting: {$greeting}!</p>'],
      ['contactId' => $this->contactId],
      []
    );
    $this->assertEquals('<p>Greeting: Dear Bob!</p>', $rendered['msg_html']);

    $rendered = CRM_Core_TokenSmarty::render(
      ['msg_html' => '<p>{if !empty("{contact.contact_id}")}Yes CID{else}No CID{/if}</p>'],
      ['contactId' => $this->contactId],
      []
    );
    $this->assertEquals('<p>Yes CID</p>', $rendered['msg_html']);

    $rendered = CRM_Core_TokenSmarty::render(
      ['msg_html' => '<p>{assign var="greeting" value="hey yo {contact.first_name|upper} {contact.last_name|upper} circa {domain.now|crmDate:"%m/%Y"}"}My Greeting: {$greeting}!</p>'],
      ['contactId' => $this->contactId],
      []
    );
    $this->assertEquals('<p>My Greeting: hey yo BOB ROBERTS circa 04/2022!</p>', $rendered['msg_html']);

    $rendered = CRM_Core_TokenSmarty::render(
      ['msg_html' => '<p>{assign var="greeting" value="hey yo {contact.first_name} {contact.last_name|upper} circa {domain.now|crmDate:"shortdate"}"}My Greeting: {$greeting|capitalize}!</p>'],
      ['contactId' => $this->contactId],
      []
    );
    $this->assertEquals('<p>My Greeting: Hey Yo Bob ROBERTS Circa 04/08/2022!</p>', $rendered['msg_html']);
  }

  /**
   * A template that specifically opts out of Smarty.
   */
  public function testDisableSmarty(): void {
    $rendered = CRM_Core_TokenSmarty::render(
      ['msg_subject' => 'First name is {contact.first_name}. ExtraFoo is {$extra.foo}.'],
      ['contactId' => $this->contactId, 'smarty' => FALSE],
      ['extra' => ['foo' => 'foobar']]
    );
    $this->assertEquals('First name is Bob. ExtraFoo is {$extra.foo}.', $rendered['msg_subject']);
  }

  /**
   * Test smarty rendered dates using the setting short name.
   *
   * @param string $format
   * @param string $expected
   *
   * @dataProvider getDateFormats
   */
  public function testSmartySettingDates(string $format, string $expected = ''): void {
    $date = '2010-09-19 13:34:45';
    CRM_Core_Smarty::singleton()->assign('date', $date);
    $string = '{$date|crmDate:' . $format . '}';
    $this->assertEquals($expected, CRM_Utils_String::parseOneOffStringThroughSmarty($string));
  }

  /**
   * Get date formats to test.
   */
  public function getDateFormats(): array {
    return [
      ['Full', 'September 19th, 2010'],
      ['Datetime', 'September 19th, 2010  1:34 PM'],
      ['Partial', 'September 2010'],
      ['Time', ' 1:34 PM'],
      ['Year', '2010'],
      ['FinancialBatch', '09/19/2010'],
      ['shortdate', '09/19/2010'],
    ];
  }

  /**
   * Someone malicious gives cutesy expressions (via token-content) that tries to provoke extra evaluation.
   */
  public function testCutesyTokenData(): void {
    $cutesyContactId = $this->individualCreate([
      'first_name' => '{$extra.foo}{contact.last_name}',
      'last_name' => 'Roberts',
    ]);
    $rendered = CRM_Core_TokenSmarty::render(
      ['msg_subject' => 'First name is {contact.first_name}. ExtraFoo is {$extra.foo}.'],
      ['contactId' => $cutesyContactId],
      ['extra' => ['foo' => 'foobar']]
    );
    $this->assertEquals('First name is {$extra.foo}{contact.last_name}. ExtraFoo is foobar.', $rendered['msg_subject']);
  }

  /**
   * Someone malicious gives cutesy expressions (via Smarty-content) that tries to provoke extra evaluation.
   */
  public function testCutesySmartyData(): void {
    $rendered = CRM_Core_TokenSmarty::render(
      ['msg_subject' => 'First name is {contact.first_name}. ExtraFoo is {$extra.foo}.'],
      ['contactId' => $this->contactId],
      ['extra' => ['foo' => '{contact.last_name}{$extra.foo}']]
    );
    $this->assertEquals('First name is Bob. ExtraFoo is {contact.last_name}{$extra.foo}.', $rendered['msg_subject']);
  }

  /**
   * The same tokens are used in multiple parts of the template - without redundant evaluation.
   */
  public function testDataLoadCount(): void {
    // Define a token `{counter.i}` which increments whenever tokens are evaluated.
    Civi::dispatcher()->addListener('civi.token.eval', function (\Civi\Token\Event\TokenValueEvent $e) {
      static $i;
      foreach ($e->getRows() as $row) {
        /** @var \Civi\Token\TokenRow $row */
        $i = is_null($i) ? 1 : (1 + $i);
        $row->tokens('counter', 'i', 'eval#' . $i);
      }
    });
    $templates = [
      'subject' => 'Subject {counter.i}',
      'body' => 'Body {counter.i} is really {counter.i}.',
    ];
    $rendered = CRM_Core_TokenSmarty::render($templates, ['contactId' => $this->contactId]);
    $this->assertEquals('Subject eval#1', $rendered['subject']);
    $this->assertEquals('Body eval#1 is really eval#1.', $rendered['body']);

    $rendered = CRM_Core_TokenSmarty::render($templates, ['contactId' => $this->contactId]);
    $this->assertEquals('Subject eval#2', $rendered['subject']);
    $this->assertEquals('Body eval#2 is really eval#2.', $rendered['body']);

  }

}
