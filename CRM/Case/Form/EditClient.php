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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class assigns the current case to another client.
 */
class CRM_Case_Form_EditClient extends CRM_Core_Form {

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);

    //get current client name.
    $this->assign('currentClientName', CRM_Contact_BAO_Contact::displayName($cid));

    //set the context.
    $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&force=1&cid={$cid}&selectedChild=case");
    if ($context == 'search') {
      $qfKey = CRM_Utils_Request::retrieve('key', 'String', $this);
      //validate the qfKey
      $urlParams = 'force=1';
      if (CRM_Utils_Rule::qfKey($qfKey)) {
        $urlParams .= "&qfKey=$qfKey";
      }
      $url = CRM_Utils_System::url('civicrm/case/search', $urlParams);
    }
    elseif ($context == 'dashboard') {
      $url = CRM_Utils_System::url('civicrm/case', 'reset=1');
    }
    elseif (in_array($context, [
      'dashlet',
      'dashletFullscreen',
    ])) {
      $url = CRM_Utils_System::url('civicrm/dashboard', 'reset=1');
    }
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext($url);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addEntityRef('reassign_contact_id', ts('Select Contact'), ['create' => TRUE], TRUE);
    $this->addButtons([
      [
        'type' => 'done',
        'name' => ts('Reassign Case'),
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);

    // This form may change the url structure so should not submit via ajax
    $this->preventAjaxSubmit();
  }

  public function addRules() {
    $this->addFormRule([get_class($this), 'formRule'], $this);
  }

  /**
   * @param $vals
   * @param $rule
   * @param CRM_Core_Form $form
   *
   * @return array
   */
  public static function formRule($vals, $rule, $form) {
    $errors = [];
    if (empty($vals['reassign_contact_id']) || $vals['reassign_contact_id'] == $form->get('cid')) {
      $errors['reassign_contact_id'] = ts("Please select a different contact.");
    }
    return $errors;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    //assign case to another client.
    $mainCaseId = CRM_Case_BAO_Case::mergeCases($params['reassign_contact_id'], $this->get('id'), $this->get('cid'), NULL, TRUE);

    // user context
    $url = CRM_Utils_System::url('civicrm/contact/view/case',
      "reset=1&action=view&cid={$params['reassign_contact_id']}&id={$mainCaseId[0]}&show=1"
    );
    CRM_Core_Session::singleton()->pushUserContext($url);
  }

}
