<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */

/**
 * This class generates form components for custom data
 *
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 *
 */
class CRM_Profile_Form_Dynamic extends CRM_Profile_Form {

  /**
   * Pre processing work done here.
   *
   * @param
   *
   * @return void
   */
  public function preProcess() {
    if ($this->get('register')) {
      $this->_mode = CRM_Profile_Form::MODE_REGISTER;
    }
    else {
      $this->_mode = CRM_Profile_Form::MODE_EDIT;
    }

    if ($this->get('skipPermission')) {
      $this->_skipPermission = TRUE;
    }

    // also allow dupes to be updated for edit in my account (CRM-2232)
    $this->_isUpdateDupe = TRUE;

    parent::preProcess();
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->addButtons(array(
      array(
        'type' => 'upload',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ),
    ));

    // also add a hidden element for to trick drupal
    $this->addElement('hidden', "edit[civicrm_dummy_field]", "CiviCRM Dummy Field for Drupal");
    parent::buildQuickForm();

    $this->addFormRule(array('CRM_Profile_Form_Dynamic', 'formRule'), $this);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param CRM_Core_Form $form
   *
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $form) {
    $errors = array();

    // if no values, return
    if (empty($fields) || empty($fields['edit'])) {
      return TRUE;
    }

    return CRM_Profile_Form::formRule($fields, $files, $form);
  }

  /**
   * Process the user submitted custom data values.
   *
   *
   * @return void
   */
  public function postProcess() {
    parent::postProcess();
  }

}
