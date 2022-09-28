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
  }

  /**
   * A template which uses token-data as part of a Smarty expression.
   */
  public function testTokenInSmarty() {
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
  }

  /**
   * A template that specifically opts out of Smarty.
   */
  public function testDisableSmarty() {
    $rendered = CRM_Core_TokenSmarty::render(
      ['msg_subject' => 'First name is {contact.first_name}. ExtraFoo is {$extra.foo}.'],
      ['contactId' => $this->contactId, 'smarty' => FALSE],
      ['extra' => ['foo' => 'foobar']]
    );
    $this->assertEquals('First name is Bob. ExtraFoo is {$extra.foo}.', $rendered['msg_subject']);
  }

  /**
   * Someone malicious gives cutesy expressions (via token-content) that tries to provoke extra evaluation.
   */
  public function testCutesyTokenData() {
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
  public function testCutesySmartyData() {
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
  public function testDataLoadCount() {
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
