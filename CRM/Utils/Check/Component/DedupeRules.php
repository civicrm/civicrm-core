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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Check_Component_DedupeRules extends CRM_Utils_Check_Component {

  /**
   * Get dedupe rules, grouped by contact type, for a specific usage
   *
   * @param string $used (Supervised, Unsupervised, General)
   * @return string[]
   */
  private static function getContactTypesForRule($used) {
    $dedupeRules = \Civi\Api4\DedupeRuleGroup::get(FALSE)
      ->addSelect('contact_type')
      ->addGroupBy('contact_type')
      ->addWhere('used', '=', $used)
      ->execute();

    $types = [];
    foreach ($dedupeRules as $rule) {
      if (!in_array($rule, $types)) {
        $types[] = $rule['contact_type'];
      }
    }
    return $types;
  }

  /**
   * Returns an array of missing expected contact types
   *
   * @param string[] $rules
   * @return array
   */
  private static function getMissingRules($rules) {
    $types = array_column(CRM_Contact_BAO_ContactType::basicTypeInfo(), 'label', 'name');
    return array_diff_key($types, array_flip($rules));
  }

  /**
   * @return CRM_Utils_Check_Message[]
   */
  public static function checkDedupeRulesExist() {
    $messages = [];

    $ruleTypes = ['Supervised' => ts('Supervised'), 'Unsupervised' => ts('Unsupervised')];
    foreach ($ruleTypes as $ruleType => $ruleLabel) {
      $rules = self::getContactTypesForRule($ruleType);
      $missingRules = self::getMissingRules($rules);
      if ($missingRules) {
        $message = new CRM_Utils_Check_Message(
          __FUNCTION__ . $ruleType,
          ts('For CiviCRM to function correctly you must have at least 2 dedupe rules configured for each contact type. You are missing a rule of type %1 for: %2', [1 => $ruleLabel, 2 => implode(', ', $missingRules)]),
          ts('%1 dedupe rules missing', [1 => $ruleLabel]),
          \Psr\Log\LogLevel::WARNING,
          'fa-server'
        );
        $message->addAction(
          ts('Configure dedupe rules'),
          FALSE,
          'href',
          ['path' => 'civicrm/contact/deduperules', 'query' => ['reset' => 1]]
        );
        $messages[] = $message;
      }
    }
    return $messages;
  }

}
