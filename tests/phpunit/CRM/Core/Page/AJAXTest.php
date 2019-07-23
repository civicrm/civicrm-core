<?php

/**
 * Class CRM_Core_Page_AJAXTest
 * @group headless
 */
class CRM_Core_Page_AJAXTest extends CiviUnitTestCase {

  public function testCheckAuthz() {
    $cases = [];

    $cases[] = ['method', 'CRM_Foo', FALSE, 'method'];
    $cases[] = ['method', 'CRM_Foo_Page_AJAX_Bar', FALSE, 'method'];
    $cases[] = ['method', 'CRM_Contact_Page_AJAX', TRUE, 'getAddressDisplay'];
    $cases[] = ['method', 'CRM_Foo_Page_AJAX', FALSE, 'method('];
    $cases[] = ['method', 'CRM_Foo_Page_AJAX', FALSE, 'method()'];
    $cases[] = ['method', 'othermethod;CRM_Foo_Page_AJAX', FALSE, 'method'];
    $cases[] = ['method', 'CRM_Foo_Page_AJAX;othermethod', FALSE, 'method'];
    $cases[] = ['method', 'CRM_Foo_Page_Inline_Bar', FALSE, ''];
    $cases[] = ['method', 'CRM_Foo_Page_Inline_Bar', FALSE, 'method'];
    $cases[] = ['method', 'CRM_Foo->method', FALSE];

    $cases[] = ['page', 'CRM_Foo', FALSE];
    $cases[] = ['page', 'CRM_Foo_Bar', FALSE];
    $cases[] = ['page', 'CRM_Foo_Page', FALSE];
    $cases[] = ['page', 'CRM_Foo_Page_Bar', FALSE];
    $cases[] = ['page', 'CRM_Foo_Page_Inline', FALSE];
    $cases[] = ['page', 'CRM_Contact_Page_Inline_CommunicationPreferences', TRUE];
    $cases[] = ['page', 'CRM_Foo_Page_Inline_Bar_Bang', FALSE];
    $cases[] = ['page', 'othermethod;CRM_Foo_Page_Inline_Bar', FALSE];
    $cases[] = ['page', 'CRM_Foo_Page_Inline_Bar;othermethod', FALSE];
    $cases[] = ['page', 'CRM_Foo_Form', FALSE];
    $cases[] = ['page', 'CRM_Foo_Form_Bar', FALSE];
    $cases[] = ['page', 'CRM_Foo_Form_Inline', FALSE];
    $cases[] = ['page', 'CRM_Contact_Form_Inline_Email', TRUE];
    $cases[] = ['page', 'CRM_Foo_Form_Inline_Bar_Bang', FALSE];
    $cases[] = ['page', 'othermethod;CRM_Foo_Form_Inline_Bar', FALSE];
    $cases[] = ['page', 'CRM_Foo_Form_Inline_Bar;othermethod', FALSE];

    // aliases for 'page'
    $cases[] = ['class', 'CRM_Foo_Bar', FALSE];
    $cases[] = ['class', 'CRM_Contact_Page_Inline_Phone', TRUE];
    $cases[] = ['', 'CRM_Foo_Bar', FALSE];
    $cases[] = ['', 'CRM_Contact_Page_Inline_Demographics', TRUE];

    // invalid type
    $cases[] = ['invalidtype', 'CRM_Foo_Page_Inline_Bar', FALSE];
    $cases[] = ['invalidtype', 'CRM_Foo_Page_AJAX::method', FALSE];

    foreach ($cases as $case) {
      list ($type, $className, $expectedResult) = $case;
      $methodName = CRM_Utils_Array::value(3, $case);
      $actualResult = CRM_Core_Page_AJAX::checkAuthz($type, $className, $methodName);
      if ($methodName) {
        $this->assertEquals($expectedResult, $actualResult,
          sprintf('Check type=[%s] value=[%s] method=[%s]', $type, $className, $methodName));
      }
      else {
        $this->assertEquals($expectedResult, $actualResult,
          sprintf('Check type=[%s] value=[%s]', $type, $className));
      }
    }
  }

}
