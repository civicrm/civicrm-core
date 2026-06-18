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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class provides the functionality to SMS a group of event participants.
 */
class CRM_Event_Form_Task_SMS extends CRM_Event_Form_Task {
  use CRM_Contact_Form_Task_SMSTrait;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess(): void {
    $this->bounceOnNoActiveProviders();
    parent::preProcess();
    $this->assign('single', FALSE);
    $this->assign('isAdmin', CRM_Core_Permission::check('administer CiviCRM'));
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm(): void {
    $this->assign('suppressForm', FALSE);
    $this->buildSmsForm();
  }

}
