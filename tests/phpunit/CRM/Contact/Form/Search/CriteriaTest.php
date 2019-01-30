<?php

/*
  +--------------------------------------------------------------------+
  | CiviCRM version 5                                                  |
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
 * Test class for CRM_Contact_Form_Search_Criteria
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_Form_Search_CriteriaTest extends CiviUnitTestCase {

  /**
   * Test that the 'multiple bulk email' setting correctly affects the type of
   * field used for the 'email on hold' criteria in Advanced Search.
   */
  public function testAdvancedHSearchObservesMultipleBulkEmailSetting() {

    // If setting is enabled, criteria should be a select element.
    Civi::settings()->set('civimail_multiple_bulk_emails', 1);
    $form = new CRM_Contact_Form_Search_Advanced();
    $form->controller = new CRM_Contact_Controller_Search();
    $form->preProcess();
    $form->buildQuickForm();
    $onHoldElemenClass = (get_class($form->_elements[$form->_elementIndex['email_on_hold']]));
    $this->assertEquals('HTML_QuickForm_select', $onHoldElemenClass, 'civimail_multiple_bulk_emails setting = 1, so email_on_hold should be a select element.');

    // If setting is disabled, criteria should be a checkbox.
    Civi::settings()->set('civimail_multiple_bulk_emails', 0);
    $form = new CRM_Contact_Form_Search_Advanced();
    $form->controller = new CRM_Contact_Controller_Search();
    $form->preProcess();
    $form->buildQuickForm();
    $onHoldElemenClass = (get_class($form->_elements[$form->_elementIndex['email_on_hold']]));
    $this->assertEquals('HTML_QuickForm_advcheckbox', $onHoldElemenClass, 'civimail_multiple_bulk_emails setting = 0, so email_on_hold should be a checkbox.');
  }

}
