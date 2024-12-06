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
class CRM_Contribute_BAO_Contribution_Utils {

  /**
   * Get the contribution details by month of the year.
   *
   * @deprecated since 5.80 will be removed around 5.90
   * @param int $param
   *   Year.
   *
   * @return array|null
   *   associated array
   */
  public static function contributionChartMonthly($param) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    if ($param) {
      $param = [1 => [$param, 'Integer']];
    }
    else {
      $param = date("Y");
      $param = [1 => [$param, 'Integer']];
    }

    $query = "
    SELECT   sum(contrib.total_amount) AS ctAmt,
             MONTH( contrib.receive_date) AS contribMonth
      FROM   civicrm_contribution AS contrib
INNER JOIN   civicrm_contact AS contact ON ( contact.id = contrib.contact_id )
     WHERE   contrib.contact_id = contact.id
       AND   contrib.is_test = 0
       AND   contrib.contribution_status_id = 1
       AND   date_format(contrib.receive_date,'%Y') = %1
       AND   contact.is_deleted = 0
  GROUP BY   contribMonth
  ORDER BY   month(contrib.receive_date)";

    $dao = CRM_Core_DAO::executeQuery($query, $param);

    $params = NULL;
    while ($dao->fetch()) {
      if ($dao->contribMonth) {
        $params['By Month'][$dao->contribMonth] = $dao->ctAmt;
      }
    }
    return $params;
  }

  /**
   * Get the contribution details by year.
   *
   * @return array|null
   *   associated array
   *
   * @deprecated since 5.80 will be removed around 5.90
   */
  public static function contributionChartYearly() {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    $config = CRM_Core_Config::singleton();
    $yearClause = "year(contrib.receive_date) as contribYear";
    if (!empty($config->fiscalYearStart) && ($config->fiscalYearStart['M'] != 1 || $config->fiscalYearStart['d'] != 1)) {
      $yearClause = "CASE
        WHEN (MONTH(contrib.receive_date)>= " . $config->fiscalYearStart['M'] . "
          && DAYOFMONTH(contrib.receive_date)>= " . $config->fiscalYearStart['d'] . " )
          THEN
            concat(YEAR(contrib.receive_date), '-',YEAR(contrib.receive_date)+1)
          ELSE
            concat(YEAR(contrib.receive_date)-1,'-', YEAR(contrib.receive_date))
        END AS contribYear";
    }

    $query = "
    SELECT   sum(contrib.total_amount) AS ctAmt,
             {$yearClause}
      FROM   civicrm_contribution AS contrib
INNER JOIN   civicrm_contact contact ON ( contact.id = contrib.contact_id )
     WHERE   contrib.is_test = 0
       AND   contrib.contribution_status_id = 1
       AND   contact.is_deleted = 0
  GROUP BY   contribYear
  ORDER BY   contribYear";
    $dao = CRM_Core_DAO::executeQuery($query);

    $params = NULL;
    while ($dao->fetch()) {
      if (!empty($dao->contribYear)) {
        $params['By Year'][$dao->contribYear] = $dao->ctAmt;
      }
    }
    return $params;
  }

  /**
   * @param array $params
   * @param int $contactID
   * @param $mail
   */
  public static function createCMSUser(&$params, $contactID, $mail) {
    // lets ensure we only create one CMS user
    static $created = FALSE;

    if ($created) {
      return;
    }
    $created = TRUE;

    if (!empty($params['cms_create_account'])) {
      $params['contactID'] = !empty($params['onbehalf_contact_id']) ? $params['onbehalf_contact_id'] : $contactID;
      if (!CRM_Core_BAO_CMSUser::create($params, $mail)) {
        CRM_Core_Error::statusBounce(ts('Your profile is not saved and Account is not created.'));
      }
    }
  }

  /**
   * @param int $contactID
   *
   * @return mixed
   */
  public static function getFirstLastDetails($contactID) {
    static $_cache;

    if (!$_cache) {
      $_cache = [];
    }

    if (!isset($_cache[$contactID])) {
      $sql = "
SELECT   total_amount, receive_date
FROM     civicrm_contribution c
WHERE    contact_id = %1
ORDER BY receive_date ASC
LIMIT 1
";
      $params = [1 => [$contactID, 'Integer']];

      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      $details = [
        'first' => NULL,
        'last' => NULL,
      ];
      if ($dao->fetch()) {
        $details['first'] = [
          'total_amount' => $dao->total_amount,
          'receive_date' => $dao->receive_date,
        ];
      }

      // flip asc and desc to get the last query
      $sql = str_replace('ASC', 'DESC', $sql);
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      if ($dao->fetch()) {
        $details['last'] = [
          'total_amount' => $dao->total_amount,
          'receive_date' => $dao->receive_date,
        ];
      }

      $_cache[$contactID] = $details;
    }
    return $_cache[$contactID];
  }

  /**
   * Calculate the tax amount based on given tax rate.
   *
   * @param float $amount
   *   Amount of field.
   * @param float $taxRate
   *   Tax rate of selected financial account for field.
   *
   * @return array
   *   array of tax amount
   *
   */
  public static function calculateTaxAmount($amount, $taxRate) {
    // There can not be any rounding at this stage - as it should be done at point of display.
    return ['tax_amount' => ($taxRate / 100) * $amount];
  }

  /**
   * Format monetary amount: round and return to desired decimal place
   * @see https://issues.civicrm.org/jira/browse/CRM-20145
   *
   * @param float $amount
   *   Monetary amount
   * @param int $decimals
   *   How many decimal places to round to and return
   *
   * @return float
   *   Amount rounded and returned with the desired decimal places
   */
  public static function formatAmount($amount, $decimals = 2) {
    CRM_Core_Error::deprecatedFunctionWarning('Use CRM_Utils_Rule::cleanMoney instead');
    return number_format((float) round($amount, (int) $decimals), (int) $decimals, '.', '');
  }

  /**
   * Get contribution statuses by entity e.g. contribution, membership or 'participant'
   *
   * @deprecated
   *
   * This is called from a couple of places outside of core so it has been made
   * unused and deprecated rather than having the now-obsolete parameter change.
   * It should work much the same for the places that call it with a notice. It is
   * not an api function & not supported for use outside core. Extensions should write
   * their own functions.
   *
   * @param string $usedFor
   * @param string $name
   *   Contribution ID
   *
   * @return array
   *   Array of contribution statuses in array('status id' => 'label') format
   */
  public static function getContributionStatuses($usedFor = 'contribution', $name = NULL) {
    $statusNames = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'validate');
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    if ($usedFor !== 'contribution') {
      return self::getPendingAndCompleteStatuses();
    }
    $statusNamesToUnset = [
      // For records which represent a data template for a recurring
      // contribution that may not yet have a payment. This status should not
      // be available from forms. 'Template' contributions should only be created
      // in conjunction with a ContributionRecur record, and should have their
      // is_template field set to 1. This status excludes them from reports
      // that are still ignorant of the is_template field.
      'Template',
    ];
    // on create fetch statuses on basis of component
    if (!$name) {
      return self::getPendingCompleteFailedAndCancelledStatuses();
    }
    else {
      switch ($name) {
        case 'Completed':
          // [CRM-17498] Removing unsupported status change options.
          $statusNamesToUnset = array_merge($statusNamesToUnset, [
            'Pending',
            'Failed',
            'Partially paid',
            'Pending refund',
          ]);
          break;

        case 'Cancelled':
        case 'Chargeback':
        case 'Refunded':
          $statusNamesToUnset = array_merge($statusNamesToUnset, [
            'Pending',
            'Failed',
          ]);
          break;

        case 'Pending':
        case 'In Progress':
          $statusNamesToUnset = array_merge($statusNamesToUnset, [
            'Refunded',
            'Chargeback',
          ]);
          break;

        case 'Failed':
          $statusNamesToUnset = array_merge($statusNamesToUnset, [
            'Pending',
            'Refunded',
            'Chargeback',
            'Completed',
            'In Progress',
            'Cancelled',
          ]);
          break;
      }
    }

    foreach ($statusNamesToUnset as $name) {
      unset($statusNames[CRM_Utils_Array::key($name, $statusNames)]);
    }

    $statuses = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id');

    foreach ($statuses as $statusID => $label) {
      if (!array_key_exists($statusID, $statusNames)) {
        unset($statuses[$statusID]);
      }
    }

    return $statuses;
  }

  /**
   * Get the options for pending and completed as an array with labels as values.
   *
   * @return array
   */
  public static function getPendingAndCompleteStatuses(): array {
    $statusIDS = [
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'),
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
    ];
    return array_intersect_key(CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id'), array_flip($statusIDS));
  }

  /**
   * Get the options for pending and completed as an array with labels as values.
   *
   * @return array
   */
  public static function getPendingCompleteFailedAndCancelledStatuses(): array {
    $statusIDS = [
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'),
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Failed'),
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Cancelled'),
    ];
    return array_intersect_key(CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id'), array_flip($statusIDS));
  }

  /**
   * CRM-8254 / CRM-6907 - override default currency if applicable
   * these lines exist to support a non-default currency on the form but are probably
   * obsolete & meddling wth the defaultCurrency is not the right approach....
   *
   * @param array $params
   */
  public static function overrideDefaultCurrency($params) {
    $config = CRM_Core_Config::singleton();
    $config->defaultCurrency = CRM_Utils_Array::value('currency', $params, $config->defaultCurrency);
  }

  /**
   * Get either the public title if set or the title of a contribution page for use in workflow message template.
   * @param int $contribution_page_id
   * @return string
   */
  public static function getContributionPageTitle($contribution_page_id) {
    $title = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $contribution_page_id, 'frontend_title');
    if (empty($title)) {
      $title = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $contribution_page_id, 'title');
    }
    return $title;
  }

}
