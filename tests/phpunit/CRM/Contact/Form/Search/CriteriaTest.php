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
