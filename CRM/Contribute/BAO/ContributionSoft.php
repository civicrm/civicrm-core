<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Contribute_BAO_ContributionSoft extends CRM_Contribute_DAO_ContributionSoft {

  /**
   * Construct method.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Add contribution soft credit record.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return object
   *   soft contribution of object that is added
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
   * Process the soft contribution and/or link to personal campaign page.
   *
   * @param array $params
   * @param object $contribution CRM_Contribute_DAO_Contribution
   *
   */
  public static function processSoftContribution($params, $contribution) {
    //retrieve existing soft-credit and pcp id(s) if any against $contribution
    $softIDs = self::getSoftCreditIds($contribution->id);
    $pcpId = self::getSoftCreditIds($contribution->id, TRUE);

    if ($pcp = CRM_Utils_Array::value('pcp', $params)) {
      $softParams = array();
      $softParams['id'] = $pcpId ? $pcpId : NULL;
      $softParams['contribution_id'] = $contribution->id;
      $softParams['pcp_id'] = $pcp['pcp_made_through_id'];
      $softParams['contact_id'] = CRM_Core_DAO::getFieldValue('CRM_PCP_DAO_PCP',
        $pcp['pcp_made_through_id'], 'contact_id'
      );
      $softParams['currency'] = $contribution->currency;
      $softParams['amount'] = $contribution->total_amount;
      $softParams['pcp_display_in_roll'] = CRM_Utils_Array::value('pcp_display_in_roll', $pcp);
      $softParams['pcp_roll_nickname'] = CRM_Utils_Array::value('pcp_roll_nickname', $pcp);
      $softParams['pcp_personal_note'] = CRM_Utils_Array::value('pcp_personal_note', $pcp);
      $softParams['soft_credit_type_id'] = CRM_Core_OptionGroup::getValue('soft_credit_type', 'pcp', 'name');
      $contributionSoft = self::add($softParams);
      //Send notification to owner for PCP
      if ($contributionSoft->pcp_id && empty($pcpId)) {
        CRM_Contribute_Form_Contribution_Confirm::pcpNotifyOwner($contribution, $contributionSoft);
      }
    }
    //Delete PCP against this contribution and create new on submitted PCP information
    elseif (array_key_exists('pcp', $params) && $pcpId) {
      $deleteParams = array('id' => $pcpId);
      CRM_Contribute_BAO_ContributionSoft::del($deleteParams);
    }
    if (isset($params['soft_credit'])) {
      $softParams = $params['soft_credit'];
      foreach ($softParams as $softParam) {
        if (!empty($softIDs)) {
          $key = key($softIDs);
          $softParam['id'] = $softIDs[$key];
          unset($softIDs[$key]);
        }
        $softParam['contribution_id'] = $contribution->id;
        $softParam['currency'] = $contribution->currency;
        //case during Contribution Import when we assign soft contribution amount as contribution's total_amount by default
        if (empty($softParam['amount'])) {
          $softParam['amount'] = $contribution->total_amount;
        }
        CRM_Contribute_BAO_ContributionSoft::add($softParam);
      }

      // delete any extra soft-credit while updating back-office contribution
      foreach ((array) $softIDs as $softID) {
        if (!in_array($softID, $params['soft_credit_ids'])) {
          $deleteParams = array('id' => $softID);
          CRM_Contribute_BAO_ContributionSoft::del($deleteParams);
        }
      }
    }
  }

  /**
   * Function used to save pcp / soft credit entry.
   *
   * This is used by contribution and also event pcps
   *
   * @param array $params
   * @param object $form
   *   Form object.
   */
  public static function formatSoftCreditParams(&$params, &$form) {
    $pcp = $softParams = $softIDs = array();
    if (!empty($params['pcp_made_through_id'])) {
      $fields = array(
        'pcp_made_through_id',
        'pcp_display_in_roll',
        'pcp_roll_nickname',
        'pcp_personal_note',
      );
      foreach ($fields as $f) {
        $pcp[$f] = CRM_Utils_Array::value($f, $params);
      }
    }

    if (!empty($form->_values['honoree_profile_id']) && !empty($params['soft_credit_type_id'])) {
      $honorId = NULL;

      $contributionSoftParams['soft_credit_type_id'] = CRM_Core_OptionGroup::getValue('soft_credit_type', 'pcp', 'name');
      //check if there is any duplicate contact
      $profileContactType = CRM_Core_BAO_UFGroup::getContactType($form->_values['honoree_profile_id']);
      $dedupeParams = CRM_Dedupe_Finder::formatParams($params['honor'], $profileContactType);
      $dedupeParams['check_permission'] = FALSE;
      $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, $profileContactType);
      if (count($ids)) {
        $honorId = CRM_Utils_Array::value(0, $ids);
      }

      $honorId = CRM_Contact_BAO_Contact::createProfileContact(
        $params['honor'], CRM_Core_DAO::$_nullArray,
        $honorId, NULL,
        $form->_values['honoree_profile_id']
      );
      $softParams[] = array(
        'contact_id' => $honorId,
        'soft_credit_type_id' => $params['soft_credit_type_id'],
      );

      if (CRM_Utils_Array::value('is_email_receipt', $form->_values)) {
        $form->_values['honor'] = array(
          'soft_credit_type' => CRM_Utils_Array::value(
            $params['soft_credit_type_id'],
            CRM_Core_OptionGroup::values("soft_credit_type")
          ),
          'honor_id' => $honorId,
          'honor_profile_id' => $form->_values['honoree_profile_id'],
          'honor_profile_values' => $params['honor'],
        );
      }
    }
    elseif (!empty($params['soft_credit_contact_id'])) {
      //build soft credit params
      foreach ($params['soft_credit_contact_id'] as $key => $val) {
        if ($val && $params['soft_credit_amount'][$key]) {
          $softParams[$key]['contact_id'] = $val;
          $softParams[$key]['amount'] = CRM_Utils_Rule::cleanMoney($params['soft_credit_amount'][$key]);
          $softParams[$key]['soft_credit_type_id'] = $params['soft_credit_type'][$key];
          if (!empty($params['soft_credit_id'][$key])) {
            $softIDs[] = $softParams[$key]['id'] = $params['soft_credit_id'][$key];
          }
        }
      }
    }

    $params['pcp'] = !empty($pcp) ? $pcp : NULL;
    $params['soft_credit'] = $softParams;
    $params['soft_credit_ids'] = $softIDs;
  }

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Contribute_BAO_ContributionSoft
   */
  public static function retrieve(&$params, &$defaults) {
    $contributionSoft = new CRM_Contribute_DAO_ContributionSoft();
    $contributionSoft->copyValues($params);
    if ($contributionSoft->find(TRUE)) {
      CRM_Core_DAO::storeValues($contributionSoft, $defaults);
      return $contributionSoft;
    }
    return NULL;
  }

  /**
   * Delete soft credits.
   *
   * @param array $params
   *
   */
  public static function del($params) {
    //delete from contribution soft table
    $contributionSoft = new CRM_Contribute_DAO_ContributionSoft();
    foreach ($params as $column => $value) {
      $contributionSoft->$column = $value;
    }
    $contributionSoft->delete();
  }

  /**
   * @param int $contact_id
   * @param int $isTest
   *
   * @return array
   */
  public static function getSoftContributionTotals($contact_id, $isTest = 0) {

    $whereClause = "AND cc.cancel_date IS NULL";

    $query = "
    SELECT SUM(amount) as amount, AVG(total_amount) as average, cc.currency
    FROM civicrm_contribution_soft  ccs
      LEFT JOIN civicrm_contribution cc ON ccs.contribution_id = cc.id
    WHERE cc.is_test = %2 AND ccs.contact_id = %1 {$whereClause}
    GROUP BY currency";

    $params = array(
      1 => array($contact_id, 'Integer'),
      2 => array($isTest, 'Integer'),
    );

    $cs = CRM_Core_DAO::executeQuery($query, $params);

    $count = 0;
    $amount = $average = $cancelAmount = array();

    while ($cs->fetch()) {
      if ($cs->amount > 0) {
        $count++;
        $amount[] = $cs->amount;
        $average[] = $cs->average;
        $currency[] = $cs->currency;
      }
    }

    //to get cancel amount
    $cancelAmountWhereClause = "AND cc.cancel_date IS NOT NULL";
    $query = str_replace($whereClause, $cancelAmountWhereClause, $query);
    $cancelAmountSQL  = CRM_Core_DAO::executeQuery($query, $params);
    while ($cancelAmountSQL->fetch()) {
      if ($cancelAmountSQL->amount > 0) {
        $count++;
        $cancelAmount[] = $cancelAmountSQL->amount;
      }
    }

    if ($count > 0) {
      return array(
        implode(',&nbsp;', $amount),
        implode(',&nbsp;', $average),
        implode(',&nbsp;', $currency),
        implode(',&nbsp;', $cancelAmount),
      );
    }
    return array(0, 0);
  }

  /**
   * Retrieve soft contributions for contribution record.
   *
   * @param int $contributionID
   * @param bool $all
   *   Include PCP data.
   *
   * @return array
   *   Array of soft contribution ids, amounts, and associated contact ids
   */
  public static function getSoftContribution($contributionID, $all = FALSE) {
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
          'soft_credit_type_label' => CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', $dao->soft_credit_type_id),
        );
        $count++;
      }
    }

    return $softContribution;
  }

  /**
   * @param int $contributionID
   * @param bool $isPCP
   *
   * @return array
   */
  public static function getSoftCreditIds($contributionID, $isPCP = FALSE) {
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
   * Wrapper for ajax soft contribution selector.
   *
   * @param array $params
   *   Associated array for params.
   *
   * @return array
   *   Associated array of soft contributions
   */
  public static function getSoftContributionSelector($params) {
    $isTest = 0;
    if (!empty($params['isTest'])) {
      $isTest = $params['isTest'];
    }
    // Format the params.
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];
    $params['sort'] = CRM_Utils_Array::value('sortBy', $params);
    $contactId = $params['cid'];

    $filter = NULL;
    if ($params['context'] == 'membership' && !empty($params['entityID']) && $contactId) {
      $filter = " AND cc.id IN (SELECT contribution_id FROM civicrm_membership_payment WHERE membership_id = {$params['entityID']})";
    }

    $softCreditList = self::getSoftContributionList($contactId, $filter, $isTest, $params);

    $softCreditListDT = array();
    $softCreditListDT['data'] = array_values($softCreditList);
    $softCreditListDT['recordsTotal'] = $params['total'];
    $softCreditListDT['recordsFiltered'] = $params['total'];

    return $softCreditListDT;
  }

  /**
   *  Function to retrieve the list of soft contributions for given contact.
   *
   * @param int $contact_id
   *   Contact id.
   * @param string $filter
   * @param int $isTest
   *   Additional filter criteria, later used in where clause.
   * @param array $dTParams
   *
   * @return array
   */
  public static function getSoftContributionList($contact_id, $filter = NULL, $isTest = 0, &$dTParams = NULL) {
    $config = CRM_Core_Config::singleton();
    $links = array(
      CRM_Core_Action::VIEW => array(
        'name' => ts('View'),
        'url' => 'civicrm/contact/view/contribution',
        'qs' => 'reset=1&id=%%contributionid%%&cid=%%contactId%%&action=view&context=contribution&selectedChild=contribute',
        'title' => ts('View related contribution'),
      ),
    );
    $orderBy = 'cc.receive_date DESC';
    if (!empty($dTParams['sort'])) {
      $orderBy = $dTParams['sort'];
    }
    $limit = '';
    if (!empty($dTParams['rowCount']) && $dTParams['rowCount'] > 0) {
      $limit = " LIMIT {$dTParams['offset']}, {$dTParams['rowCount']} ";
    }
    $softOgId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'soft_credit_type', 'id', 'name');
    $statusOgId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'contribution_status', 'id', 'name');

    $query = '
    SELECT SQL_CALC_FOUND_ROWS ccs.id, ccs.amount as amount,
           ccs.contribution_id,
           ccs.pcp_id,
           ccs.pcp_display_in_roll,
           ccs.pcp_roll_nickname,
           ccs.pcp_personal_note,
           ccs.soft_credit_type_id,
           sov.label as sct_label,
           cc.receive_date,
           cc.contact_id as contributor_id,
           cc.contribution_status_id as contribution_status_id,
           cov.label as contribution_status,
           cp.title as pcp_title,
           cc.currency,
           contact.display_name as contributor_name,
           cct.name as financial_type
    FROM civicrm_contribution_soft ccs
      LEFT JOIN civicrm_contribution cc
            ON ccs.contribution_id = cc.id
      LEFT JOIN civicrm_pcp cp
            ON ccs.pcp_id = cp.id
      LEFT JOIN civicrm_contact contact ON
      ccs.contribution_id = cc.id AND cc.contact_id = contact.id
      LEFT JOIN civicrm_financial_type cct ON cc.financial_type_id = cct.id
      LEFT JOIN civicrm_option_value sov ON sov.option_group_id = %3 AND ccs.soft_credit_type_id = sov.value
      LEFT JOIN civicrm_option_value cov ON cov.option_group_id = %4 AND cc.contribution_status_id = cov.value
    ';

    $where = "
      WHERE cc.is_test = %2 AND ccs.contact_id = %1";
    if ($filter) {
      $where .= $filter;
    }

    $query .= "{$where} ORDER BY {$orderBy} {$limit}";

    $params = array(
      1 => array($contact_id, 'Integer'),
      2 => array($isTest, 'Integer'),
      3 => array($softOgId, 'Integer'),
      4 => array($statusOgId, 'Integer'),
    );
    $cs = CRM_Core_DAO::executeQuery($query, $params);

    $dTParams['total'] = CRM_Core_DAO::singleValueQuery('SELECT FOUND_ROWS()');
    $result = array();
    while ($cs->fetch()) {
      $result[$cs->id]['amount'] = CRM_Utils_Money::format($cs->amount, $cs->currency);
      $result[$cs->id]['currency'] = $cs->currency;
      $result[$cs->id]['contributor_id'] = $cs->contributor_id;
      $result[$cs->id]['contribution_id'] = $cs->contribution_id;
      $result[$cs->id]['contributor_name'] = CRM_Utils_System::href(
        $cs->contributor_name,
        'civicrm/contact/view',
        "reset=1&cid={$cs->contributor_id}"
      );
      $result[$cs->id]['financial_type'] = $cs->financial_type;
      $result[$cs->id]['receive_date'] = CRM_Utils_Date::customFormat($cs->receive_date, $config->dateformatDatetime);
      $result[$cs->id]['pcp_id'] = $cs->pcp_id;
      $result[$cs->id]['pcp_title'] = ($cs->pcp_title) ? $cs->pcp_title : 'n/a';
      $result[$cs->id]['pcp_display_in_roll'] = $cs->pcp_display_in_roll;
      $result[$cs->id]['pcp_roll_nickname'] = $cs->pcp_roll_nickname;
      $result[$cs->id]['pcp_personal_note'] = $cs->pcp_personal_note;
      $result[$cs->id]['contribution_status'] = $cs->contribution_status;
      $result[$cs->id]['sct_label'] = $cs->sct_label;
      $replace = array(
        'contributionid' => $cs->contribution_id,
        'contactId' => $cs->contributor_id,
      );
      $result[$cs->id]['links'] = CRM_Core_Action::formLink($links, NULL, $replace);

      if ($isTest) {
        $result[$cs->id]['contribution_status'] = $result[$cs->id]['contribution_status'] . '<br /> (test)';
      }
    }
    return $result;
  }

  /**
   * Function to assign honor profile fields to template/form, if $honorId (as soft-credit's contact_id)
   * is passed  then  whole honoreeprofile fields with title/value assoc array assigned or only honoreeName
   * is assigned
   *
   * @param CRM_Core_Form $form
   * @param array $params
   * @param int $honorId
   */
  public static function formatHonoreeProfileFields($form, $params, $honorId = NULL) {
    if (empty($form->_values['honoree_profile_id'])) {
      return;
    }
    $profileContactType = CRM_Core_BAO_UFGroup::getContactType($form->_values['honoree_profile_id']);
    $profileFields = CRM_Core_BAO_UFGroup::getFields($form->_values['honoree_profile_id']);
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
