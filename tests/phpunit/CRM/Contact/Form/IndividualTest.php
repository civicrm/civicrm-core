<?php

/**
 * Class CRM_Contact_Form_IndividualTest
 * @group headless
 */
class CRM_Contact_Form_IndividualTest extends CiviUnitTestCase {

  /**
   * Similar to tests in CRM_Core_FormTest where it's just testing there's no
   * red boxes when you open the form, but Individual is more complicated.
   *
   * This doesn't test much expected output just that the page opens without
   * notices or errors. For example to make it fail change the spelling of a
   * variable in the form so it has a typo, and then this test will throw an
   * exception.
   */
  public function testOpeningNewIndividualForm() {
    $form = new CRM_Contact_Form_Contact();
    $form->controller = new CRM_Core_Controller_Simple('CRM_Contact_Form_Contact', 'New Individual');

    $form->set('reset', '1');
    $form->set('ct', 'Individual');

    ob_start();
    $form->controller->_actions['display']->perform($form, 'display');
    $contents = ob_get_contents();
    ob_end_clean();

    // There's a bunch of other stuff we could check for in $contents, but then
    // it's tied to the html output and has the same maintenance problems
    // as webtests. Mostly what we're doing in this test is checking that it
    // doesn't generate any E_NOTICES/WARNINGS or other errors. But let's do
    // one check.
    $this->assertStringContainsString('<label for="first_name">', $contents);
  }

}
