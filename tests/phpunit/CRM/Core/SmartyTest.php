<?php

/**
 * Class CRM_Core_SmartyTest
 * @group headless
 *
 */
class CRM_Core_SmartyTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  /**
   * Check that temporary Smarty variables work.
   *
   * This overlaps with CrmScopeTest (which actually tests more diverse scenarios). However, here we specifically check the PHP APIs
   * (`fetchWith()`) and the correctness of different forms of emptiness.
   *
   * @throws \Exception
   * @see \CRM_Core_Smarty_plugins_CrmScopeTest
   */
  public function testFetchWithCleanNonExistent(): void {
    $smarty = CRM_Core_Smarty::singleton();
    $this->assertArrayNotHasKey('my_variable', $smarty->getTemplateVars());

    $rendered = $smarty->fetchWith('eval:({$my_variable})', [
      'my_variable' => 'temporary value',
    ]);
    $this->assertEquals('(temporary value)', $rendered);

    $this->assertArrayNotHasKey('my_variable', $smarty->getTemplateVars());
  }

  /**
   * Check that temporary Smarty variables work.
   *
   * This overlaps with CrmScopeTest (which actually tests more diverse scenarios). However, here we specifically check the PHP APIs
   * (`fetchWith()`) and the correctness of different forms of emptiness.
   *
   * @see \CRM_Core_Smarty_plugins_CrmScopeTest
   */
  public function testFetchWith_CleanNull(): void {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('my_variable', NULL);
    $this->assertEquals(NULL, $smarty->getTemplateVars()['my_variable']);

    $tpl = 'eval:({$my_variable})';
    $this->assertEquals('()', $smarty->fetchWith($tpl, []));
    $this->assertEquals('(temporary value)', $smarty->fetchWith($tpl, [
      'my_variable' => 'temporary value',
    ]));

    // Assert global state
    $this->assertEquals(NULL, $smarty->getTemplateVars()['my_variable']);
  }

  /**
   * Test that {ts} correctly handles escaping attributes, template-wide
   * escape_html, and {setfilter} blocks.
   *
   * @param string $template
   * @param string $expectedResult
   * @param array $templateVars
   * @param bool $globalEscapeHtml
   *
   * @dataProvider tsEscapingProvider
   */
  public function testTsEscaping(string $template, string $expectedResult, array $templateVars = [], bool $globalEscapeHtml = FALSE): void {
    $smarty = \CRM_Core_Smarty::singleton();
    $oldEscapeHtml = $smarty->escape_html;
    $smarty->escape_html = $globalEscapeHtml;
    try {
      $this->assertEquals($expectedResult, \CRM_Utils_String::parseOneOffStringThroughSmarty($template, $templateVars));
    }
    finally {
      $smarty->escape_html = $oldEscapeHtml;
    }
  }

  public static function tsEscapingProvider(): array {
    return [
      'plain' => [
        '{ts}Hello World{/ts}',
        'Hello World',
      ],
      'no_escape' => [
        '{ts}Hello <b>World</b>{/ts}',
        'Hello <b>World</b>',
      ],
      'escape_html_attr' => [
        '{ts escape="html"}Hello <b>World</b>{/ts}',
        'Hello &lt;b&gt;World&lt;/b&gt;',
      ],
      'sub_literal' => [
        '{ts 1="Dave"}Hello %1{/ts}',
        'Hello Dave',
      ],
      'sub_var' => [
        '{ts 1=$name}Hello %1{/ts}',
        'Hello Alice',
        ['name' => 'Alice'],
      ],
      'setfilter' => [
        '{setfilter escape:"html"}{ts}Hello <b>World</b>{/ts}{/setfilter}',
        'Hello &lt;b&gt;World&lt;/b&gt;',
      ],
      'nofilter_in_setfilter' => [
        '{setfilter escape:"html"}{ts nofilter}Hello <b>World</b>{/ts}{/setfilter}',
        'Hello <b>World</b>',
      ],
      'escape_url' => [
        '{ts escape="url"}Hello World &amp;{/ts}',
        'Hello%20World%20%26amp%3B',
      ],
      'global_escape' => [
        '{ts}Hello <b>World</b>{/ts}',
        'Hello &lt;b&gt;World&lt;/b&gt;',
        [],
        TRUE,
      ],
      'global_no_double' => [
        '{ts escape="html"}Hello <b>World</b>{/ts}',
        'Hello &lt;b&gt;World&lt;/b&gt;',
        [],
        TRUE,
      ],
      'escape_sql' => [
        '{ts escape="sql"}Hello \'World\'{/ts}',
        'Hello \\\'World\\\'',
      ],
      'escape_js' => [
        '{ts escape="js"}Hello "World" & \'Dave\'{/ts}',
        'Hello \u0022World\u0022 \u0026 \u0027Dave\u0027',
      ],
      'nofilter_with_vars_in_setfilter' => [
        '{setfilter escape:"html"}{ts nofilter 1=$world|escape:"html" 2=$markup}Hello <b>%1</b> %2{/ts}{/setfilter}',
        'Hello <b>&lt;b&gt;Earth&lt;/b&gt;</b> <i>Space</i>',
        ['world' => '<b>Earth</b>', 'markup' => '<i>Space</i>'],
      ],
    ];
  }

}
