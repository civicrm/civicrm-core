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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
  public static $tableName = NULL;

  /**
   * Must be set to entity shortname (eg. event)
   *
   * @var string
   */
  public static $entityShortname = NULL;

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
   * Add buttons to the form.
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
    ]);
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

  /**
   * Given the component id, compute the contact id
   * since it's used for things like send email.
   *
   * @todo At the moment this duplicates a similar function in CRM_Core_DAO
   * because right now only the case component is using this. Since the
   * default $orderBy is '' which is what the original does, others should be
   * easily convertable as NFC.
   * @todo The passed in variables should be class member variables. Shouldn't
   * need to have passed in vars.
   *
   * @param $componentIDs
   * @param string $tableName
   * @param string $idField
   *
   * @return array
   */
  public function getContactIDsFromComponent($componentIDs, $tableName, $idField = 'id') {
    $contactIDs = [];

    if (empty($componentIDs)) {
      return $contactIDs;
    }

    $orderBy = $this->orderBy();

    $IDs = implode(',', $componentIDs);
    $query = "
SELECT contact_id
  FROM $tableName
 WHERE $idField IN ( $IDs ) $orderBy
";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $contactIDs[] = $dao->contact_id;
    }
    return $contactIDs;
  }

  /**
   * Default ordering for getContactIDsFromComponent. Subclasses can override.
   *
   * @return string
   *   SQL fragment. Either return '' or a valid order clause including the
   *   words "ORDER BY", e.g. "ORDER BY `{$this->idField}`"
   */
  public function orderBy() {
    return '';
  }

}
