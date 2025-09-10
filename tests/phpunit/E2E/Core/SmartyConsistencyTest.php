<?php

namespace E2E\Core;

use Civi\Test\RemoteTestFunction;

/**
 * Check that common syntax evaluates as expected on different versions of Smarty.
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
    // Observe: {$string nofilter} IS portable
    $this->check('Dragon {$name nofilter}!',
      ['name' => 'Run & Hide'],
      [
        '2_plain' => 'Dragon Run & Hide!',
        '4_plain' => 'Dragon Run & Hide!',
        '5_plain' => 'Dragon Run & Hide!',
        '5_auto' => 'Dragon Run & Hide!',
      ]
    );

    // Observe: {$string|escape nofilter} IS portable
    $this->check(
      'Dragon {$name|escape nofilter}',
      ['name' => 'Run & Hide'],
      [
        '2_plain' => 'Dragon Run &amp; Hide',
        '4_plain' => 'Dragon Run &amp; Hide',
        '5_plain' => 'Dragon Run &amp; Hide',
        '5_auto' => 'Dragon Run &amp; Hide',
      ]
    );

    // Observe: {$object|@json_encode nofilter} IS portable
    $this->check(
      'var dragon = {$contact|@json_encode nofilter};',
      ['contact' => ['name' => 'Run & Hide']],
      [
        '2_plain' => 'var dragon = {"name":"Run & Hide"};',
        '4_plain' => 'var dragon = {"name":"Run & Hide"};',
        '5_plain' => 'var dragon = {"name":"Run & Hide"};',
        '5_auto' => 'var dragon = {"name":"Run & Hide"};',
      ]
    );
  }

  public function testNonPortable() {
    // Observe: {$string} is NOT portable
    $this->check('Dragon {$name}!',
      ['name' => 'Run & Hide'],
      [
        '2_plain' => 'Dragon Run & Hide!',
        '4_plain' => 'Dragon Run & Hide!',
        '5_plain' => 'Dragon Run & Hide!',
        '5_auto' => 'Dragon Run &amp; Hide!', /* outlier */
      ]
    );

    // Observe: {$string|smarty:nodefaults} is NOT portable
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

    // Observe: {$data|@json_encode} IS NOT portable.
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

    // Observe: {$data|json_encode nofilter} is NOT portable
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
    foreach ($versions as $version => $expectResult) {
      $result = $this->render($version, $template, $vars);
      $this->assertEquals($expectResult, $result, "Test Smarty v{$version} with template: {$template}");
    }
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
