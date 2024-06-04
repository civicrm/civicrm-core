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

use Civi\Token\TokenProcessor;

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
   * Rows to act on.
   *
   * e.g
   *  [
   *    ['contact_id' => 4, 'participant_id' => 6, 'schema' => ['contactId' => 5, 'participantId' => 6],
   *  ]
   * @var array
   */
  protected $rows = [];

  /**
   * Set where the browser should be directed to next.
   *
   * @param string $pathPart
   *
   * @throws \CRM_Core_Exception
   */
  public function setNextUrl(string $pathPart) {
    //set the context for redirection for any task actions
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
    $urlParams = 'force=1';
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams .= "&qfKey=$qfKey";
    }

    $session = CRM_Core_Session::singleton();
    $searchFormName = strtolower($this->get('searchFormName') ?? '');
    if ($searchFormName === 'search') {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/' . $pathPart . '/search', $urlParams));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url("civicrm/contact/search/$searchFormName",
        $urlParams
      ));
    }
  }

  /**
   * Get the ids the user has selected or an empty array if selection has not been used.
   *
   * @param array $values
   */
  public function getSelectedIDs(array $values): array {
    if ($values['radio_ts'] === 'ts_sel') {
      $ids = [];
      foreach ($values as $name => $value) {
        if (substr($name, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
          $ids[] = substr($name, CRM_Core_Form::CB_PREFIX_LEN);
        }
      }
      return $ids;
    }
    return [];
  }

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

    $searchFormValues = $form->getSearchFormValues();

    $form->_task = $searchFormValues['task'];
    $isSelectedContacts = ($searchFormValues['radio_ts'] ?? NULL) === 'ts_sel';
    $form->assign('isSelectedContacts', $isSelectedContacts);
    $entityIds = [];
    if ($isSelectedContacts) {
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
      $query->_distinctComponentClause = $form->getDistinctComponentClause();
      $query->_groupByComponentClause = $form->getGroupByComponentClause();
      $result = $query->searchQuery(0, 0, $sortOrder);
      $selector = $form->getEntityAliasField();
      while ($result->fetch()) {
        $entityIds[] = $result->$selector;
      }
    }

    if (!empty($entityIds)) {
      $form->_componentClause = ' ' . $form->getTableName() . '.id IN ( ' . implode(',', $entityIds) . ' ) ';
      $form->assign('totalSelected' . ucfirst($form::$entityShortname) . 's', count($entityIds));
    }

    $form->_entityIds = $form->_componentIds = $entityIds;

    // Some functions (eg. PDF letter tokens) rely on Ids being in specific fields rather than the generic $form->_entityIds
    // So we set that specific field here (eg. for cases $form->_caseIds = $form->_entityIds).
    // FIXME: This is really to handle legacy code that should probably be updated to use $form->_entityIds
    $entitySpecificIdsName = '_' . $form::$entityShortname . 'Ids';
    $form->$entitySpecificIdsName = $form->_entityIds;
    $form->setNextUrl($form::$entityShortname);

  }

  /**
   * Given the entity id, compute the contact id since its used for things like send email
   * For example, for cases we need to override this function as the table name is civicrm_case_contact
   */
  public function setContactIDs() {
    $this->_contactIds = CRM_Core_DAO::getContactIDsFromComponent($this->_entityIds,
      $this->getTableName()
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

  /**
   * Get the submitted values for the form.
   *
   * @return array
   */
  public function getSearchFormValues() {
    if ($this->_action === CRM_Core_Action::ADVANCED) {
      return $this->controller->exportValues('Advanced');
    }
    if ($this->_action === CRM_Core_Action::PROFILE) {
      return $this->controller->exportValues('Builder');
    }
    if ($this->_action == CRM_Core_Action::COPY) {
      return $this->controller->exportValues('Custom');
    }
    if ($this->get('entity') !== 'Contact') {
      return $this->controller->exportValues('Search');
    }
    return $this->controller->exportValues('Basic');
  }

  /**
   * Get the name of the table for the relevant entity.
   *
   * @return string
   */
  public function getTableName() {
    CRM_Core_Error::deprecatedFunctionWarning('function should be overridden');
    return $this::$tableName;
  }

  /**
   * Get the clause for grouping by the component.
   *
   * @return string
   */
  public function getDistinctComponentClause() {
    return " ( " . $this->getTableName() . ".id )";
  }

  /**
   * Get the group by clause for the component.
   *
   * @return string
   */
  public function getGroupByComponentClause() {
    return " GROUP BY " . $this->getTableName() . ".id ";
  }

  /**
   * Get the group by clause for the component.
   *
   * @return string
   */
  public function getEntityAliasField() {
    CRM_Core_Error::deprecatedFunctionWarning('function should be overridden');
    return $this::$entityShortname . '_id';
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens() {
    $tokenProcessor = new TokenProcessor(Civi::dispatcher(), ['schema' => $this->getTokenSchema()]);
    return $tokenProcessor->listTokens();
  }

  /**
   * Get the token processor schema required to list any tokens for this task.
   *
   * @return array
   */
  protected function getTokenSchema(): array {
    return ['contactId'];
  }

  /**
   * Get the rows from the results.
   *
   * @return array
   */
  protected function getRows(): array {
    $rows = [];
    foreach ($this->getContactIDs() as $contactID) {
      $rows[] = ['contact_id' => $contactID, 'schema' => ['contactId' => $contactID]];
    }
    return $rows;
  }

  /**
   * Get the relevant contact IDs.
   *
   * @return array
   */
  protected function getContactIDs(): array {
    if (!isset($this->_contactIds)) {
      $this->setContactIDs();
    }
    return $this->_contactIds;
  }

}
