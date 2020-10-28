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
    $this->dispatcher->addListener('civi.token.list', [$this, 'onListTokens']);
    $this->dispatcher->addListener('civi.token.eval', [$this, 'onEvalTokens']);
    $this->counts = [
      'onListTokens' => 0,
      'onEvalTokens' => 0,
    ];
  }

  /**
   * Test that a row can be added via "addRow(array $context)".
   */
  public function testAddRow() {
    $p = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
    ]);
    $createdRow = $p->addRow(['one' => 'Apple'])
      ->context('two', 'Banana');
    $gotRow = $p->getRow(0);
    foreach ([$createdRow, $gotRow] as $row) {
      $this->assertEquals('Apple', $row->context['one']);
      $this->assertEquals('Banana', $row->context['two']);
    }
  }

  /**
   * Test that multiple rows can be added via "addRows(array $contexts)".
   */
  public function testAddRows() {
    $p = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
    ]);
    $createdRows = $p->addRows([
      ['one' => 'Apple', 'two' => 'Banana'],
      ['one' => 'Pomme', 'two' => 'Banane'],
    ]);
    $gotRow0 = $p->getRow(0);
    foreach ([$createdRows[0], $gotRow0] as $row) {
      $this->assertEquals('Apple', $row->context['one']);
      $this->assertEquals('Banana', $row->context['two']);
    }
    $gotRow1 = $p->getRow(1);
    foreach ([$createdRows[1], $gotRow1] as $row) {
      $this->assertEquals('Pomme', $row->context['one']);
      $this->assertEquals('Banane', $row->context['two']);
    }
  }

  /**
   * Check that the TokenRow helper can correctly read/update context
   * values.
   */
  public function testRowContext() {
    $p = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
      'omega' => '99',
    ]);
    $createdRow = $p->addRow()
      ->context('one', 1)
      ->context('two', [2 => 3])
      ->context([
        'two' => [4 => 5],
        'three' => [6 => 7],
        'omega' => '98',
      ]);
    $gotRow = $p->getRow(0);
    foreach ([$createdRow, $gotRow] as $row) {
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
    $p = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
      'omega' => '99',
    ]);
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
    $p = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
    ]);
    $createdRow = $p->addRow()
      ->tokens('one', 1)
      ->tokens('two', [2 => 3])
      ->tokens([
        'two' => [4 => 5],
        'three' => [6 => 7],
      ])
      ->tokens('four', 8, 9);
    $gotRow = $p->getRow(0);
    foreach ([$createdRow, $gotRow] as $row) {
      $this->assertEquals(1, $row->tokens['one']);
      $this->assertEquals(3, $row->tokens['two'][2]);
      $this->assertEquals(5, $row->tokens['two'][4]);
      $this->assertEquals(7, $row->tokens['three'][6]);
      $this->assertEquals(9, $row->tokens['four'][8]);
    }
  }

  public function testGetMessageTokens() {
    $p = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
    ]);
    $p->addMessage('greeting_html', 'Good morning, <p>{contact.display_name}</p>. {custom.foobar}!', 'text/html');
    $p->addMessage('greeting_text', 'Good morning, {contact.display_name}. {custom.whizbang}, {contact.first_name}!', 'text/plain');
    $expected = [
      'contact' => ['display_name', 'first_name'],
      'custom' => ['foobar', 'whizbang'],
    ];
    $this->assertEquals($expected, $p->getMessageTokens());
  }

  public function testListTokens() {
    $p = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
    ]);
    $p->addToken(['entity' => 'MyEntity', 'field' => 'myField', 'label' => 'My Label']);
    $this->assertEquals(['{MyEntity.myField}' => 'My Label'], $p->listTokens());
  }

  /**
   * Perform a full mail-merge, substituting multiple tokens for multiple
   * contacts in multiple messages.
   */
  public function testFull() {
    $p = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
    ]);
    $p->addMessage('greeting_html', 'Good morning, <p>{contact.display_name}</p>. {custom.foobar} Bye!', 'text/html');
    $p->addMessage('greeting_text', 'Good morning, {contact.display_name}. {custom.foobar} Bye!', 'text/plain');
    $p->addRow()
      ->context(['contact_id' => 123])
      ->format('text/plain')->tokens([
        'contact' => ['display_name' => 'What'],
      ]);
    $p->addRow()
      ->context(['contact_id' => 4])
      ->format('text/plain')->tokens([
        'contact' => ['display_name' => 'Who'],
      ]);
    $p->addRow()
      ->context(['contact_id' => 10])
      ->format('text/plain')->tokens([
        'contact' => ['display_name' => 'Darth Vader'],
      ]);

    $expectHtml = [
      0 => 'Good morning, <p>What</p>. #0123 is a good number. Trickster {contact.display_name}. Bye!',
      1 => 'Good morning, <p>Who</p>. #0004 is a good number. Trickster {contact.display_name}. Bye!',
      2 => 'Good morning, <p>Darth Vader</p>. #0010 is a good number. Trickster {contact.display_name}. Bye!',
    ];

    $expectText = [
      0 => 'Good morning, What. #0123 is a good number. Trickster {contact.display_name}. Bye!',
      1 => 'Good morning, Who. #0004 is a good number. Trickster {contact.display_name}. Bye!',
      2 => 'Good morning, Darth Vader. #0010 is a good number. Trickster {contact.display_name}. Bye!',
    ];

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
    $e->register('custom', [
      'foobar' => 'A special message about foobar',
    ]);
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
