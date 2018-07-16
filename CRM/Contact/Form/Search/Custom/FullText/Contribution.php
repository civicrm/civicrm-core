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
class CRM_Contact_Form_Search_Custom_FullText_Contribution extends CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct('Contribution', ts('Contributions'));
  }

  /**
   * Check if search is permitted.
   *
   * @return bool
   */
  public function isActive() {
    $config = CRM_Core_Config::singleton();
    return in_array('CiviContribute', $config->enableComponents) &&
    CRM_Core_Permission::check('access CiviContribute');
  }

  /**
   * @inheritDoc
   */
  public function fillTempTable($queryText, $entityIDTableName, $toTable, $queryLimit, $detailLimit) {
    $queries = $this->prepareQueries($queryText, $entityIDTableName);
    $result = $this->runQueries($queryText, $queries, $entityIDTableName, $queryLimit);
    $this->moveIDs($entityIDTableName, $toTable, $detailLimit);
    if (!empty($result['files'])) {
      $this->moveFileIDs($toTable, 'contribution_id', $result['files']);
    }
    return $result;
  }

  /**
   * Get contribution ids in entity tables.
   *
   * @param string $queryText
   * @param string $entityIDTableName
   * @return array
   *   list tables/queries (for runQueries)
   */
  public function prepareQueries($queryText, $entityIDTableName) {
    // Note: For available full-text indices, see CRM_Core_InnoDBIndexer

    $contactSQL = array();
    $contactSQL[] = "
SELECT     distinct cc.id
FROM       civicrm_contribution cc
INNER JOIN civicrm_contact c ON cc.contact_id = c.id
WHERE      ({$this->matchText('civicrm_contact c', array('sort_name', 'display_name', 'nick_name'), $queryText)})
";
    $tables = array(
      'civicrm_contribution' => array(
        'id' => 'id',
        'fields' => array(
          'source' => NULL,
          'amount_level' => NULL,
          'trxn_Id' => NULL,
          'invoice_id' => NULL,
          'check_number' => 'Int', // Odd: This is really a VARCHAR, so why are we searching like an INT?
          'total_amount' => 'Int',
        ),
      ),
      'file' => array(
        'xparent_table' => 'civicrm_contribution',
      ),
      'sql' => $contactSQL,
      'civicrm_note' => array(
        'id' => 'entity_id',
        'entity_table' => 'civicrm_contribution',
        'fields' => array(
          'subject' => NULL,
          'note' => NULL,
        ),
      ),
    );

    // get the custom data info
    $this->fillCustomInfo($tables, "( 'Contribution' )");
    return $tables;
  }

  /**
   * Move IDs.
   *
   * @param string $fromTable
   * @param string $toTable
   * @param int $limit
   */
  public function moveIDs($fromTable, $toTable, $limit) {
    $sql = "
INSERT INTO {$toTable}
( table_name, contact_id, sort_name, contribution_id, financial_type, contribution_page, contribution_receive_date,
  contribution_total_amount, contribution_trxn_Id, contribution_source, contribution_status, contribution_check_number )
   SELECT  'Contribution', c.id, c.sort_name, cc.id, cct.name, ccp.title, cc.receive_date,
           cc.total_amount, cc.trxn_id, cc.source, contribution_status.label, cc.check_number
     FROM  {$fromTable} ct
INNER JOIN civicrm_contribution cc ON cc.id = ct.entity_id
LEFT JOIN  civicrm_contact c ON cc.contact_id = c.id
LEFT JOIN  civicrm_financial_type cct ON cct.id = cc.financial_type_id
LEFT JOIN  civicrm_contribution_page ccp ON ccp.id = cc.contribution_page_id
LEFT JOIN  civicrm_option_group option_group_contributionStatus ON option_group_contributionStatus.name = 'contribution_status'
LEFT JOIN  civicrm_option_value contribution_status ON
( contribution_status.option_group_id = option_group_contributionStatus.id AND contribution_status.value = cc.contribution_status_id )
{$this->toLimit($limit)}
";
    CRM_Core_DAO::executeQuery($sql);
  }

}
