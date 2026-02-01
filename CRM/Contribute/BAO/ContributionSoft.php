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

use Civi\Api4\Contribution;
use Civi\Api4\ContributionSoft;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contribute_BAO_ContributionSoft extends CRM_Contribute_DAO_ContributionSoft {

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
    CRM_Utils_Hook::pre($hook, 'ContributionSoft', $params['id'] ?? NULL, $params);

    $contributionSoft = new CRM_Contribute_DAO_ContributionSoft();
    $contributionSoft->copyValues($params);

    // set currency for CRM-1496
    if (!isset($contributionSoft->currency)) {
      $config = CRM_Core_Config::singleton();
      $contributionSoft->currency = $config->defaultCurrency;
    }
    $result = $contributionSoft->save();
    CRM_Utils_Hook::post($hook, 'ContributionSoft', $contributionSoft->id, $contributionSoft, $params);
    return $result;
  }

  /**
   * Process the soft contribution and/or link to personal campaign page.
   *
   * @internal
   *
   * @param array $params
   * @param CRM_Contribute_BAO_Contribution $contribution
   *
   * @throws \CRM_Core_Exception
   */
  public static function processSoftContribution($params, $contribution) {
    if (array_key_exists('pcp', $params)) {
      self::processPCP($params['pcp'], $contribution);
    }
    if (isset($params['soft_credit'])) {
      $softIDs = self::getSoftCreditIds($contribution->id);
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
   *
   * @deprecated since 6.10 will be removed around 6.22
   */
  public static function formatSoftCreditParams(&$params, &$form) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
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
   * Retrieve DB object and copy to defaults array.
   *
   * @param array $params
   *   Array of criteria values.
   * @param array $defaults
   *   Array to be populated with found values.
   *
   * @return self|null
   *   The DAO object, if found.
   *
   * @deprecated
   */
  public static function retrieve($params, &$defaults) {
    CRM_Core_Error::deprecatedFunctionWarning('apiv4');
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * @param int $contact_id
   * @param int $isTest
   *
   * @return array
   */
  public static function getSoftContributionTotals($contact_id, $isTest = 0) {

    $contributionSofts = ContributionSoft::get()
      ->addSelect('currency', 'SUM(amount) AS SUM_amount', 'AVG(amount) AS AVG_amount', 'COUNT(id) AS COUNT_id')
      ->setGroupBy([
        'currency',
      ])
      ->addWhere('contact_id', '=', $contact_id)
      ->addWhere('contribution_id.is_test', '=', $isTest);

    $contributionSoftsNoCancel = $contributionSofts->addWhere('contribution_id.cancel_date', 'IS NULL')->execute();
    $contributionSoftsYesCancel = $contributionSofts->addWhere('contribution_id.cancel_date', 'IS NOT NULL')->execute();

    $count = $countCancelled = 0;
    $amount = $average = $cancelAmount = [];

    foreach ($contributionSoftsNoCancel as $csByCurrency) {
      $count += $csByCurrency['COUNT_id'];
      $amount[] = CRM_Utils_Money::format($csByCurrency['SUM_amount'], $csByCurrency['currency']);
      $average[] = CRM_Utils_Money::format($csByCurrency['AVG_amount'], $csByCurrency['currency']);
    }

    //to get cancel amount
    foreach ($contributionSoftsYesCancel as $csByCurrency) {
      $countCancelled += $csByCurrency['COUNT_id'];
      $amount[] = CRM_Utils_Money::format($csByCurrency['SUM_amount'], $csByCurrency['currency']);
    }

    if ($contributionSoftsNoCancel->rowCount || $contributionSoftsYesCancel->rowCount) {
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
    FROM civicrm_contribution_soft ccs
      INNER JOIN civicrm_contact c on c.id = ccs.contact_id
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
   *
   * @throws \CRM_Core_Exception
   */
  public static function getSoftContributionSelector($params): array {
    $isTest = 0;
    if (!empty($params['isTest'])) {
      $isTest = $params['isTest'];
    }
    // Format the params.
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];
    $params['sort'] = $params['sortBy'] ?? NULL;
    $contactId = $params['cid'];

    $softCreditList = self::getSoftContributionList($contactId, $params['entityID'] ?? NULL, $isTest, $params);

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
   * @param ?int $membershipID
   * @param int $isTest
   *   Additional filter criteria, later used in where clause.
   * @param null $dTParams
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public static function getSoftContributionList($contact_id, ?int $membershipID = NULL, $isTest = 0, &$dTParams = NULL): array {
    // This is necessary for dataTables sorting.
    $dataTableMapping = [
      'sct_label' => 'soft_credit_type_id:label',
      'contributor_name' => 'contact_id.sort_name',
      'financial_type' => 'contribution_id.financial_type_id:label',
      'contribution_status' => 'contribution_id.contribution_status_id:label',
      'receive_date' => 'contribution_id.receive_date',
      'pcp_title' => 'pcp_id.title',
      'amount' => 'amount',
    ];
    $config = CRM_Core_Config::singleton();
    $links = [
      CRM_Core_Action::VIEW => [
        'name' => ts('View'),
        'url' => 'civicrm/contact/view/contribution',
        'qs' => 'reset=1&id=%%contributionid%%&cid=%%contactId%%&action=view&context=contribution&selectedChild=contribute',
        'title' => ts('View related contribution'),
        'weight' => -20,
      ],
    ];

    $contributionSofts = ContributionSoft::get()
      ->addSelect('*', 'contribution_id.receive_date', 'contribution_id.contact_id', 'contribution_id.contact_id.display_name', 'soft_credit_type_id:label', 'contribution_id.contribution_status_id:label', 'contribution_id.financial_type_id:label', 'pcp_id.title', 'row_count')
      ->addWhere('contact_id', '=', $contact_id)
      ->addWhere('contribution_id.is_test', '=', $isTest);

    if ($membershipID) {
      $contributionSofts->addJoin('LineItem', 'INNER', NULL,
        ['lineitem.contribution_id', '=', 'contribution_id'],
        ['lineitem.entity_id', '=', $membershipID],
        ['lineitem.entity_table', '=', '"civicrm_membership"']
      );
    }

    if (!empty($dTParams['rowCount']) && $dTParams['rowCount'] > 0) {
      $contributionSofts
        ->setLimit($dTParams['rowCount'])
        ->setOffset($dTParams['offset'] ?? 0);
    }

    if (!empty($dTParams['sort'])) {
      [$sortField, $direction] = explode(' ', $dTParams['sort']);
      $contributionSofts->addOrderBy($dataTableMapping[$sortField] ?: $sortField, strtoupper($direction));
    }
    else {
      $contributionSofts->addOrderBy('contribution_id.receive_date', 'DESC');
    }
    $contributionSofts = $contributionSofts->execute();

    $dTParams['total'] = $contributionSofts->rowCount;
    $result = [];
    foreach ($contributionSofts as $cs) {
      $result[$cs['id']]['amount'] = Civi::format()->money($cs['amount'], $cs['currency']);
      $result[$cs['id']]['currency'] = $cs['currency'];
      $result[$cs['id']]['contributor_id'] = $cs['contribution_id.contact_id'];
      $result[$cs['id']]['contribution_id'] = $cs['contribution_id'];
      $result[$cs['id']]['contributor_name'] = CRM_Utils_System::href(
        $cs['contribution_id.contact_id.display_name'],
        'civicrm/contact/view',
        "reset=1&cid={$cs['contribution_id.contact_id']}"
      );
      $result[$cs['id']]['financial_type'] = $cs['contribution_id.financial_type_id:label'];
      $result[$cs['id']]['receive_date'] = CRM_Utils_Date::customFormat($cs['contribution_id.receive_date'], $config->dateformatDatetime);
      $result[$cs['id']]['pcp_id'] = $cs['pcp_id'];
      $result[$cs['id']]['pcp_title'] = ($cs['pcp_id.title'] ?? 'n/a');
      $result[$cs['id']]['pcp_display_in_roll'] = $cs['pcp_display_in_roll'];
      $result[$cs['id']]['pcp_roll_nickname'] = $cs['pcp_roll_nickname'];
      $result[$cs['id']]['pcp_personal_note'] = $cs['pcp_personal_note'];
      $result[$cs['id']]['contribution_status'] = $cs['contribution_id.contribution_status_id:label'];
      $result[$cs['id']]['sct_label'] = $cs['soft_credit_type_id:label'];
      $replace = [
        'contributionid' => $cs['contribution_id'],
        'contactId' => $cs['contribution_id.contact_id'],
      ];
      $result[$cs['id']]['links'] = CRM_Core_Action::formLink($links, NULL, $replace);

      if ($isTest) {
        $result[$cs['id']]['contribution_status'] = CRM_Core_TestEntity::appendTestText($result[$cs['id']]['contribution_status']);
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
    $profileID = $form->_values['honoree_profile_id'];
    if (!is_array($params)) {
      CRM_Core_Error::deprecatedWarning('this could indicate a bug - see https://lab.civicrm.org/dev/core/-/issues/4881');
      $params = [];
    }
    $honoreeVariables = self::getHonorTemplateVariables($profileID, $honorId, $params);

    foreach ($honoreeVariables as $honorField => $honorValue) {
      $form->assign($honorField, $honorValue);
    }
  }

  /**
   * Process the pcp associated with a contribution.
   *
   * @param array $pcp
   * @param \CRM_Contribute_BAO_Contribution $contribution
   *
   * @throws \CRM_Core_Exception
   */
  protected static function processPCP($pcp, $contribution) {
    $pcpId = self::getSoftCreditIds($contribution->id, TRUE);

    if ($pcp) {
      $softParams = [];
      $softParams['id'] = $pcpId ?: NULL;
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
      //Send notification to owner for PCP if the contribution is already completed.
      if ($contributionSoft->pcp_id && empty($pcpId)
        && 'Completed' === CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contribution->contribution_status_id)
      ) {
        self::pcpNotifyOwner($contribution->id, (array) $contributionSoft);
      }
    }
    //Delete PCP against this contribution and create new on submitted PCP information
    elseif ($pcpId) {
      civicrm_api3('ContributionSoft', 'delete', ['id' => $pcpId]);
    }
  }

  /**
   * Function used to send notification mail to pcp owner.
   *
   * @param int $contributionID
   * @param array $contributionSoft
   *   Contribution object.
   *
   * @throws \CRM_Core_Exception
   */
  public static function pcpNotifyOwner(int $contributionID, array $contributionSoft): void {
    $params = ['id' => $contributionSoft['pcp_id']];
    $contribution = Contribution::get(FALSE)
      ->addWhere('id', '=', $contributionID)
      ->addSelect('receive_date', 'contact_id')->execute()->first();
    CRM_Core_DAO::commonRetrieve('CRM_PCP_DAO_PCP', $params, $pcpInfo);
    $ownerNotifyID = CRM_Core_DAO::getFieldValue('CRM_PCP_DAO_PCPBlock', $pcpInfo['pcp_block_id'], 'owner_notify_id');
    $ownerNotifyOption = CRM_Core_PseudoConstant::getName('CRM_PCP_DAO_PCPBlock', 'owner_notify_id', $ownerNotifyID);

    if ($ownerNotifyOption !== 'no_notifications' &&
      (($ownerNotifyOption === 'owner_chooses' &&
          CRM_Core_DAO::getFieldValue('CRM_PCP_DAO_PCP', $contributionSoft['pcp_id'], 'is_notify')) ||
        $ownerNotifyOption === 'all_owners')) {
      $pcpInfoURL = CRM_Utils_System::url('civicrm/pcp/info',
        "reset=1&id={$contributionSoft['pcp_id']}",
        TRUE, NULL, FALSE, TRUE
      );
      // set email in the template here

      if (CRM_Core_BAO_LocationType::getBilling()) {
        [$donorName, $email] = CRM_Contact_BAO_Contact_Location::getEmailDetails($contribution['contact_id'],
          FALSE, CRM_Core_BAO_LocationType::getBilling());
      }
      // get primary location email if no email exist( for billing location).
      if (!$email) {
        [$donorName, $email] = CRM_Contact_BAO_Contact_Location::getEmailDetails($contribution['contact_id']);
      }
      [$ownerName, $ownerEmail] = CRM_Contact_BAO_Contact_Location::getEmailDetails($contributionSoft['contact_id']);
      $tplParams = [
        'page_title' => $pcpInfo['title'],
        'receive_date' => $contribution['receive_date'],
        'total_amount' => $contributionSoft['amount'],
        'donors_display_name' => $donorName,
        'donors_email' => $email,
        'pcpInfoURL' => $pcpInfoURL,
        'is_honor_roll_enabled' => $contributionSoft['pcp_display_in_roll'],
        'currency' => $contributionSoft['currency'],
      ];
      $domainValues = CRM_Core_BAO_Domain::getNameAndEmail();
      $sendTemplateParams = [
        'groupName' => 'msg_tpl_workflow_contribution',
        'workflow' => 'pcp_owner_notify',
        'contactId' => $contributionSoft['contact_id'],
        'toEmail' => $ownerEmail,
        'toName' => $ownerName,
        'from' => "$domainValues[0] <$domainValues[1]>",
        'tplParams' => $tplParams,
        'PDFFilename' => 'receipt.pdf',
      ];
      CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
    }
  }

  /**
   * @param int|null $profileID
   * @param int|null $honorId
   * @param array $params
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @internal temporary function to retrieve template variables for honor profile.
   *
   */
  public static function getHonorTemplateVariables(?int $profileID, ?int $honorId, array $params): array {
    $honoreeVariables = ['honoreeProfile' => NULL, 'honorName' => NULL];
    if (!$profileID) {
      return $honoreeVariables;
    }
    $profileContactType = CRM_Core_BAO_UFGroup::getContactType($profileID);
    $profileFields = CRM_Core_BAO_UFGroup::getFields($profileID);
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
    // This looks like a really drawn out way to get the display name...
    switch ($profileContactType) {
      case 'Individual':
        if (array_key_exists('prefix_id', $params)) {
          $honorName = CRM_Utils_Array::value($params['prefix_id'],
            CRM_Contact_DAO_Contact::buildOptions('prefix_id')
          );
          unset($profileFields['prefix_id']);
        }
        $honorName .= ' ' . $params['first_name'] . ' ' . $params['last_name'];
        unset($profileFields['first_name']);
        unset($profileFields['last_name']);
        if (array_key_exists('suffix_id', $params)) {
          $honorName .= ' ' . CRM_Utils_Array::value($params['suffix_id'],
              CRM_Contact_DAO_Contact::buildOptions('suffix_id')
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
      $honoreeVariables['honoreeProfile'] = $honoreeProfileFields;
    }
    else {
      $honoreeVariables['honorName'] = $honorName;
    }
    return $honoreeVariables;
  }

  /**
   * @param string|null $entityName
   * @param int|null $userId
   * @param array $conditions
   * @inheritDoc
   */
  public function addSelectWhereClause(?string $entityName = NULL, ?int $userId = NULL, array $conditions = []): array {
    $clauses['contribution_id'] = CRM_Utils_SQL::mergeSubquery('Contribution');
    CRM_Utils_Hook::selectWhereClause($this, $clauses, $userId, $conditions);
    return $clauses;
  }

}
