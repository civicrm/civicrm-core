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
class CRM_Contact_Form_Search_Custom_FullText_Contribution extends CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery {

  function __construct() {
    parent::__construct('Contribution', ts('Contributions'));
  }

  function isActive() {
    $config = CRM_Core_Config::singleton();
    return in_array('CiviContribute', $config->enableComponents) &&
      CRM_Core_Permission::check('access CiviContribute');
  }

  /**
   * {@inheritdoc}
   */
  public function fillTempTable($queryText, $entityIDTableName, $toTable, $queryLimit, $detailLimit) {
    $count = $this->fillContributionIDs($queryText, $entityIDTableName, $queryLimit);
    $this->moveContributionIDs($entityIDTableName, $toTable, $detailLimit);
    return $count;
  }

  /**
   * get contribution ids in entity tables.
   *
   * @param string $queryText
   * @return int the total number of matches
   */
  function fillContributionIDs($queryText, $entityIDTableName, $limit) {
    $contactSQL = array();
    $contactSQL[] = "
SELECT     distinct cc.id
FROM       civicrm_contribution cc
INNER JOIN civicrm_contact c ON cc.contact_id = c.id
WHERE      (c.sort_name LIKE {$this->toSqlWildCard($queryText)} OR
           c.display_name LIKE {$this->toSqlWildCard($queryText)})
";
    $tables = array(
      'civicrm_contribution' => array(
        'id' => 'id',
        'fields' => array(
          'source' => NULL,
          'amount_level' => NULL,
          'trxn_Id' => NULL,
          'invoice_id' => NULL,
          'check_number' => (is_numeric($queryText)) ? 'Int' : NULL,
          'total_amount' => (is_numeric($queryText)) ? 'Int' : NULL,
        ),
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
    return $this->runQueries($queryText, $tables, $entityIDTableName, $limit);
  }

  public function moveContributionIDs($fromTable, $toTable, $limit) {
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