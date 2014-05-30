<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Contact_Form_Search_Custom_FullText_Participant extends CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery {

  function __construct() {
    parent::__construct('Participant', ts('Participants'));
  }

  function isActive() {
    $config = CRM_Core_Config::singleton();
    return in_array('CiviEvent', $config->enableComponents) &&
      CRM_Core_Permission::check('view event participants');
  }

  /**
   * {@inheritdoc}
   */
  public function fillTempTable($queryText, $entityIDTableName, $toTable, $queryLimit, $detailLimit) {
    $count = $this->fillParticipantIDs($queryText, $entityIDTableName, $queryLimit);
    $this->moveParticipantIDs($entityIDTableName, $toTable, $detailLimit);
    return $count;
  }

  /**
   * get participant ids in entity tables.
   *
   * @param string $queryText
   * @return int the total number of matches
   */
  function fillParticipantIDs($queryText, $entityIDTableName, $limit) {
    $contactSQL = array();
    $contactSQL[] = "
SELECT     distinct cp.id
FROM       civicrm_participant cp
INNER JOIN civicrm_contact c ON cp.contact_id = c.id
WHERE      (c.sort_name LIKE {$this->toSqlWildCard($queryText)} OR c.display_name LIKE {$this->toSqlWildCard($queryText)})
";
    $tables = array(
      'civicrm_participant' => array(
        'id' => 'id',
        'fields' => array(
          'source' => NULL,
          'fee_level' => NULL,
          'fee_amount' => (is_numeric($queryText)) ? 'Int' : NULL,
        ),
      ),
      'sql' => $contactSQL,
      'civicrm_note' => array(
        'id' => 'entity_id',
        'entity_table' => 'civicrm_participant',
        'fields' => array(
          'subject' => NULL,
          'note' => NULL,
        ),
      ),
    );

    // get the custom data info
    $this->fillCustomInfo($tables, "( 'Participant' )");
    return $this->runQueries($queryText, $tables, $entityIDTableName, $limit);
  }

  public function moveParticipantIDs($fromTable, $toTable, $limit) {
    $sql = "
INSERT INTO {$toTable}
( table_name, contact_id, sort_name, participant_id, event_title, participant_fee_level, participant_fee_amount,
participant_register_date, participant_source, participant_status, participant_role )
   SELECT  'Participant', c.id, c.sort_name, cp.id, ce.title, cp.fee_level, cp.fee_amount, cp.register_date, cp.source,
           participantStatus.label, cp.role_id
     FROM  {$fromTable} ct
INNER JOIN civicrm_participant cp ON cp.id = ct.entity_id
LEFT JOIN  civicrm_contact c ON cp.contact_id = c.id
LEFT JOIN  civicrm_event ce ON ce.id = cp.event_id
LEFT JOIN  civicrm_participant_status_type participantStatus ON participantStatus.id = cp.status_id
{$this->toLimit($limit)}
";
    CRM_Core_DAO::executeQuery($sql);
  }

}