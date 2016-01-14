<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * This class contains the functions for Case Contact management.
 */
class CRM_Case_BAO_CaseContact extends CRM_Case_DAO_CaseContact {

  /**
   * Create case contact record.
   *
   * @param array $params
   *   case_id, contact_id
   *
   * @return CRM_Case_BAO_CaseContact
   */
  public static function create($params) {
    $caseContact = new self();
    $caseContact->copyValues($params);
    $caseContact->save();

    // add to recently viewed
    $caseType = CRM_Case_BAO_Case::getCaseType($caseContact->case_id);
    $url = CRM_Utils_System::url('civicrm/contact/view/case',
      "action=view&reset=1&id={$caseContact->case_id}&cid={$caseContact->contact_id}&context=home"
    );

    $title = CRM_Contact_BAO_Contact::displayName($caseContact->contact_id) . ' - ' . $caseType;

    $recentOther = array();
    if (CRM_Core_Permission::checkActionPermission('CiviCase', CRM_Core_Action::DELETE)) {
      $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/contact/view/case',
        "action=delete&reset=1&id={$caseContact->case_id}&cid={$caseContact->contact_id}&context=home"
      );
    }

    // add the recently created case
    CRM_Utils_Recent::add($title,
      $url,
      $caseContact->case_id,
      'Case',
      $caseContact->contact_id,
      NULL,
      $recentOther
    );

    return $caseContact;
  }

  /**
   * @inheritDoc
   */
  public function apiWhereClause() {
    // In order to make things easier for downstream developers, we reuse and adapt case acls here.
    // This doesn't yield the most straightforward query, but hopefully the sql engine will sort it out.
    $clauses = array(
      // Case acls already check for contact access so we can just mark contact_id as handled
      'contact_id' => NULL,
      'case_id' => array(),
    );
    $caseSubclauses = array();
    $caseBao = new CRM_Case_BAO_Case();
    foreach ($caseBao->apiWhereClause() as $field => $fieldClauses) {
      if ($field == 'id' && $fieldClauses) {
        $clauses['case_id'] = array_merge($clauses['case_id'], (array) $fieldClauses);
      }
      elseif ($fieldClauses) {
        $caseSubclauses[] = "$field " . implode(" AND $field ", (array) $fieldClauses);
      }
    }
    if ($caseSubclauses) {
      $clauses['case_id'][] = 'IN (SELECT id FROM civicrm_case WHERE ' . implode(' AND ', $caseSubclauses) . ')';
    }
    return $clauses;
  }

}
