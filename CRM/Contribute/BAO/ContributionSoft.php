<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'ContributionSoft', CRM_Utils_Array::value('id', $params), $params);

    $contributionSoft = new CRM_Contribute_DAO_ContributionSoft();
    $contributionSoft->copyValues($params);

    // set currency for CRM-1496
    if (!isset($contributionSoft->currency)) {
      $config = CRM_Core_Config::singleton();
      $contributionSoft->currency = $config->defaultCurrency;
    }
    $result = $contributionSoft->save();
    CRM_Utils_Hook::post($hook, 'ContributionSoft', $contributionSoft->id, $contributionSoft);
    return $result;
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
      $softParams = [];
      $softParams['id'] = $pcpId ? $pcpId : NULL;
      $softParams['contribution_id'] = $contribution->id;
      $softParams['pcp_id'] = $pcp['pcp_made_through_id'];
      $softParams['contact_id'] = CRM_Core_DAO::getFieldValue('CRM_PCP_DAO_PCP',
        $pcp['pcp_made_through_id'], 'contact_id'
      );
      $softParams['currency'] = $contribution->currency;
      $softParams['amount'] = $contribution->total_amount;
      $softParams['pcp_display_in_roll'] = $pcp['pcp_display_in_roll'] ?? NULL;
      $softParams['pcp_roll_nickname'] = $pcp['pcp_roll_nickname'] ?? NULL;
      $softParams['pcp_personal_note'] = $pcp['pcp_personal_note'] ?? NULL;
      $softParams['soft_credit_type_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', 'pcp');
      $contributionSoft = self::add($softParams);
      //Send notification to owner for PCP
      if ($contributionSoft->pcp_id && empty($pcpId)) {
        CRM_Contribute_Form_Contribution_Confirm::pcpNotifyOwner($contribution, $contributionSoft);
      }
    }
    //Delete PCP against this contribution and create new on submitted PCP information
    elseif (array_key_exists('pcp', $params) && $pcpId) {
      civicrm_api3('ContributionSoft', 'delete', ['id' => $pcpId]);
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
          civicrm_api3('ContributionSoft', 'delete', ['id' => $softID]);
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
    $pcp = $softParams = $softIDs = [];
    if (!empty($params['pcp_made_through_id'])) {
      $fields = [
        'pcp_made_through_id',
        'pcp_display_in_roll',
        'pcp_roll_nickname',
        'pcp_personal_note',
      ];
      foreach ($fields as $f) {
        $pcp[$f] = $params[$f] ?? NULL;
      }
    }

    if (!empty($form->_values['honoree_profile_id']) && !empty($params['soft_credit_type_id'])) {
      $honorId = NULL;

      // @todo fix use of deprecated function.
      $contributionSoftParams['soft_credit_type_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', 'pcp');
      //check if there is any duplicate contact
      // honoree should never be the donor
      $exceptKeys = [
        'contactID' => 0,
        'onbehalf_contact_id' => 0,
      ];
      $except = array_values(array_intersect_key($params, $exceptKeys));
      $ids = CRM_Contact_BAO_Contact::getDuplicateContacts(
        $params['honor'],
        CRM_Core_BAO_UFGroup::getContactType($form->_values['honoree_profile_id']),
        'Unsupervised',
        $except,
        FALSE
      );
      if (count($ids)) {
        $honorId = $ids[0] ?? NULL;
      }

      $null = [];
      $honorId = CRM_Contact_BAO_Contact::createProfileContact(
        $params['honor'], $null,
        $honorId, NULL,
        $form->_values['honoree_profile_id']
      );
      $softParams[] = [
        'contact_id' => $honorId,
        'soft_credit_type_id' => $params['soft_credit_type_id'],
      ];

      if (!empty($form->_values['is_email_receipt'])) {
        $form->_values['honor'] = [
          'soft_credit_type' => CRM_Utils_Array::value(
            $params['soft_credit_type_id'],
            CRM_Core_OptionGroup::values("soft_credit_type")
          ),
          'honor_id' => $honorId,
          'honor_profile_id' => $form->_values['honoree_profile_id'],
          'honor_profile_values' => $params['honor'],
        ];
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

    $params = [
      1 => [$contact_id, 'Integer'],
      2 => [$isTest, 'Integer'],
    ];

    $cs = CRM_Core_DAO::executeQuery($query, $params);

    $count = $countCancelled = 0;
    $amount = $average = $cancelAmount = [];

    while ($cs->fetch()) {
      if ($cs->amount > 0) {
        $count++;
        $amount[] = CRM_Utils_Money::format($cs->amount, $cs->currency);
        $average[] = CRM_Utils_Money::format($cs->average, $cs->currency);
      }
    }

    //to get cancel amount
    $cancelAmountWhereClause = "AND cc.cancel_date IS NOT NULL";
    $query = str_replace($whereClause, $cancelAmountWhereClause, $query);
    $cancelAmountSQL = CRM_Core_DAO::executeQuery($query, $params);
    while ($cancelAmountSQL->fetch()) {
      if ($cancelAmountSQL->amount > 0) {
        $countCancelled++;
        $cancelAmount[] = CRM_Utils_Money::format($cancelAmountSQL->amount, $cancelAmountSQL->currency);
      }
    }

    if ($count > 0 || $countCancelled > 0) {
      return [
        $count,
        $countCancelled,
        implode(',&nbsp;', $amount),
        implode(',&nbsp;', $average),
        implode(',&nbsp;', $cancelAmount),
      ];
    }
    return [0, 0];
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
    $softContributionFields = self::getSoftCreditContributionFields([$contributionID], $all);
    return $softContributionFields[$contributionID] ?? [];
  }

  /**
   * Retrieve soft contributions for an array of contribution records.
   *
   * @param array $contributionIDs
   * @param bool $all
   *   Include PCP data.
   *
   * @return array
   *   Array of soft contribution ids, amounts, and associated contact ids
   */
  public static function getSoftCreditContributionFields($contributionIDs, $all = FALSE) {
    $pcpFields = [
      'pcp_id',
      'pcp_title',
      'pcp_display_in_roll',
      'pcp_roll_nickname',
      'pcp_personal_note',
    ];

    $query = "
    SELECT ccs.id, pcp_id, ccs.contribution_id as contribution_id, cpcp.title as pcp_title, pcp_display_in_roll, pcp_roll_nickname, pcp_personal_note, ccs.currency as currency, amount, ccs.contact_id as contact_id, c.display_name, ccs.soft_credit_type_id
    FROM civicrm_contribution_soft ccs INNER JOIN civicrm_contact c on c.id = ccs.contact_id
    LEFT JOIN civicrm_pcp cpcp ON ccs.pcp_id = cpcp.id
    WHERE contribution_id IN (%1)
    ";
    $queryParams = [1 => [implode(',', $contributionIDs), 'CommaSeparatedIntegers']];
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);

    $softContribution = $indexes = [];
    while ($dao->fetch()) {
      if ($dao->pcp_id) {
        if ($all) {
          foreach ($pcpFields as $val) {
            $softContribution[$dao->contribution_id][$val] = $dao->$val;
          }
          $softContribution[$dao->contribution_id]['pcp_soft_credit_to_name'] = $dao->display_name;
          $softContribution[$dao->contribution_id]['pcp_soft_credit_to_id'] = $dao->contact_id;
        }
      }
      else {
        // Use a 1-based array because that's what this function returned before refactoring in https://github.com/civicrm/civicrm-core/pull/14747
        $indexes[$dao->contribution_id] = isset($indexes[$dao->contribution_id]) ? $indexes[$dao->contribution_id] + 1 : 1;
        $softContribution[$dao->contribution_id]['soft_credit'][$indexes[$dao->contribution_id]] = [
          'contact_id' => $dao->contact_id,
          'soft_credit_id' => $dao->id,
          'currency' => $dao->currency,
          'amount' => $dao->amount,
          'contact_name' => $dao->display_name,
          'soft_credit_type' => $dao->soft_credit_type_id,
          'soft_credit_type_label' => CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', $dao->soft_credit_type_id),
        ];
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
    $params = [1 => [$contributionID, 'Integer']];

    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $id = [];
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
    $params['sort'] = $params['sortBy'] ?? NULL;
    $contactId = $params['cid'];

    $filter = NULL;
    if ($params['context'] == 'membership' && !empty($params['entityID']) && $contactId) {
      $filter = " AND cc.id IN (SELECT contribution_id FROM civicrm_membership_payment WHERE membership_id = {$params['entityID']})";
    }

    $softCreditList = self::getSoftContributionList($contactId, $filter, $isTest, $params);

    $softCreditListDT = [];
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
    $links = [
      CRM_Core_Action::VIEW => [
        'name' => ts('View'),
        'url' => 'civicrm/contact/view/contribution',
        'qs' => 'reset=1&id=%%contributionid%%&cid=%%contactId%%&action=view&context=contribution&selectedChild=contribute',
        'title' => ts('View related contribution'),
      ],
    ];
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

    $params = [
      1 => [$contact_id, 'Integer'],
      2 => [$isTest, 'Integer'],
      3 => [$softOgId, 'Integer'],
      4 => [$statusOgId, 'Integer'],
    ];
    $cs = CRM_Core_DAO::executeQuery($query, $params);

    $dTParams['total'] = CRM_Core_DAO::singleValueQuery('SELECT FOUND_ROWS()');
    $result = [];
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
      $replace = [
        'contributionid' => $cs->contribution_id,
        'contactId' => $cs->contributor_id,
      ];
      $result[$cs->id]['links'] = CRM_Core_Action::formLink($links, NULL, $replace);

      if ($isTest) {
        $result[$cs->id]['contribution_status'] = CRM_Core_TestEntity::appendTestText($result[$cs->id]['contribution_status']);
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
    $honoreeProfileFields = $values = [];
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
          $honorName = CRM_Utils_Array::value($params['prefix_id'],
            CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'prefix_id')
          );
          unset($profileFields['prefix_id']);
        }
        $honorName .= ' ' . $params['first_name'] . ' ' . $params['last_name'];
        unset($profileFields['first_name']);
        unset($profileFields['last_name']);
        if (array_key_exists('suffix_id', $params)) {
          $honorName .= ' ' . CRM_Utils_Array::value($params['suffix_id'],
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
