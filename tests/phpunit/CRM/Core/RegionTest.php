<?php

/**
 * Class CRM_Core_RegionTest
 * @group headless
 */
class CRM_Core_RegionTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    require_once 'CRM/Core/Smarty.php';
    require_once 'CRM/Core/Region.php';

    // Templates injected into regions should normally be file names, but for unit-testing it's handy to use "string:" notation
    require_once 'CRM/Core/Smarty/resources/String.php';
    civicrm_smarty_register_string_resource();
  }

  /**
   * When a {crmRegion} is blank and when there are no extra snippets, the
   * output is blank.
   */
  public function testBlank() {
    $smarty = CRM_Core_Smarty::singleton();
    $actual = $smarty->fetch('string:{crmRegion name=testBlank}{/crmRegion}');
    $expected = '';
    $this->assertEquals($expected, $actual);
  }

  /**
   * When a {crmRegion} is not blank and when there are no extra snippets,
   * the output is only determined by the {crmRegion} block.
   */
  public function testDefault() {
    $smarty = CRM_Core_Smarty::singleton();
    $actual = $smarty->fetch('string:{crmRegion name=testDefault}default<br/>{/crmRegion}');
    $expected = 'default<br/>';
    $this->assertEquals($expected, $actual);
  }

  /**
   * Disable the normal content of a {crmRegion} and apply different content from a snippet
   */
  public function testOverride() {
    CRM_Core_Region::instance('testOverride')->update('default', array(
      'disabled' => TRUE,
    ));
    CRM_Core_Region::instance('testOverride')->add(array(
      'markup' => 'override<br/>',
    ));

    $smarty = CRM_Core_Smarty::singleton();
    $actual = $smarty->fetch('string:{crmRegion name=testOverride}default<br/>{/crmRegion}');
    $expected = 'override<br/>';
    $this->assertEquals($expected, $actual);
  }

  /**
   * Test that each of the major content formats are correctly evaluated.
   */
  public function testAllTypes() {
    CRM_Core_Region::instance('testAllTypes')->add(array(
      'markup' => 'some-markup<br/>',
    ));
    CRM_Core_Region::instance('testAllTypes')->add(array(
      // note: 'template' would normally be a file name
      'template' => 'string:smarty-is-{$snippet.extrainfo}<br/>',
      'extrainfo' => 'dynamic',
    ));
    CRM_Core_Region::instance('testAllTypes')->add(array(
      // note: returns a value which gets appended to the region
      'callback' => 'implode',
      'arguments' => array('-', array('callback', 'with', 'specific', 'args<br/>')),
    ));
    CRM_Core_Region::instance('testAllTypes')->add(array(
      // note: returns a value which gets appended to the region
      'callback' => function(&$spec, &$html) {
         return "callback-return<br/>";
      },
    ));
    CRM_Core_Region::instance('testAllTypes')->add(array(
      // note: returns void; directly modifies region's $html
      'callback' => function(&$spec, &$html) {
        $html = "callback-ref<br/>" . $html;
      },
    ));
    CRM_Core_Region::instance('testAllTypes')->add(array(
      'scriptUrl' => '/foo%20bar.js',
    ));
    CRM_Core_Region::instance('testAllTypes')->add(array(
      'script' => 'alert("hi");',
    ));
    CRM_Core_Region::instance('testAllTypes')->add(array(
      'jquery' => '$("div");',
    ));
    CRM_Core_Region::instance('testAllTypes')->add(array(
      'styleUrl' => '/foo%20bar.css',
    ));
    CRM_Core_Region::instance('testAllTypes')->add(array(
      'style' => 'body { background: black; }',
    ));

    $smarty = CRM_Core_Smarty::singleton();
    $actual = $smarty->fetch('string:{crmRegion name=testAllTypes}default<br/>{/crmRegion}');
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

  /**
   * Test of nested arrangement in which one {crmRegion} directly includes another {crmRegion}
   */
  public function testDirectNest() {
    CRM_Core_Region::instance('testDirectNestOuter')->add(array(
      'template' => 'string:O={$snippet.weight} ',
      'weight' => -5,
    ));
    CRM_Core_Region::instance('testDirectNestOuter')->add(array(
      'template' => 'string:O={$snippet.weight} ',
      'weight' => 5,
    ));

    CRM_Core_Region::instance('testDirectNestInner')->add(array(
      'template' => 'string:I={$snippet.weight} ',
      'weight' => -5,
    ));
    CRM_Core_Region::instance('testDirectNestInner')->add(array(
      'template' => 'string:I={$snippet.weight} ',
      'weight' => 5,
    ));

    $smarty = CRM_Core_Smarty::singleton();
    $actual = $smarty->fetch('string:{crmRegion name=testDirectNestOuter}left {crmRegion name=testDirectNestInner}middle {/crmRegion}right {/crmRegion}');
    $expected = 'O=-5 left I=-5 middle I=5 right O=5 ';
    $this->assertEquals($expected, $actual);
  }

  /**
   * Test of nested arrangement in which one {crmRegion} is enhanced with a snippet which, in turn, includes another {crmRegion}
   */
  public function testIndirectNest() {
    CRM_Core_Region::instance('testIndirectNestOuter')->add(array(
      // Note: all three $snippet references are bound to the $snippet which caused this template to be included,
      // regardless of any nested {crmRegion}s
      'template' => 'string: O={$snippet.region}{crmRegion name=testIndirectNestInner} O={$snippet.region}{/crmRegion} O={$snippet.region}',
    ));

    CRM_Core_Region::instance('testIndirectNestInner')->add(array(
      'template' => 'string: I={$snippet.region}',
    ));

    $smarty = CRM_Core_Smarty::singleton();
    $actual = $smarty->fetch('string:{crmRegion name=testIndirectNestOuter}default{/crmRegion}');
    $expected = 'default O=testIndirectNestOuter O=testIndirectNestOuter I=testIndirectNestInner O=testIndirectNestOuter';
    $this->assertEquals($expected, $actual);
  }

  /**
   * Output from an inner-region should not be executed verbatim; this is obvious but good to verify
   */
  public function testNoInjection() {
    CRM_Core_Region::instance('testNoInjectionOuter')->add(array(
      'template' => 'string:{$snippet.scarystuff} ',
      'scarystuff' => '{$is_outer_scary}',
    ));
    CRM_Core_Region::instance('testNoInjectionInner')->add(array(
      'template' => 'string:{$snippet.scarystuff} ',
      'scarystuff' => '{$is_inner_scary}',
    ));

    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('is_outer_scary', 'egad');
    $smarty->assign('is_inner_scary', 'egad');
    $smarty->assign('also_scary', 'egad');
    $actual = $smarty->fetch('string:{crmRegion name=testNoInjectionOuter}left {crmRegion name=testNoInjectionInner}middle {literal}{$also_scary}{/literal} {/crmRegion}right {/crmRegion}');
    $expected = 'left middle {$also_scary} {$is_inner_scary} right {$is_outer_scary} ';
    $this->assertEquals($expected, $actual);
  }

  /**
   * Make sure that standard Smarty variables ($smarty->assign(...)) as well
   * as the magical $snippet variable both evaluate correctly.
   */
  public function testSmartyVars() {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('extrainfo', 'one');
    CRM_Core_Region::instance('testSmartyVars')->add(array(
      'template' => 'string:var-style-{$extrainfo}<br/>',
    ));

    CRM_Core_Region::instance('testSmartyVars')->add(array(
      'template' => 'string:var-style-{$snippet.extrainfo}<br/>',
      'extrainfo' => 'two',
    ));

    $actual = $smarty->fetch('string:{crmRegion name=testSmartyVars}default<br/>{/crmRegion}');
    $expected = 'default<br/>var-style-one<br/>var-style-two<br/>';
    $this->assertEquals($expected, $actual);
  }

  public function testWeight() {
    CRM_Core_Region::instance('testWeight')->add(array(
      'markup' => 'prepend-5<br/>',
      'weight' => -5,
    ));
    CRM_Core_Region::instance('testWeight')->add(array(
      'markup' => 'append+3<br/>',
      'weight' => 3,
    ));
    CRM_Core_Region::instance('testWeight')->add(array(
      'markup' => 'prepend-3<br/>',
      'weight' => -3,
    ));
    CRM_Core_Region::instance('testWeight')->add(array(
      'markup' => 'append+5<br/>',
      'weight' => 5,
    ));

    $smarty = CRM_Core_Smarty::singleton();
    $actual = $smarty->fetch('string:{crmRegion name=testWeight}default<br/>{/crmRegion}');
    $expected = 'prepend-5<br/>prepend-3<br/>default<br/>append+3<br/>append+5<br/>';
    $this->assertEquals($expected, $actual);
  }

}
