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
class CRM_Contact_Form_Search_Custom_ActivitySearch extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_formValues;
  protected $_aclFrom = NULL;
  protected $_aclWhere = NULL;

  /**
   * Class constructor.
   *
   * @param array $formValues
   *
   * @throws \CRM_Core_Exception
   */
  public function __construct(&$formValues) {
    $this->_formValues = self::formatSavedSearchFields($formValues);

    /**
     * Define the columns for search result rows
     */
    $this->_columns = [
      ts('Name') => 'sort_name',
      ts('Status') => 'activity_status_id',
      ts('Activity Type') => 'activity_type_id',
      ts('Activity Subject') => 'activity_subject',
      ts('Scheduled By') => 'source_contact',
      ts('Scheduled Date') => 'activity_date',
      ' ' => 'activity_id',
      '   ' => 'case_id',
      ts('Location') => 'location',
      ts('Duration') => 'duration',
      ts('Details') => 'details',
      ts('Assignee') => 'assignee',
    ];

    //Add custom fields to columns array for inclusion in export
    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Activity',
      [],
      NULL,
      NULL,
      [],
      NULL,
      TRUE,
      NULL,
      FALSE,
      CRM_Core_Permission::VIEW
    );

    //use simplified formatted groupTree
    $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree);

    //cycle through custom fields and assign to _columns array
    foreach ($groupTree as $key) {
      foreach ($key['fields'] as $field) {
        $fieldlabel = $key['title'] . ": " . $field['label'];
        $this->_columns[$fieldlabel] = $field['column_name'];
      }
    }
  }

  /**
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form) {

    /**
     * You can define a custom title for the search form
     */
    $this->setTitle(ts('Find Latest Activities'));

    /**
     * Define the search form fields here
     */
    // Allow user to choose which type of contact to limit search on
    $form->add('select', 'contact_type', ts('Find...'), CRM_Core_SelectValues::contactType());

    // Text box for Activity Subject
    $form->add('text',
      'activity_subject',
      ts('Activity Subject')
    );

    // Select box for Activity Type
    $activityType = ['' => ts(' - select activity - ')] + CRM_Activity_BAO_Activity::buildOptions('activity_type_id', 'search');

    $form->add('select', 'activity_type_id', ts('Activity Type'),
      $activityType,
      FALSE
    );

    // textbox for Activity Status
    $activityStatus = ['' => ts(' - select status - ')] + CRM_Activity_BAO_Activity::buildOptions('status_id', 'search');

    $form->add('select', 'activity_status_id', ts('Activity Status'),
      $activityStatus,
      FALSE
    );

    // Activity Date range
    $form->add('datepicker', 'start_date', ts('Activity Date From'), [], FALSE, ['time' => FALSE]);
    $form->add('datepicker', 'end_date', ts('...through'), [], FALSE, ['time' => FALSE]);

    // Contact Name field
    $form->add('text', 'sort_name', ts('Contact Name'));

    /**
     * If you are using the sample template, this array tells the template fields to render
     * for the search form.
     */
    $form->assign('elements', [
      'contact_type',
      'activity_subject',
      'activity_type_id',
      'activity_status_id',
      'start_date',
      'end_date',
      'sort_name',
    ]);
  }

  /**
   * Define the smarty template used to layout the search form and results listings.
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/ActivitySearch.tpl';
  }

  /**
   * Construct the search query.
   *
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public function all(
    $offset = 0, $rowcount = 0, $sort = NULL,
    $includeContactIDs = FALSE, $justIDs = FALSE
  ) {

    // SELECT clause must include contact_id as an alias for civicrm_contact.id
    if ($justIDs) {
      $select = 'contact_a.id as contact_id';
    }
    else {
      $select = '
                contact_a.id                as contact_id,
                contact_a.sort_name         as sort_name,
                contact_a.contact_type      as contact_type,
                activity.id                 as activity_id,
                activity.activity_type_id   as activity_type_id,
                contact_b.sort_name         as source_contact,
                activity.subject            as activity_subject,
                activity.activity_date_time as activity_date,
                activity.status_id          as activity_status_id,
                cca.case_id                 as case_id,
                activity.location           as location,
                activity.duration           as duration,
                activity.details            as details,
                assignment.activity_id      as assignment_activity,
                contact_c.display_name      as assignee
                ';
    }

    $from = $this->from();

    $where = $this->where($includeContactIDs);

    if (!empty($where)) {
      $where = "WHERE $where";
    }

    // add custom group fields to SELECT and FROM clause
    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Activity',
      [],
      NULL,
      NULL,
      [],
      NULL,
      TRUE,
      NULL,
      FALSE,
      CRM_Core_Permission::VIEW
    );

    foreach ($groupTree as $key) {
      if (!empty($key['extends']) && $key['extends'] === 'Activity') {
        $select .= ", " . $key['table_name'] . ".*";
        $from .= " LEFT JOIN " . $key['table_name'] . " ON " . $key['table_name'] . ".entity_id = activity.id";
      }
    }
    // end custom groups add

    $sql = " SELECT $select FROM   $from $where ";

    //no need to add order when only contact Ids.
    if (!$justIDs) {
      // Define ORDER BY for query in $sort, with default value
      if (!empty($sort)) {
        if (is_string($sort)) {
          $sort = CRM_Utils_Type::escape($sort, 'String');
          $sql .= " ORDER BY $sort ";
        }
        else {
          $sql .= ' ORDER BY ' . trim($sort->orderBy());
        }
      }
      else {
        $sql .= 'ORDER BY contact_a.sort_name, activity.activity_date_time DESC, activity.activity_type_id, activity.status_id, activity.subject';
      }
    }
    else {
      //CRM-14107, since there could be multiple activities against same contact,
      //we need to provide GROUP BY on contact id to prevent duplicacy on prev/next entries
      $sql .= 'GROUP BY contact_a.id
ORDER BY contact_a.sort_name';
    }

    if ($rowcount > 0 && $offset >= 0) {
      $offset = CRM_Utils_Type::escape($offset, 'Int');
      $rowcount = CRM_Utils_Type::escape($rowcount, 'Int');
      $sql .= " LIMIT $offset, $rowcount ";
    }
    return $sql;
  }

  /**
   * Alters the date display in the Activity Date Column. We do this after we already have
   * the result so that sorting on the date column stays pertinent to the numeric date value
   * @param $row
   */
  public function alterRow(&$row) {
    $row['activity_date'] = CRM_Utils_Date::customFormat($row['activity_date'], '%B %E%f, %Y %l:%M %P');
    $row['activity_type_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Activity_DAO_Activity', 'activity_type_id', $row['activity_type_id']);
    $row['activity_status_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Activity_DAO_Activity', 'activity_status_id', $row['activity_status_id']);
  }

  /**
   * Regular JOIN statements here to limit results to contacts who have activities.
   * @return string
   */
  public function from() {
    $this->buildACLClause('contact_a');
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);

    $from = "
        civicrm_activity activity
            LEFT JOIN civicrm_activity_contact target
                 ON activity.id = target.activity_id AND target.record_type_id = {$targetID}
            JOIN civicrm_contact contact_a
                 ON contact_a.id = target.contact_id
            LEFT JOIN civicrm_activity_contact sourceContact
                 ON activity.id = sourceContact.activity_id AND sourceContact.record_type_id = {$sourceID}
            JOIN civicrm_contact contact_b
                 ON sourceContact.contact_id = contact_b.id
            LEFT JOIN civicrm_case_activity cca
                 ON activity.id = cca.activity_id
            LEFT JOIN civicrm_activity_contact assignment
                 ON activity.id = assignment.activity_id AND assignment.record_type_id = {$assigneeID}
            LEFT JOIN civicrm_contact contact_c
                 ON assignment.contact_id = contact_c.id {$this->_aclFrom}";

    return $from;
  }

  /**
   * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values.
   *
   * @param bool $includeContactIDs
   *
   * @return string
   */
  public function where($includeContactIDs = FALSE) {
    $clauses = [];

    // add contact name search; search on primary name, source contact, assignee
    $contactname = $this->_formValues['sort_name'];
    if (!empty($contactname)) {
      $dao = new CRM_Core_DAO();
      $contactname = $dao->escape($contactname);
      $clauses[] = "(contact_a.sort_name LIKE '%{$contactname}%' OR
                           contact_b.sort_name LIKE '%{$contactname}%' OR
                           contact_c.display_name LIKE '%{$contactname}%')";
    }

    $subject = $this->_formValues['activity_subject'];

    if (!empty($this->_formValues['contact_type'])) {
      $clauses[] = "contact_a.contact_type LIKE '%{$this->_formValues['contact_type']}%'";
    }

    if (!empty($subject)) {
      $dao = new CRM_Core_DAO();
      $subject = $dao->escape($subject);
      $clauses[] = "activity.subject LIKE '%{$subject}%'";
    }

    if (!empty($this->_formValues['activity_status_id'])) {
      $clauses[] = "activity.status_id = {$this->_formValues['activity_status_id']}";
    }

    if (!empty($this->_formValues['activity_type_id'])) {
      $clauses[] = "activity.activity_type_id = {$this->_formValues['activity_type_id']}";
    }

    if (!empty($this->_formValues['start_date'])) {
      $clauses[] = "activity.activity_date_time >= '{$this->_formValues['start_date']} 00:00:00'";
    }

    if (!empty($this->_formValues['end_date'])) {
      $clauses[] = "activity.activity_date_time <= '{$this->_formValues['end_date']} 23:59:59'";
    }

    if ($includeContactIDs) {
      $contactIDs = [];
      foreach ($this->_formValues as $id => $value) {
        if ($value &&
          substr($id, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX
        ) {
          $contactIDs[] = substr($id, CRM_Core_Form::CB_PREFIX_LEN);
        }
      }

      if (!empty($contactIDs)) {
        $contactIDs = implode(', ', $contactIDs);
        $clauses[] = "contact_a.id IN ( $contactIDs )";
      }
    }

    if ($this->_aclWhere) {
      $clauses[] = " {$this->_aclWhere} ";
    }
    return implode(' AND ', $clauses);
  }

  /*
   * Functions below generally don't need to be modified
   */

  /**
   * @inheritDoc
   *
   * @throws \CRM_Core_Exception
   */
  public function count() {
    $sql = $this->all();

    $dao = CRM_Core_DAO::executeQuery($sql);
    return $dao->N;
  }

  /**
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $returnSQL Not used; included for consistency with parent; SQL is always returned
   *
   * @return string
   */
  public function contactIDs($offset = 0, $rowcount = 0, $sort = NULL, $returnSQL = TRUE) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }

  /**
   * @return array
   */
  public function &columns() {
    return $this->_columns;
  }

  /**
   * @return null
   */
  public function summary() {
    return NULL;
  }

  /**
   * @param string $tableAlias
   */
  public function buildACLClause($tableAlias = 'contact') {
    list($this->_aclFrom, $this->_aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause($tableAlias);
  }

  /**
   * Format saved search fields for this custom group.
   *
   * Note this is a function to facilitate the transition to jcalendar for
   * saved search groups. In time it can be stripped out again.
   *
   * @param array $formValues
   *
   * @return array
   */
  public static function formatSavedSearchFields($formValues) {
    $dateFields = [
      'start_date',
      'end_date',
    ];
    foreach ($formValues as $element => $value) {
      if (in_array($element, $dateFields) && !empty($value)) {
        $formValues[$element] = date('Y-m-d', strtotime($value));
      }
    }

    return $formValues;
  }

}
