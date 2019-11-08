<?php

namespace Civi\Token;

use Symfony\Component\EventDispatcher\EventDispatcher;

class TokenCompatSubscriberTest extends \CiviUnitTestCase {

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $dispatcher;

  protected function setUp() {
    $this->useTransaction(TRUE);
    parent::setUp();
    $this->dispatcher = new EventDispatcher();
    $tcs = new TokenCompatSubscriber();
    $this->dispatcher->addListener(Events::TOKEN_EVALUATE, [
      $tcs,
      'onEvaluate',
    ]);
    $this->dispatcher->addListener(Events::TOKEN_RENDER, [$tcs, 'onRender']);
    $this->hookClass = \CRM_Utils_Hook::singleton();
  }

  /**
   *
   */
  public function testGreetingToken() {
    $cid = $this->individualCreate([
      'last_name' => 'Tester',
      'first_name' => 'Cata',
    ]);

    $p = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
    ]);
    $p->addMessage('greeting_html', '<p>{contact.email_greeting}</p>. {custom.foobar} Bye!', 'text/html');
    $p->addMessage('greeting_text', '{contact.email_greeting}. {custom.foobar} Bye!', 'text/plain');
    $p->addRow()
      ->context(['contactId' => $cid]);

    $expectHtml = [
      0 => '<p>Dear Cata</p>.  Bye!',
    ];

    $expectText = [
      0 => 'Dear Cata.  Bye!',
    ];

    foreach ($p->evaluate()->getRows() as $key => $row) {
      $this->assertEquals($expectHtml[$key], $row->render('greeting_html'));
      $this->assertEquals($expectText[$key], $row->render('greeting_text'));
    }
  }

  /**
   *
   */
  public function testGreetingTokenWIthCustomContactToken() {
    $this->hookClass->setHook('civicrm_tokens', [$this, 'hookTokens']);
    $this->hookClass->setHook('civicrm_tokenValues', [
      $this,
      'hookTokenValues',
    ]);
    $cid = $this->individualCreate([
      'last_name' => 'Tester',
      'first_name' => 'Cata',
    ]);

    $p = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
    ]);
    $p->addMessage('greeting_html', '<p>{contact.email_greeting}</p>. {custom.foobar} Bye!', 'text/html');
    $p->addMessage('greeting_text', '{contact.email_greeting}. {custom.foobar} Bye!', 'text/plain');
    $p->addRow()
      ->context(['contactId' => $cid]);

    $expectHtml = [
      0 => '<p>Dear Cata</p>.  Bye!',
    ];

    $expectText = [
      0 => 'Dear Cata.  Bye!',
    ];

    foreach ($p->evaluate()->getRows() as $key => $row) {
      $this->assertEquals($expectHtml[$key], $row->render('greeting_html'));
      $this->assertEquals($expectText[$key], $row->render('greeting_text'));
    }
  }

  public function hookTokens(&$tokens) {
    $tokens['contact'] = [
      'contact.something' => 'something in contact',
    ];
  }

  public function hookTokenValues(&$values, $cids, $job = NULL, $tokens = [], $context = NULL) {
    foreach ($cids as $cid) {
      $values[$cid]['contact.something'] = 'value in contact';
    }
  }

}
