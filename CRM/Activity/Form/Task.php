<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Class for activity form task actions.
 * FIXME: This needs refactoring to properly inherit from CRM_Core_Form_Task and share more functions.
 */
class CRM_Activity_Form_Task extends CRM_Core_Form_Task {

  /**
   * The array that holds all the member ids.
   *
   * @var array
   */
  public $_activityHolderIds;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    self::preProcessCommon($this);
  }

  /**
   * Common pre-process function.
   *
   * @param CRM_Core_Form $form
   */
  public static function preProcessCommon(&$form) {
    $form->_activityHolderIds = array();

    $values = $form->controller->exportValues($form->get('searchFormName'));

    $form->_task = $values['task'];
    $activityTasks = CRM_Activity_Task::tasks();
    $form->assign('taskName', $activityTasks[$form->_task]);

    $ids = array();
    if ($values['radio_ts'] == 'ts_sel') {
      foreach ($values as $name => $value) {
        if (substr($name, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
          $ids[] = substr($name, CRM_Core_Form::CB_PREFIX_LEN);
        }
      }
    }
    else {
      $queryParams = $form->get('queryParams');
      $query = new CRM_Contact_BAO_Query($queryParams, NULL, NULL, FALSE, FALSE,
        CRM_Contact_BAO_Query::MODE_ACTIVITY
      );
      $query->_distinctComponentClause = '( civicrm_activity.id )';
      $query->_groupByComponentClause = " GROUP BY civicrm_activity.id ";

      // CRM-12675
      $activityClause = NULL;

      $components = CRM_Core_Component::getNames();
      $componentClause = array();
      foreach ($components as $componentID => $componentName) {
        if ($componentName != 'CiviCase' && !CRM_Core_Permission::check("access $componentName")) {
          $componentClause[] = " (activity_type.component_id IS NULL OR activity_type.component_id <> {$componentID}) ";
        }
      }
      if (!empty($componentClause)) {
        $activityClause = implode(' AND ', $componentClause);
      }
      $result = $query->searchQuery(0, 0, NULL, FALSE, FALSE, FALSE, FALSE, FALSE, $activityClause);

      while ($result->fetch()) {
        if (!empty($result->activity_id)) {
          $ids[] = $result->activity_id;
        }
      }
    }

    if (!empty($ids)) {
      $form->_componentClause = ' civicrm_activity.id IN ( ' . implode(',', $ids) . ' ) ';
      $form->assign('totalSelectedActivities', count($ids));
    }

    $form->_activityHolderIds = $form->_componentIds = $ids;

    // Set the context for redirection for any task actions.
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $form);
    $urlParams = 'force=1';
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams .= "&qfKey=$qfKey";
    }

    $session = CRM_Core_Session::singleton();
    $searchFormName = strtolower($form->get('searchFormName'));
    if ($searchFormName == 'search') {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/activity/search', $urlParams));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url("civicrm/contact/search/$searchFormName",
        $urlParams
      ));
    }
  }

  /**
   * Given the membership id, compute the contact id
   * since it's used for things like send email.
   */
  public function setContactIDs() {
    $IDs = implode(',', $this->_activityHolderIds);

    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $query = "
SELECT contact_id
FROM   civicrm_activity_contact
WHERE  activity_id IN ( $IDs ) AND
       record_type_id = {$sourceID}";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $contactIDs[] = $dao->contact_id;
    }
    $this->_contactIds = $contactIDs;
  }

  /**
   * Simple shell that derived classes can call to add buttons to
   * the form with a customized title for the main Submit
   *
   * @param string $title
   *   Title of the main button.
   * @param string $nextType
   *   Button type for the form after processing.
   * @param string $backType
   * @param bool $submitOnce
   */
  public function addDefaultButtons($title, $nextType = 'next', $backType = 'back', $submitOnce = FALSE) {
    $this->addButtons(array(
      array(
        'type' => $nextType,
        'name' => $title,
        'isDefault' => TRUE,
      ),
      array(
        'type' => $backType,
        'name' => ts('Cancel'),
      ),
    ));
  }

}
