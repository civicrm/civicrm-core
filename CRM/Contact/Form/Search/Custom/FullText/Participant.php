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
class CRM_Contact_Form_Search_Custom_FullText_Participant extends CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct('Participant', ts('Participants'));
  }

  /**
   * Check if user has permission.
   *
   * @return bool
   */
  public function isActive() {
    $config = CRM_Core_Config::singleton();
    return in_array('CiviEvent', $config->enableComponents) &&
    CRM_Core_Permission::check('view event participants');
  }

  /**
   * @inheritDoc
   */
  public function fillTempTable($queryText, $entityIDTableName, $toTable, $queryLimit, $detailLimit) {
    $queries = $this->prepareQueries($queryText, $entityIDTableName);
    $result = $this->runQueries($queryText, $queries, $entityIDTableName, $queryLimit);
    $this->moveIDs($entityIDTableName, $toTable, $detailLimit);
    if (!empty($result['files'])) {
      $this->moveFileIDs($toTable, 'participant_id', $result['files']);
    }
    return $result;
  }

  /**
   * Get participant ids in entity tables.
   *
   * @param string $queryText
   * @param string $entityIDTableName
   *
   * @return array
   *   list tables/queries (for runQueries)
   */
  public function prepareQueries($queryText, $entityIDTableName) {
    // Note: For available full-text indices, see CRM_Core_InnoDBIndexer

    $contactSQL = array();
    $contactSQL[] = "
SELECT     distinct cp.id
FROM       civicrm_participant cp
INNER JOIN civicrm_contact c ON cp.contact_id = c.id
WHERE      ({$this->matchText('civicrm_contact c', array('sort_name', 'display_name', 'nick_name'), $queryText)})
";
    $tables = array(
      'civicrm_participant' => array(
        'id' => 'id',
        'fields' => array(
          'source' => NULL,
          'fee_level' => NULL,
          'fee_amount' => 'Int',
        ),
      ),
      'file' => array(
        'xparent_table' => 'civicrm_participant',
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
    return $tables;
  }

  /**
   * Move IDs.
   * @param string $fromTable
   * @param string $toTable
   * @param int $limit
   */
  public function moveIDs($fromTable, $toTable, $limit) {
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
