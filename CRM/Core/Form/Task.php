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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This is a shared parent class for form task actions.
 */
abstract class CRM_Core_Form_Task extends CRM_Core_Form {

  /**
   * The task being performed
   *
   * @var int
   */
  protected $_task;

  /**
   * The additional clause that we restrict the search with
   *
   * @var string
   */
  protected $_componentClause = NULL;

  /**
   * The array that holds all the component ids
   *
   * @var array
   */
  protected $_componentIds;

  /**
   * @var int
   */
  protected $queryMode;

  /**
   * The array that holds all the case ids
   *
   * @var array
   */
  public $_entityIds;

  /**
   * The array that holds all the contact ids
   *
   * @var array
   */
  public $_contactIds;

  /**
   * Must be set to entity table name (eg. civicrm_participant) by child class
   *
   * @var string
   */
  static $tableName = NULL;

  /**
   * Must be set to entity shortname (eg. event)
   *
   * @var string
   */
  static $entityShortname = NULL;

  /**
   * Build all the data structures needed to build the form.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    self::preProcessCommon($this);
  }

  /**
   * Common pre-processing function.
   *
   * @param CRM_Core_Form_Task $form
   *
   * @throws \CRM_Core_Exception
   */
  public static function preProcessCommon(&$form) {
    $form->_entityIds = [];

    $searchFormValues = $form->controller->exportValues($form->get('searchFormName'));

    $form->_task = $searchFormValues['task'];
    $className = 'CRM_' . ucfirst($form::$entityShortname) . '_Task';
    $entityTasks = $className::tasks();
    $form->assign('taskName', $entityTasks[$form->_task]);

    $entityIds = [];
    if ($searchFormValues['radio_ts'] == 'ts_sel') {
      foreach ($searchFormValues as $name => $value) {
        if (substr($name, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
          $entityIds[] = substr($name, CRM_Core_Form::CB_PREFIX_LEN);
        }
      }
    }
    else {
      $queryParams = $form->get('queryParams');
      $sortOrder = NULL;
      if ($form->get(CRM_Utils_Sort::SORT_ORDER)) {
        $sortOrder = $form->get(CRM_Utils_Sort::SORT_ORDER);
      }

      $query = new CRM_Contact_BAO_Query($queryParams, NULL, NULL, FALSE, FALSE, $form->getQueryMode());
      $query->_distinctComponentClause = " ( " . $form::$tableName . ".id )";
      $query->_groupByComponentClause = " GROUP BY " . $form::$tableName . ".id ";
      $result = $query->searchQuery(0, 0, $sortOrder);
      $selector = $form::$entityShortname . '_id';
      while ($result->fetch()) {
        $entityIds[] = $result->$selector;
      }
    }

    if (!empty($entityIds)) {
      $form->_componentClause = ' ' . $form::$tableName . '.id IN ( ' . implode(',', $entityIds) . ' ) ';
      $form->assign('totalSelected' . ucfirst($form::$entityShortname) . 's', count($entityIds));
    }

    $form->_entityIds = $form->_componentIds = $entityIds;

    // Some functions (eg. PDF letter tokens) rely on Ids being in specific fields rather than the generic $form->_entityIds
    // So we set that specific field here (eg. for cases $form->_caseIds = $form->_entityIds).
    // FIXME: This is really to handle legacy code that should probably be updated to use $form->_entityIds
    $entitySpecificIdsName = '_' . $form::$entityShortname . 'Ids';
    $form->$entitySpecificIdsName = $form->_entityIds;

    //set the context for redirection for any task actions
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $form);
    $urlParams = 'force=1';
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams .= "&qfKey=$qfKey";
    }

    $session = CRM_Core_Session::singleton();
    $searchFormName = strtolower($form->get('searchFormName'));
    if ($searchFormName == 'search') {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/' . $form::$entityShortname . '/search', $urlParams));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url("civicrm/contact/search/$searchFormName",
        $urlParams
      ));
    }
  }

  /**
   * Given the entity id, compute the contact id since its used for things like send email
   * For example, for cases we need to override this function as the table name is civicrm_case_contact
   */
  public function setContactIDs() {
    $this->_contactIds = CRM_Core_DAO::getContactIDsFromComponent($this->_entityIds,
      $this::$tableName
    );
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
      ]
    );
  }

  /**
   * Get the query mode (eg. CRM_Core_BAO_Query::MODE_CASE)
   * Should be overridden by child classes in most cases
   *
   * @return int
   */
  public function getQueryMode() {
    return $this->queryMode ?: CRM_Contact_BAO_Query::MODE_CONTACTS;
  }

}
