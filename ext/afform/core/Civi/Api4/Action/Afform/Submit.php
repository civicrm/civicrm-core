<?php

namespace Civi\Api4\Action\Afform;

use Civi\Api4\Generic\Traits\ArrayQueryActionTrait;
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

  use ArrayQueryActionTrait;

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
    // preprocess submitted values
    $this->_entityValues = $this->preprocessSubmittedValues($this->values);

    // get the submission information if we have submission id.
    // currently we don't support processing of already processed forms
    // return validation error in those cases
    if (!empty($this->args['sid'])) {
      $afformSubmissionData = \Civi\Api4\AfformSubmission::get(FALSE)
        ->addWhere('id', '=', $this->args['sid'])
        ->addWhere('afform_name', '=', $this->name)
        ->addWhere('status_id:name', '=', 'Processed')
        ->execute()->count();

      if ($afformSubmissionData > 0) {
        throw new \CRM_Core_Exception(ts('Submission is already processed.'));
      }
    }

    // Call validation handlers
    $event = new AfformValidateEvent($this->_afform, $this->_formDataModel, $this, $this->_entityValues);
    \Civi::dispatcher()->dispatch('civi.afform.validate', $event);
    $errors = $event->getErrors();
    if ($errors) {
      \Civi::log('afform')->error('Afform Validation errors: ' . print_r($errors, TRUE));
      throw new \CRM_Core_Exception(implode("\n", $errors));
    }

    // Save submission record
    $status = 'Processed';
    if (!empty($this->_afform['create_submission']) && empty($this->args['sid'])) {
      if (!empty($this->_afform['manual_processing'])) {
        $status = 'Pending';
      }

      $userId = \CRM_Core_Session::getLoggedInContactID();

      $submissionRecord = [
        'contact_id' => $userId,
        'afform_name' => $this->name,
        'data' => $this->getValues(),
        'status_id:name' => $status,
      ];
      // Update draft if it exists
      if ($userId) {
        $draft = AfformSubmission::get(FALSE)
          ->addWhere('contact_id', '=', $userId)
          ->addWhere('status_id:name', '=', 'Draft')
          ->addWhere('afform_name', '=', $this->name)
          ->addSelect('id')
          ->execute()->first();
        if ($draft) {
          $submissionRecord['id'] = $draft['id'];
        }
      }

      $submission = AfformSubmission::save(FALSE)
        ->addRecord($submissionRecord)
        ->execute()->first();
    }

    // let's not save the data in other CiviCRM table if manual verification is needed.
    if (!empty($this->_afform['manual_processing']) && empty($this->args['sid'])) {
      // check for verification email
      $this->processVerficationEmail($submission['id']);
      return [];
    }

    // process and save various enities
    $this->processFormData($this->_entityValues);

    $submissionData = $this->combineValuesAndIds($this->getValues(), $this->_entityIds);
    // Update submission record with entity IDs.
    if (!empty($this->_afform['create_submission'])) {
      $submissionId = $submission['id'];
      if (!empty($this->args['sid'])) {
        $submissionId = $this->args['sid'];
      }

      AfformSubmission::update(FALSE)
        ->addWhere('id', '=', $submissionId)
        ->addValue('data', $submissionData)
        ->addValue('status_id:name', $status)
        ->execute();
    }

    // Return ids plus token for uploading files
    foreach ($this->_entityIds as $key => $value) {
      $this->setResponseItem($key, $value);
    }

    // todo - add only if needed?
    $this->setResponseItem('token', $this->generatePostSubmitToken());

    if (isset($this->_response['redirect']) || isset($this->_reponse['message'])) {
      // redirect / message is already set, ignore defaults
    }
    elseif ($this->_afform['confirmation_type'] === 'show_confirmation_message') {
      $message = $this->replaceTokens($this->_afform['confirmation_message']);
      $this->setResponseItem('message', $message);
    }
    elseif ($this->_afform['redirect']) {
      $redirect = $this->replaceTokens($this->_afform['redirect']);
      $this->setResponseItem('redirect', $redirect);
    }

    return [$this->_response];
  }

  /**
   * Validate field values checking required & maxlength
   *
   * @param \Civi\Afform\Event\AfformValidateEvent $event
   */
  public static function validateFieldInput(AfformValidateEvent $event): void {
    foreach ($event->getFormDataModel()->getEntities() as $afEntityName => $afEntity) {
      $entityValues = $event->getEntityValues()[$afEntityName] ?? [];
      foreach ($entityValues as $values) {
        foreach ($afEntity['fields'] as $fieldName => $attributes) {
          $error = self::getFieldInputError($event, $afEntity['type'], $fieldName, $attributes, $values['fields'][$fieldName] ?? NULL);
          if ($error) {
            $event->setError($error);
          }
        }
        foreach ($afEntity['joins'] as $joinEntity => $join) {
          foreach ($values['joins'][$joinEntity] ?? [] as $joinIndex => $joinValues) {
            foreach ($join['fields'] ?? [] as $fieldName => $attributes) {
              $error = self::getFieldInputError($event, $joinEntity, $fieldName, $attributes, $joinValues[$fieldName] ?? NULL, $joinEntity);
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
   * PHP interpretation of the "af-if" directive to determine conditional status.
   * @return bool - Is this conditional true or not.
   */
  public static function checkAfformConditional(array $conditional, array $allEntityValues) : bool {
    foreach ($conditional as $clause) {
      $clauseResult = self::checkAfformConditionalClause($clause, $allEntityValues);
      if (!$clauseResult) {
        return FALSE;
      }
    }
    return TRUE;
  }

  private static function checkAfformConditionalClause(array $clause, array $allEntityValues) {
    if ($clause[0] == 'OR') {
      // recurse here.
      $orResult = FALSE;
      foreach ($clause[1] as $subClause) {
        $orResult = $orResult || self::checkAfformConditionalClause($subClause, $allEntityValues);
      }
      return $orResult;
    }
    else {
      $submittedValue = self::getValueFromEntity($clause[0], $allEntityValues);
      // `==` is deprecated in favor of `=`
      $op = $clause[1] === '==' ? '=' : $clause[1];
      $expected = isset($clause[2]) ? \CRM_Utils_JS::decode($clause[2]) : NULL;
      return self::compareValues($submittedValue, $op, $expected);
    }
  }

  /**
   * Given a value like "Individual1[0][fields][Volunteer_Info.Residency_History]", searches a multi-dimensional array for the corresponding value if it exists.
   */
  private static function getValueFromEntity(string $getThisValue, array $allEntityValues) {
    $keys = explode('[', str_replace(']', '', $getThisValue));

    // Initialize the value to the original array
    $value = $allEntityValues;

    foreach ($keys as $key) {
      // Strip quotes from array key
      $key = trim($key, '\'"');
      if (isset($value[$key])) {
        $value = $value[$key];
      }
      else {
        // If any key is not found, return null
        return NULL;
      }
    }
    return $value;
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
   * If a required field is missing a value or exceeds the maxlength, return an error message
   */
  private static function getFieldInputError(AfformValidateEvent $event, string $apiEntity, string $fieldName, $attributes, $value) {
    return self::getRequiredFieldError($event, $apiEntity, $fieldName, $attributes, $value) ?? self::getMaxlengthError($apiEntity, $fieldName, $attributes, $value);
  }

  /**
   * If a required field is missing a value, return an error message
   *
   * @param \Civi\Afform\Event\AfformValidateEvent $event
   * @param string $apiEntity
   * @param string $fieldName
   * @param array $attributes
   * @param mixed $value
   * @return string|null
   */
  private static function getRequiredFieldError(AfformValidateEvent $event, string $apiEntity, string $fieldName, $attributes, $value) {
    // If we have a value, no need to check if required
    if ($value || is_numeric($value) || is_bool($value)) {
      return NULL;
    }
    // Required set to false, no need to validate
    if (isset($attributes['defn']['required']) && !$attributes['defn']['required']) {
      return NULL;
    }
    // InputType set to 'DisplayOnly' which skips validation
    if (($attributes['defn']['input_type'] ?? NULL) === 'DisplayOnly') {
      return NULL;
    }
    // Load full field definition, because $attributes['defn'] only has the form markup
    $fullDefn = FormDataModel::getField($apiEntity, $fieldName, 'create');

    // With the full definition loaded, check input_type again
    if (($attributes['defn']['input_type'] ?? $fullDefn['input_type']) === 'DisplayOnly') {
      return NULL;
    }
    // we don't need to validate the file fields as it's handled separately
    if ($fullDefn['input_type'] === 'File') {
      return NULL;
    }

    $isRequired = $attributes['defn']['required'] ?? $fullDefn['required'] ?? FALSE;
    $isVisible = TRUE;
    if ($isRequired) {
      $conditionals = $attributes['af-if'] ?? [];
      foreach ($conditionals as $conditional) {
        $isVisible = self::checkAfformConditional($conditional, $event->getEntityValues());
        if (!$isVisible) {
          break;
        }
      }
    }
    if ($isRequired && $isVisible) {
      $label = $attributes['defn']['label'] ?? $fullDefn['label'] ?? $fieldName;
      return E::ts('%1 is a required field.', [1 => $label]);
    }
    return NULL;
  }

  /**
   * If a required field is missing a value or exceeds the maxlength, return an error message
   */
  private static function getMaxlengthError(string $apiEntity, string $fieldName, $attributes, $value) {
    // If we have no value, no need to check maxlength
    if (!$value || !is_string($value)) {
      return NULL;
    }

    if (array_key_exists('maxlength', $attributes['defn']['input_attrs'] ?? [])) {
      $maxlength = $attributes['defn']['input_attrs']['maxlength'];
    }
    else {
      $fullDefn = FormDataModel::getField($apiEntity, $fieldName, 'create');
      $maxlength = $fullDefn['input_attrs']['maxlength'] ?? NULL;
    }
    // Use mb_strlen() which better matches the behavior of javascript's String.length
    if ($maxlength && mb_strlen($value) > $maxlength) {
      $fullDefn ??= FormDataModel::getField($apiEntity, $fieldName, 'create');
      $label = $attributes['defn']['label'] ?? $fullDefn['label'] ?? $fieldName;
      return E::ts('%1 has a max length of %2.', [1 => $label, 2 => $maxlength]);
    }
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
   * When using a "quick-add" form, this ensures the predetermined "data" values from the parent form's entity
   * will be copied to the newly-created entity in the popup form.
   *
   * @param \Civi\Afform\Event\AfformSubmitEvent $event
   */
  public static function preprocessParentFormValues(AfformSubmitEvent $event): void {
    $entityType = $event->getEntityType();
    $apiRequest = $event->getApiRequest();
    $args = $apiRequest->getArgs();
    if (str_starts_with($args['parentFormName'] ?? '', 'afform:') && str_contains($args['parentFormFieldName'] ?? '', ':')) {
      [, $parentFormName] = explode(':', $args['parentFormName']);
      [$parentFormEntityName, $parentFormFieldName] = explode(':', $args['parentFormFieldName']);
      $parentForm = civicrm_api4('Afform', 'get', [
        'select' => ['layout'],
        'where' => [
          ['name', '=', $parentFormName],
          ['submit_currently_open', '=', TRUE],
        ],
      ])->first();
      if ($parentForm) {
        $parentFormDataModel = new FormDataModel($parentForm['layout']);
        $entity = $parentFormDataModel->getEntity($parentFormEntityName);
        if (!$entity || $entity['type'] !== $entityType || empty($entity['data'])) {
          return;
        }
        $records = $event->getRecords();
        foreach ($records as &$record) {
          $record['fields'] = $entity['data'] + $record['fields'];
        }
        $event->setRecords($records);
      }
    }
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
    $entityType = $event->getEntityType();
    if (!CoreUtil::isContact($entityType)) {
      return;
    }
    // When creating a contact, verify they have a name or email address
    foreach ($event->records as $index => $contact) {
      // This trick ensures the array is not technically empty so it can get past the empty check in `processGenericEntity()`
      $event->records[$index]['fields'] += ['id' => NULL];
      if (!empty($contact['fields']['id'])) {
        continue;
      }
      if (!empty($contact['fields']) && \CRM_Contact_BAO_Contact::hasName($contact['fields'] + ['contact_type' => $entityType])) {
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
        \Civi::log('afform')->debug('Silently ignoring exception in Afform processGenericEntity call for "' . $event->getEntityName() . '". Message: ' . $e->getMessage());
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
    // Iterate through multiple relationships (if using af-repeat)
    foreach ($event->records as $relationship) {
      $relationship = $relationship['fields'] ?? [];
      if (empty($relationship['contact_id_a']) || empty($relationship['contact_id_b']) || empty($relationship['relationship_type_id'])) {
        self::saveRelationshipById($relationship, $event->getEntity(), $api4);
      }
      else {
        self::saveRelationshipByValues($relationship, $event->getEntity(), $api4);
      }
    }
  }

  /**
   * @param array $relationship
   * @param array $entity
   * @param callable $api4
   */
  private static function saveRelationshipById(array $relationship, array $entity, callable $api4): void {
    if (!empty($entity['actions']['update']) && !empty($relationship['id'])) {
      $api4('Relationship', 'save', ['records' => [$relationship]]);
    }
  }

  /**
   * @param array $relationship
   * @param array $entity
   * @param callable $api4
   */
  private static function saveRelationshipByValues(array $relationship, array $entity, callable $api4): void {
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
        if (!empty($entity['actions']['update'])) {
          $where = [
            ['is_active', '=', $isActive],
            ['relationship_type_id', '=', $relationship['relationship_type_id']],
          ];
          // Reciprocal relationship types need an extra check
          if ($isReciprocal) {
            $where[] = [
              'OR', [
                ['AND', [['contact_id_a', '=', $contact_id_a], ['contact_id_b', '=', $contact_id_b]]],
                ['AND', [['contact_id_a', '=', $contact_id_b], ['contact_id_b', '=', $contact_id_a]]],
              ],
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
        $api4('Relationship', 'save', ['records' => [$params]]);
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
    $mainEntity = $event->getFormDataModel()->getEntity($event->getEntityName());
    foreach ($joins as $joinEntityName => $joinValues) {
      $values = self::filterEmptyJoins($mainEntity, $joinEntityName, $joinValues);
      $whereClause = self::getJoinWhereClause($event->getFormDataModel(), $event->getEntityName(), $joinEntityName, $entityId);
      $mainIdField = CoreUtil::getIdFieldName($mainEntity['type']);
      $joinIdField = CoreUtil::getIdFieldName($joinEntityName);
      $joinAllowedAction = self::getJoinAllowedAction($mainEntity, $joinEntityName);

      // Forward FK e.g. Event.loc_block_id => LocBlock
      $forwardFkField = self::getFkField($mainEntity['type'], $joinEntityName);
      if ($forwardFkField && $values) {
        // Add id to values for update op, but only if id is not already on the form
        if ($whereClause && $joinAllowedAction['update'] && empty($mainEntity['joins'][$joinEntityName]['fields'][$joinIdField])) {
          $values[0][$joinIdField] = $whereClause[0][2];
        }
        $result = civicrm_api4($joinEntityName, 'save', [
          // Disable permission checks because the main entity has already been vetted
          'checkPermissions' => FALSE,
          'records' => $values,
        ]);
        civicrm_api4($mainEntity['type'], 'update', [
          'checkPermissions' => FALSE,
          'where' => [[$mainIdField, '=', $entityId]],
          'values' => [$forwardFkField['name'] => $result[0]['id']],
        ]);
        $indexedResult = array_combine(array_keys($values), (array) $result);
        $event->setJoinIds($index, $joinEntityName, $indexedResult);
      }

      // Reverse FK e.g. Contact <= Email.contact_id
      elseif ($values) {
        // In update mode, set ids of existing values
        if ($joinAllowedAction['update']) {
          $existingJoinValues = $event->getApiRequest()->loadJoins($joinEntityName, $mainEntity, $entityId, $index);
          foreach ($existingJoinValues as $joinIndex => $existingJoin) {
            if (!empty($existingJoin[$joinIdField]) && !empty($values[$joinIndex])) {
              $values[$joinIndex][$joinIdField] = $existingJoin[$joinIdField];
            }
          }
        }
        else {
          foreach ($values as $key => $value) {
            unset($values[$key][$joinIdField]);
          }
        }
        // Use REPLACE action if update+delete are both allowed (only need to check for 'delete' as it implies 'update')
        if ($joinAllowedAction['delete']) {
          $result = civicrm_api4($joinEntityName, 'replace', [
            // Disable permission checks because the main entity has already been vetted
            'checkPermissions' => FALSE,
            'where' => $whereClause,
            'records' => $values,
          ]);
        }
        else {
          $fkField = self::getFkField($joinEntityName, $mainEntity['type']);
          $result = civicrm_api4($joinEntityName, 'save', [
            // Disable permission checks because the main entity has already been vetted
            'checkPermissions' => FALSE,
            'defaults' => [$fkField['name'] => $entityId],
            'records' => $values,
          ]);
        }
        $indexedResult = array_combine(array_keys($values), (array) $result);
        $event->setJoinIds($index, $joinEntityName, $indexedResult);
      }
      // REPLACE doesn't work if there are no records, have to use DELETE
      elseif ($joinAllowedAction['delete']) {
        try {
          civicrm_api4($joinEntityName, 'delete', [
            // Disable permission checks because the main entity has already been vetted
            'checkPermissions' => FALSE,
            'where' => $whereClause,
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
   * @param array $mainEntity
   * @param string $joinEntityName
   * @param array $join
   * @return array
   */
  private static function filterEmptyJoins(array $mainEntity, string $joinEntityName, $join) {
    $idField = CoreUtil::getIdFieldName($joinEntityName);
    // Files will be uploaded later, fill with placeholder values for now
    // TODO: Somehow check if a file has actually been selected for upload
    $fileFields = self::getFileFields($joinEntityName, $mainEntity['joins'][$joinEntityName]['fields'] ?? []);
    return array_filter($join, function($item) use($joinEntityName, $idField, $fileFields) {
      $item = array_merge($item, $fileFields);
      if (!empty($item[$idField])) {
        return TRUE;
      }
      switch ($joinEntityName) {
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

  /**
   * Function to send the verification email if configured
   *
   * @param int $submissionId
   *
   * @return void
   */
  private function processVerficationEmail(int $submissionId):void {
    // check if email verification configured and message template is set
    if (empty($this->_afform['allow_verification_by_email']) || empty($this->_afform['email_confirmation_template_id'])) {
      return;
    }

    $emailValue = '';
    $submittedValues = $this->getValues();
    foreach ($this->_formDataModel->getEntities() as $entityName => $entity) {
      foreach ($submittedValues[$entityName] ?? [] as $values) {
        $values['joins'] = array_intersect_key($values['joins'] ?? [], $entity['joins']);
        foreach ($values['joins'] as $joinEntity => &$joinValues) {
          if ($joinEntity === 'Email') {
            foreach ($joinValues as $fld => $val) {
              if (!empty($val['email'])) {
                $emailValue = $val['email'];
                break;
              }
            }
          }
        }
      }
    }

    // processing sending of email only if email field exists in the form
    if (!empty($emailValue)) {
      $this->sendEmail($emailValue, $submissionId);
    }
  }

  /**
   * Function to send email
   *
   * @param string $emailAddress
   * @param int $submissionId
   *
   * @return void
   */
  private function sendEmail(string $emailAddress, int $submissionId) {
    // get domain email address
    [$domainEmailName, $domainEmailAddress] = \CRM_Core_BAO_Domain::getNameAndEmail();

    $tokenContext = [
      'validateAfformSubmission' => [
        'submissionId' => $submissionId,
      ],
    ];

    // send email
    $emailParams = [
      'messageTemplateID' => $this->_afform['email_confirmation_template_id'],
      'from' => "$domainEmailName <" . $domainEmailAddress . ">",
      'toEmail' => $emailAddress,
      'tokenContext' => $tokenContext,
    ];

    \CRM_Core_BAO_MessageTemplate::sendTemplate($emailParams);
  }

}
