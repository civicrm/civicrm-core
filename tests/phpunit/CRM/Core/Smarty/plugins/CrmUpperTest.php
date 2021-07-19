<?php

/**
 * Class CRM_Core_Smarty_plugins_CrmUpperTest
 * @group headless
 */
class CRM_Core_Smarty_plugins_CrmUpperTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    require_once 'CRM/Core/Smarty.php';

    // Templates should normally be file names, but for unit-testing it's handy to use "string:" notation
    require_once 'CRM/Core/Smarty/resources/String.php';
    civicrm_smarty_register_string_resource();
  }

  /**
   * Test accents with upper
   */
  public function testUpper() {
    $smarty = CRM_Core_Smarty::singleton();
    $actual = $smarty->fetch('string:{assign var="nom" value="Pièrre"}{$nom|crmUpper}');
    $this->assertEquals('PIÈRRE', $actual);
  }

}
