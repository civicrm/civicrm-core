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

  protected function setUp(): void {
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

  public function testRenderLocalizedSmarty() {
    $this->dispatcher->addSubscriber(new TokenCompatSubscriber());
    $p = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
      'smarty' => TRUE,
    ]);
    $p->addMessage('text', '{ts}Yes{/ts} {ts}No{/ts}', 'text/plain');
    $p->addRow([]);
    $p->addRow(['locale' => 'fr_FR']);
    $p->addRow(['locale' => 'es_MX']);

    $expectText = [
      'Yes No',
      'Oui Non',
      'Sí No',
    ];

    $rowCount = 0;
    foreach ($p->evaluate()->getRows() as $key => $row) {
      /** @var TokenRow */
      $this->assertTrue($row instanceof TokenRow);
      $this->assertEquals($expectText[$key], $row->render('text'));
      $rowCount++;
    }
    $this->assertEquals(3, $rowCount);
  }

  public function testRenderLocalizedHookToken() {
    $cid = $this->individualCreate();

    $this->dispatcher->addSubscriber(new TokenCompatSubscriber());
    \Civi::dispatcher()->addListener('hook_civicrm_tokens', function($e) {
      $e->tokens['trans'] = [
        'trans.affirm' => ts('Translated affirmation'),
      ];
    });
    \Civi::dispatcher()->addListener('hook_civicrm_tokenValues', function($e) {
      if (in_array('affirm', $e->tokens['trans'])) {
        foreach ($e->contactIDs as $cid) {
          $e->details[$cid]['trans.affirm'] = ts('Yes');
        }
      }
    });

    $p = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
      'smarty' => FALSE,
    ]);
    $p->addMessage('text', '!!{trans.affirm}!!', 'text/plain');
    $p->addRow(['contactId' => $cid]);
    $p->addRow(['contactId' => $cid, 'locale' => 'fr_FR']);
    $p->addRow(['contactId' => $cid, 'locale' => 'es_MX']);

    $expectText = [
      '!!Yes!!',
      '!!Oui!!',
      '!!Sí!!',
    ];

    $rowCount = 0;
    foreach ($p->evaluate()->getRows() as $key => $row) {
      /** @var TokenRow */
      $this->assertTrue($row instanceof TokenRow);
      $this->assertEquals($expectText[$key], $row->render('text'));
      $rowCount++;
    }
    $this->assertEquals(3, $rowCount);
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

  /**
   * Inspired by dev/core#2673. This creates three custom tokens and uses each
   * of them in a different template (subject/body_text/body_html). Ensure
   * that all 3 tokens are properly evaluated.
   *
   * This is not literally the same as dev/core#2673. But that class of problem
   * could arise in different code-paths. This just ensures that it arise in
   * TokenProcessor.
   *
   * It also improves test-coverage of hooks and TokenProcessor.
   *
   * @link https://lab.civicrm.org/dev/core/-/issues/2673
   */
  public function testHookTokenDiagonal() {
    $cid = $this->individualCreate();

    $this->dispatcher->addSubscriber(new TokenCompatSubscriber());
    \Civi::dispatcher()->addListener('hook_civicrm_tokens', function($e) {
      $e->tokens['fruit'] = [
        'fruit.apple' => ts('Apple'),
        'fruit.banana' => ts('Banana'),
        'fruit.cherry' => ts('Cherry'),
      ];
    });
    \Civi::dispatcher()->addListener('hook_civicrm_tokenValues', function($e) {
      $fruits = array_intersect($e->tokens['fruit'], ['apple', 'banana', 'cherry']);
      foreach ($fruits as $fruit) {
        foreach ($e->contactIDs as $cid) {
          $e->details[$cid]['fruit.' . $fruit] = 'Nomnomnom' . $fruit;
        }
      }
    });

    $p = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
      'smarty' => FALSE,
    ]);
    $p->addMessage('subject', '!!{fruit.apple}!!', 'text/plain');
    $p->addMessage('body_html', '!!{fruit.banana}!!', 'text/html');
    $p->addMessage('body_text', '!!{fruit.cherry}!!', 'text/plain');
    $p->addMessage('other', 'No fruit :(', 'text/plain');
    $p->addRow(['contactId' => $cid]);
    $p->evaluate();

    foreach ($p->getRows() as $row) {
      $this->assertEquals('!!Nomnomnomapple!!', $row->render('subject'));
      $this->assertEquals('!!Nomnomnombanana!!', $row->render('body_html'));
      $this->assertEquals('!!Nomnomnomcherry!!', $row->render('body_text'));
      $this->assertEquals('No fruit :(', $row->render('other'));
      $looped = TRUE;
    }
    $this->assertTrue(isset($looped));
  }

}
