<?php

/**
 * Class CRM_Core_RegionTest
 * @group headless
 * @group resources
 */
class CRM_Core_RegionTest extends CiviUnitTestCase {

  use CRM_Core_Resources_CollectionTestTrait;

  /**
   * @return \CRM_Core_Resources_CollectionInterface
   */
  public function createEmptyCollection() {
    if (empty(Civi::$statics['CRM_Core_RegionTestId'])) {
      Civi::$statics['CRM_Core_RegionTestId'] = 1;
    }
    else {
      ++Civi::$statics['CRM_Core_RegionTestId'];
    }
    $r = new CRM_Core_Region('region_' . Civi::$statics['CRM_Core_RegionTestId']);
    $r->filter(function($snippet) {
      return $snippet['name'] !== 'default';
    });
    return $r;
  }

  /**
   * When a {crmRegion} is blank and when there are no extra snippets, the
   * output is blank.
   *
   * @throws \CRM_Core_Exception
   */
  public function testBlank(): void {
    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmRegion name="testBlank"}{/crmRegion}');
    $expected = '';
    $this->assertEquals($expected, $actual);
  }

  /**
   * When a {crmRegion} is not blank and when there are no extra snippets,
   * the output is only determined by the {crmRegion} block.
   *
   * @throws \CRM_Core_Exception
   */
  public function testDefault(): void {
    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmRegion name="testDefault"}default<br/>{/crmRegion}');
    $expected = 'default<br/>';
    $this->assertEquals($expected, $actual);
  }

  /**
   * Disable the normal content of a {crmRegion} and apply different content from a snippet
   */
  public function testOverride(): void {
    CRM_Core_Region::instance('testOverride')->update('default', [
      'disabled' => TRUE,
    ]);
    CRM_Core_Region::instance('testOverride')->add([
      'markup' => 'override<br/>',
    ]);

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmRegion name=testOverride}default<br/>{/crmRegion}');
    $expected = 'override<br/>';
    $this->assertEquals($expected, $actual);
  }

  /**
   * Test that each of the major content formats are correctly evaluated.
   */
  public function testAllTypes(): void {
    CRM_Core_Region::instance('testAllTypes')->add([
      'markup' => 'some-markup<br/>',
    ]);
    CRM_Core_Region::instance('testAllTypes')->add([
      // note: 'template' would normally be a file name
      'template' => 'eval:smarty-is-{$snippet.extrainfo}<br/>',
      'extrainfo' => 'dynamic',
    ]);
    CRM_Core_Region::instance('testAllTypes')->add([
      // note: returns a value which gets appended to the region
      'callback' => 'implode',
      'arguments' => ['-', ['callback', 'with', 'specific', 'args<br/>']],
    ]);
    CRM_Core_Region::instance('testAllTypes')->add([
      // note: returns a value which gets appended to the region
      'callback' => function(&$spec, &$html) {
         return "callback-return<br/>";
      },
    ]);
    CRM_Core_Region::instance('testAllTypes')->add([
      // note: returns void; directly modifies region's $html
      'callback' => function(&$spec, &$html) {
        $html = "callback-ref<br/>" . $html;
      },
    ]);
    CRM_Core_Region::instance('testAllTypes')->add([
      'scriptUrl' => '/foo%20bar.js',
    ]);
    CRM_Core_Region::instance('testAllTypes')->add([
      'script' => 'alert("hi");',
    ]);
    CRM_Core_Region::instance('testAllTypes')->add([
      'jquery' => '$("div");',
    ]);
    CRM_Core_Region::instance('testAllTypes')->add([
      'styleUrl' => '/foo%20bar.css',
    ]);
    CRM_Core_Region::instance('testAllTypes')->add([
      'style' => 'body { background: black; }',
    ]);

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmRegion name="testAllTypes"}default<br/>{/crmRegion}');
    $expected = "callback-ref<br/>"
      . "default<br/>"
      . "some-markup<br/>"
      . "smarty-is-dynamic<br/>"
      . "callback-with-specific-args<br/>"
      . "callback-return<br/>"
      . "<script type=\"text/javascript\" src=\"/foo%20bar.js\">\n</script>\n"
      . "<script type=\"text/javascript\">\nalert(\"hi\");\n</script>\n"
      . "<script type=\"text/javascript\">\nCRM.\$(function(\$) {\n\$(\"div\");\n});\n</script>\n"
      . "<link href=\"/foo%20bar.css\" rel=\"stylesheet\" type=\"text/css\"/>\n"
      . "<style type=\"text/css\">\nbody { background: black; }\n</style>\n";
    $this->assertEquals($expected, $actual);
  }

  public function esmLoaders(): array {
    return [
      ['browser'],
      ['shim-slow'],
      ['shim-fast'],
    ];
  }

  /**
   * @dataProvider esmLoaders
   * @param string $loader
   */
  public function testEsm(string $loader) {
    Civi::settings()->set('esm_loader', $loader);

    $expected = [];
    $expected['browser'] = "default<br/>" .
      "<script type=\"module\" src=\"/my%20module.mjs\">\n</script>\n"
      . "<script type=\"module\">\nimport foo from \"./foobar.mjs\";\n</script>\n";
    $expected['shim-fast'] = $expected['browser'];
    $expected['shim-slow'] = "default<br/>" .
      "<script type=\"module-shim\" src=\"/my%20module.mjs\">\n</script>\n"
      . "<script type=\"module-shim\">\nimport foo from \"./foobar.mjs\";\n</script>\n";

    CRM_Core_Region::instance('testEsm')->add([
      'scriptUrl' => '/my%20module.mjs',
      'esm' => TRUE,
    ]);
    CRM_Core_Region::instance('testEsm')->add([
      'script' => 'import foo from "./foobar.mjs";',
      'esm' => TRUE,
    ]);

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmRegion name="testEsm"}default<br/>{/crmRegion}');
    $this->assertEquals($expected[$loader], $actual);

    $header = CRM_Core_Region::instance('html-header')->render('');
    switch ($loader) {
      case 'shim-fast':
      case 'shim-slow':
        $this->assertTrue(str_contains($header, 'es-module-shims'), 'HTML header should have shim');
        break;

      default:
        $this->assertFalse(str_contains($header, 'es-module-shims'), 'HTML header should not have shim');
        break;
    }
  }

  /**
   * Test of nested arrangement in which one {crmRegion} directly includes another {crmRegion}
   */
  public function testDirectNest(): void {
    CRM_Core_Region::instance('testDirectNestOuter')->add([
      'template' => 'eval:O={$snippet.weight} ',
      'weight' => -5,
    ]);
    CRM_Core_Region::instance('testDirectNestOuter')->add([
      'template' => 'eval:O={$snippet.weight} ',
      'weight' => 5,
    ]);

    CRM_Core_Region::instance('testDirectNestInner')->add([
      'template' => 'eval:I={$snippet.weight} ',
      'weight' => -5,
    ]);
    CRM_Core_Region::instance('testDirectNestInner')->add([
      'template' => 'eval:I={$snippet.weight} ',
      'weight' => 5,
    ]);

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmRegion name="testDirectNestOuter"}left {crmRegion name="testDirectNestInner"}middle {/crmRegion}right {/crmRegion}');
    $expected = 'O=-5 left I=-5 middle I=5 right O=5 ';
    $this->assertEquals($expected, $actual);
  }

  /**
   * Test of nested arrangement in which one {crmRegion} is enhanced with a snippet which, in turn, includes another {crmRegion}
   */
  public function testIndirectNest(): void {
    CRM_Core_Region::instance('testIndirectNestOuter')->add([
      // Note: all three $snippet references are bound to the $snippet which caused this template to be included,
      // regardless of any nested {crmRegion}s
      'template' => 'string: O={$snippet.region}{crmRegion name=testIndirectNestInner} O={$snippet.region}{/crmRegion} O={$snippet.region}',
    ]);

    CRM_Core_Region::instance('testIndirectNestInner')->add([
      'template' => 'string: I={$snippet.region}',
    ]);

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmRegion name=testIndirectNestOuter}default{/crmRegion}');
    $expected = 'default O=testIndirectNestOuter O=testIndirectNestOuter I=testIndirectNestInner O=testIndirectNestOuter';
    $this->assertEquals($expected, $actual);
  }

  /**
   * Output from an inner-region should not be executed verbatim; this is obvious but good to verify
   */
  public function testNoInjection(): void {
    CRM_Core_Region::instance('testNoInjectionOuter')->add([
      'template' => 'eval:{$snippet.scarystuff} ',
      'scarystuff' => '{$is_outer_scary}',
    ]);
    CRM_Core_Region::instance('testNoInjectionInner')->add([
      'template' => 'eval:{$snippet.scarystuff} ',
      'scarystuff' => '{$is_inner_scary}',
    ]);
    error_reporting(E_ALL & ~E_NOTICE);
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('is_outer_scary', 'egad');
    $smarty->assign('is_inner_scary', 'egad');
    $smarty->assign('also_scary', 'egad');

    $actual = $smarty->fetch('eval:{crmRegion name="testNoInjectionOuter"}left {crmRegion name="testNoInjectionInner"}middle {literal}{$also_scary}{/literal} {/crmRegion}right {/crmRegion}');
    $expected = 'left middle {$also_scary} {$is_inner_scary} right {$is_outer_scary} ';
    $this->assertEquals($expected, $actual);
  }

  /**
   * Make sure that standard Smarty variables ($smarty->assign(...)) as well
   * as the magical $snippet variable both evaluate correctly.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSmartyVars(): void {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('extrainfo', 'one');
    CRM_Core_Region::instance('testSmartyVars')->add([
      'template' => 'eval:var-style-{$extrainfo}<br/>',
    ]);

    CRM_Core_Region::instance('testSmartyVars')->add([
      'template' => 'eval:var-style-{$snippet.extrainfo}<br/>',
      'extrainfo' => 'two',
    ]);

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmRegion name=testSmartyVars}default<br/>{/crmRegion}');
    $expected = 'default<br/>var-style-one<br/>var-style-two<br/>';
    $this->assertEquals($expected, $actual);
  }

  public function testWeight(): void {
    CRM_Core_Region::instance('testWeight')->add([
      'markup' => 'prepend-5<br/>',
      'weight' => -5,
    ]);
    CRM_Core_Region::instance('testWeight')->add([
      'markup' => 'append+3<br/>',
      'weight' => 3,
    ]);
    CRM_Core_Region::instance('testWeight')->add([
      'markup' => 'prepend-3<br/>',
      'weight' => -3,
    ]);
    CRM_Core_Region::instance('testWeight')->add([
      'markup' => 'append+5<br/>',
      'weight' => 5,
    ]);

    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{crmRegion name=testWeight}default<br/>{/crmRegion}');
    $expected = 'prepend-5<br/>prepend-3<br/>default<br/>append+3<br/>append+5<br/>';
    $this->assertEquals($expected, $actual);
  }

}
