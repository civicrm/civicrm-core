<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
   * Variables assigned to smarty.
   *
   * @var array
   */
  protected $smartyVariables = [];

  /**
   * @param string $content
   * @param string $context
   * @param string $tplName
   * @param CRM_Core_Page $object
   */
  public function checkPageContent(&$content, $context, $tplName, &$object) {
    $this->pageContent = $content;
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
    foreach ($expectedStrings as $expectedString) {
      $this->assertContains($expectedString, $this->pageContent);
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
   * Set up environment to listen for page content.
   */
  protected function listenForPageContent() {
    $this->hookClass->setHook('civicrm_alterContent', [
      $this,
      'checkPageContent'
    ]);
  }

}
