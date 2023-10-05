<?php
namespace Civi\Token;

use Brick\Money\Context\DefaultContext;
use Brick\Money\Money;
use Civi\Api4\Website;
use Civi\Token\Event\TokenRegisterEvent;
use Civi\Token\Event\TokenValueEvent;
use Civi\Core\CiviEventDispatcher;

class TokenProcessorTest extends \CiviUnitTestCase {

  /**
   * @var \Civi\Core\CiviEventDispatcher
   */
  protected $dispatcher;

  /**
   * @var array
   *   Array(string $funcName => int $invocationCount).
   */
  protected $counts;

  protected function setUp(): void {
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->dispatcher = new CiviEventDispatcher();
    $this->dispatcher->addListener('civi.token.list', [$this, 'onListTokens']);
    $this->dispatcher->addListener('civi.token.eval', [$this, 'onEvalTokens']);
    $this->counts = [
      'onListTokens' => 0,
      'onEvalTokens' => 0,
    ];
  }

  /**
   * The visitTokens() method is internal - but it is important basis for other
   * methods. Specifically, it parses all token expressions and invokes a
   * callback for each.
   *
   * Ensure these callbacks get the expected data (with various quirky
   * notations).
   *
   * @throws \CRM_Core_Exception
   */
  public function testVisitTokens(): void {
    $p = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
    ]);
    $examples = [
      '{foo.bar}' => ['foo', 'bar', NULL],
      '{foo.bar|whiz}' => ['foo', 'bar', ['whiz']],
      '{foo.bar|whiz:"bang"}' => ['foo', 'bar', ['whiz', 'bang']],
      '{FoO.bAr|whiz:"bang"}' => ['FoO', 'bAr', ['whiz', 'bang']],
      '{oo_f.ra_b|b_52:"bang":"b@ng, on +he/([do0r])?!"}' => ['oo_f', 'ra_b', ['b_52', 'bang', 'b@ng, on +he/([do0r])?!']],
      '{foo.bar.whiz}' => ['foo', 'bar.whiz', NULL],
      '{foo.bar.whiz|bang}' => ['foo', 'bar.whiz', ['bang']],
      '{foo.bar:label}' => ['foo', 'bar:label', NULL],
      '{foo.bar:label|truncate:"10"}' => ['foo', 'bar:label', ['truncate', '10']],
    ];
    foreach ($examples as $input => $expected) {
      array_unshift($expected, $input);
      $log = [];
      $filtered = $p->visitTokens($input, function (?string $fullToken, ?string $entity, ?string $field, ?array $modifier) use (&$log) {
        $log[] = [$fullToken, $entity, $field, $modifier];
        return 'Replaced!';
      });
      $this->assertCount(1, $log, "Should receive one callback on expression: $input");
      $this->assertEquals($expected, $log[0]);
      $this->assertEquals('Replaced!', $filtered);
    }
  }

  /**
   * Test that a row can be added via "addRow(array $context)".
   */
  public function testAddRow(): void {
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
  public function testAddRows(): void {
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
  public function testRowContext(): void {
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
  public function testGetContextValues(): void {
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
  public function testRowTokens(): void {
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

  public function getPartialNonPartial(): array {
    return [
      'no-partial' => [['partial_locales' => FALSE]],
      'yes-partial' => [['partial_locales' => TRUE]],
    ];
  }

  /**
   * @group locale
   * @dataProvider getPartialNonPartial
   *
   * @param array $settings
   *
   * @throws \CRM_Core_Exception
   */
  public function testRenderLocalizedSmarty(array $settings): void {
    $cleanup = \CRM_Utils_AutoClean::swapSettings($settings);
    \CRM_Utils_Time::setTime('2022-04-08 16:32:04');
    $resetTime = \CRM_Utils_AutoClean::with(['CRM_Utils_Time', 'resetTime']);
    $this->dispatcher->addSubscriber(new \CRM_Core_DomainTokens());
    $this->dispatcher->addSubscriber(new TokenCompatSubscriber());
    $this->dispatcher->addSubscriber(new \CRM_Contact_Tokens());
    $p = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
      'smarty' => TRUE,
    ]);
    $p->addMessage('text', '{ts}Yes{/ts} {ts}No{/ts} {domain.now|crmDate:"%B"}', 'text/plain');
    $p->addRow([]);
    $p->addRow(['locale' => 'fr_FR']);
    $p->addRow(['locale' => 'es_MX']);

    $expectText = [
      'Yes No April',
      'Oui Non avril',
      'Sí No abril',
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

  public function testRenderLocalizedHookToken(): void {
    $cid = $this->individualCreate();

    $this->dispatcher->addSubscriber(new TokenCompatSubscriber());
    $this->dispatcher->addSubscriber(new \CRM_Contact_Tokens());
    \Civi::dispatcher()->addListener('hook_civicrm_tokens', function($e) {
      $e->tokens['trans'] = [
        'trans.affirm' => ts('Translated affirmation'),
      ];
    });
    \Civi::dispatcher()->addListener('hook_civicrm_tokenValues', function($e) {
      if (in_array('affirm', $e->tokens['trans'], TRUE)) {
        foreach ($e->contactIDs as $cid) {
          $e->details[$cid]['trans.affirm'] = ts('Yes');
        }
      }
    });

    unset(\Civi::$statics['CRM_Contact_Tokens']['hook_tokens']);
    $tokenProcessor = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
      'smarty' => FALSE,
    ]);
    $tokenProcessor->addMessage('text', '!!{trans.affirm}!!', 'text/plain');
    $tokenProcessor->addRow(['contactId' => $cid]);
    $tokenProcessor->addRow(['contactId' => $cid, 'locale' => 'fr_FR']);
    $tokenProcessor->addRow(['contactId' => $cid, 'locale' => 'es_MX']);

    $expectText = [
      '!!Yes!!',
      '!!Oui!!',
      '!!Sí!!',
    ];

    $rowCount = 0;
    foreach ($tokenProcessor->evaluate()->getRows() as $key => $row) {
      /** @var TokenRow */
      $this->assertInstanceOf(TokenRow::class, $row);
      $this->assertEquals($expectText[$key], $row->render('text'));
      $rowCount++;
    }
    $this->assertEquals(3, $rowCount);
  }

  /**
   * Test that double urls created by https:// followed by a token are cleaned up.
   *
   * The ckeditor UI makes it easy to put https:// in the html when adding links,
   * but they in the website url already.
   *
   * @throws \CRM_Core_Exception
   *
   * @noinspection HttpUrlsUsage
   */
  public function testRenderDoubleUrl(): void {
    $this->dispatcher->addSubscriber(new \CRM_Contact_Tokens());
    $this->dispatcher->addSubscriber(new TidySubscriber());
    $contactID = $this->individualCreate();
    $websiteID = Website::create()->setValues(['contact_id' => $contactID, 'url' => 'https://example.com'])->execute()->first()['id'];
    $row = $this->renderUrlMessage($contactID);
    $this->assertEquals('<a href="https://example.com">blah</a>', $row->render('one'));
    $this->assertEquals('<a href="https://example.com">blah</a>', $row->render('two'));

    Website::update()->setValues(['url' => 'http://example.com'])->addWhere('id', '=', $websiteID)->execute();
    $row = $this->renderUrlMessage($contactID);
    $this->assertEquals('<a href="http://example.com">blah</a>', $row->render('one'));
    $this->assertEquals('<a href="http://example.com">blah</a>', $row->render('two'));
  }

  /**
   * Render a message with double url potential.
   *
   * @param int $contactID
   *
   * @return \Civi\Token\TokenRow
   *
   * @noinspection HttpUrlsUsage
   */
  protected function renderUrlMessage(int $contactID): TokenRow {
    $tokenProcessor = $this->getTokenProcessor(['schema' => ['contactId']]);
    $tokenProcessor->addRow(['contactId' => $contactID]);
    $tokenProcessor->addMessage('one', '<a href="https://{contact.website_first.url}">blah</a>', 'text/html');
    $tokenProcessor->addMessage('two', '<a href="http://{contact.website_first.url}">blah</a>', 'text/html');
    return $tokenProcessor->evaluate()->getRow(0);
  }

  /**
   * Check that we can render contribution and contribution_recur tokens when passing a contribution ID.
   * This checks Bestspoke tokens
   *
   * @return void
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testRenderContributionRecurTokenFromContribution(): void {
    $cid = $this->individualCreate();
    $crid = \Civi\Api4\ContributionRecur::create(FALSE)
      ->addValue('contact_id', $cid)
      ->addValue('amount', 5)
      ->execute()
      ->first()['id'];
    $coid = $this->contributionCreate(['contact_id' => $cid, 'contribution_recur_id' => $crid]);

    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'schema' => ['contactId', 'contributionId'],
      'smarty' => FALSE,
    ]);
    $tokenProcessor->addMessage('text', '!!{contribution.id}{contribution.contribution_recur_id.id}{contribution.contribution_recur_id.amount}!!', 'text/plain');
    $tokenProcessor->addRow()->context(['contactId' => $cid, 'contributionId' => $coid]);

    $expectText = [
      "!!{$coid}{$crid}$5.00!!",
    ];

    $rowCount = 0;
    foreach ($tokenProcessor->evaluate()->getRows() as $key => $row) {
      /** @var TokenRow */
      $this->assertTrue($row instanceof TokenRow);
      $this->assertEquals($expectText[$key], $row->render('text'));
      $rowCount++;
    }
    $this->assertEquals(1, $rowCount);
  }

  /**
   * Check that we can render membership and contribution_recur tokens when passing a membership ID.
   * This checks Bestspoke Tokens work correctly
   *
   * @return void
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testRenderContributionRecurTokenFromMembership(): void {
    $cid = $this->individualCreate();
    $crid = \Civi\Api4\ContributionRecur::create(FALSE)
      ->addValue('contact_id', $cid)
      ->addValue('amount', 5)
      ->execute()
      ->first()['id'];
    $mid = $this->contactMembershipCreate(['contribution_recur_id' => $crid, 'contact_id' => $cid]);

    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'schema' => ['contactId', 'membershipId'],
      'smarty' => FALSE,
    ]);
    $tokenProcessor->addMessage('text', '!!{membership.id}{membership.contribution_recur_id.id}{membership.contribution_recur_id.amount}!!', 'text/plain');
    $tokenProcessor->addRow()->context(['contactId' => $cid, 'membershipId' => $mid]);

    $expectText = [
      "!!{$mid}{$crid}$5.00!!",
    ];

    $rowCount = 0;
    foreach ($tokenProcessor->evaluate()->getRows() as $key => $row) {
      /** @var TokenRow */
      $this->assertTrue($row instanceof TokenRow);
      $this->assertEquals($expectText[$key], $row->render('text'));
      $rowCount++;
    }
    $this->assertEquals(1, $rowCount);
  }

  public function testGetMessageTokens(): void {
    $tokenProcessor = $this->getTokenProcessor();
    $tokenProcessor->addMessage('greeting_html', 'Good morning, <p>{contact.display_name}</p>. {custom.foobar}!', 'text/html');
    $tokenProcessor->addMessage('greeting_text', 'Good morning, {contact.display_name}. {custom.whiz_bang}, {contact.first_name}!', 'text/plain');

    $expected = [
      'contact' => ['display_name', 'first_name'],
      'custom' => ['foobar', 'whiz_bang'],
    ];
    $this->assertEquals($expected, $tokenProcessor->getMessageTokens());
  }

  /**
   * Test getting available tokens.
   */
  public function testListTokens(): void {
    $tokenProcessor = $this->getTokenProcessor();
    $tokenProcessor->addToken(['entity' => 'MyEntity', 'field' => 'myField', 'label' => 'My Label']);
    $this->assertEquals(['{MyEntity.myField}' => 'My Label'], $tokenProcessor->listTokens());
  }

  /**
   * Perform a full mail-merge, substituting multiple tokens for multiple
   * contacts in multiple messages.
   */
  public function testFull(): void {
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

  public function getFilterExamples(): array {
    $exampleTokens = [
      // All the "{my_text.*}" tokens will be treated as plain-text ("text/plain").
      'my_text' => [
        'whiz_bang' => 'Some Text',
        'empty_string' => '',
        'emotive' => 'The Test :>',
      ],
      // All the "{my_rich_text.*}" tokens will be treated as markup ("text/html").
      'my_rich_text' => [
        'whiz_bang' => '<b>Some &ldquo;Text&rdquo;</b>',
        'empty_string' => '',
        'and_such' => '<strong>testing &amp; such</strong>',
      ],
      'my_currencies' => [
        'amount' => Money::of(123, 'USD', new DefaultContext()),
        'currency' => 'EUR',
        'locale' => 'fr_FR',
      ],
    ];

    $testCases = [];
    $testCases['TextMessages with TextData'] = [
      'text/plain',
      [
        'This is {my_text.whiz_bang}.' => 'This is Some Text.',
        'This is {my_text.whiz_bang|lower}...' => 'This is some text...',
        'This is {my_text.whiz_bang|upper}!' => 'This is SOME TEXT!',
        'This is {my_text.whiz_bang|boolean}!' => 'This is 1!',
        'This is {my_text.empty_string|boolean}!' => 'This is 0!',
        'This is {my_text.whiz_bang|default:"bang"}.' => 'This is Some Text.',
        'This is {my_text.empty_string|default:"bop"}.' => 'This is bop.',
      ],
      $exampleTokens,
    ];
    $testCases['HtmlMessages with HtmlData'] = [
      'text/html',
      [
        'This is {my_rich_text.whiz_bang}.' => 'This is <b>Some &ldquo;Text&rdquo;</b>.',
        'This is {my_rich_text.whiz_bang|lower}...' => 'This is <b>some &ldquo;text&rdquo;</b>...',
        'This is {my_rich_text.whiz_bang|upper}!' => 'This is <b>SOME &ldquo;TEXT&rdquo;</b>!',
        'This is {my_rich_text.whiz_bang|boolean}!' => 'This is 1!',
        'This is {my_rich_text.empty_string|boolean}!' => 'This is 0!',
        'This is {my_rich_text.whiz_bang|default:"bang"}.' => 'This is <b>Some &ldquo;Text&rdquo;</b>.',
        'This is {my_rich_text.empty_string|default:"bop"}.' => 'This is bop.',
      ],
      $exampleTokens,
    ];
    $testCases['HtmlMessages with TextData'] = [
      'text/html',
      [
        'This is {my_text.emotive}...' => 'This is The Test :&gt;...',
        'This is {my_text.emotive|lower}...' => 'This is the test :&gt;...',
        'This is {my_text.emotive|upper}!' => 'This is THE TEST :&gt;!',
      ],
      $exampleTokens,
    ];
    $testCases['TextMessages with HtmlData'] = [
      'text/plain',
      [
        'This is {my_rich_text.and_such}...' => 'This is testing & such...',
        'This is {my_rich_text.and_such|lower}...' => 'This is testing & such...',
        'This is {my_rich_text.and_such|upper}!' => 'This is TESTING & SUCH!',
      ],
      $exampleTokens,
    ];
    $testCases['crmMoney testing'] = [
      'text/plain',
      [
        'Amount: {my_currencies.amount}' => 'Amount: $123.00',
        'Amount as money: {my_currencies.amount|crmMoney}' => 'Amount as money: $123.00',
        'Amount as money in France: {my_currencies.amount|crmMoney:"fr_FR"}' => 'Amount as money in France: 123,00 $US',
      ],
      $exampleTokens,
    ];
    return $testCases;
  }

  /**
   * @param string $messageFormat
   * @param array $exampleMessages
   * @param array $exampleTokens
   * @return void
   * @dataProvider getFilterExamples
   */
  public function testFilters(string $messageFormat, array $exampleMessages, array $exampleTokens): void {
    $expectExampleCount = 2 * count($exampleMessages);
    $actualExampleCount = 0;

    foreach ($exampleMessages as $inputMessage => $expectOutput) {
      foreach ([TRUE, FALSE] as $useSmarty) {
        $p = new TokenProcessor($this->dispatcher, [
          'controller' => __CLASS__,
          'smarty' => $useSmarty,
        ]);
        $p->addMessage('example', $inputMessage, $messageFormat);
        $p->addRow()
          ->format('text/plain')->tokens(\CRM_Utils_Array::subset($exampleTokens, ['my_text']))
          ->format('text/html')->tokens(\CRM_Utils_Array::subset($exampleTokens, ['my_rich_text']))
          ->format('text/plain')->tokens(\CRM_Utils_Array::subset($exampleTokens, ['my_currencies']));
        foreach ($p->evaluate()->getRows() as $row) {
          $this->assertEquals($expectOutput, $row->render('example'));
          $actualExampleCount++;
        }
      }
    }

    $this->assertEquals($expectExampleCount, $actualExampleCount);
  }

  public function onListTokens(TokenRegisterEvent $e): void {
    $this->counts[__FUNCTION__]++;
    $e->register('custom', [
      'foobar' => 'A special message about foobar',
    ]);
  }

  public function onEvalTokens(TokenValueEvent $e): void {
    $this->counts[__FUNCTION__]++;
    foreach ($e->getRows() as $row) {
      /** @var TokenRow $row */
      $row->format('text/html');
      $row->tokens['custom']['foobar'] = sprintf('#%04d is a good number. Trickster {contact.display_name}.', $row->context['contact_id']);
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
  public function testHookTokenDiagonal(): void {
    $cid = $this->individualCreate();

    $this->dispatcher->addSubscriber(new TokenCompatSubscriber());
    $this->dispatcher->addSubscriber(new \CRM_Contact_Tokens());

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

    unset(\Civi::$statics['CRM_Contact_Tokens']['hook_tokens']);
    $tokenProcessor = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
      'smarty' => FALSE,
    ]);
    $tokenProcessor->addMessage('subject', '!!{fruit.apple}!!', 'text/plain');
    $tokenProcessor->addMessage('body_html', '!!{fruit.banana}!!', 'text/html');
    $tokenProcessor->addMessage('body_text', '!!{fruit.cherry}!!', 'text/plain');
    $tokenProcessor->addMessage('other', 'No fruit :(', 'text/plain');
    $tokenProcessor->addRow(['contactId' => $cid]);
    $tokenProcessor->evaluate();

    foreach ($tokenProcessor->getRows() as $row) {
      $this->assertEquals('!!Nomnomnomapple!!', $row->render('subject'));
      $this->assertEquals('!!Nomnomnombanana!!', $row->render('body_html'));
      $this->assertEquals('!!Nomnomnomcherry!!', $row->render('body_text'));
      $this->assertEquals('No fruit :(', $row->render('other'));
      $looped = TRUE;
    }
    $this->assertTrue(isset($looped));
  }

  /**
   * Define extended tokens with funny symbols
   */
  public function testHookTokenExtraChar(): void {
    $cid = $this->individualCreate();

    $this->dispatcher->addSubscriber(new TokenCompatSubscriber());
    $this->dispatcher->addSubscriber(new \CRM_Contact_Tokens());
    \Civi::dispatcher()->addListener('hook_civicrm_tokens', function ($e) {
      $e->tokens['food'] = [
        'food.fruit.apple' => ts('Apple'),
        'food.fruit:summary' => ts('Fruit summary'),
      ];
    });
    \Civi::dispatcher()->addListener('hook_civicrm_tokenValues', function ($e) {
      foreach ($e->tokens['food'] ?? [] as $subtoken) {
        foreach ($e->contactIDs as $cid) {
          switch ($subtoken) {
            case 'fruit.apple':
              $e->details[$cid]['food.fruit.apple'] = 'Fruit of the Tree';
              break;

            case 'fruit:summary':
              $e->details[$cid]['food.fruit:summary'] = 'Apples, Bananas, and Cherries Oh My';
              break;
          }
        }
      }
    });
    unset(\Civi::$statics['CRM_Contact_Tokens']['hook_tokens']);
    $expectRealSmartyOutputs = [
      TRUE => 'Fruit of the Tree yes',
      FALSE => 'Fruit of the Tree {if 1}yes{else}no{/if}',
    ];

    $loops = 0;
    foreach ([TRUE, FALSE] as $smarty) {
      $tokenProcessor = new TokenProcessor($this->dispatcher, [
        'controller' => __CLASS__,
        'smarty' => $smarty,
      ]);
      $tokenProcessor->addMessage('real_dot', '!!{food.fruit.apple}!!', 'text/plain');
      $tokenProcessor->addMessage('real_dot_smarty', '{food.fruit.apple} {if 1}yes{else}no{/if}', 'text/plain');
      $tokenProcessor->addMessage('real_colon', 'Summary of fruits: {food.fruit:summary}!', 'text/plain');
      $tokenProcessor->addMessage('not_real_1', '!!{food.fruit}!!', 'text/plain');
      $tokenProcessor->addMessage('not_real_2', '!!{food.apple}!!', 'text/plain');
      $tokenProcessor->addMessage('not_real_3', '!!{fruit.apple}!!', 'text/plain');
      $tokenProcessor->addMessage('not_real_4', '!!{food.fruit:apple}!!', 'text/plain');
      $tokenProcessor->addRow(['contactId' => $cid]);
      $tokenProcessor->evaluate();

      foreach ($tokenProcessor->getRows() as $row) {
        $loops++;
        $this->assertEquals('!!Fruit of the Tree!!', $row->render('real_dot'));
        $this->assertEquals($expectRealSmartyOutputs[$smarty], $row->render('real_dot_smarty'));
        $this->assertEquals('Summary of fruits: Apples, Bananas, and Cherries Oh My!', $row->render('real_colon'));
        $this->assertEquals('!!!!', $row->render('not_real_1'));
        $this->assertEquals('!!!!', $row->render('not_real_2'));
        $this->assertEquals('!!!!', $row->render('not_real_3'));
        $this->assertEquals('!!!!', $row->render('not_real_4'));
      }
    }
    $this->assertEquals(2, $loops);
  }

  /**
   * Process a message using mocked data.
   */
  public function testMockData_ContactContribution(): void {
    $this->dispatcher->addSubscriber(new TokenCompatSubscriber());
    $this->dispatcher->addSubscriber(new \CRM_Contribute_Tokens());
    $this->dispatcher->addSubscriber(new \CRM_Contact_Tokens());
    $p = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
      'schema' => ['contributionId', 'contactId'],
    ]);
    $p->addMessage('example', 'Invoice #{contribution.invoice_id} for {contact.display_name}!', 'text/plain');
    $p->addRow([
      'contactId' => 11,
      'contact' => [
        'display_name' => 'The Override',
      ],
      'contributionId' => 111,
      'contribution' => [
        'id' => 111,
        'receive_date' => '2012-01-02',
        'invoice_id' => 11111,
      ],
    ]);
    $p->addRow([
      'contactId' => 22,
      'contact' => [
        'display_name' => 'Another Override',
      ],
      'contributionId' => 222,
      'contribution' => [
        'id' => 111,
        'receive_date' => '2012-01-02',
        'invoice_id' => 22222,
      ],
    ]);
    $p->evaluate();

    $outputs = [];
    foreach ($p->getRows() as $row) {
      $outputs[] = $row->render('example');
    }
    $this->assertEquals('Invoice #11111 for The Override!', $outputs[0]);
    $this->assertEquals('Invoice #22222 for Another Override!', $outputs[1]);
  }

  /**
   * Process a message using mocked data, accessed through a Smarty alias.
   */
  public function testMockData_SmartyAlias_Contribution(): void {
    $this->dispatcher->addSubscriber(new TokenCompatSubscriber());
    $this->dispatcher->addSubscriber(new \CRM_Contribute_Tokens());
    $this->dispatcher->addSubscriber(new \CRM_Contact_Tokens());

    $p = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
      'schema' => ['contributionId'],
      'smarty' => TRUE,
      'smartyTokenAlias' => [
        'theInvoiceId' => 'contribution.invoice_id',
      ],
    ]);
    $p->addMessage('example', 'Invoice #{$theInvoiceId}!', 'text/plain');
    $p->addRow([
      'contributionId' => 333,
      'contribution' => [
        'id' => 333,
        'receive_date' => '2012-01-02',
        'invoice_id' => 33333,
      ],
    ]);
    $p->addRow([
      'contributionId' => 444,
      'contribution' => [
        'id' => 444,
        'receive_date' => '2012-01-02',
        'invoice_id' => 44444,
      ],
    ]);
    $p->evaluate();

    $outputs = [];
    foreach ($p->getRows() as $row) {
      $outputs[] = $row->render('example');
    }
    $this->assertEquals('Invoice #33333!', $outputs[0]);
    $this->assertEquals('Invoice #44444!', $outputs[1]);

  }

  /**
   * This defines a compatibility mechanism wherein an old Smarty expression can
   * be evaluated based on a newer token expression.
   *
   * Ex: $tokenContext['oldSmartyVar'] = 'new_entity.new_field';
   */
  public function testSmartyTokenAlias_Contribution(): void {
    $first = $this->contributionCreate(['contact_id' => $this->individualCreate(), 'receive_date' => '2010-01-01', 'invoice_id' => 100, 'trxn_id' => 1000]);
    $second = $this->contributionCreate(['contact_id' => $this->individualCreate(), 'receive_date' => '2011-02-02', 'invoice_id' => 200, 'trxn_id' => 1]);
    $this->dispatcher->addSubscriber(new TokenCompatSubscriber());
    $this->dispatcher->addSubscriber(new \CRM_Contribute_Tokens());
    $this->dispatcher->addSubscriber(new \CRM_Contact_Tokens());

    $p = new TokenProcessor($this->dispatcher, [
      'controller' => __CLASS__,
      'schema' => ['contributionId'],
      'smarty' => TRUE,
      'smartyTokenAlias' => [
        'theInvoiceId' => 'contribution.invoice_id',
      ],
    ]);
    $p->addMessage('example', 'Invoice #{$theInvoiceId}!', 'text/plain');
    $p->addRow(['contributionId' => $first]);
    $p->addRow(['contributionId' => $second]);
    $p->evaluate();

    $outputs = [];
    foreach ($p->getRows() as $row) {
      $outputs[] = $row->render('example');
    }
    $this->assertEquals('Invoice #100!', $outputs[0]);
    $this->assertEquals('Invoice #200!', $outputs[1]);
  }

  /**
   * Get a token processor instance.
   *
   * @param array $context
   *
   * @return \Civi\Token\TokenProcessor
   */
  protected function getTokenProcessor(array $context = []): TokenProcessor {
    return new TokenProcessor($this->dispatcher, array_merge([
      'controller' => __CLASS__,
    ], $context));
  }

  ///**
  // * This defines a compatibility mechanism wherein an old Smarty expression can
  // * be evaluated based on a newer token expression.
  // *
  // * The following example doesn't work because the handling of greeting+contact
  // * tokens still use a special override (TokenCompatSubscriber::onRender).
  // *
  // * Ex: $tokenContext['oldSmartyVar'] = 'new_entity.new_field';
  // */
  //  public function testSmartyTokenAlias_Contact(): void {
  //    $alice = $this->individualCreate(['first_name' => 'Alice']);
  //    $bob = $this->individualCreate(['first_name' => 'Bob']);
  //    $this->dispatcher->addSubscriber(new TokenCompatSubscriber());
  //
  //    $p = new TokenProcessor($this->dispatcher, [
  //      'controller' => __CLASS__,
  //      'schema' => ['contactId'],
  //      'smarty' => TRUE,
  //      'smartyTokenAlias' => [
  //        'myFirstName' => 'contact.first_name',
  //      ],
  //    ]);
  //    $p->addMessage('example', 'Hello {$myFirstName}!', 'text/plain');
  //    $p->addRow(['contactId' => $alice]);
  //    $p->addRow(['contactId' => $bob]);
  //    $p->evaluate();
  //
  //    $outputs = [];
  //    foreach ($p->getRows() as $row) {
  //      $outputs[] = $row->render('example');
  //    }
  //    $this->assertEquals('Hello Alice!', $outputs[0]);
  //    $this->assertEquals('Hello Bob!', $outputs[1]);
  //  }

}
