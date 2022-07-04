<?php

/**
 * Class CRM_Core_Smarty_EscapeTest
 * @group headless
 * @group resources
 */
class CRM_Core_Smarty_EscapeTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();

    // Templates injected into regions should normally be file names, but for unit-testing it's handy to use "string:" notation
    require_once 'CRM/Core/Smarty/resources/String.php';
    civicrm_smarty_register_string_resource();

    $this->useTransaction();
  }

  protected function getCommonVariables(): array {
    return [
      'xInt' => 100,
      'xFloat' => 55.44,
      'xString' => 'Hello world',
      'xMarkup' => '<b>Yo!</b>',
      'xNULL' => NULL,
      'xTRUE' => TRUE,
      'xFALSE' => FALSE,
    ];
  }

  /**
   * List of examples which should always render the same way, regardless of processingmode.
   *
   * @return array
   */
  protected function getCommonExamples(): array {
    return [
      // Smarty template => Expected output
      'string:{$xInt}' => '100',
      'string:{$xFloat}' => '55.44',
      'string:{$xString|crmEscape:"none"}' => 'Hello world',
      'string:{$xString|crmEscape:"html"}' => 'Hello world',
      'string:{$xMarkup|crmEscape:"none"}' => '<b>Yo!</b>',
      'string:{$xMarkup|crmEscape:"html"}' => '&lt;b&gt;Yo!&lt;/b&gt;',
      'string:{$xMarkup|crmEscape:"html"|crmEscape:"none"}' => '&lt;b&gt;Yo!&lt;/b&gt;',
      'string:{$xMarkup|crmEscape:"html"|crmEscape:"html"}' => '&amp;lt;b&amp;gt;Yo!&amp;lt;/b&amp;gt;',
      'string:{if $xInt eq 100}match{else}no match{/if}' => 'match',
      'string:{if $xInt eq 88}match{else}no match{/if}' => 'no match',
      'string:{if $xFloat > 6}yes{else}no{/if}' => 'yes',
      'string:{if $xFloat > 40}yes{else}no{/if}' => 'yes',
      'string:{if $xFloat > 60}yes{else}no{/if}' => 'no',
    ];
  }

  protected function getDefaultUnescapedExamples(): array {
    return [
      // Smarty template => Expected output
      'string:{$xInt}, {$xString}, {$xMarkup}' => '100, Hello world, <b>Yo!</b>',
      'string:{$xString}' => 'Hello world',
      'string:{$xMarkup}' => '<b>Yo!</b>',
    ];
  }

  protected function getDefaultEscapedExamples(): array {
    return [
      // Smarty template => Expected output
      'string:{$xInt}, {$xString}, {$xMarkup}' => '100, Hello world, &lt;b&gt;Yo!&lt;/b&gt;',
      'string:{$xString}' => 'Hello world',
      'string:{$xMarkup}' => '&lt;b&gt;Yo!&lt;/b&gt;',
    ];
  }

  public function testDefaultOff() {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assignAll($this->getCommonVariables());
    $GLOBALS['_CIVICRM_SMARTY_DEFAULT_ESCAPE'] = FALSE;
    $examples = $this->getCommonExamples() + $this->getDefaultUnescapedExamples();
    $actual = $this->renderAll($smarty, $examples);
    $this->assertEquals($examples, $actual);
  }

  public function testDefaultOn() {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assignAll($this->getCommonVariables());
    $GLOBALS['_CIVICRM_SMARTY_DEFAULT_ESCAPE'] = TRUE;
    $examples = $this->getCommonExamples() + $this->getDefaultEscapedExamples();
    // $examples = $this->getCommonExamples();
    // $examples = $this->getDefaultEscapedExamples();
    $actual = $this->renderAll($smarty, $examples);
    $this->assertEquals($examples, $actual);
  }

  protected function renderAll(CRM_Core_Smarty $smarty, array $examples): array {
    $result = [];
    foreach ($examples as $template => $expected) {
      $result[$template] = $smarty->fetch($template);
    }
    return $result;
  }

}
