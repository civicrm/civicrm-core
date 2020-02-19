<?php

/**
 * @group headless
 */
class CRM_Core_FormTest extends CiviUnitTestCase {

  /**
   * Simulate opening various forms. All we're looking to do here is
   * see if any warnings or notices come up, the equivalent of red boxes
   * on the screen, but which are hidden when using popup forms.
   * So no assertions required.
   *
   * @param string $classname
   * @dataProvider formClassList
   */
  public function testOpeningForms(string $classname) {
    $form = $this->getFormObject($classname);
    $form->preProcess();
    $form->buildQuickForm();
    $form->setDefaultValues();
    $form->assign('action', CRM_Core_Action::UPDATE);
    $form->getTemplate()->fetch($form->getTemplateFileName());
  }

  /**
   * Dataprovider for testOpeningForms().
   * TODO: Add more forms! Use a descriptive array key so when it fails
   * it will make it clearer what form it is, although you'll see the class
   * anyway.
   */
  public function formClassList() {
    return [
      'Add New Tag' => ['CRM_Tag_Form_Edit'],
    ];
  }

}
