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
  }

  public function testNonPortable() {
    // NOT PORTABLE: {$string}
    $this->check('Dragon {$name}!',
      ['name' => 'Run & Hide'],
      [
        '2_plain' => 'Dragon Run & Hide!',
        '4_plain' => 'Dragon Run & Hide!',
        '5_plain' => 'Dragon Run & Hide!',
        '5_auto' => 'Dragon Run &amp; Hide!', /* outlier */
      ]
    );

    // NOT PORTABLE: {$string|smarty:nodefaults}
    $this->check(
      'Dragon {$name|smarty:nodefaults}',
      ['name' => 'Run & Hide'],
      [
        '2_plain' => 'Dragon Run & Hide',
        '4_plain' => 'Dragon Run & Hide',
        '5_plain' => 'Dragon Run & Hide',
        '5_auto' => 'Dragon Run &amp; Hide', /* outlier */
      ]
    );

    // NOT PORTABLE: {$data|@json_encode}
    $this->check(
      'var dragon = {$contact|@json_encode};',
      ['contact' => ['name' => 'Run & Hide']],
      [
        '2_plain' => 'var dragon = {"name":"Run & Hide"};',
        '4_plain' => 'var dragon = {"name":"Run & Hide"};',
        '5_plain' => 'var dragon = {"name":"Run & Hide"};',
        '5_auto' => 'var dragon = {&quot;name&quot;:&quot;Run &amp; Hide&quot;};', /* outlier */
      ]
    );

    // NOT PORTABLE: {$data|json_encode nofilter}
    $this->check(
      'var dragon = {$contact|json_encode nofilter};',
      ['contact' => ['name' => 'Run & Hide']],
      [
        '2_plain' => 'var dragon = Array;', /* outlier */
        '4_plain' => 'var dragon = {"name":"Run & Hide"};',
        '5_plain' => 'var dragon = {"name":"Run & Hide"};',
        '5_auto' => 'var dragon = {"name":"Run & Hide"};',
      ]
    );

    // NOT PORTABLE: {$data|json nofilter}
    $this->check(
      'var dragon = {$contact|json nofilter};',
      ['contact' => ['name' => 'Run & Hide']],
      [
        '2_plain' => 'var dragon = Array;', /* outlier */
        '4_plain' => 'var dragon = {"name":"Run & Hide"};',
        '5_plain' => 'var dragon = {"name":"Run & Hide"};',
        '5_auto' => 'var dragon = {"name":"Run & Hide"};',
      ]
    );

    // NOT PORTABLE: {$data|@json|smarty:nodefaults}
    $this->check(
      'var dragon = {$contact|@json|smarty:nodefaults};',
      ['contact' => ['name' => 'Run & Hide']],
      [
        '2_plain' => 'var dragon = {"name":"Run & Hide"};',
        '4_plain' => 'var dragon = {"name":"Run & Hide"};',
        '5_plain' => 'var dragon = {"name":"Run & Hide"};',
        '5_auto' => 'var dragon = {&quot;name&quot;:&quot;Run &amp; Hide&quot;};', /* outlier */
      ]
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
   * @return string
   */
  protected function render(string $version, string $template, array $vars = []): string {
    [$expectVer] = explode('_', $version);
    return $this->smarty[$version]->execute([$expectVer, $template, $vars]);
  }

  /**
   * Render a Smarty template across several versions. Compare results.
   *
   * @param string $template
   * @param array $vars
   * @param array $versions
   * @return void
   */
  protected function check(string $template, array $vars, array $versions): void {
    $actualResults = [];
    foreach ($versions as $version => $expectResult) {
      $result = $this->render($version, $template, $vars);
      $actualResults[$version] = $result;
    }
    $this->assertEquals($versions, $actualResults, "Test Smarty template: {$template}");
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
      '2_plain' => $expect,
      '4_plain' => $expect,
      '5_plain' => $expect,
      '5_auto' => $expect,
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
