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

namespace Civi\Api4\Action\Contact;

use Civi\Api4\DedupeRuleGroup;
use Civi\Api4\Generic\BasicGetFieldsAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\Utils\FormattingUtil;

/**
 * Get matching contacts based on a dedupe rule
 * @method $this setDedupeRule(string $dedupeRule)
 * @method string getDedupeRule()
 */
class GetDuplicates extends \Civi\Api4\Generic\DAOCreateAction {

  /**
   * Name or ID of dedupe rule to use.
   *
   * A default rule group can be used such as "Individual.Unsupervised" or "Household.Supervised"
   * or else the name of a specific rule group can be given.
   * To look up a rule by ID, pass an integer.
   *
   * @var string|int
   * @optionsCallback getRuleGroupNames
   * @required
   */
  protected $dedupeRule;

  /**
   * Options callback for dedupeRule param
   * @return string[]
   */
  protected function getRuleGroupNames() {
    $rules = [];
    $contactTypes = $this->getEntityName() === 'Contact' ? \CRM_Contact_BAO_ContactType::basicTypes() : [$this->getEntityName()];
    foreach ($contactTypes as $contactType) {
      $rules[] = $contactType . '.Unsupervised';
      $rules[] = $contactType . '.Supervised';
    }
    $specific = DedupeRuleGroup::get(FALSE)
      ->addSelect('name')
      ->execute()->column('name');
    return array_merge($rules, $specific);
  }

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    if (is_int($this->dedupeRule)) {
      $this->dedupeRule = \CRM_Core_DAO::getFieldValue('CRM_Dedupe_DAO_RuleGroup', $this->dedupeRule);
    }

    $dedupeParams = [
      'check_permission' => $this->getCheckPermissions(),
    ];
    $item = $this->values;

    $this->formatWriteValues($item);

    if (strpos($this->dedupeRule, '.Unsupervised') || strpos($this->dedupeRule, '.Supervised')) {
      [$contactType, $ruleType] = explode('.', $this->dedupeRule);
      if (!empty($item['contact_type']) && $contactType !== $item['contact_type']) {
        throw new \CRM_Core_Exception('Mismatched contact type.');
      }
      $item['contact_type'] = $contactType;
      $dedupeParams['rule'] = $ruleType;
    }
    else {
      $ruleGroup = DedupeRuleGroup::get(FALSE)
        ->addWhere('name', '=', $this->dedupeRule)
        ->addSelect('id', 'contact_type')
        ->execute()->single();
      if (!empty($item['contact_type']) && $ruleGroup['contact_type'] !== $item['contact_type']) {
        throw new \CRM_Core_Exception('Mismatched contact type.');
      }
      $item['contact_type'] = $ruleGroup['contact_type'];
      $dedupeParams['rule_group_id'] = $ruleGroup['id'];
    }

    $this->formatDedupeParams($item, $dedupeParams);

    foreach (\CRM_Contact_BAO_Contact::findDuplicates($dedupeParams) as $id) {
      $result[] = ['id' => (int) $id];
    }
  }

  /**
   * Sorts fields by table+column name per deduper expectations.
   *
   * @param array $item
   * @param array $dedupeParams
   */
  private function formatDedupeParams(array $item, array &$dedupeParams) {
    foreach (['Email', 'Phone', 'Address', 'IM'] as $entity) {
      $prefix = strtolower($entity) . '_primary.';
      $entityValues = \CRM_Utils_Array::filterByPrefix($item, $prefix);
      $this->transformCustomParams($entityValues, $dedupeParams);
      if ($entityValues) {
        if ($entity == 'Phone' && array_key_exists('phone', $entityValues)) {
          $entityValues['phone_numeric'] = preg_replace('/[^\d]/', '', $entityValues['phone']);
        }
        $dedupeParams['civicrm_' . strtolower($entity)] = $entityValues;
      }
    }
    // After removing all other entity fields, remaining fields belong to contact
    $this->transformCustomParams($item, $dedupeParams);
    $dedupeParams['civicrm_contact'] = $item;
    $dedupeParams['contact_type'] = $item['contact_type'];
  }

  /**
   * Sorts custom fields by table+column name per deduper expectations.
   *
   * @param array $entityValues
   * @param array $dedupeParams
   * @throws \CRM_Core_Exception
   */
  private function transformCustomParams(array &$entityValues, array &$dedupeParams) {
    foreach ($entityValues as $name => $value) {
      $field = $this->getCustomFieldInfo($name);
      if ($field) {
        unset($entityValues[$name]);
        if (isset($value)) {
          if ($field['suffix']) {
            $options = FormattingUtil::getPseudoconstantList($field, $name, $entityValues, 'create');
            $value = FormattingUtil::replacePseudoconstant($options, $value, TRUE);
          }
          $dedupeParams[$field['table_name']][$field['column_name']] = $value;
        }
      }
    }
  }

  /**
   * Combines getFields from Contact + related entities into a flat array
   *
   * @return array
   */
  public static function fields(BasicGetFieldsAction $action) {
    $fields = [];
    $ignore = ['id', 'contact_id', 'is_primary', 'on_hold', 'location_type_id', 'phone_type_id'];
    foreach ([$action->getEntityName(), 'Email', 'Phone', 'Address', 'IM'] as $entity) {
      $entityFields = (array) civicrm_api4($entity, 'getFields', [
        'checkPermissions' => FALSE,
        'action' => 'create',
        'loadOptions' => $action->getLoadOptions(),
        'where' => [['name', 'NOT IN', $ignore], ['type', 'IN', ['Field', 'Custom']]],
      ]);
      if ($entity !== $action->getEntityName()) {
        $prefix = strtolower($entity) . '_primary.';
        foreach ($entityFields as &$field) {
          $field['name'] = $prefix . $field['name'];
        }
      }
      $fields = array_merge($fields, $entityFields);
    }
    return $fields;
  }

}
