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
class CRM_Dedupe_BAO_RuleGroup extends CRM_Dedupe_BAO_DedupeRuleGroup {

  /**
   * @deprecated
   * @param string $contactType
   * @return array|string[]
   */
  public static function getByType($contactType = NULL): array {
    CRM_Core_Error::deprecatedFunctionWarning('APIv4 DedupeRuleGroup::get');
    return parent::getByType($contactType);
  }

}
