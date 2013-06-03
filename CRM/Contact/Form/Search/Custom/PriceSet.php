<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Contact_Form_Search_Custom_PriceSet extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_eventID = NULL;

  protected $_tableName = NULL;

  function __construct(&$formValues) {
    parent::__construct($formValues);

    $this->_eventID = CRM_Utils_Array::value('event_id',
      $this->_formValues
    );

    $this->setColumns();

    if ($this->_eventID) {
      $this->buildTempTable();

      $this->fillTable();
    }
  }

  function __destruct() {
    /*
        if ( $this->_eventID ) {
            $sql = "DROP TEMPORARY TABLE {$this->_tableName}";
            CRM_Core_DAO::executeQuery( $sql );
        }
        */
  }

  function buildTempTable() {
    $randomNum        = md5(uniqid());
    $this->_tableName = "civicrm_temp_custom_{$randomNum}";
    $sql              = "
CREATE TEMPORARY TABLE {$this->_tableName} (
  id int unsigned NOT NULL AUTO_INCREMENT,
  contact_id int unsigned NOT NULL,
  participant_id int unsigned NOT NULL,
";

    foreach ($this->_columns as $dontCare => $fieldName) {
      if (in_array($fieldName, array(
        'contact_id',
            'participant_id',
            'display_name',
          ))) {
        continue;
      }
      $sql .= "{$fieldName} int default 0,\n";
    }

    $sql .= "
PRIMARY KEY ( id ),
UNIQUE INDEX unique_participant_id ( participant_id )
) ENGINE=HEAP
";

    CRM_Core_DAO::executeQuery($sql);
  }

  function fillTable() {
    $sql = "
REPLACE INTO {$this->_tableName}
( contact_id, participant_id )
SELECT c.id, p.id
FROM   civicrm_contact c,
       civicrm_participant p
WHERE  p.contact_id = c.id
  AND  p.is_test    = 0
  AND  p.event_id = {$this->_eventID}
  AND  p.status_id NOT IN (4,11,12)
  AND  ( c.is_deleted = 0 OR c.is_deleted IS NULL )
";
    CRM_Core_DAO::executeQuery($sql);

    $sql = "
SELECT c.id as contact_id,
       p.id as participant_id,
       l.price_field_value_id as price_field_value_id,
       l.qty
FROM   civicrm_contact c,
       civicrm_participant  p,
       civicrm_line_item    l
WHERE  c.id = p.contact_id
AND    p.event_id = {$this->_eventID}
AND    p.id = l.entity_id
AND    l.entity_table ='civicrm_participant'
ORDER BY c.id, l.price_field_value_id;
";

    $dao = CRM_Core_DAO::executeQuery($sql);

    // first store all the information by option value id
    $rows = array();
    while ($dao->fetch()) {
      $contactID = $dao->contact_id;
      $participantID = $dao->participant_id;
      if (!isset($rows[$participantID])) {
        $rows[$participantID] = array();
      }

      $rows[$participantID][] = "price_field_{$dao->price_field_value_id} = {$dao->qty}";
    }

    foreach (array_keys($rows) as $participantID) {
      $values = implode(',', $rows[$participantID]);
      $sql = "
UPDATE {$this->_tableName}
SET $values
WHERE participant_id = $participantID;
";
      CRM_Core_DAO::executeQuery($sql);
    }
  }

  function priceSetDAO($eventID = NULL) {

    // get all the events that have a price set associated with it
    $sql = "
SELECT e.id    as id,
       e.title as title,
       p.price_set_id as price_set_id
FROM   civicrm_event      e,
       civicrm_price_set_entity  p

WHERE  p.entity_table = 'civicrm_event'
AND    p.entity_id    = e.id
";

    $params = array();
    if ($eventID) {
      $params[1] = array($eventID, 'Integer');
      $sql .= " AND e.id = $eventID";
    }

    $dao = CRM_Core_DAO::executeQuery($sql,
      $params
    );
    return $dao;
  }

  function buildForm(&$form) {
    $dao = $this->priceSetDAO();

    $event = array();
    while ($dao->fetch()) {
      $event[$dao->id] = $dao->title;
    }

    if (empty($event)) {
      CRM_Core_Error::fatal(ts('There are no events with Price Sets'));
    }

    $form->add('select',
      'event_id',
      ts('Event'),
      $event,
      TRUE
    );

    /**
     * You can define a custom title for the search form
     */
    $this->setTitle('Price Set Export');

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array('event_id'));
  }

  function setColumns() {
    $this->_columns = array(
      ts('Contact Id') => 'contact_id',
      ts('Participant Id') => 'participant_id',
      ts('Name') => 'display_name',
    );

    if (!$this->_eventID) {
      return;
    }

    // for the selected event, find the price set and all the columns associated with it.
    // create a column for each field and option group within it
    $dao = $this->priceSetDAO($this->_formValues['event_id']);

    if ($dao->fetch() &&
      !$dao->price_set_id
    ) {
      CRM_Core_Error::fatal(ts('There are no events with Price Sets'));
    }

    // get all the fields and all the option values associated with it
    $priceSet = CRM_Price_BAO_Set::getSetDetail($dao->price_set_id);
    if (is_array($priceSet[$dao->price_set_id])) {
      foreach ($priceSet[$dao->price_set_id]['fields'] as $key => $value) {
        if (is_array($value['options'])) {
          foreach ($value['options'] as $oKey => $oValue) {
            $columnHeader = CRM_Utils_Array::value('label', $value);
            if (CRM_Utils_Array::value('html_type', $value) != 'Text') {
              $columnHeader .= ' - ' . $oValue['label'];
            }

            $this->_columns[$columnHeader] = "price_field_{$oValue['id']}";
          }
        }
      }
    }
  }

  function summary() {
    return NULL;
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL,
    $includeContactIDs = FALSE, $justIDs = FALSE
  ) {
    if ($justIDs) {
      $selectClause = "contact_a.id as contact_id";
    }
    else {
      $selectClause = "
contact_a.id             as contact_id  ,
contact_a.display_name   as display_name";

      foreach ($this->_columns as $dontCare => $fieldName) {
        if (in_array($fieldName, array(
              'contact_id',
              'display_name',
            ))) {
          continue;
        }
        $selectClause .= ",\ntempTable.{$fieldName} as {$fieldName}";
      }
    }

    return $this->sql($selectClause,
      $offset, $rowcount, $sort,
      $includeContactIDs, NULL
    );
  }

  function from() {
    return "
FROM       civicrm_contact contact_a
INNER JOIN {$this->_tableName} tempTable ON ( tempTable.contact_id = contact_a.id )
";
  }

  function where($includeContactIDs = FALSE) {
    return ' ( 1 ) ';
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  function setDefaultValues() {
    return array();
  }

  function alterRow(&$row) {}

  function setTitle($title) {
    if ($title) {
      CRM_Utils_System::setTitle($title);
    }
    else {
      CRM_Utils_System::setTitle(ts('Export Price Set Info for an Event'));
    }
  }
}

