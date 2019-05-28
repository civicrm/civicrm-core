<?php
namespace Civi\Token;

use Civi\Token\Event\TokenRegisterEvent;
use Civi\Token\Event\TokenValueEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TokenProcessorTest extends \CiviUnitTestCase {

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $dispatcher;

  /**
   * @var array
   *   Array(string $funcName => int $invocationCount).
   */
  protected $counts;

  protected function setUp() {
    $this->useTransaction(TRUE);
    parent::setUp();
    $this->dispatcher = new EventDispatcher();
    $this->dispatcher->addListener(Events::TOKEN_REGISTER, array($this, 'onListTokens'));
    $this->dispatcher->addListener(Events::TOKEN_EVALUATE, array($this, 'onEvalTokens'));
    $this->counts = array(
      'onListTokens' => 0,
      'onEvalTokens' => 0,
    );
  }

  /**
   * Check that the TokenRow helper can correctly read/update context
   * values.
   */
  public function testRowContext() {
    $p = new TokenProcessor($this->dispatcher, array(
      'controller' => __CLASS__,
      'omega' => '99',
    ));
    $createdRow = $p->addRow()
      ->context('one', 1)
      ->context('two', array(2 => 3))
      ->context(array(
        'two' => array(4 => 5),
        'three' => array(6 => 7),
        'omega' => '98',
      ));
    $gotRow = $p->getRow(0);
    foreach (array($createdRow, $gotRow) as $row) {
      $this->assertEquals(1, $row->context['one']);
      $this->assertEquals(3, $row->context['two'][2]);
      $this->assertEquals(5, $row->context['two'][4]);
      $this->assertEquals(7, $row->context['three'][6]);
      $this->assertEquals(98, $row->context['omega']);
      $this->assertEquals(__CLASS__, $row->context['controller']);
    }
  }

  /**
   * Check that getContextValues() returns the correct data
   */
  public function testGetContextValues() {
    $p = new TokenProcessor($this->dispatcher, array(
      'controller' => __CLASS__,
      'omega' => '99',
    ));
    $p->addRow()->context('id', 10)->context('omega', '98');
    $p->addRow()->context('id', 10)->context('contact', (object) ['cid' => 10]);
    $p->addRow()->context('id', 11)->context('contact', (object) ['cid' => 11]);
    $this->assertArrayValuesEqual([10, 11], $p->getContextValues('id'));
    $this->assertArrayValuesEqual(['99', '98'], $p->getContextValues('omega'));
    $this->assertArrayValuesEqual([10, 11], $p->getContextValues('contact', 'cid'));
  }

  /**
   * Check that the TokenRow helper can correctly read/update token
   * values.
   */
  public function testRowTokens() {
    $p = new TokenProcessor($this->dispatcher, array(
      'controller' => __CLASS__,
    ));
    $createdRow = $p->addRow()
      ->tokens('one', 1)
      ->tokens('two', array(2 => 3))
      ->tokens(array(
        'two' => array(4 => 5),
        'three' => array(6 => 7),
      ))
      ->tokens('four', 8, 9);
    $gotRow = $p->getRow(0);
    foreach (array($createdRow, $gotRow) as $row) {
      $this->assertEquals(1, $row->tokens['one']);
      $this->assertEquals(3, $row->tokens['two'][2]);
      $this->assertEquals(5, $row->tokens['two'][4]);
      $this->assertEquals(7, $row->tokens['three'][6]);
      $this->assertEquals(9, $row->tokens['four'][8]);
    }
  }

  public function testGetMessageTokens() {
    $p = new TokenProcessor($this->dispatcher, array(
      'controller' => __CLASS__,
    ));
    $p->addMessage('greeting_html', 'Good morning, <p>{contact.display_name}</p>. {custom.foobar}!', 'text/html');
    $p->addMessage('greeting_text', 'Good morning, {contact.display_name}. {custom.whizbang}, {contact.first_name}!', 'text/plain');
    $expected = array(
      'contact' => array('display_name', 'first_name'),
      'custom' => array('foobar', 'whizbang'),
    );
    $this->assertEquals($expected, $p->getMessageTokens());
  }

  public function testListTokens() {
    $p = new TokenProcessor($this->dispatcher, array(
      'controller' => __CLASS__,
    ));
    $p->addToken(array('entity' => 'MyEntity', 'field' => 'myField', 'label' => 'My Label'));
    $this->assertEquals(array('{MyEntity.myField}' => 'My Label'), $p->listTokens());
  }

  /**
   * Perform a full mail-merge, substituting multiple tokens for multiple
   * contacts in multiple messages.
   */
  public function testFull() {
    $p = new TokenProcessor($this->dispatcher, array(
      'controller' => __CLASS__,
    ));
    $p->addMessage('greeting_html', 'Good morning, <p>{contact.display_name}</p>. {custom.foobar} Bye!', 'text/html');
    $p->addMessage('greeting_text', 'Good morning, {contact.display_name}. {custom.foobar} Bye!', 'text/plain');
    $p->addRow()
      ->context(array('contact_id' => 123))
      ->format('text/plain')->tokens(array(
        'contact' => array('display_name' => 'What'),
      ));
    $p->addRow()
      ->context(array('contact_id' => 4))
      ->format('text/plain')->tokens(array(
        'contact' => array('display_name' => 'Who'),
      ));
    $p->addRow()
      ->context(array('contact_id' => 10))
      ->format('text/plain')->tokens(array(
        'contact' => array('display_name' => 'Darth Vader'),
      ));

    $expectHtml = array(
      0 => 'Good morning, <p>What</p>. #0123 is a good number. Trickster {contact.display_name}. Bye!',
      1 => 'Good morning, <p>Who</p>. #0004 is a good number. Trickster {contact.display_name}. Bye!',
      2 => 'Good morning, <p>Darth Vader</p>. #0010 is a good number. Trickster {contact.display_name}. Bye!',
    );

    $expectText = array(
      0 => 'Good morning, What. #0123 is a good number. Trickster {contact.display_name}. Bye!',
      1 => 'Good morning, Who. #0004 is a good number. Trickster {contact.display_name}. Bye!',
      2 => 'Good morning, Darth Vader. #0010 is a good number. Trickster {contact.display_name}. Bye!',
    );

    $rowCount = 0;
    foreach ($p->evaluate()->getRows() as $key => $row) {
      /** @var TokenRow */
      $this->assertTrue($row instanceof TokenRow);
      $this->assertEquals($expectHtml[$key], $row->render('greeting_html'));
      $this->assertEquals($expectText[$key], $row->render('greeting_text'));
      $rowCount++;
    }
    $this->assertEquals(3, $rowCount);
    // This may change in the future.
    $this->assertEquals(0, $this->counts['onListTokens']);
    $this->assertEquals(1, $this->counts['onEvalTokens']);
  }

  public function onListTokens(TokenRegisterEvent $e) {
    $this->counts[__FUNCTION__]++;
    $e->register('custom', array(
      'foobar' => 'A special message about foobar',
    ));
  }

  public function onEvalTokens(TokenValueEvent $e) {
    $this->counts[__FUNCTION__]++;
    foreach ($e->getRows() as $row) {
      /** @var TokenRow $row */
      $row->format('text/html');
      $row->tokens['custom']['foobar'] = sprintf("#%04d is a good number. Trickster {contact.display_name}.", $row->context['contact_id']);
    }
  }

}
