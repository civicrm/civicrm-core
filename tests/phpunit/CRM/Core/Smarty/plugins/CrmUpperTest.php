<?php

/**
 * Class CRM_Core_Smarty_plugins_CrmUpperTest
 * @group headless
 */
class CRM_Core_Smarty_plugins_CrmUpperTest extends CiviUnitTestCase {

  /**
   * Test accents with upper
   */
  public function testUpper(): void {
    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{assign var="nom" value="Pièrre"}{$nom|crmUpper}');
    $this->assertEquals('PIÈRRE', $actual);
  }

}
