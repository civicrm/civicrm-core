<?php

namespace E2E\Core;

use Civi\Test\RemoteTestFunction;

/**
 * Check that common syntax evaluates as expected on different versions of Smarty.
 *
 * TIP: Every example is flagged as "PORTABLE" or "NOT PORTABLE". To get a summary,
 * run: `grep PORTABLE tests/phpunit/E2E/Core/SmartyConsistencyTest.php`
 *
 * @package E2E\Core
 * @group e2e
 */
class SmartyConsistencyTest extends \CiviEndToEndTestCase {

  /**
   * @var \Civi\Test\RemoteTestFunction[]
   */
  protected array $smarty;

  protected function setUp(): void {
    parent::setUp();
    $this->smarty = $this->createSmartyRenderers();
  }

  public function testPortable() {
    // PORTABLE: {$string nofilter}
    $this->checkPortable('Dragon {$name nofilter}!',
      ['name' => 'Run & Hide'],
      'Dragon Run & Hide!'
    );

    // PORTABLE: {$string|escape nofilter}
    $this->checkPortable('Dragon {$name|escape nofilter}',
      ['name' => 'Run & Hide'],
      'Dragon Run &amp; Hide'
    );

    // PORTABLE: {$object|@json_encode nofilter}
    $this->checkPortable(
      'var dragon = {$contact|@json_encode nofilter};',
      ['contact' => ['name' => 'Run & Hide']],
      'var dragon = {"name":"Run & Hide"};'
    );

    // PORTABLE: {$object|@json nofilter}
    $this->checkPortable(
      'var dragon = {$contact|@json nofilter};',
      ['contact' => ['name' => 'Run & Hide']],
      'var dragon = {"name":"Run & Hide"};'
    );

    // PORTABLE: {if isset($variable)}
    // PORTABLE: {if empty($variable)}
    $this->checkPortable(
    // PORTABLE: isset($variable)
      '{if isset($known)}isset-known=yes{/if}, '
      . '{if isset($unknown)}isset-unknown=yes{/if}, '
      . '{if empty($known)}empty-known=yes{/if}, '
      . '{if empty($unknown)}empty-unknown=yes{/if}',
      ['known' => 'yes'],
      'isset-known=yes, , , empty-unknown=yes'
    );

    // PORTABLE: {if isset($array.field)}
    // PORTABLE: {if empty($array.field)}
    $this->checkPortable(
    // PORTABLE: isset($variable)
      '{if isset($array.known)}isset-known=yes{/if}, '
      . '{if isset($array.unknown)}isset-unknown=yes{/if}, '
      . '{if empty($array.known)}empty-known=yes{/if}, '
      . '{if empty($array.unknown)}empty-unknown=yes{/if}',
      ['array' => ['known' => 'yes']],
      'isset-known=yes, , , empty-unknown=yes'
    );

    // PORTABLE: {if $x === null}
    $this->checkPortable(
      '{if $x === null}x=null{/if}, '
      . '{if $x !== null}x!=null{/if}, '
      . '{if $y !== null}y!=null{/if}',
      ['x' => NULL, 'y' => '100'],
      "x=null, , y!=null"
    );

    // PORTABLE: {if $x === true}
    $this->checkPortable(
      '{if $x === true}x=true{/if}, '
      . '{if $x !== true}x!=true{/if}, '
      . '{if $y !== true}y!=true{/if}',
      ['x' => TRUE, 'y' => 'FALSE'],
      "x=true, , y!=true"
    );

    // PORTABLE: {elseif} and {else if}
    $this->checkPortable(
      '{if $x == 1}one{elseif $x == 2}two{else}three{/if}',
      ['x' => 2],
      "two"
    );

    // PORTABLE: {if count ( $wizard.steps ) > 5}
    $this->checkPortable(
      '{if count ( $wizard.steps ) > 5}true{/if}',
      ['wizard' => ['steps' => [1, 2, 3, 4, 5, 6]]],
      'true'
    );
  }

  public function testNonPortable() {
    // NOT PORTABLE: {if $x === NULL} (*legal in TPL files but not in TPL strings*}
    $this->checkRegex(
      '{if $x === NULL}x=null{/if}',
      ['x' => NULL],
      [
        '2_plain' => ['/^EXCEPTION.*\(secure mode\) .*NULL.* not allowed/'],
        '4_plain' => ['/^EXCEPTION.*access to constants not permitted/'],
        '5_plain' => ['/^EXCEPTION.*access to constants not permitted/'],
        '5_auto' => ['/^EXCEPTION.*access to constants not permitted/'],
      ]
    );

    // NOT PORTABLE: {if $x === TRUE} (*legal in TPL files but not in TPL strings*}
    $this->checkRegex(
      '{if $x === TRUE}x=true{/if}',
      ['x' => NULL],
      [
        '2_plain' => ['/^EXCEPTION.*\(secure mode\) .*TRUE.* not allowed/'],
        '4_plain' => ['/^EXCEPTION.*access to constants not permitted/'],
        '5_plain' => ['/^EXCEPTION.*access to constants not permitted/'],
        '5_auto' => ['/^EXCEPTION.*access to constants not permitted/'],
      ]
    );

    // NOT PORTABLE: {else if}
    $this->checkRegex(
      '{if $x == 1}one{else if $x == 2}two{else}three{/if}',
      ['x' => 2],
      [
        '2_plain' => ['/EXCEPTION: Message was not parsed due to invalid smarty syntax/'], /* outlier */
        '4_plain' => ['/^two$/'],
        '5_plain' => ['/^two$/'],
        '5_auto' => ['/^two$/'],
      ]
    );

    // NOT PORTABLE: {\n$name} (whitespace at start of expression)
    $this->check(
      "Hello {\$name}. Goodbye {\n\$name}.",
      ['name' => 'Bob'],
      [
        '2_plain' => ["Hello Bob. Goodbye Bob."], /* outlier */
        '4_plain' => ["Hello Bob. Goodbye {\n\$name}."],
        '5_plain' => ["Hello Bob. Goodbye {\n\$name}."],
        '5_auto' => ["Hello Bob. Goodbye {\n\$name}."],
      ]
    );

    // NOT PORTABLE: {$string}
    $this->check('Dragon {$name}!',
      ['name' => 'Run & Hide'],
      [
        '2_plain' => ['Dragon Run & Hide!'],
        '4_plain' => ['Dragon Run & Hide!'],
        '5_plain' => ['Dragon Run & Hide!'],
        '5_auto' => ['Dragon Run &amp; Hide!'], /* outlier */
      ]
    );

    // NOT PORTABLE: {$string|smarty:nodefaults}
    $this->check(
      'Dragon {$name|smarty:nodefaults}!',
      ['name' => 'Run & Hide'],
      [
        '2_plain' => ['Dragon Run & Hide!'],
        '4_plain' => ['Dragon Run & Hide!'],
        '5_plain' => ['Dragon Run & Hide!'],
        '5_auto' => ['Dragon Run &amp; Hide!'], /* outlier */
      ]
    );

    // NOT PORTABLE: {$data|@json_encode}
    $this->check(
      'var dragon = {$contact|@json_encode};',
      ['contact' => ['name' => 'Run & Hide']],
      [
        '2_plain' => ['var dragon = {"name":"Run & Hide"};'],
        '4_plain' => ['var dragon = {"name":"Run & Hide"};'],
        '5_plain' => ['var dragon = {"name":"Run & Hide"};'],
        '5_auto' => ['var dragon = {&quot;name&quot;:&quot;Run &amp; Hide&quot;};'], /* outlier */
      ]
    );

    // NOT PORTABLE: {$data|json_encode nofilter}
    $this->check(
      'var dragon = {$contact|json_encode nofilter};',
      ['contact' => ['name' => 'Run & Hide']],
      [
        '2_plain' => ['var dragon = Array;', '/Array to string conversion/'], /* outlier */
        '4_plain' => ['var dragon = {"name":"Run & Hide"};'],
        '5_plain' => ['var dragon = {"name":"Run & Hide"};'],
        '5_auto' => ['var dragon = {"name":"Run & Hide"};'],
      ]
    );

    // NOT PORTABLE: {$data|json nofilter}
    $this->check(
      'var dragon = {$contact|json nofilter};',
      ['contact' => ['name' => 'Run & Hide']],
      [
        '2_plain' => ['var dragon = Array;', '/Array to string conversion/'], /* outlier */
        '4_plain' => ['var dragon = {"name":"Run & Hide"};'],
        '5_plain' => ['var dragon = {"name":"Run & Hide"};'],
        '5_auto' => ['var dragon = {"name":"Run & Hide"};'],
      ]
    );

    // NOT PORTABLE: {$data|@json|smarty:nodefaults}
    $this->check(
      'var dragon = {$contact|@json|smarty:nodefaults};',
      ['contact' => ['name' => 'Run & Hide']],
      [
        '2_plain' => ['var dragon = {"name":"Run & Hide"};'],
        '4_plain' => ['var dragon = {"name":"Run & Hide"};'],
        '5_plain' => ['var dragon = {"name":"Run & Hide"};'],
        '5_auto' => ['var dragon = {&quot;name&quot;:&quot;Run &amp; Hide&quot;};'], /* outlier */
      ]
    );
  }

  public function testSpacing(): void {
    // PORTABLE: {$name|escape:"html" nofilter}
    $this->checkPortable(
      'hello {$name|escape:"html" nofilter}',
      ['name' => '&'],
      'hello &amp;'
    );

    // PORTABLE: {$name|escape : "html" nofilter}
    $this->checkPortable(
      'hello {$name|escape : "html" nofilter}',
      ['name' => '&'],
      'hello &amp;'
    );

    // NOT PORTABLE: {$name | escape:"html" nofilter}
    $this->checkRegex(
      'hello {$name | escape:"html" nofilter}',
      ['name' => '&'],
      [
        '2_plain' => ['/hello &/'], /* outlier */
        '4_plain' => ['/^EXCEPTION/'],
        '5_plain' => ['/^EXCEPTION/'],
        '5_auto' => ['/^EXCEPTION/'],
      ],
    );

    // PORTABLE: {block key=bareword}
    $this->checkPortable(
      '{ts 1=Alice}Hello %1{/ts}',
      [],
      'Hello Alice'
    );

    // PORTABLE: {block key="String"}
    $this->checkPortable(
      '{ts 1="Alice"}Hello %1{/ts}',
      [],
      'Hello Alice'
    );

    // PORTABLE: {block key = "String" }
    $this->checkPortable(
      '{ts 1 = "Alice" }Hello %1{/ts}',
      [],
      'Hello Alice'
    );

    // PORTABLE: {block key1 = $value|modifiers key2 = $value|modifiers}
    $this->checkPortable(
      '{ts 1 = $name|escape:"html" 2 = $name|escape:"url"}Hello %1 %2{/ts}',
      ['name' => '&'],
      'Hello &amp; %26'
    );

    // INVALID: {block key = $value | modifiers}
    $this->checkInvalid(
      '{ts 1 = $name | escape:"html" }Hello %1{/ts}',
      ['name' => '&'],
    );
  }

  public function testBlockParamFilters(): void {
    // PORTABLE: {block param=$x|filter}
    $this->checkPortable(
      '{ts 1=$x|escape}hello %1{/ts}',
      ['x' => '&'],
      'hello &amp;'
    );

    // PORTABLE: {block 1=$x|filter_a 2=$x|filter_b 3=$x|filter_c}
    $this->checkPortable(
      implode('', [
        '{ts',
        ' 1=$x|escape',
        ' 2=$x|escape:"url"',
        ' 3=$x|escape:html',
        ' 4=$x|escape|escape:url',
        '}hello %1 - %2 - %3 - %4{/ts}',
      ]),
      ['x' => '&'],
      'hello &amp; - %26 - &amp; - %26amp%3B'
    );
  }

  public function testPurifyInteractions(): void {
    // Old code with `|smarty:nodefaults` is sometimes combined with `|purify`. What does it mean? What's the replacemnet?
    // The tests here show that:
    // 1. "purify" has its own escaping.
    // 2. To prevent double-escaping, "|purify" should be paired with "nofilter" (or, previously, "|smarty:nodefaults").
    // 3. To prevent double-escaping, "|purify" should NOT be paired with "|escape".
    // 4. For an expression with a modifier-sequence, it doesn't matter where `|smarty:nodefaults` is
    //    placed. It acts as a general signal about the overall expression (not a liver filter).

    // To test the behavior of different notations, we choose a string with a mix of good text (e.g. "Alice"),
    // fixable text (e.g. "&"), bad-text (e.g. "<script>..."), and pre-escaped text ("&lt;script&gt;...").

    // NOT PORTABLE: {$name|smarty:nodefaults|purify}
    $this->check(
      'Hello {$name|smarty:nodefaults|purify}',
      ['name' => 'Alice & Bob <script>alert</script> & Carol &lt;script&gt;confirm&lt;/script&gt;'],
      [
        '2_plain' => ['Hello Alice &amp; Bob  &amp; Carol &lt;script&gt;confirm&lt;/script&gt;'],
        '4_plain' => ['Hello Alice &amp; Bob  &amp; Carol &lt;script&gt;confirm&lt;/script&gt;'],
        '5_plain' => ['Hello Alice &amp; Bob  &amp; Carol &lt;script&gt;confirm&lt;/script&gt;'],
        '5_auto' => ['Hello Alice &amp;amp; Bob  &amp;amp; Carol &amp;lt;script&amp;gt;confirm&amp;lt;/script&amp;gt;'], /* outlier */
      ]
    );

    // NOT PORTABLE: {$name|purify}
    $this->check(
      'Hello {$name|purify}',
      ['name' => 'Alice & Bob <script>alert</script> & Carol &lt;script&gt;confirm&lt;/script&gt;'],
      [
        '2_plain' => ['Hello Alice &amp; Bob  &amp; Carol &lt;script&gt;confirm&lt;/script&gt;'],
        '4_plain' => ['Hello Alice &amp; Bob  &amp; Carol &lt;script&gt;confirm&lt;/script&gt;'],
        '5_plain' => ['Hello Alice &amp; Bob  &amp; Carol &lt;script&gt;confirm&lt;/script&gt;'],
        '5_auto' => ['Hello Alice &amp;amp; Bob  &amp;amp; Carol &amp;lt;script&amp;gt;confirm&amp;lt;/script&amp;gt;'], /* outlier */
      ]
    );

    // PORTABLE: {$name|purify nofilter}
    $this->checkPortable(
      'Hello {$name|purify nofilter}',
      ['name' => 'Alice & Bob <script>alert</script> & Carol &lt;script&gt;confirm&lt;/script&gt;'],
      'Hello Alice &amp; Bob  &amp; Carol &lt;script&gt;confirm&lt;/script&gt;'
    );

    // PORTABLE-WARN: {$name|purify|escape nofilter} (*but you probably don't want this*)
    // Note that "purify" does its own escaping, so this is doubly escaped.
    $this->checkPortable(
      'Hello {$name|purify|escape nofilter}',
      ['name' => 'Alice & Bob <script>alert</script> & Carol &lt;script&gt;confirm&lt;/script&gt;'],
      'Hello Alice &amp;amp; Bob  &amp;amp; Carol &amp;lt;script&amp;gt;confirm&amp;lt;/script&amp;gt;'
    );

    // PORTABLE-WARN: {$name|escape|purify nofilter} (*but you probably don't want this*)
    // Note that "purify" does its own escaping, so this is doubly escaped.
    $this->checkPortable(
      'Hello {$name|purify|escape nofilter}',
      ['name' => 'Alice & Bob <script>alert</script> & Carol &lt;script&gt;confirm&lt;/script&gt;'],
      'Hello Alice &amp;amp; Bob  &amp;amp; Carol &amp;lt;script&amp;gt;confirm&amp;lt;/script&amp;gt;'
    );
  }

  /**
   * Render a Smarty template.
   *
   * @param string $version
   *   Smarty version/configuration to use.
   * @param string $template
   *   The template we wish to evaluate.
   * @param array $vars
   *   Any Smarty variables to include.
   * @return array
   *   Tuple: [0 => string $rendered, 1 => string $warnings]
   */
  protected function render(string $version, string $template, array $vars = []): array {
    [$expectVer] = explode('_', $version);
    $rendered = $this->smarty[$version]->execute([$expectVer, $template, $vars]);
    $warnings = $this->smarty[$version]->getClient()->getLastStdErr();
    return [$rendered, $warnings];
  }

  /**
   * Render a Smarty template across several versions. Compare results.
   *
   * @param string $template
   * @param array $vars
   * @param array $versions
   *   List of versions and their expected output.
   *   For ordinary/successful output, use `[string $expectedOutput]`.
   *   For unusual output with expected warnings, use `[string $expectedOutput, string $expectedWarningRegex]`.
   *   Ex: [
   *     '2_auto' => ['Hello world']
   *     '3_auto' => ['Hello world']
   *     '4_auto' => ['Hello world', '/Warning: foobar was deprecated in v4.5.6/'],
   *   ];
   *
   * @return void
   */
  protected function check(string $template, array $vars, array $versions): void {
    $expectResults = [];
    $actualResults = [];
    foreach ($versions as $version => $expectResult) {
      $expectRendered = $expectResult[0];
      $expectWarnings = $expectResult[1] ?? NULL;
      $expectResults[$version] = $expectRendered;

      [$actualRendered, $actualWarnings] = $this->render($version, $template, $vars);
      if (!$expectWarnings && !$actualWarnings) {
        // OK
      }
      elseif ($expectWarnings && preg_match($expectWarnings, $actualWarnings)) {
        // OK
      }
      else {
        $actualRendered .= "\n$actualWarnings";
      }
      $actualResults[$version] = $actualRendered;
    }
    $this->assertEquals($expectResults, $actualResults, "Test Smarty template: {$template}");
  }

  /**
   * Render a Smarty template across several versions. Compare results.
   *
   * @param string $template
   * @param array $vars
   * @param array $versions
   *   List of versions and their expected output.
   *   For ordinary/successful output, use `[string $expectedOutput]`.
   *   For unusual output with expected warnings, use `[string $expectedOutput, string $expectedWarningRegex]`.
   *   Ex: [
   *     '2_auto' => ['Hello world']
   *     '3_auto' => ['Hello world']
   *     '4_auto' => ['Hello world', '/Warning: foobar was deprecated in v4.5.6/'],
   *   ];
   *
   * @return void
   */
  protected function checkRegex(string $template, array $vars, array $versions): void {
    $expectResults = [];
    $actualResults = [];
    foreach ($versions as $version => $expectResult) {
      $expectRenderedPattern = $expectResult[0];
      $expectWarnings = $expectResult[1] ?? NULL;
      $expectResults[$version] = 'MATCH: ' . $expectRenderedPattern;

      [$actualRendered, $actualWarnings] = $this->render($version, $template, $vars);
      if (!$expectWarnings && !$actualWarnings) {
        // OK
      }
      elseif ($expectWarnings && preg_match($expectWarnings, $actualWarnings)) {
        // OK
      }
      else {
        $actualRendered .= "\n$actualWarnings";
      }
      if (preg_match($expectRenderedPattern, $actualRendered)) {
        $actualResults[$version] = 'MATCH: ' . $expectRenderedPattern;
      }
      else {
        $actualResults[$version] = 'NOT MATCH: ' . $expectRenderedPattern . "\n" . $actualRendered;
      }

    }
    $this->assertEquals($expectResults, $actualResults, "Test Smarty template: {$template}");
  }

  /**
   * Render a smarty template across several versions. All versions should yield
   * the same output.
   *
   * @param string $template
   * @param array $vars
   * @param string $expect
   * @return void
   */
  protected function checkPortable(string $template, array $vars, string $expect): void {
    $this->check($template, $vars, [
      '2_plain' => [$expect, NULL],
      '4_plain' => [$expect, NULL],
      '5_plain' => [$expect, NULL],
      '5_auto' => [$expect, NULL],
    ]);
  }

  protected function checkInvalid(string $template, array $vars, string $expectRegex = '/EXCEPTION: Message was not parsed due to invalid smarty syntax/'): void {
    $this->checkRegex($template, $vars, [
      '2_plain' => [$expectRegex],
      '4_plain' => [$expectRegex],
      '5_plain' => [$expectRegex],
      '5_auto' => [$expectRegex],
    ]);
  }

  /**
   * @return array
   */
  protected function createSmartyRenderers(): array {
    $smartyFuncs = [];
    $versions = [
      '2_plain' => [
        'CIVICRM_SMARTY_DEFAULT_ESCAPE' => 0,
        'CIVICRM_SMARTY_AUTOLOAD_PATH' => \Civi::paths()->getPath('[civicrm.packages]/Smarty/Smarty.class.php'),
      ],
      '4_plain' => [
        'CIVICRM_SMARTY_DEFAULT_ESCAPE' => 0,
        'CIVICRM_SMARTY_AUTOLOAD_PATH' => \Civi::paths()->getPath('[civicrm.packages]/smarty4/vendor/autoload.php'),
      ],
      '5_plain' => [
        'CIVICRM_SMARTY_DEFAULT_ESCAPE' => 0,
        'CIVICRM_SMARTY_AUTOLOAD_PATH' => \Civi::paths()->getPath('[civicrm.packages]/smarty5/Smarty.php'),
      ],
      '5_auto' => [
        'CIVICRM_SMARTY_DEFAULT_ESCAPE' => 1,
        'CIVICRM_SMARTY_AUTOLOAD_PATH' => \Civi::paths()->getPath('[civicrm.packages]/smarty5/Smarty.php'),
      ],
    ];

    foreach ($versions as $version => $env) {
      $f = RemoteTestFunction::register(__CLASS__, 'render_' . $version, function (int $expectVer, string $template, $vars = []) {
        $actualVer = \CRM_Core_Smarty::findVersion();
        if ($actualVer !== $expectVer) {
          throw new \RuntimeException("Tried to load Smarty v$expectVer but found v$actualVer");
        }
        try {
          return \CRM_Utils_String::parseOneOffStringThroughSmarty($template, $vars);
        }
        catch (\CRM_Core_Exception $e) {
          return 'EXCEPTION: ' . $e->getMessage();
        }
      });
      $f->setClient($f->createCvClient($env));
      $smartyFuncs[$version] = $f;
    }
    return $smartyFuncs;
  }

}
