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
 * $Id$
 *
 */

/**
 * Class for event form task actions.
 * FIXME: This needs refactoring to properly inherit from CRM_Core_Form_Task and share more functions.
 */
class CRM_Event_Form_Task extends CRM_Core_Form_Task {

  /**
   * The array that holds all the participant ids.
   *
   * @var array
   */
  protected $_participantIds;

  /**
   * Build all the data structures needed to build the form.
   *
   * @param
   *
   * @return void
   */
  public function preProcess() {
    self::preProcessCommon($this);
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function preProcessCommon(&$form) {
    $form->_participantIds = [];

    $values = $form->controller->exportValues($form->get('searchFormName'));

    $form->_task = $values['task'];
    $tasks = CRM_Event_Task::permissionedTaskTitles(CRM_Core_Permission::getPermission());
    if (!array_key_exists($form->_task, $tasks)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }
    $form->assign('taskName', $tasks[$form->_task]);

    $ids = [];
    if ($values['radio_ts'] == 'ts_sel') {
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
        CRM_Contact_BAO_Query::MODE_EVENT
      );
      $query->_distinctComponentClause = "civicrm_participant.id";
      $query->_groupByComponentClause = " GROUP BY civicrm_participant.id ";
      $result = $query->searchQuery(0, 0, $sortOrder);
      while ($result->fetch()) {
        $ids[] = $result->participant_id;
      }
    }

    if (!empty($ids)) {
      $form->_componentClause = ' civicrm_participant.id IN ( ' . implode(',', $ids) . ' ) ';
      $form->assign('totalSelectedParticipants', count($ids));
    }

    $form->_participantIds = $form->_componentIds = $ids;

    //set the context for redirection for any task actions
    $session = CRM_Core_Session::singleton();

    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $form);
    $urlParams = 'force=1';
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams .= "&qfKey=$qfKey";
    }

    $searchFormName = strtolower($form->get('searchFormName'));
    if ($searchFormName == 'search') {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/event/search', $urlParams));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url("civicrm/contact/search/$searchFormName",
        $urlParams
      ));
    }
  }

  /**
   * Given the participant id, compute the contact id
   * since its used for things like send email
   */
  public function setContactIDs() {
    $this->_contactIds = CRM_Core_DAO::getContactIDsFromComponent($this->_participantIds,
      'civicrm_participant'
    );
  }

  /**
   * Simple shell that derived classes can call to add buttons to.
   * the form with a customized title for the main Submit
   *
   * @param string $title
   *   Title of the main button.
   * @param string $nextType
   * @param string $backType
   * @param bool $submitOnce
   *
   * @return void
   */
  public function addDefaultButtons($title, $nextType = 'next', $backType = 'back', $submitOnce = FALSE) {
    $this->addButtons([
      [
        'type' => $nextType,
        'name' => $title,
        'isDefault' => TRUE,
      ],
      [
        'type' => $backType,
        'name' => ts('Cancel'),
      ],
    ]);
  }

}
