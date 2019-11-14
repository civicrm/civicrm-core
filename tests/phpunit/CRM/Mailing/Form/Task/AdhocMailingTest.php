<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */
/**
 * Test class for CRM_Contact_Form_Task_EmailCommon.
 * @group headless
 */
class CRM_Mailing_Form_Task_AdhocMailingTest extends CiviUnitTestCase {

  /**
   * @throws \Exception
   */
  protected function setUp() {
    parent::setUp();
    $this->_contactIds = [
      $this->individualCreate(['first_name' => 'Antonia', 'last_name' => 'D`souza']),
      $this->individualCreate(['first_name' => 'Anthony', 'last_name' => 'Collins']),
    ];
    $this->_optionValue = $this->callAPISuccess('optionValue', 'create', [
      'label' => '"Seamus Lee" <seamus@example.com>',
      'option_group_id' => 'from_email_address',
    ]);
  }

  /**
   * Test creating a hidden smart group from a search builder search.
   *
   * A hidden smart group is a group used for sending emails.
   *
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function testCreateHiddenGroupFromSearchBuilder() {
    $this->createLoggedInUser();
    $formValues = [
      'qfKey' => 'dde96a85ddebb90fb66de44859404aeb_2077',
      'entryURL' => 'http://dmaster.local/civicrm/contact/search/builder?reset=1',
      'mapper' => [1 => [['Individual']]],
      'operator' => [1 => ['=']],
      'value' => [1 => [0 => 'erwr']],
      '_qf_default' => 'Builder:refresh',
      '_qf_Builder_refresh' => 'Search',
      'radio_ts' => '',
    ];
    $form = $this->getFormObject('CRM_Mailing_Form_Task_AdhocMailing', $formValues, 'Builder');
    $form->setAction(CRM_Core_Action::PROFILE);
    $form->set('formValues', $formValues);
    $form->set('isSearchBuilder', 1);
    $form->set('context', 'builder');
    try {
      $form->preProcess();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      // Nothing to see here.
    }
    $savedSearch = $this->callAPISuccessGetSingle('SavedSearch', []);
    $this->assertEquals($formValues, $savedSearch['form_values']);
  }

}
