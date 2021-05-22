<?php
require_once 'CiviTest/CiviCaseTestCase.php';

/**
 * Class CRM_Case_Form_SearchTest
 * @group headless
 */
class CRM_Case_Form_SearchTest extends CiviCaseTestCase {

  public function setUp():void {
    parent::setUp();
    $this->quickCleanup(['civicrm_case_contact', 'civicrm_case', 'civicrm_contact']);
  }

  /**
   * Similar to tests in CRM_Core_FormTest where it's just testing there's no
   * red boxes when you open the form, but it requires CiviCase to be
   * enabled so putting in a separate place.
   *
   * This doesn't test much expected output just that the page opens without
   * notices or errors. For example to make it fail change the spelling of a
   * variable so it has a typo in CRM_Case_Form_Search::preProcess(), and then
   * this test will throw an exception.
   */
  public function testOpeningFindCaseForm() {
    $form = new CRM_Case_Form_Search();
    $form->controller = new CRM_Case_Controller_Search('Find Cases');

    ob_start();
    $form->controller->_actions['display']->perform($form, 'display');
    $contents = ob_get_contents();
    ob_end_clean();

    // There's a bunch of other stuff we could check for in $contents, but then
    // it's tied to the html output and has the same maintenance problems
    // as webtests. Mostly what we're doing in this test is checking that it
    // doesn't generate any E_NOTICES/WARNINGS or other errors. But let's do
    // one check.
    $this->assertStringContainsString('<label for="case_id">', $contents);
  }

}
