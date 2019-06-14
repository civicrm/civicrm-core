<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |                                    |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */
/**
 * Test class for CRM_Contact_Form_Task_EmailCommon.
 * @group headless
 */
class CRM_Mailing_Form_Task_AdHocMailingTest extends CiviUnitTestCase {

  protected function setUp() {
    parent::setUp();
    $this->_contactIds = [
      $this->individualCreate(['first_name' => 'Antonia', 'last_name' => 'D`souza']),
      $this->individualCreate(['first_name' => 'Anthony', 'last_name' => 'Collins']),
    ];
    $this->_optionValue = $this->callApiSuccess('optionValue', 'create', [
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
    try {
      $form->preProcess();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      // Nothing to see here.
    }
    $savedSearch = $this->callAPISuccessGetSingle('SavedSearch', []);
    $this->assertEquals(['bla'], $savedSearch);
  }

}
