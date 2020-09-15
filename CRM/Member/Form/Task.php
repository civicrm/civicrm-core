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
 * Class for member form task actions.
 * FIXME: This needs refactoring to properly inherit from CRM_Core_Form_Task and share more functions.
 */
class CRM_Member_Form_Task extends CRM_Core_Form_Task {

  /**
   * The array that holds all the member ids.
   *
   * @var array
   */
  protected $_memberIds;

  /**
   * Build all the data structures needed to build the form.
   *
   * @param
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    self::preProcessCommon($this);
  }

  /**
   * @param CRM_Core_Form $form
   *
   * @throws \CRM_Core_Exception
   */
  public static function preProcessCommon(&$form) {
    $form->_memberIds = [];

    $values = $form->controller->exportValues($form->get('searchFormName'));

    $form->_task = $values['task'];
    $tasks = CRM_Member_Task::permissionedTaskTitles(CRM_Core_Permission::getPermission());
    if (!array_key_exists($form->_task, $tasks)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }
    $form->assign('taskName', $tasks[$form->_task]);

    $ids = [];
    if ($values['radio_ts'] === 'ts_sel') {
      foreach ($values as $name => $value) {
        if (substr($name, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
          $ids[] = substr($name, CRM_Core_Form::CB_PREFIX_LEN);
        }
      }
    }
    else {
      $queryParams = $form->get('queryParams');
      $sortOrder = NULL;
      if ($form->get(CRM_Utils_Sort::SORT_ORDER)) {
        $sortOrder = $form->get(CRM_Utils_Sort::SORT_ORDER);
      }
      $query = new CRM_Contact_BAO_Query($queryParams, NULL, NULL, FALSE, FALSE,
        CRM_Contact_BAO_Query::MODE_MEMBER
      );
      $query->_distinctComponentClause = ' civicrm_membership.id';
      $query->_groupByComponentClause = ' GROUP BY civicrm_membership.id ';
      $result = $query->searchQuery(0, 0, $sortOrder);

      while ($result->fetch()) {
        $ids[] = $result->membership_id;
      }
    }

    if (!empty($ids)) {
      $form->_componentClause = ' civicrm_membership.id IN ( ' . implode(',', $ids) . ' ) ';
      $form->assign('totalSelectedMembers', count($ids));
    }

    $form->_memberIds = $form->_componentIds = $ids;

    //set the context for redirection for any task actions
    $session = CRM_Core_Session::singleton();

    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $form);
    $urlParams = 'force=1';
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams .= "&qfKey=$qfKey";
    }

    $searchFormName = strtolower($form->get('searchFormName'));
    if ($searchFormName === 'search') {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/member/search', $urlParams));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url("civicrm/contact/search/$searchFormName",
        $urlParams
      ));
    }
  }

  /**
   * Given the membership id, compute the contact id
   * since its used for things like send email
   */
  public function setContactIDs() {
    $this->_contactIds = CRM_Core_DAO::getContactIDsFromComponent($this->_memberIds,
      'civicrm_membership'
    );
  }

}
