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

namespace Civi\Import;

use Civi\Api4\Address;
use Civi\Api4\Contact;
use Civi\Api4\CustomField;
use Civi\Api4\DedupeRule;
use Civi\Api4\DedupeRuleGroup;
use Civi\Api4\Email;
use Civi\Api4\Phone;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class to parse contribution csv files.
 */
abstract class ImportParser extends \CRM_Import_Parser {

  private array $dedupeRules = [];

  /**
   * Validate that the mapping has the required fields.
   *
   * @throws \CRM_Core_Exception
   */
  public function validateMapping($mapping): void {
    $mappedFields = [];
    foreach ($mapping as $mappingField) {
      // Civiimport uses MappingField['name'] - $mappingField[0] is (soft) deprecated.
      $mappingFieldName = $mappingField['name'] ?? $mappingField[0] ?? '';
      $mappedFields[$mappingFieldName] = $mappingFieldName;
    }
    $entity = $this->baseEntity;
    $missingFields = $this->getMissingFields($this->getRequiredFieldsForEntity($entity, $this->getActionForEntity($entity)), $mappedFields);
    if (!empty($missingFields)) {
      $error = [];
      foreach ($missingFields as $missingField) {
        $error[] = ts('Missing required field: %1', [1 => $missingField]);
      }
      throw new \CRM_Core_Exception(implode('<br/>', $error));
    }
  }

  /**
   * Get the actions to display in the rich UI.
   *
   * Filter by the input actions - e.g ['update' 'select'] will only return those keys.
   *
   * @param array $actions
   * @param string $entity
   *
   * @return array
   */
  protected function getActions(array $actions, string $entity = 'Contact'): array {
    $actionList['Contact'] = [
      'ignore' => [
        'id' => 'ignore',
        'text' => ts('No action'),
        'description' => ts('Contact not altered'),
      ],
      'select' => [
        'id' => 'select',
        'text' => ts('Match existing Contact'),
        'description' => ts('Look up existing contact. Skip row if not found'),
      ],
      'update' => [
        'id' => 'update',
        'text' => ts('Update existing Contact.'),
        'description' => ts('Update existing Contact. Skip row if not found'),
      ],
      'save' => [
        'id' => 'save',
        'text' => ts('Update existing Contact or Create'),
        'description' => ts('Create new contact if not found'),
      ],
    ];
    return array_values(array_intersect_key($actionList[$entity], array_fill_keys($actions, TRUE)));
  }

  /**
   * Save the contact.
   *
   * @param string $entity
   * @param array $contact
   *
   * @return int|null
   *
   * @throws \Civi\API\Exception\UnauthorizedException|\CRM_Core_Exception
   */
  protected function saveContact(string $entity, array $contact): ?int {
    if (in_array($this->getActionForEntity($entity), ['update', 'save', 'create'])) {
      return Contact::save()
        ->setRecords([$contact])
        ->execute()
        ->first()['id'];
    }
    return NULL;
  }

  /**
   * @param string|null $contactType
   * @param string|null $prefix
   *
   * @return array[]
   * @throws \CRM_Core_Exception
   */
  protected function getContactFields(?string $contactType, ?string $prefix = ''): array {
    $contactFields = $this->getAllContactFields('');
    $matchText = ' ' . ts('(match to %1)', [1 => $prefix]);
    $contactTypes = [$contactType];
    if (!$contactType) {
      $contactTypes = ['Individual', 'Organization', 'Household'];
    }
    foreach ($contactTypes as $type) {
      $dedupeFields = $this->getDedupeFields($type);
      foreach ($dedupeFields as $fieldName => $dedupeField) {
        if (!isset($contactFields[$fieldName])) {
          continue;
        }
        $contactFields[$fieldName]['title'] .= $matchText;
        $contactFields[$fieldName]['match_rule'] = $this->getDefaultRuleForContactType($type);
      }
    }

    $contactFields['external_identifier']['title'] .= $matchText;
    $contactFields['external_identifier']['match_rule'] = '*';
    $contactFields['id']['match_rule'] = '*';
    if ($prefix) {
      $prefixedFields = [];
      foreach ($contactFields as $name => $contactField) {
        $contactField['entity_prefix'] = $prefix . '.';
        $contactField['entity'] = 'Contact';
        $contactField['entity_instance'] = ucfirst($prefix);
        $prefixedFields[$prefix . '.' . $name] = $contactField;
      }
      return $prefixedFields;
    }
    return $contactFields;
  }

  /**
   * Get all contact import fields metadata.
   *
   * @param string $prefix
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getAllContactFields(string $prefix = 'Contact.'): array {
    $allContactFields = (array) Contact::getFields()
      ->addWhere('readonly', '=', FALSE)
      ->addWhere('usage', 'CONTAINS', 'import')
      ->addWhere('fk_entity', 'IS EMPTY')
      ->setAction('save')
      ->addOrderBy('title')
      ->execute()->indexBy('name');

    $contactTypeFields['Individual'] = (array) Contact::getFields()
      ->addWhere('readonly', '=', FALSE)
      ->addWhere('usage', 'CONTAINS', 'import')
      ->addWhere('fk_entity', 'IS EMPTY')
      ->setAction('save')
      ->setSelect(['name'])
      ->addValue('contact_type', 'Individual')
      ->addOrderBy('title')
      ->execute()->indexBy('name');

    $contactTypeFields['Organization'] = (array) Contact::getFields()
      ->addWhere('readonly', '=', FALSE)
      ->addWhere('usage', 'CONTAINS', 'import')
      ->addWhere('fk_entity', 'IS EMPTY')
      ->setAction('save')
      ->setSelect(['name'])
      ->addValue('contact_type', 'Organization')
      ->addOrderBy('title')
      ->execute()->indexBy('name');

    $contactTypeFields['Household'] = (array) Contact::getFields()
      ->addWhere('readonly', '=', FALSE)
      ->addWhere('usage', 'CONTAINS', 'import')
      ->addWhere('fk_entity', 'IS EMPTY')
      ->setAction('save')
      ->setSelect(['name'])
      ->addOrderBy('title')
      ->execute()->indexBy('name');

    $prefixedFields = [];
    foreach ($allContactFields as $fieldName => $field) {
      $field['contact_type'] = [];
      foreach ($contactTypeFields as $contactTypeName => $fields) {
        if (array_key_exists($fieldName, $fields)) {
          $field['contact_type'][$contactTypeName] = $contactTypeName;
        }
      }
      $fieldName = $prefix . $fieldName;
      $prefixedFields[$fieldName] = $field;
    }

    $addressFields = (array) Address::getFields()
      ->addWhere('readonly', '=', FALSE)
      ->addWhere('usage', 'CONTAINS', 'import')
      ->setAction('save')
      ->addOrderBy('title')
      // Exclude these fields to keep it simpler for now - we just map to primary
      ->addWhere('name', 'NOT IN', ['id', 'location_type_id', 'master_id'])
      ->execute()->indexBy('name');
    foreach ($addressFields as $fieldName => $field) {
      // Set entity to contact as primary fields used in Contact actions
      $field['entity'] = 'Contact';
      $field['name'] = 'address_primary.' . $fieldName;
      $field['contact_type'] = ['Individual' => 'Individual', 'Organization' => 'Organization', 'Household' => 'Household'];
      $prefixedFields[$prefix . 'address_primary.' . $fieldName] = $field;
    }

    $phoneFields = (array) Phone::getFields()
      ->addWhere('readonly', '=', FALSE)
      ->addWhere('usage', 'CONTAINS', 'import')
      ->setAction('save')
      // Exclude these fields to keep it simpler for now - we just map to primary
      ->addWhere('name', 'NOT IN', ['id', 'location_type_id', 'phone_type_id'])
      ->addOrderBy('title')
      ->execute()->indexBy('name');
    foreach ($phoneFields as $fieldName => $field) {
      $field['entity'] = 'Contact';
      $field['name'] = 'phone_primary.' . $fieldName;
      $field['contact_type'] = ['Individual' => 'Individual', 'Organization' => 'Organization', 'Household' => 'Household'];
      $prefixedFields[$prefix . 'phone_primary.' . $fieldName] = $field;
    }

    $emailFields = (array) Email::getFields()
      ->addWhere('readonly', '=', FALSE)
      ->addWhere('usage', 'CONTAINS', 'import')
      ->setAction('save')
      // Exclude these fields to keep it simpler for now - we just map to primary
      ->addWhere('name', 'NOT IN', ['id', 'location_type_id'])
      ->addOrderBy('title')
      ->execute()->indexBy('name');

    foreach ($emailFields as $fieldName => $field) {
      $field['entity'] = 'Contact';
      $field['name'] = 'email_primary.' . $fieldName;
      $field['contact_type'] = ['Individual' => 'Individual', 'Organization' => 'Organization', 'Household' => 'Household'];
      $prefixedFields[$prefix . 'email_primary.' . $fieldName] = $field;
    }
    return $prefixedFields;
  }

  /**
   * Get the dedupe rule, including an array of fields with weights.
   *
   * The fields are keyed according to the metadata.
   *
   * @param string|null $contactType
   * @param string|null $name
   *
   * @return array
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function getDedupeRule(?string $contactType, ?string $name = NULL): array {
    if (!$contactType && !$name) {
      $name = 'unique_identifier_match';
    }
    if (!$name) {
      $name = $this->getDefaultRuleForContactType($contactType);
    }
    if (empty($this->dedupeRules[$name])) {
      $where = [['name', '=', $name]];
      $this->loadRules($where);
    }
    return $this->dedupeRules[$name];
  }

  /**
   * Get all dedupe rules.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public function getAllDedupeRules(): array {
    $this->loadRules();
    return $this->dedupeRules;
  }

  /**
   * @param array $where
   *
   * @throws \CRM_Core_Exception
   */
  protected function loadRules(array $where = []): void {
    $rules = DedupeRuleGroup::get(FALSE)
      ->setWhere($where)
      ->addSelect('threshold', 'name', 'id', 'title', 'contact_type')
      ->execute();
    foreach ($rules as $dedupeRule) {
      $fields = [];
      $name = $dedupeRule['name'];
      $this->dedupeRules[$name] = $dedupeRule;
      $this->dedupeRules[$name]['title'] = $dedupeRule['title'] . ' (' . ts('Unique Match') . ')';
      $this->dedupeRules[$name]['rule_message'] = $fieldMessage = '';
      // Now we add the fields in a format like ['first_name' => 6, 'custom_8' => 9]
      // The number is the weight and we add both api three & four style fields so the
      // array can be used for converted & unconverted.
      $ruleFields = DedupeRule::get(FALSE)
        ->addWhere('dedupe_rule_group_id', '=', $this->dedupeRules[$name]['id'])
        ->addSelect('id', 'rule_table', 'rule_field', 'rule_weight')
        ->execute();
      foreach ($ruleFields as $ruleField) {
        $fieldMessage .= ' ' . $ruleField['rule_field'] . '(weight ' . $ruleField['rule_weight'] . ')';
        if ($ruleField['rule_table'] === 'civicrm_contact') {
          $fields[$ruleField['rule_field']] = $ruleField['rule_weight'];
        }
        // If not a contact field we add both api variants of fields.
        elseif ($ruleField['rule_table'] === 'civicrm_phone') {
          // Actually the dedupe rule for phone should always be phone_numeric. so checking 'phone' is probably unnecessary
          if (in_array($ruleField['rule_field'], ['phone', 'phone_numeric'], TRUE)) {
            $fields['phone'] = $ruleField['rule_weight'];
            $fields['phone_primary.phone'] = $ruleField['rule_weight'];
          }
        }
        elseif ($ruleField['rule_field'] === 'email') {
          $fields['email'] = $ruleField['rule_weight'];
          $fields['email_primary.email'] = $ruleField['rule_weight'];
        }
        elseif ($ruleField['rule_table'] === 'civicrm_address') {
          $fields[$ruleField['rule_field']] = $ruleField['rule_weight'];
          $fields['address_primary.' . $ruleField['rule_field']] = $ruleField['rule_weight'];
        }
        else {
          // At this point it must be a custom field.
          $customField = CustomField::get(FALSE)
            ->addWhere('custom_group_id.table_name', '=', $ruleField['rule_table'])
            ->addWhere('column_name', '=', $ruleField['rule_field'])
            ->addSelect('id', 'name', 'custom_group_id.name')
            ->execute()
            ->first();
          $fields['custom_' . $customField['id']] = $ruleField['rule_weight'];
          $fields[$customField['custom_group_id.name'] . '.' . $customField['name']] = $ruleField['rule_weight'];
        }
      }
      $this->dedupeRules[$name]['rule_message'] = ts('Missing required contact matching fields.') . " $fieldMessage " . ts('(Sum of all weights should be greater than or equal to threshold: %1).', [1 => $this->dedupeRules[$name]['threshold']]) . '<br />' . ts('Or Provide Contact ID or External ID.');

      $this->dedupeRules[$name]['fields'] = $fields;
      $this->dedupeRules[$name . '.first'] = $this->dedupeRules[$name];
      $this->dedupeRules[$name . '.first']['name'] .= '.first';
      $this->dedupeRules[$name . '.first']['title'] = str_replace('(' . ts('Unique Match') . ')', '(' . ts('First Match') . ')', $this->dedupeRules[$name . '.first']['title']);
    }
    // Contact type not specified. Return generic rules, maybe update to return
    // Select rules - ie be able to choose a mixture to pick up by type - eg. if a
    // row is clearly individual it could use that row.
    $this->dedupeRules['unique_identifier_match'] = [
      'name' => 'unique_identifier_match',
      'threshold' => 1,
      'title' => ts('ID or external identifier'),
      'rule_message' => ts('Contact ID or external identifier must be provided'),
      'fields' => [
        'id' => 1,
        'external_identifier' => 1,
      ],
      'contact_type' => NULL,
    ];
    $this->dedupeRules['unique_email_match'] = [
      'name' => 'unique_email_match',
      'threshold' => 1,
      'title' => ts('Unique email'),
      'rule_message' => ts('Email must be provided'),
      'fields' => [
        'email' => 1,
        'email_primary.email' => 1,
      ],
      'contact_type' => NULL,
    ];
  }

  /**
   * Get the fields for the dedupe rule.
   *
   * @param string $contactType
   *
   * @return array
   */
  protected function getDedupeFields(string $contactType): array {
    return $this->getDedupeRule($contactType)['fields'];
  }

  /**
   * Get the required fields.
   *
   * @return array
   */
  protected function getRequiredFields(): array {
    return [[$this->getRequiredFieldsForMatch(), $this->getRequiredFieldsForCreate()]];
  }

}
