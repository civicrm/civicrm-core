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
 */
class GetDuplicates extends \Civi\Api4\Generic\DAOCreateAction {

  /**
   * Name of dedupe rule to use.
   *
   * Specifying "Unsupervised" or "Supervised" will use the default rule group
   * for the contact type, or else the name of a specific rule group can be given.
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
    $generic = ['Unsupervised', 'Supervised'];
    $specific = DedupeRuleGroup::get(FALSE)
      ->addSelect('name')
      ->execute()->column('name');
    return array_merge($generic, $specific);
  }

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $defaultRule = $ruleId = NULL;
    $item = $this->values;

    $contactType = $item['contact_type'] ?? NULL;
    $this->formatWriteValues($item);
    $this->transformCustomParams($item);

    if (in_array($this->dedupeRule, ['Unsupervised', 'Supervised'], TRUE)) {
      if (!$contactType) {
        throw new \API_Exception('Using a default dedupe rule requires contact type.');
      }
      $defaultRule = $this->dedupeRule;
    }
    else {
      $ruleGroup = DedupeRuleGroup::get(FALSE)
        ->addWhere('name', '=', $this->dedupeRule)
        ->addSelect('id', 'contact_type')
        ->execute()->single();
      $contactType = $ruleGroup['contact_type'];
      $ruleId = $ruleGroup['id'];
    }

    foreach (\CRM_Contact_BAO_Contact::getDuplicateContacts($item, $contactType, $defaultRule, [], $this->checkPermissions, $ruleId) as $id) {
      $result[] = ['id' => $id];
    }
  }

  /**
   * @param array $params
   * @throws \API_Exception
   */
  private function transformCustomParams(&$params) {
    foreach ($params as $name => $value) {
      $field = $this->getCustomFieldInfo($name);
      if ($field) {
        unset($params[$name]);

        if (isset($value)) {
          if ($field['suffix']) {
            $options = FormattingUtil::getPseudoconstantList($field, $name, $params, 'create');
            $value = FormattingUtil::replacePseudoconstant($options, $value, TRUE);
          }
          $params["custom_{$field['id']}"] = $value;
        }
      }
    }
  }

  /**
   * Combines getfields from Contact + related entities into a flat array
   *
   * @return array
   */
  public static function fields(BasicGetFieldsAction $action) {
    $fields = [];
    $ignore = ['id', 'contact_id', 'is_primary', 'on_hold', 'location_type_id', 'phone_type_id', 'website_type_id'];
    foreach (['Contact', 'Email', 'Phone', 'Address', 'Website', 'IM', 'OpenID'] as $entity) {
      $entityFields = (array) civicrm_api4($entity, 'getFields', [
        'action' => 'create',
        'loadOptions' => $action->getLoadOptions(),
        'where' => [['name', 'NOT IN', $ignore], ['type', 'IN', ['Field', 'Custom']]],
      ]);
      $fields = array_merge($fields, $entityFields);
    }
    foreach ($fields as $index => $field) {
      $fields[$index]['required'] = $field['name'] === 'contact_type';
    }
    return $fields;
  }

}
