<?php

/**
 * Class CRM_Core_Page_Inline_HelpTest
 * @group headless
 */
class CRM_Core_Page_Inline_HelpTest extends CiviUnitTestCase {

  /**
   * various test cases
   */
  public function fileTestCases(): array {
    $cases = [];
    $cases['relative paths are allowed'] = ['CRM/Admin/Form/Setting/Url', TRUE];
    $cases['sometimes civi does this on windows'] = ['CRM\\Admin\\Form\\Setting\\Url', TRUE];
    $cases['absolute path are not allowed'] = [\Civi::paths()->getPath('[civicrm.root]/tests/phpunit/CiviTest/test'), FALSE];
    $cases['valid but uses disallowed ..'] = ['CRM/Admin/Form/../Form/Setting/Url', FALSE];
    $cases[] = ['.dot/not/allowed', FALSE];
    $cases[] = ['dot/not/.allowed/in/path/either', FALSE];
    $cases[] = ['C:\win\paths\bad', FALSE];
    $cases[] = ['', FALSE];
    $cases[] = [NULL, FALSE];
    return $cases;
  }

  /**
   * @dataProvider fileTestCases
   */
  public function testHelpFileLoad($testCase, $expectedSuccess): void {
    $_REQUEST = [];
    $_REQUEST['class_name'] = 'CRM_Core_Page_Inline_Help';
    $_REQUEST['file'] = $testCase;
    $_REQUEST['id'] = 'url_vars';
    $page = new CRM_Core_Page_Inline_Help();
    try {
      $page->run();
    }
    catch (CRM_Core_Exception $e) {
      $this->assertEquals('File name is not valid', $e->getMessage());
      $this->assertFalse($expectedSuccess);
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertTrue($expectedSuccess);
    }
  }

}
