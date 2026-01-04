<?php

/**
 * Class CRM_Core_Smarty_plugins_CrmUpperTest
 * @group headless
 */
class CRM_Core_Smarty_plugins_CrmUpperTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  /**
   * Test accents with upper
   */
  public function testUpper(): void {
    $actual = CRM_Utils_String::parseOneOffStringThroughSmarty('{assign var="nom" value="Pièrre"}{$nom|crmUpper}');
    $this->assertEquals('PIÈRRE', $actual);
  }

}
