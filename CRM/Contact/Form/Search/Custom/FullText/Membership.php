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
class CRM_Contact_Form_Search_Custom_FullText_Membership extends CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery {

  function __construct() {
    parent::__construct('Membership', ts('Memberships'));
  }

  function isActive() {
    $config = CRM_Core_Config::singleton();
    return in_array('CiviMember', $config->enableComponents) &&
      CRM_Core_Permission::check('access CiviMember');
  }

  /**
   * {@inheritdoc}
   */
  public function fillTempTable($queryText, $entityIDTableName, $toTable, $queryLimit, $detailLimit) {
    $count = $this->fillMembershipIDs($queryText, $entityIDTableName, $queryLimit);
    $this->moveMembershipIDs($entityIDTableName, $toTable, $detailLimit);
    return $count;
  }

  /**
   * get membership ids in entity tables.
   *
   * @param string $queryText
   * @return int the total number of matches
   */
  function fillMembershipIDs($queryText, $entityIDTableName, $limit) {
    $contactSQL = array();
    $contactSQL[] = "
SELECT     distinct cm.id
FROM       civicrm_membership cm
INNER JOIN civicrm_contact c ON cm.contact_id = c.id
WHERE      (c.sort_name LIKE {$this->toSqlWildCard($queryText)} OR c.display_name LIKE {$this->toSqlWildCard($queryText)})
";
    $tables = array(
      'civicrm_membership' => array(
        'id' => 'id',
        'fields' => array('source' => NULL),
      ),
      'sql' => $contactSQL,
    );

    // get the custom data info
    $this->fillCustomInfo($tables, "( 'Membership' )");
    return $this->runQueries($queryText, $tables, $entityIDTableName, $limit);
  }

  public function moveMembershipIDs($fromTable, $toTable, $limit) {
    $sql = "
INSERT INTO {$toTable}
( table_name, contact_id, sort_name, membership_id, membership_type, membership_fee, membership_start_date,
membership_end_date, membership_source, membership_status )
   SELECT  'Membership', c.id, c.sort_name, cm.id, cmt.name, cc.total_amount, cm.start_date, cm.end_date, cm.source, cms.name
     FROM  {$fromTable} ct
INNER JOIN civicrm_membership cm ON cm.id = ct.entity_id
LEFT JOIN  civicrm_contact c ON cm.contact_id = c.id
LEFT JOIN  civicrm_membership_type cmt ON cmt.id = cm.membership_type_id
LEFT JOIN  civicrm_membership_payment cmp ON cmp.membership_id = cm.id
LEFT JOIN  civicrm_contribution cc ON cc.id = cmp.contribution_id
LEFT JOIN  civicrm_membership_status cms ON cms.id = cm.status_id
{$this->toLimit($limit)}
";
    CRM_Core_DAO::executeQuery($sql);
  }

}