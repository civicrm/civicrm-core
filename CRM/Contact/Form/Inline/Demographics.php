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
 * Form helper class for demographics section.
 */
class CRM_Contact_Form_Inline_Demographics extends CRM_Contact_Form_Inline {

  /**
   * Build the form object elements.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    CRM_Contact_Form_Edit_Demographics::buildQuickForm($this);
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    $this->saveContact();
    $this->response();
  }

}
