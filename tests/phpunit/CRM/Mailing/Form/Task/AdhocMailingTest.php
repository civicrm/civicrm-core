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
 * Test class for CRM_Mailing_Form_Task_AdhocMailing.
 * @group headless
 */
class CRM_Mailing_Form_Task_AdhocMailingTest extends CiviUnitTestCase {

  /**
   * Test creating a hidden smart group from a search builder search.
   *
   * A hidden smart group is a group used for sending emails.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateHiddenGroupFromSearchBuilder(): void {
    $this->createLoggedInUser();
    $formValues = [
      'entryURL' => 'http://dmaster.local/civicrm/contact/search/builder?reset=1',
      'mapper' => [1 => [['Individual']]],
      'operator' => [1 => ['=']],
      'value' => [1 => [0 => 'erwr']],
      '_qf_default' => 'Builder:refresh',
      '_qf_Builder_refresh' => 'Search',
      'radio_ts' => '',
    ];
    $form = $this->getSearchFormObject('CRM_Mailing_Form_Task_AdhocMailing', $formValues, 'Builder');
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
    $savedSearch = $this->callAPISuccess('SavedSearch', 'get', ['sequential' => 1, 'options' => ['sort' => "id DESC"]]);
    $this->assertGreaterThan(0, $savedSearch['count']);
    $this->assertEquals($formValues, $savedSearch['values'][0]['form_values']);
  }

}
