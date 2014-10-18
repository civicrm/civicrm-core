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
class CRM_Contribute_BAO_ContributionSoft extends CRM_Contribute_DAO_ContributionSoft {

  /**
   * construct method
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * function to add contribution soft credit record
   *
   * @param array  $params (reference ) an assoc array of name/value pairs
   *
   * @return object soft contribution of object that is added
   * @access public
   *
   */
  public static function add(&$params) {
    $contributionSoft = new CRM_Contribute_DAO_ContributionSoft();
    $contributionSoft->copyValues($params);

    // set currency for CRM-1496
    if (!isset($contributionSoft->currency)) {
      $config = CRM_Core_Config::singleton();
      $contributionSoft->currency = $config->defaultCurrency;
    }
    return $contributionSoft->save();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Contribute_BAO_ContributionSoft object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $contributionSoft = new CRM_Contribute_DAO_ContributionSoft();
    $contributionSoft->copyValues($params);
    if ($contributionSoft->find(TRUE)) {
      CRM_Core_DAO::storeValues($contributionSoft, $defaults);
      return $contributionSoft;
    }
    return NULL;
  }

  /**
   * Function to delete soft credits
   *
   * @param $params
   *
   * @internal param int $contributionTypeId
   * @static
   */
  static function del($params) {
    //delete from contribution soft table
    $contributionSoft = new CRM_Contribute_DAO_ContributionSoft();
    foreach($params as $column => $value) {
      $contributionSoft->$column = $value;
    }
    $contributionSoft->delete();
  }

  /**
   * @param $contact_id
   * @param int $isTest
   *
   * @return array
   */
  static function getSoftContributionTotals($contact_id, $isTest = 0) {
    $query = '
    SELECT SUM(amount) as amount, AVG(total_amount) as average, cc.currency
    FROM civicrm_contribution_soft  ccs
      LEFT JOIN civicrm_contribution cc ON ccs.contribution_id = cc.id
    WHERE cc.is_test = %2 AND ccs.contact_id = %1
    GROUP BY currency';

    $params = array(1 => array($contact_id, 'Integer'),
      2 => array($isTest, 'Integer'));

    $cs = CRM_Core_DAO::executeQuery($query, $params);

    $count = 0;
    $amount = $average = array();

    while ($cs->fetch()) {
      if ($cs->amount > 0) {
        $count++;
        $amount[] = $cs->amount;
        $average[] = $cs->average;
        $currency[] = $cs->currency;
      }
    }

    if ($count > 0) {
      return array(
        implode(',&nbsp;', $amount),
        implode(',&nbsp;', $average),
        implode(',&nbsp;', $currency),
      );
    }
    return array(0, 0);
  }

  /**
   *  Function to retrieve soft contributions for contribution record.
   *
   * @param $contributionID
   * @param boolean $all include PCP data
   *
   * @internal param array $params an associated array
   * @return array of soft contribution ids, amounts, and associated contact ids
   * @static
   */
  static function getSoftContribution($contributionID, $all = FALSE) {
    $pcpFields = array(
      'pcp_id',
      'pcp_title',
      'pcp_display_in_roll',
      'pcp_roll_nickname',
      'pcp_personal_note',
    );

    $query = '
    SELECT ccs.id, pcp_id, cpcp.title as pcp_title, pcp_display_in_roll, pcp_roll_nickname, pcp_personal_note, ccs.currency as currency, amount, ccs.contact_id as contact_id, c.display_name, ccs.soft_credit_type_id
    FROM civicrm_contribution_soft ccs INNER JOIN civicrm_contact c on c.id = ccs.contact_id
    LEFT JOIN civicrm_pcp cpcp ON ccs.pcp_id = cpcp.id
    WHERE contribution_id = %1;
    ';

    $params = array(1 => array($contributionID, 'Integer'));

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $softContribution = array();
    $count = 1;
    while ($dao->fetch()) {
      if ($dao->pcp_id) {
        if ($all) {
          foreach ($pcpFields as $val) {
            $softContribution[$val] = $dao->$val;
          }
          $softContribution['pcp_soft_credit_to_name'] = $dao->display_name;
          $softContribution['pcp_soft_credit_to_id'] = $dao->contact_id;
        }
      }
      else {
        $softContribution['soft_credit'][$count] = array(
          'contact_id' => $dao->contact_id,
          'soft_credit_id' => $dao->id,
          'currency' => $dao->currency,
          'amount' => $dao->amount,
          'contact_name' => $dao->display_name,
          'soft_credit_type' => $dao->soft_credit_type_id,
          'soft_credit_type_label' => CRM_Core_OptionGroup::getLabel('soft_credit_type', $dao->soft_credit_type_id)
        );
        $count++;
      }
    }

    return $softContribution;
  }

  /**
   * @param $contributionID
   * @param bool $isPCP
   *
   * @return array
   */
  static function getSoftCreditIds($contributionID , $isPCP = FALSE) {
    $query = "
  SELECT id
  FROM  civicrm_contribution_soft
  WHERE contribution_id = %1
  ";

    if ($isPCP) {
      $query .= " AND pcp_id IS NOT NULL";
    }
    else {
      $query .= " AND pcp_id IS NULL";
    }
    $params = array(1 => array($contributionID, 'Integer'));

    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $id = array();
    $type = '';
    while ($dao->fetch()) {
      if ($isPCP) {
        return $dao->id;
      }
      $id[] = $dao->id;
    }
    return $id;
  }

  /**
   *  Function to retrieve the list of soft contributions for given contact.
   *
   * @param int     $contact_id contact id
   * @param int     $isTest
   * @param string  $filter  additional filter criteria, later used in where clause
   *
   * @return array
   * @static
   */
  static function getSoftContributionList($contact_id, $filter = NULL, $isTest = 0) {
    $query = '
    SELECT ccs.id, ccs.amount as amount,
           ccs.contribution_id,
           ccs.pcp_id,
           ccs.pcp_display_in_roll,
           ccs.pcp_roll_nickname,
           ccs.pcp_personal_note,
           ccs.soft_credit_type_id,
           cc.receive_date,
           cc.contact_id as contributor_id,
           cc.contribution_status_id as contribution_status_id,
           cp.title as pcp_title,
           cc.currency,
           contact.display_name,
           cct.name as contributionType
    FROM civicrm_contribution_soft ccs
      LEFT JOIN civicrm_contribution cc
            ON ccs.contribution_id = cc.id
      LEFT JOIN civicrm_pcp cp
            ON ccs.pcp_id = cp.id
      LEFT JOIN civicrm_contact contact ON
      ccs.contribution_id = cc.id AND cc.contact_id = contact.id
      LEFT JOIN civicrm_financial_type cct ON cc.financial_type_id = cct.id
    ';

    $where = "
      WHERE cc.is_test = %2 AND ccs.contact_id = %1";
    if ($filter) {
      $where .= $filter;
    }

    $query .= "{$where} ORDER BY cc.receive_date DESC";

    $params = array(
      1 => array($contact_id, 'Integer'),
      2 => array($isTest, 'Integer')
    );
    $cs = CRM_Core_DAO::executeQuery($query, $params);
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    $result = array();
    while ($cs->fetch()) {
      $result[$cs->id]['amount'] = $cs->amount;
      $result[$cs->id]['currency'] = $cs->currency;
      $result[$cs->id]['contributor_id'] = $cs->contributor_id;
      $result[$cs->id]['contribution_id'] = $cs->contribution_id;
      $result[$cs->id]['contributor_name'] = $cs->display_name;
      $result[$cs->id]['financial_type'] = $cs->contributionType;
      $result[$cs->id]['receive_date'] = $cs->receive_date;
      $result[$cs->id]['pcp_id'] = $cs->pcp_id;
      $result[$cs->id]['pcp_title'] = $cs->pcp_title;
      $result[$cs->id]['pcp_display_in_roll'] = $cs->pcp_display_in_roll;
      $result[$cs->id]['pcp_roll_nickname'] = $cs->pcp_roll_nickname;
      $result[$cs->id]['pcp_personal_note'] = $cs->pcp_personal_note;
      $result[$cs->id]['contribution_status'] = CRM_Utils_Array::value($cs->contribution_status_id, $contributionStatus);
      $result[$cs->id]['sct_label'] = CRM_Core_OptionGroup::getLabel('soft_credit_type', $cs->soft_credit_type_id);

      if ($isTest) {
        $result[$cs->id]['contribution_status'] = $result[$cs->id]['contribution_status'] . '<br /> (test)';
      }
    }
    return $result;
  }

  /*
   *  Function to assign honor profile fields to template/form, if $honorId (as soft-credit's contact_id)
   *  is passed  then  whole honoreeprofile fields with title/value assoc array assigned or only honoreeName
   *  is assigned
   *
   *  @static
   */

  /**
   * @param $form
   * @param $params
   * @param $honoreeprofileId
   * @param null $honorId
   */
  static function formatHonoreeProfileFields($form, $params, $honoreeprofileId, $honorId = NULL) {
    $profileContactType = CRM_Core_BAO_UFGroup::getContactType($honoreeprofileId);
    $profileFields = CRM_Core_BAO_UFGroup::getFields($honoreeprofileId);
    $honoreeProfileFields = $values = array();
    $honorName = NULL;

    if ($honorId) {
      CRM_Core_BAO_UFGroup::getValues($honorId, $profileFields, $values, FALSE, $params);
      if (empty($params)) {
        foreach ($profileFields as $name => $field) {
          $title = $field['title'];
          $params[$field['name']] = $values[$title];
        }
      }
    }

    //remove name related fields and construct name string with prefix/suffix
    //which will be later assigned to template
    switch ($profileContactType) {
      case 'Individual':
        if (array_key_exists('prefix_id', $params)) {
          $honorName = CRM_Utils_Array::value(CRM_Utils_Array::value('prefix_id', $params),
            CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'prefix_id')
          );
          unset($profileFields['prefix_id']);
        }
          $honorName .= ' ' . $params['first_name'] . ' ' . $params['last_name'];
          unset($profileFields['first_name']);
          unset($profileFields['last_name']);
          if (array_key_exists('suffix_id', $params)) {
            $honorName .= ' ' . CRM_Utils_Array::value(CRM_Utils_Array::value('suffix_id', $params),
              CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'suffix_id')
            );
            unset($profileFields['suffix_id']);
          }
          break;
        case 'Organization':
          $honorName = $params['organization_name'];
          unset($profileFields['organization_name']);
          break;
        case 'Household':
          $honorName = $params['household_name'];
          unset($profileFields['household_name']);
          break;
    }

    if ($honorId) {
      $honoreeProfileFields['Name'] = $honorName;
      foreach ($profileFields as $name => $field) {
        $title = $field['title'];
        $honoreeProfileFields[$title] = $values[$title];
      }
      $form->assign('honoreeProfile', $honoreeProfileFields);
    }
    else {
      $form->assign('honorName', $honorName);
    }
  }
}

