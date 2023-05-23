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
 * @deprecated
 */
class CRM_Dedupe_BAO_Rule extends CRM_Dedupe_BAO_DedupeRule {

  /**
   * @param int $cid
   * @param int $oid
   * @deprecated
   * @return bool
   */
  public static function validateContacts($cid, $oid) {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Dedupe_BAO_DedupeRule::validateContacts');
    return parent::validateContacts($cid, $oid);
  }

  /**
   * @param array $params
   * @deprecated
   * @return array
   */
  public static function dedupeRuleFields($params) {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Dedupe_BAO_DedupeRule::dedupeRuleFields');
    return parent::dedupeRuleFields($params);
  }

}
