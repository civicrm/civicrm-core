<?php

namespace Civi\Api4\Action\Afform;

use CRM_Afform_ExtensionUtil as E;
use Civi\Afform\Event\AfformSubmitEvent;
use Civi\Afform\Event\AfformValidateEvent;
use Civi\Afform\FormDataModel;
use Civi\Api4\AfformSubmission;
use Civi\Api4\RelationshipType;
use Civi\Api4\Utils\CoreUtil;

/**
 * Class Submit
 * @package Civi\Api4\Action\Afform
 */
class Submit extends AbstractProcessor {

  /**
   * @deprecated - You may simply use the event name directly. dev/core#1744
   */
  const EVENT_NAME = 'civi.afform.submit';

  /**
   * Submitted values
   * @var array
   * @required
   */
  protected $values;

  protected function processForm() {
    // Preprocess submitted values
    $entityValues = [];
    foreach ($this->_formDataModel->getEntities() as $entityName => $entity) {
      $entityValues[$entityName] = [];
      // Gather submitted field values from $values['fields'] and sub-entities from $values['joins']
      foreach ($this->values[$entityName] ?? [] as $values) {
        // Only accept values from fields on the form
        $values['fields'] = array_intersect_key($values['fields'] ?? [], $entity['fields']);
        // Only accept joins set on the form
        $values['joins'] = array_intersect_key($values['joins'] ?? [], $entity['joins']);
        foreach ($values['joins'] as $joinEntity => &$joinValues) {
          // Enforce the limit set by join[max]
          $joinValues = array_slice($joinValues, 0, $entity['joins'][$joinEntity]['max'] ?? NULL);
          foreach ($joinValues as $index => $vals) {
            // Only accept values from join fields on the form
            $joinValues[$index] = array_intersect_key($vals, $entity['joins'][$joinEntity]['fields'] ?? []);
            // Merge in pre-set data
            $joinValues[$index] = array_merge($joinValues[$index], $entity['joins'][$joinEntity]['data'] ?? []);
          }
        }
        $entityValues[$entityName][] = $values;
      }
      if (!empty($entity['data'])) {
        // If no submitted values but data exists, fill the minimum number of records
        for ($index = 0; $index < $entity['min']; $index++) {
          $entityValues[$entityName][$index] = $entityValues[$entityName][$index] ?? ['fields' => []];
        }
        // Predetermined values override submitted values
        foreach ($entityValues[$entityName] as $index => $vals) {
          $entityValues[$entityName][$index]['fields'] = $entity['data'] + $vals['fields'];
        }
      }
    }

    // Call validation handlers
    $event = new AfformValidateEvent($this->_afform, $this->_formDataModel, $this, $entityValues);
    \Civi::dispatcher()->dispatch('civi.afform.validate', $event);
    $errors = $event->getErrors();
    if ($errors) {
      throw new \CRM_Core_Exception(ts('Validation Error', ['plural' => '%1 Validation Errors', 'count' => count($errors)]), 0, ['validation' => $errors]);
    }

    // Save submission record
    if (!empty($this->_afform['create_submission'])) {
      $submission = AfformSubmission::create(FALSE)
        ->addValue('contact_id', \CRM_Core_Session::getLoggedInContactID())
        ->addValue('afform_name', $this->name)
        ->addValue('data', $this->getValues())
        ->execute()->first();
    }

    // Call submit handlers
    $entityWeights = \Civi\Afform\Utils::getEntityWeights($this->_formDataModel->getEntities(), $entityValues);
    foreach ($entityWeights as $entityName) {
      $entityType = $this->_formDataModel->getEntity($entityName)['type'];
      $records = $this->replaceReferences($entityName, $entityValues[$entityName]);
      $this->fillIdFields($records, $entityName);
      $event = new AfformSubmitEvent($this->_afform, $this->_formDataModel, $this, $records, $entityType, $entityName, $this->_entityIds);
      \Civi::dispatcher()->dispatch('civi.afform.submit', $event);
    }

    $submissionData = $this->combineValuesAndIds($this->getValues(), $this->_entityIds);
    // Update submission record with entity IDs.
    if (!empty($this->_afform['create_submission'])) {
      AfformSubmission::update(FALSE)
        ->addWhere('id', '=', $submission['id'])
        ->addValue('data', $submissionData)
        ->execute();
    }

    // Return ids and a token for uploading files
    return [
      ['token' => $this->generatePostSubmitToken()] + $this->_entityIds,
    ];
  }

  /**
   * Recursively add entity IDs to the values.
   */
  protected function combineValuesAndIds($values, $ids, $isJoin = FALSE) {
    $combined = [];
    $values += array_fill_keys(array_keys($ids), []);
    foreach ($values as $name => $value) {
      foreach ($value as $idx => $val) {
        $idData = $ids[$name][$idx] ?? [];
        if (!$isJoin) {
          $idData['_joins'] = $this->combineValuesAndIds($val['joins'] ?? [], $idData['_joins'] ?? [], TRUE);
        }
        $item = array_merge($isJoin ? $val : ($val['fields'] ?? []), $idData);
        $combined[$name][$idx] = $item;
      }
    }
    return $combined;
  }

  /**
   * Validate required field values
   *
   * @param \Civi\Afform\Event\AfformValidateEvent $event
   */
  public static function validateRequiredFields(AfformValidateEvent $event): void {
    foreach ($event->getFormDataModel()->getEntities() as $entityName => $entity) {
      $entityValues = $event->getEntityValues()[$entityName] ?? [];
      foreach ($entityValues as $values) {
        foreach ($entity['fields'] as $fieldName => $attributes) {
          $error = self::getRequiredFieldError($entity['type'], $fieldName, $attributes, $values['fields'][$fieldName] ?? NULL);
          if ($error) {
            $event->setError($error);
          }
        }
        foreach ($entity['joins'] as $joinEntity => $join) {
          foreach ($values['joins'][$joinEntity] ?? [] as $joinIndex => $joinValues) {
            foreach ($join['fields'] ?? [] as $fieldName => $attributes) {
              $error = self::getRequiredFieldError($joinEntity, $fieldName, $attributes, $joinValues[$fieldName] ?? NULL);
              if ($error) {
                $event->setError($error);
              }
            }
          }
        }
      }
    }
  }

  /**
   * Validate all fields of type "EntityRef" contain values that are allowed by filters
   *
   * @param \Civi\Afform\Event\AfformValidateEvent $event
   */
  public static function validateEntityRefFields(AfformValidateEvent $event): void {
    $formName = $event->getAfform()['name'];
    foreach ($event->getFormDataModel()->getEntities() as $entityName => $entity) {
      $entityValues = $event->getEntityValues()[$entityName] ?? [];
      foreach ($entityValues as $values) {
        foreach ($entity['fields'] as $fieldName => $attributes) {
          $error = self::getEntityRefError($formName, $entityName, $entity['type'], $fieldName, $attributes, $values['fields'][$fieldName] ?? NULL);
          if ($error) {
            $event->setError($error);
          }
        }
        foreach ($entity['joins'] as $joinEntity => $join) {
          foreach ($values['joins'][$joinEntity] ?? [] as $joinIndex => $joinValues) {
            foreach ($join['fields'] ?? [] as $fieldName => $attributes) {
              $error = self::getEntityRefError($formName, $entityName . '+' . $joinEntity, $joinEntity, $fieldName, $attributes, $joinValues[$fieldName] ?? NULL);
              if ($error) {
                $event->setError($error);
              }
            }
          }
        }
      }
    }
  }

  /**
   * If a required field is missing a value, return an error message
   *
   * @param string $apiEntity
   * @param string $fieldName
   * @param array $attributes
   * @param mixed $value
   * @return string|null
   */
  private static function getRequiredFieldError(string $apiEntity, string $fieldName, $attributes, $value) {
    // If we have a value, no need to check if required
    if ($value || is_numeric($value) || is_bool($value)) {
      return NULL;
    }
    // Required set to false, no need to validate
    if (isset($attributes['defn']['required']) && !$attributes['defn']['required']) {
      return NULL;
    }
    $fullDefn = FormDataModel::getField($apiEntity, $fieldName, 'create');
    $isRequired = $attributes['defn']['required'] ?? $fullDefn['required'] ?? FALSE;
    if ($isRequired) {
      $label = $attributes['defn']['label'] ?? $fullDefn['label'];
      return E::ts('%1 is a required field.', [1 => $label]);
    }
    return NULL;
  }

  /**
   * Return an error if an EntityRef field is submitted with a value outside the range of its savedSearch filters
   *
   * @param string $formName
   * @param string $entityName
   * @param string $apiEntity
   * @param string $fieldName
   * @param array $attributes
   * @param mixed $value
   * @return string|null
   */
  private static function getEntityRefError(string $formName, string $entityName, string $apiEntity, string $fieldName, $attributes, $value) {
    $values = array_filter((array) $value);
    // If we have no values, continue
    if (!$values) {
      return NULL;
    }
    $fullDefn = FormDataModel::getField($apiEntity, $fieldName, 'create');
    $fieldType = $attributes['defn']['input_type'] ?? $fullDefn['input_type'];
    $fkEntity = $attributes['defn']['fk_entity'] ?? $fullDefn['fk_entity'] ?? $apiEntity;
    if ($fieldType === 'EntityRef') {
      $result = (array) civicrm_api4($fkEntity, 'autocomplete', [
        'ids' => $values,
        'formName' => "afform:$formName",
        'fieldName' => "$entityName:$fieldName",
      ]);
      if (count($result) < count($values) || array_diff($values, array_column($result, 'id'))) {
        $label = $attributes['defn']['label'] ?? FormDataModel::getField($apiEntity, $fieldName, 'create')['label'];
        return E::ts('Illegal value for %1.', [1 => $label]);
      }
    }
    return NULL;
  }

  /**
   * Replace Entity reference fields with the id of the referenced entity.
   * @param string $entityName
   * @param $records
   */
  private function replaceReferences($entityName, $records) {
    $entityNames = array_diff(array_keys($this->_entityIds), [$entityName]);
    $entityType = $this->_formDataModel->getEntity($entityName)['type'];
    foreach ($records as $key => $record) {
      foreach ($record['fields'] as $field => $value) {
        if (array_intersect($entityNames, (array) $value) && $this->getEntityField($entityType, $field)['input_type'] === 'EntityRef') {
          if (is_array($value)) {
            foreach ($value as $i => $val) {
              if (in_array($val, $entityNames, TRUE)) {
                $refIds = array_filter(array_column($this->_entityIds[$val], 'id'));
                array_splice($records[$key]['fields'][$field], $i, 1, $refIds);
              }
            }
          }
          else {
            $records[$key]['fields'][$field] = $this->_entityIds[$value][0]['id'] ?? NULL;
          }
        }
      }
    }
    return $records;
  }

  /**
   * Check if contact(s) meet the minimum requirements to be created (name and/or email).
   *
   * This requires a function because simple required fields validation won't work
   * across multiple entities (contact + n email addresses).
   *
   * @param \Civi\Afform\Event\AfformSubmitEvent $event
   * @throws \CRM_Core_Exception
   * @see afform_civicrm_config
   */
  public static function preprocessContact(AfformSubmitEvent $event): void {
    if ($event->getEntityType() !== 'Contact') {
      return;
    }
    // When creating a contact, verify they have a name or email address
    foreach ($event->records as $index => $contact) {
      if (!empty($contact['fields']['id'])) {
        continue;
      }
      if (empty($contact['fields']) || \CRM_Contact_BAO_Contact::hasName($contact['fields'])) {
        continue;
      }
      foreach ($contact['joins']['Email'] ?? [] as $email) {
        if (!empty($email['email'])) {
          continue 2;
        }
      }
      // Contact has no id, name, or email. Stop creation.
      $event->records[$index]['fields'] = NULL;
    }
  }

  /**
   * @param \Civi\Afform\Event\AfformSubmitEvent $event
   * @throws \CRM_Core_Exception
   * @see afform_civicrm_config
   */
  public static function processGenericEntity(AfformSubmitEvent $event) {
    $api4 = $event->getSecureApi4();
    foreach ($event->records as $index => $record) {
      if (empty($record['fields'])) {
        continue;
      }
      try {
        $idField = CoreUtil::getIdFieldName($event->getEntityType());
        $saved = $api4($event->getEntityType(), 'save', ['records' => [$record['fields']]])->first();
        $event->setEntityId($index, $saved[$idField]);
        self::saveJoins($event, $index, $saved[$idField], $record['joins'] ?? []);
      }
      catch (\CRM_Core_Exception $e) {
        // What to do here? Sometimes we should silently ignore errors, e.g. an optional entity
        // intentionally left blank. Other times it's a real error the user should know about.
      }
    }
  }

  /**
   * @param \Civi\Afform\Event\AfformSubmitEvent $event
   */
  public static function processRelationships(AfformSubmitEvent $event) {
    if ($event->getEntityType() !== 'Relationship') {
      return;
    }
    // Prevent processGenericEntity
    $event->stopPropagation();
    $api4 = $event->getSecureApi4();
    $relationship = $event->records[0]['fields'] ?? [];
    if (empty($relationship['contact_id_a']) || empty($relationship['contact_id_b']) || empty($relationship['relationship_type_id'])) {
      return;
    }
    $relationshipType = RelationshipType::get(FALSE)
      ->addWhere('id', '=', $relationship['relationship_type_id'])
      ->execute()->single();
    $isReciprocal = $relationshipType['label_a_b'] == $relationshipType['label_b_a'];
    $isActive = !isset($relationship['is_active']) || !empty($relationship['is_active']);
    // Each contact id could be multivalued (e.g. using `af-repeat`)
    foreach ((array) $relationship['contact_id_a'] as $contact_id_a) {
      foreach ((array) $relationship['contact_id_b'] as $contact_id_b) {
        $params = $relationship;
        $params['contact_id_a'] = $contact_id_a;
        $params['contact_id_b'] = $contact_id_b;
        // Check for existing relationships (if allowed)
        if (!empty($event->getEntity()['actions']['update'])) {
          $where = [
            ['is_active', '=', $isActive],
            ['relationship_type_id', '=', $relationship['relationship_type_id']],
          ];
          // Reciprocal relationship types need an extra check
          if ($isReciprocal) {
            $where[] = ['OR',
              ['AND', ['contact_id_a', '=', $contact_id_a], ['contact_id_b', '=', $contact_id_b]],
              ['AND', ['contact_id_a', '=', $contact_id_b], ['contact_id_b', '=', $contact_id_a]],
            ];
          }
          else {
            $where[] = ['contact_id_a', '=', $contact_id_a];
            $where[] = ['contact_id_b', '=', $contact_id_b];
          }
          $existing = $api4('Relationship', 'get', ['where' => $where])->first();
          if ($existing) {
            $params['id'] = $existing['id'];
            unset($params['contact_id_a'], $params['contact_id_b']);
            // If this is a flipped reciprocal relationship, also flip the permissions
            $params['is_permission_a_b'] = $relationship['is_permission_b_a'] ?? NULL;
            $params['is_permission_b_a'] = $relationship['is_permission_a_b'] ?? NULL;
          }
        }
        $api4('Relationship', 'save', [
          'records' => [$params],
        ]);
      }
    }
  }

  /**
   * This saves joins (sub-entities) such as Email, Address, Phone, etc.
   *
   * @param \Civi\Afform\Event\AfformSubmitEvent $event
   * @param int $index
   * @param int|string $entityId
   * @param array $joins
   * @throws \CRM_Core_Exception
   */
  protected static function saveJoins(AfformSubmitEvent $event, $index, $entityId, $joins) {
    foreach ($joins as $joinEntityName => $join) {
      $values = self::filterEmptyJoins($joinEntityName, $join);
      // TODO: REPLACE works for creating or updating contacts, but different logic would be needed if
      // the contact was being auto-updated via a dedupe rule; in that case we would not want to
      // delete any existing records.
      if ($values) {
        $result = civicrm_api4($joinEntityName, 'replace', [
          // Disable permission checks because the main entity has already been vetted
          'checkPermissions' => FALSE,
          'where' => self::getJoinWhereClause($event->getFormDataModel(), $event->getEntityName(), $joinEntityName, $entityId),
          'records' => $values,
        ]);
        $indexedResult = array_combine(array_keys($values), (array) $result);
        $event->setJoinIds($index, $joinEntityName, $indexedResult);
      }
      // REPLACE doesn't work if there are no records, have to use DELETE
      else {
        try {
          civicrm_api4($joinEntityName, 'delete', [
            // Disable permission checks because the main entity has already been vetted
            'checkPermissions' => FALSE,
            'where' => self::getJoinWhereClause($event->getFormDataModel(), $event->getEntityName(), $joinEntityName, $entityId),
          ]);
        }
        catch (\CRM_Core_Exception $e) {
          // No records to delete
        }
        $event->setJoinIds($index, $joinEntityName, []);
      }
    }
  }

  /**
   * Filter out join entities that have been left blank on the form
   *
   * @param $entity
   * @param $join
   * @return array
   */
  private static function filterEmptyJoins($entity, $join) {
    $idField = CoreUtil::getIdFieldName($entity);
    $fileFields = (array) civicrm_api4($entity, 'getFields', [
      'checkPermissions' => FALSE,
      'where' => [['fk_entity', '=', 'File']],
    ], ['name']);
    // Files will be uploaded later, fill with empty values for now
    // TODO: Somehow check if a file has actually been selected for upload
    foreach ($join as &$item) {
      if (empty($item[$idField]) && $fileFields) {
        $item += array_fill_keys($fileFields, '');
      }
    }
    return array_filter($join, function($item) use($entity, $idField, $fileFields) {
      if (!empty($item[$idField]) || $fileFields) {
        return TRUE;
      }
      switch ($entity) {
        case 'Email':
          return !empty($item['email']);

        case 'Phone':
          return !empty($item['phone']);

        case 'IM':
          return !empty($item['name']);

        case 'Website':
          return !empty($item['url']);

        default:
          \CRM_Utils_Array::remove($item, 'is_primary', 'location_type_id', 'entity_id', 'contact_id', 'entity_table');
          return (bool) array_filter($item);
      }
    });
  }

  /**
   * @return array
   */
  public function getValues():array {
    return $this->values;
  }

  /**
   * @param array $values
   * @return $this
   */
  public function setValues(array $values) {
    $this->values = $values;
    return $this;
  }

  /**
   * @param array $records
   * @param string $entityName
   */
  private function fillIdFields(array &$records, string $entityName): void {
    foreach ($records as $index => &$record) {
      if (empty($record['fields']['id']) && !empty($this->_entityIds[$entityName][$index]['id'])) {
        $record['fields']['id'] = $this->_entityIds[$entityName][$index]['id'];
      }
    }
  }

  /**
   * Generates token returned from submit action
   *
   * @return string
   * @throws \Civi\Crypto\Exception\CryptoException
   */
  private function generatePostSubmitToken(): string {
    // 1 hour should be more than sufficient to upload files
    $expires = \CRM_Utils_Time::time() + (60 * 60);

    /** @var \Civi\Crypto\CryptoJwt $jwt */
    $jwt = \Civi::service('crypto.jwt');

    return $jwt->encode([
      'exp' => $expires,
      // Note: Scope is not the same as "authx" scope. "Authx" tokens are user-login tokens. This one is a more limited access token.
      'scope' => 'afformPostSubmit',
      'civiAfformSubmission' => ['name' => $this->name, 'data' => $this->_entityIds],
    ]);
  }

}
