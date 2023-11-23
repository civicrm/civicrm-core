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
   * Name of dedupe rule to use.
   *
   * A default rule group can be used such as "Individual.Unsupervised" or "Household.Supervised"
   * or else the name of a specific rule group can be given.
   *
   * @var string
   * @optionsCallback getRuleGroupNames
   * @required
   */
  protected $dedupeRule;

  /**
   * Options callback for dedupeRule param
   * @return string[]
   */
  protected function getRuleGroupNames() {
    //CRM_Contact_BAO_Contact::getDuplicateContacts only works on non-General rule groups
    $rules = DedupeRuleGroup::get(FALSE)
      ->addSelect('name')
      ->addWhere('used', '!=', 'General')
      ->execute()->column('name');
    return $rules;
  }

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $dedupeParams = [
      'check_permission' => $this->getCheckPermissions(),
    ];
    $item = $this->values;

    $this->formatWriteValues($item);

    // Get rule id, contact type and used params from selected rule

    $ruleGroup = DedupeRuleGroup::get(FALSE)
      ->addWhere('name', '=', $this->dedupeRule)
      ->execute()->single();

    if (!empty($item['contact_type']) && $ruleGroup['contact_type'] !== $item['contact_type']) {
      throw new \CRM_Core_Exception('Mismatched contact type.');
    }

    $this->formatDedupeParams($item, $dedupeParams);

    $match = \CRM_Contact_BAO_Contact::getDuplicateContacts($item, $ruleGroup['contact_type'], 'Unsupervised', [], [],$ruleGroup['id'],[]);
    if (count($match) > 0) {
      $result[] = ['id' => $match[0]];
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
