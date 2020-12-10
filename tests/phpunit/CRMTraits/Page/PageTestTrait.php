<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Trait CRMTraits_Page_PageTestTrait
 *
 * Trait for testing quickform pages in unit tests.
 */
trait CRMTraits_Page_PageTestTrait {

  /**
   * Content from the rendered page.
   *
   * @var string
   */
  protected $pageContent;

  /**
   * @var \CRM_Core_Page
   */
  protected $page;

  /**
   * @var string
   */
  protected $tplName;

  /**
   * Variables assigned to smarty.
   *
   * @var array
   */
  protected $smartyVariables = [];

  protected $context;

  /**
   * @param string $content
   * @param string $context
   * @param string $tplName
   * @param CRM_Core_Page $object
   */
  public function checkPageContent(&$content, $context, $tplName, &$object) {
    $this->pageContent = $content;
    $this->tplName = $tplName;
    $this->page = $object;
    $this->context = $context;
    // Ideally we would validate $content as valid html here.
    // Suppress console output.
    $content = '';
    $this->smartyVariables = CRM_Core_Smarty::singleton()->get_template_vars();
  }

  /**
   * Assert that the page output contains the expected strings.
   *
   * @param $expectedStrings
   */
  protected function assertPageContains($expectedStrings) {
    unset($this->smartyVariables['config']);
    unset($this->smartyVariables['session']);
    foreach ($expectedStrings as $expectedString) {
      $this->assertContains($expectedString, $this->pageContent, print_r($this->contributions, TRUE) . print_r($this->smartyVariables, TRUE));
    }
  }

  /**
   * Assert that the expected variables have been assigned to Smarty.
   *
   * @param $expectedVariables
   */
  protected function assertSmartyVariables($expectedVariables) {
    foreach ($expectedVariables as $variableName => $expectedValue) {
      $this->assertEquals($expectedValue, $this->smartyVariables[$variableName]);
    }
  }

  /**
   * Check an array assigned to smarty for the inclusion of the expected variables.
   *
   * @param string $variableName
   * @param $index
   * @param $expected
   */
  protected function assertSmartyVariableArrayIncludes($variableName, $index, $expected) {
    $smartyVariable = $this->smartyVariables[$variableName];
    if ($index !== NULL) {
      $smartyVariable = $smartyVariable[$index];
    }
    foreach ($expected as $key => $value) {
      $this->assertEquals($value, $smartyVariable[$key], 'Checking ' . $key);
    }
  }

  /**
   * Set up environment to listen for page content.
   */
  protected function listenForPageContent() {
    $this->hookClass->setHook('civicrm_alterContent', [
      $this,
      'checkPageContent',
    ]);
  }

}
