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
class CRM_Contact_Form_Search_Custom_EventAggregate extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_formValues;
  protected $_aclFrom = NULL;
  protected $_aclWhere = NULL;
  public $_permissionedComponent;

  /**
   * Class constructor.
   *
   * @param array $formValues
   */
  public function __construct(&$formValues) {
    $this->_formValues = self::formatSavedSearchFields($formValues);
    $this->_permissionedComponent = ['CiviContribute', 'CiviEvent'];

    /**
     * Define the columns for search result rows
     */
    $this->_columns = [
      ts('Event') => 'event_name',
      ts('Type') => 'event_type',
      ts('Number of<br />Participant') => 'participant_count',
      ts('Total Payment') => 'payment_amount',
      ts('Fee') => 'fee',
      ts('Net Payment') => 'net_payment',
      ts('Participant') => 'participant',
    ];
  }

  /**
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form) {

    /**
     * You can define a custom title for the search form
     */
    $this->setTitle('Find Totals for Events');

    /**
     * Define the search form fields here
     */

    $form->addElement('checkbox', 'paid_online', ts('Only show Credit Card Payments'));

    $form->addElement('checkbox', 'show_payees', ts('Show payees'));

    $event_type = CRM_Core_OptionGroup::values('event_type', FALSE);
    foreach ($event_type as $eventId => $eventName) {
      $form->addElement('checkbox', "event_type_id[$eventId]", 'Event Type', $eventName);
    }
    $events = CRM_Event_BAO_Event::getEvents(1);
    $form->add('select', 'event_id', ts('Event Name'), ['' => ts('- select -')] + $events);

    $form->add('datepicker', 'start_date', ts('Payments Date From'), [], FALSE, ['time' => FALSE]);
    $form->add('datepicker', 'end_date', ts('...through'), [], FALSE, ['time' => FALSE]);

    /**
     * If you are using the sample template, this array tells the template fields to render
     * for the search form.
     */
    $form->assign('elements', [
        'paid_online',
        'start_date',
        'end_date',
        'show_payees',
        'event_type_id',
        'event_id',
      ]);
  }

  /**
   * Define the smarty template used to layout the search form and results listings.
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/EventDetails.tpl';
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
   */
  public function all(
    $offset = 0, $rowcount = 0, $sort = NULL,
    $includeContactIDs = FALSE, $justIDs = FALSE
  ) {
    // SELECT clause must include contact_id as an alias for civicrm_contact.id if you are going to use "tasks" like export etc.
    $select = "civicrm_participant.event_id as event_id,
        COUNT(civicrm_participant.id) as participant_count,
        GROUP_CONCAT(DISTINCT(civicrm_event.title)) as event_name,
        civicrm_event.event_type_id as event_type_id,
        civicrm_option_value.label as event_type,
        IF(civicrm_contribution.payment_instrument_id <>0 , 'Yes', 'No') as payment_instrument_id,
        SUM(civicrm_contribution.total_amount) as payment_amount,
        format(sum(if(civicrm_contribution.payment_instrument_id <>0,(civicrm_contribution.total_amount *.034) +.45,0)),2) as fee,
        format(sum(civicrm_contribution.total_amount - (if(civicrm_contribution.payment_instrument_id <>0,(civicrm_contribution.total_amount *.034) +.45,0))),2) as net_payment";

    $from = $this->from();

    $onLine = CRM_Utils_Array::value('paid_online',
      $this->_formValues
    );
    if ($onLine) {
      $from .= "
        inner join civicrm_entity_financial_trxn
        on (civicrm_entity_financial_trxn.entity_id = civicrm_participant_payment.contribution_id and civicrm_entity_financial_trxn.entity_table='civicrm_contribution')";
    }

    $showPayees = CRM_Utils_Array::value('show_payees',
      $this->_formValues
    );
    if ($showPayees) {
      $select .= ",  GROUP_CONCAT(DISTINCT(civicrm_contact.display_name)) as participant ";
      $from .= " inner join civicrm_contact
                         on civicrm_contact.id = civicrm_participant.contact_id";
    }
    else {
      unset($this->_columns[ts('Participant')]);
    }

    $where = $this->where();
    $groupFromSelect = "civicrm_option_value.label, civicrm_contribution.payment_instrument_id";

    $groupBy = "event_id, event_type_id, {$groupFromSelect}";
    if (!empty($this->_formValues['event_type_id'])) {
      $groupBy = "event_type_id, event_id, {$groupFromSelect}";
    }

    $sql = "
        SELECT $select
        FROM   $from
        WHERE  $where
        GROUP BY $groupBy
        ";
    // Define ORDER BY for query in $sort, with default value
    if (!empty($sort)) {
      if (is_string($sort)) {
        $sql .= " ORDER BY $sort ";
      }
      else {
        $sql .= " ORDER BY " . trim($sort->orderBy());
      }
    }
    else {
      $sql .= "ORDER BY event_name desc";
    }

    if ($rowcount > 0 && $offset >= 0) {
      $offset = CRM_Utils_Type::escape($offset, 'Int');
      $rowcount = CRM_Utils_Type::escape($rowcount, 'Int');
      $sql .= " LIMIT $offset, $rowcount ";
    }
    return $sql;
  }

  /**
   * @return string
   */
  public function from() {
    $this->buildACLClause('contact_a');
    $from = "
        civicrm_participant_payment
        left join civicrm_participant
        on civicrm_participant_payment.participant_id=civicrm_participant.id

        left join civicrm_contact contact_a
        on civicrm_participant.contact_id = contact_a.id

        left join civicrm_event on
        civicrm_participant.event_id = civicrm_event.id

        left join civicrm_contribution
        on civicrm_contribution.id = civicrm_participant_payment.contribution_id

        left join civicrm_option_value on
        ( civicrm_option_value.value = civicrm_event.event_type_id AND civicrm_option_value.option_group_id = 14) {$this->_aclFrom}";

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

    $clauses[] = "civicrm_participant.status_id in ( 1 )";
    $clauses[] = "civicrm_contribution.is_test = 0";
    $onLine = CRM_Utils_Array::value('paid_online',
      $this->_formValues
    );
    if ($onLine) {
      $clauses[] = "civicrm_contribution.payment_instrument_id <> 0";
    }

    // As we only allow date to be submitted we need to set default times so midnight for start time and just before midnight for end time.
    if ($this->_formValues['start_date']) {
      $clauses[] = "civicrm_contribution.receive_date >= '{$this->_formValues['start_date']} 00:00:00'";
    }

    if ($this->_formValues['end_date']) {
      $clauses[] = "civicrm_contribution.receive_date <= '{$this->_formValues['end_date']} 23:59:59'";
    }

    if (!empty($this->_formValues['event_id'])) {
      $clauses[] = "civicrm_event.id = {$this->_formValues['event_id']}";
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
        $clauses[] = "contact.id IN ( $contactIDs )";
      }
    }

    if (!empty($this->_formValues['event_type_id'])) {
      $event_type_ids = implode(',', array_keys($this->_formValues['event_type_id']));
      $clauses[] = "civicrm_event.event_type_id IN ( $event_type_ids )";
    }
    if ($this->_aclWhere) {
      $clauses[] = "{$this->_aclWhere} ";
    }
    return implode(' AND ', $clauses);
  }


  /* This function does a query to get totals for some of the search result columns and returns a totals array. */
  /**
   * @return array
   */
  public function summary() {
    $totalSelect = "
        SUM(civicrm_contribution.total_amount) as payment_amount,COUNT(civicrm_participant.id) as participant_count,
        format(sum(if(civicrm_contribution.payment_instrument_id <>0,(civicrm_contribution.total_amount *.034) +.45,0)),2) as fee,
        format(sum(civicrm_contribution.total_amount - (if(civicrm_contribution.payment_instrument_id <>0,(civicrm_contribution.total_amount *.034) +.45,0))),2) as net_payment";

    $from = $this->from();

    $onLine = CRM_Utils_Array::value('paid_online',
      $this->_formValues
    );
    if ($onLine) {
      $from .= "
        inner join civicrm_entity_financial_trxn
        on (civicrm_entity_financial_trxn.entity_id = civicrm_participant_payment.contribution_id and civicrm_entity_financial_trxn.entity_table='civicrm_contribution')";
    }

    $where = $this->where();

    $sql = "
        SELECT  $totalSelect
        FROM    $from
        WHERE   $where
        ";

    $dao = CRM_Core_DAO::executeQuery($sql,
      CRM_Core_DAO::$_nullArray
    );
    $totals = [];
    while ($dao->fetch()) {
      $totals['payment_amount'] = $dao->payment_amount;
      $totals['fee'] = $dao->fee;
      $totals['net_payment'] = $dao->net_payment;
      $totals['participant_count'] = $dao->participant_count;
    }
    return $totals;
  }

  /*
   * Functions below generally don't need to be modified
   */

  /**
   * @inheritDoc
   */
  public function count() {
    $sql = $this->all();

    $dao = CRM_Core_DAO::executeQuery($sql,
      CRM_Core_DAO::$_nullArray
    );
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
    return $this->all($offset, $rowcount, $sort);
  }

  /**
   * @return array
   */
  public function &columns() {
    return $this->_columns;
  }

  /**
   * @param $title
   */
  public function setTitle($title) {
    if ($title) {
      CRM_Utils_System::setTitle($title);
    }
    else {
      CRM_Utils_System::setTitle(ts('Search'));
    }
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
