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
   * @param callable $additionalSetup
   *   Function that performs some additional setup steps specific to the form
   *
   * @dataProvider formClassList
   */
  public function testOpeningForms(string $classname, callable $additionalSetup) {
    $form = $this->getFormObject($classname);

    // call the callable parameter we were passed in
    $additionalSetup($form);

    // typical quickform/smarty flow
    $form->preProcess();
    $form->buildQuickForm();
    $form->setDefaultValues();
    $form->assign('action', $form->_action ?? CRM_Core_Action::UPDATE);
    $form->getTemplate()->fetch($form->getTemplateFileName());
  }

  /**
   * Dataprovider for testOpeningForms().
   * TODO: Add more forms!
   *
   * @return array
   *   See first one below for description.
   */
  public function formClassList() {
    return [
      // Array key is descriptive term to make it clearer which form it is when it fails.
      'Add New Tag' => [
        // classname
        'CRM_Tag_Form_Edit',
        // Function that performs some class-specific additional setup steps.
        // If there's a lot of complex steps then that suggests it should have
        // its own test elsewhere and doesn't fit well here.
        function(CRM_Core_Form $form) {},
      ],
      'Assign Account to Financial Type' => [
        'CRM_Financial_Form_FinancialTypeAccount',
        function(CRM_Core_Form $form) {
          $form->set('id', 1);
          $form->set('aid', 1);
          $form->_action = CRM_Core_Action::ADD;
        },
      ],
      // This one is a bit flawed but the only point of this test is to catch
      // simple stuff. This will catch e.g. "undefined index" and similar.
      'Find Contacts' => [
        'CRM_Contact_Form_Search_Basic',
        function(CRM_Core_Form $form) {
          $form->_action = CRM_Core_Action::BASIC;
        },
      ],
      'New Price Field' => [
        'CRM_Price_Form_Field',
        function(CRM_Core_Form $form) {
          $form->set('sid', 1);
          $form->_action = CRM_Core_Action::ADD;
        },
      ],
      // Also a bit flawed, but catches simple stuff.
      'Fulltext search' => [
        'CRM_Contact_Form_Search_Custom',
        function(CRM_Core_Form $form) {
          $form->_action = CRM_Core_Action::BASIC;
          $form->set('csid', 15);
        },
      ],
    ];
  }

}
