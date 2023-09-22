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

/**
 * This class contains all the function that are called using AJAX (dojo)
 */
class CRM_Member_Page_AJAX {

  /**
   * SetDefaults according to membership type.
   */
  public static function getMemberTypeDefaults() {
    if (!$_POST['mtype']) {
      $details['name'] = '';
      $details['auto_renew'] = '';
      $details['total_amount'] = '';

      CRM_Utils_JSON::output($details);
    }
    $memType = CRM_Utils_Type::escape($_POST['mtype'], 'Integer');

    $query = "SELECT name, minimum_fee AS total_amount, financial_type_id, auto_renew
FROM    civicrm_membership_type
WHERE   id = %1";

    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$memType, 'Positive']]);
    $properties = ['financial_type_id', 'total_amount', 'name', 'auto_renew'];
    while ($dao->fetch()) {
      foreach ($properties as $property) {
        $details[$property] = $dao->$property;
      }
    }
    $details['total_amount_numeric'] = $details['total_amount'];
    // fix the display of the monetary value, CRM-4038
    $details['total_amount'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($details['total_amount'] ?? 0);
    $options = CRM_Core_SelectValues::memberAutoRenew();
    $details['auto_renew'] = $options[$details['auto_renew']] ?? NULL;
    CRM_Utils_JSON::output($details);
  }

}
