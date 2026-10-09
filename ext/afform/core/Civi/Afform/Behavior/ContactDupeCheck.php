<?php
namespace Civi\Afform\Behavior;

use Civi\Afform\AbstractBehavior;
use CRM_Afform_ExtensionUtil as E;

/**
 * @service
 * @internal
 */
class ContactDupeCheck extends AbstractBehavior {

  public static function getEntities():array {
    return \CRM_Contact_BAO_ContactType::basicTypes();
  }

  public static function getTitle():string {
    return E::ts('Duplicate Warning');
  }

  public static function getDescription():string {
    return E::ts('Warn about existing contacts as the form is filled in.');
  }

  public static function getModes(string $entityName):array {
    $modes = [];
    $dedupeRuleGroups = \Civi\Api4\DedupeRuleGroup::get(FALSE)
      ->addWhere('contact_type', '=', $entityName)
      ->addOrderBy('used', 'DESC')
      ->addOrderBy('title', 'ASC')
      ->execute();
    foreach ($dedupeRuleGroups as $rule) {
      $modes[] = [
        'name' => self::getRuleName($rule, $entityName),
        'label' => $rule['title'],
      ];
    }
    return $modes;
  }

  /**
   * Field names watched for each rule, keyed by the mode names above.
   *
   * @return array
   */
  public static function getRuleFields(): array {
    $ruleFields = [];
    $ruleGroups = \Civi\Api4\DedupeRuleGroup::get(FALSE)
      ->addSelect('id', 'name', 'used', 'contact_type')
      ->addWhere('contact_type', 'IN', \CRM_Contact_BAO_ContactType::basicTypes())
      ->execute();
    foreach ($ruleGroups as $ruleGroup) {
      $fields = \Civi\Api4\DedupeRule::get(FALSE)
        ->addSelect('rule_field')
        ->addWhere('dedupe_rule_group_id', '=', $ruleGroup['id'])
        ->execute()->column('rule_field');
      // The deduper stores phone as a stripped-down numeric copy of the real field.
      $fields = array_map(fn($field) => $field === 'phone_numeric' ? 'phone' : $field, $fields);
      $ruleFields[self::getRuleName($ruleGroup, $ruleGroup['contact_type'])] = array_values($fields);
    }
    return $ruleFields;
  }

  /**
   * Supervised & unsupervised rules are named by type, as they differ per site.
   *
   * @param array $ruleGroup
   * @param string $entityName
   *
   * @return string
   */
  private static function getRuleName(array $ruleGroup, string $entityName): string {
    return $ruleGroup['used'] === 'General' ? $ruleGroup['name'] : $entityName . '.' . $ruleGroup['used'];
  }

}
