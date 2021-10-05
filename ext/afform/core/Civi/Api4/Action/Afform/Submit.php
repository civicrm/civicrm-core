<?php

namespace Civi\Api4\Action\Afform;

use Civi\Afform\Event\AfformSubmitEvent;
use Civi\Api4\AfformSubmission;
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
      // Predetermined values override submitted values
      if (!empty($entity['data'])) {
        foreach ($entityValues[$entityName] as $index => $vals) {
          $entityValues[$entityName][$index]['fields'] = $entity['data'] + $vals['fields'];
        }
      }
    }
    $entityWeights = \Civi\Afform\Utils::getEntityWeights($this->_formDataModel->getEntities(), $entityValues);
    foreach ($entityWeights as $entityName) {
      $entityType = $this->_formDataModel->getEntity($entityName)['type'];
      $records = $this->replaceReferences($entityName, $entityValues[$entityName]);
      $this->fillIdFields($records, $entityName);
      $event = new AfformSubmitEvent($this->_afform, $this->_formDataModel, $this, $records, $entityType, $entityName, $this->_entityIds);
      \Civi::dispatcher()->dispatch('civi.afform.submit', $event);
    }

    // Save submission record
    if (!empty($this->_afform['create_submission'])) {
      $submission = AfformSubmission::create(FALSE)
        ->addValue('contact_id', \CRM_Core_Session::getLoggedInContactID())
        ->addValue('afform_name', $this->name)
        ->addValue('data', $this->_entityIds)
        ->execute()->first();
    }

    // Return ids and a token for uploading files
    return [
      [
        'token' => $this->generatePostSubmitToken(),
      ],
    ];
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
                $records[$key]['fields'][$field][$i] = $this->_entityIds[$val][0]['id'] ?? NULL;
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
   * Validate contact(s) meet the minimum requirements to be created (name and/or email).
   *
   * This requires a function because simple required fields validation won't work
   * across multiple entities (contact + n email addresses).
   *
   * @param \Civi\Afform\Event\AfformSubmitEvent $event
   * @throws \API_Exception
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
   * @throws \API_Exception
   * @see afform_civicrm_config
   */
  public static function processGenericEntity(AfformSubmitEvent $event) {
    $api4 = $event->getSecureApi4();
    foreach ($event->records as $index => $record) {
      if (empty($record['fields'])) {
        continue;
      }
      try {
        $saved = $api4($event->getEntityType(), 'save', ['records' => [$record['fields']]])->first();
        $event->setEntityId($index, $saved['id']);
        self::saveJoins($event, $index, $saved['id'], $record['joins'] ?? []);
      }
      catch (\API_Exception $e) {
        // What to do here? Sometimes we should silently ignore errors, e.g. an optional entity
        // intentionally left blank. Other times it's a real error the user should know about.
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
   * @throws \API_Exception
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
          'where' => self::getJoinWhereClause($event->getEntityType(), $joinEntityName, $entityId),
          'records' => $values,
        ], ['id']);
        $indexedResult = array_combine(array_keys($values), (array) $result);
        $event->setJoinIds($index, $joinEntityName, $indexedResult);
      }
      // REPLACE doesn't work if there are no records, have to use DELETE
      else {
        try {
          civicrm_api4($joinEntityName, 'delete', [
            // Disable permission checks because the main entity has already been vetted
            'checkPermissions' => FALSE,
            'where' => self::getJoinWhereClause($event->getEntityType(), $joinEntityName, $entityId),
          ]);
        }
        catch (\API_Exception $e) {
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
