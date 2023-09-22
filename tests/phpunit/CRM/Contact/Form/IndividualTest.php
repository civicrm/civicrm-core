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
  public function testOpeningNewIndividualForm(): void {
    $_REQUEST['reset'] = 1;
    $_REQUEST['ct'] = 'Individual';
    $form = $this->getFormObject('CRM_Contact_Form_Contact');
    $form->assign('urlIsPublic');
    $form->assign('linkButtons', []);

    ob_start();
    $form->controller->_actions['display']->perform($form, 'display');
    $contents = ob_get_clean();

    // There's a bunch of other stuff we could check for in $contents, but then
    // it's tied to the html output and has the same maintenance problems
    // as web tests. Mostly what we're doing in this test is checking that it
    // doesn't generate any E_NOTICES/WARNINGS or other errors. But let's do
    // one check.
    $this->assertStringContainsString('<label for="first_name">', $contents);
  }

  /**
   * This is the same as testOpeningNewIndividualForm but with a custom field
   * defined. It maybe doesn't need to be a separate test but might make it
   * easier to track down problems if one fails but not the other.
   */
  public function testOpeningNewIndividualFormWithCustomField(): void {
    $custom_group = $this->customGroupCreate([]);
    $this->customFieldCreate(['custom_group_id' => $custom_group['id']]);
    $this->customFieldCreate([
      'custom_group_id' => $custom_group['id'],
      'label' => 'f2',
      'html_type' => 'Select',
      // being lazy, just re-use activity type choices
      'option_group_id' => 'activity_type',
    ]);
    $this->customFieldCreate([
      'custom_group_id' => $custom_group['id'],
      'label' => 'f3',
      'html_type' => 'Radio',
      'option_group_id' => 'gender',
    ]);
    $form = $this->getFormObject('CRM_Contact_Form_Contact');
    $form->controller = new CRM_Core_Controller_Simple('CRM_Contact_Form_Contact', 'New Individual');
    $form->assign('urlIsPublic');
    $form->assign('linkButtons', []);

    $form->set('reset', '1');
    $form->set('ct', 'Individual');

    ob_start();
    $form->controller->_actions['display']->perform($form, 'display');
    $contents = ob_get_clean();

    $this->assertStringContainsString('<label for="first_name">', $contents);
  }

}
