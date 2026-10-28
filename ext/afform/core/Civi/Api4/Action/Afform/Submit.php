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
use Civi\Core\Exception\DBQueryException;

/**
 * Class Submit
 * @package Civi\Api4\Action\Afform
 */
class Submit extends AbstractProcessor {

  use ArrayQueryActionTrait;

  /**
   * Submitted values
   * @var array
   * @required
   */
  protected $values;

  protected function validate(): array {
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
        throw new \CRM_Core_Exception(ts('Submission Processed'), 0, ['validation' => ts('Submission is already processed.')]);
      }
    }

    // Call validation handlers
    $event = new AfformValidateEvent($this->_afform, $this->_formDataModel, $this);
    \Civi::dispatcher()->dispatch('civi.afform.validate', $event);
    return $event->getErrors();
  }

  protected function processForm() {
    $errors = $this->validate();
    if ($errors) {
      \Civi::log('afform')->error('Afform Validation errors: ' . print_r($errors, TRUE));
      throw new \CRM_Core_Exception(implode("\n", $errors), 0, ['show_detailed_error' => TRUE]);
    }

    // Save submission record
    if (!empty($this->_afform['create_submission']) && empty($this->args['sid'])) {
      $userId = \CRM_Core_Session::getLoggedInContactID();

      $submissionRecord = [
        'contact_id' => $userId,
        'afform_name' => $this->name,
        'data' => $this->getValues(),
        'status_id:name' => 'Pending',
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
      $this->processVerificationEmail($submission['id']);
    }
    else {
      // process and save various entities
      $this->processFormData($this->_entityValues);

      $submissionData = $this->combineValuesAndIds($this->getValues(), $this->_entityIds);
      // Update submission record with entity IDs.
      if (!empty($this->_afform['create_submission'])) {
        $submissionId = !empty($this->args['sid']) ? $this->args['sid'] : $submission['id'];

        AfformSubmission::update(FALSE)
          ->addWhere('id', '=', $submissionId)
          ->addValue('data', $submissionData)
          ->addValue('status_id:name', 'Processed')
          ->execute();
      }

      // Return ids plus token for uploading files
      foreach ($this->_entityIds as $key => $value) {
        $this->setResponseItem($key, $value);
      }
    }

    // todo - add only if needed?
    $this->setResponseItem('token', $this->generatePostSubmitToken());

    if (isset($this->_response['redirect']) || isset($this->_response['message'])) {
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
      $entityValues = $event->getSubmittedValues()[$afEntityName] ?? [];
      foreach ($entityValues as $values) {
        foreach ($afEntity['fields'] as $fieldName => $attributes) {
          $fieldDefn = $event->getEntityFieldDefn($afEntityName, $fieldName);
          $error = self::getFieldInputError($event, $fieldName, $fieldDefn, $attributes, $values['fields'][$fieldName] ?? NULL);
          if ($error) {
            $event->addError($error);
          }
        }
        foreach ($afEntity['joins'] ?? [] as $joinEntity => $join) {
          foreach ($values['joins'][$joinEntity] ?? [] as $joinIndex => $joinValues) {
            foreach ($join['fields'] ?? [] as $fieldName => $attributes) {
              $fieldDefn = $event->getEntityFieldDefn($afEntityName, $fieldName, $joinEntity);
              $error = self::getFieldInputError($event, $fieldName, $fieldDefn, $attributes, $joinValues[$fieldName] ?? NULL);
              if ($error) {
                $event->addError($error);
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
      $entityValues = $event->getSubmittedValues()[$entityName] ?? [];
      foreach ($entityValues as $values) {
        foreach ($entity['fields'] as $fieldName => $attributes) {
          $error = self::getEntityRefError($formName, $entityName, $entity['type'], $fieldName, $attributes, $values['fields'][$fieldName] ?? NULL);
          if ($error) {
            $event->addError($error);
          }
        }
        foreach ($entity['joins'] ?? [] as $joinEntity => $join) {
          foreach ($values['joins'][$joinEntity] ?? [] as $joinIndex => $joinValues) {
            foreach ($join['fields'] ?? [] as $fieldName => $attributes) {
              $error = self::getEntityRefError($formName, $entityName . '+' . $joinEntity, $joinEntity, $fieldName, $attributes, $joinValues[$fieldName] ?? NULL);
              if ($error) {
                $event->addError($error);
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
  private static function getFieldInputError(AfformValidateEvent $event, string $fieldName, array $fieldDefn, array $attributes, $value) {
    // Check if element is disabled
    $isDisabled = FALSE;
    $disabledConditionals = $attributes['af-disabled'] ?? [];
    foreach ($disabledConditionals as $conditional) {
      if (self::checkAfformConditional($conditional, $event->getSubmittedValues())) {
        $isDisabled = TRUE;
        break;
      }
    }
    if ($isDisabled) {
      return NULL;
    }

    return self::getRequiredFieldError($event, $fieldName, $fieldDefn, $attributes, $value) ??
      self::getMaxlengthError($fieldName, $fieldDefn, $value) ??
      self::getMinlengthError($fieldName, $fieldDefn, $value) ??
      self::getMinMaxError($fieldName, $fieldDefn, $value);
  }

  /**
   * If a required field is missing a value, return an error message
   */
  private static function getRequiredFieldError(AfformValidateEvent $event, string $fieldName, array $fieldDefn, array $attributes, $value) {
    // If we have a value, skip
    if ($value || is_numeric($value) || is_bool($value)) {
      return NULL;
    }
    // InputType set to 'DisplayOnly' which skips validation
    if (($fieldDefn['input_type'] ?? NULL) === 'DisplayOnly') {
      return NULL;
    }
    // we don't need to validate the file fields as it's handled separately
    if ($fieldDefn['input_type'] === 'File') {
      return NULL;
    }

    $isRequired = !empty($fieldDefn['required']);
    if (!$isRequired && !empty($attributes['af-required'])) {
      $isRequired = TRUE;
      foreach ($attributes['af-required'] as $conditional) {
        if (!self::checkAfformConditional($conditional, $event->getSubmittedValues())) {
          $isRequired = FALSE;
          break;
        }
      }
    }

    if (!$isRequired) {
      return NULL;
    }

    // Check if element is visible
    $isVisible = TRUE;
    $conditionals = $attributes['af-if'] ?? [];
    foreach ($conditionals as $conditional) {
      $isVisible = self::checkAfformConditional($conditional, $event->getSubmittedValues());
      if (!$isVisible) {
        break;
      }
    }
    if ($isVisible) {
      $label = $fieldDefn['label'] ?? $fieldDefn['title'] ?? $fieldName;
      return E::ts('%1 is a required field.', [1 => $label]);
    }
    return NULL;
  }

  /**
   * If a string value exceeds the maxlength, return an error message.
   */
  private static function getMaxlengthError(string $fieldName, array $fieldDefn, $value) {
    // If we have no value, no need to check maxlength (`required` has already been checked)
    if (!$value || !is_string($value) || ($fieldDefn['data_type'] ?? '') !== 'String') {
      return NULL;
    }

    $maxlength = $fieldDefn['input_attrs']['maxlength'] ?? NULL;

    // Use mb_strlen() which better matches the behavior of javascript's String.length
    if ($maxlength && mb_strlen($value) > $maxlength) {
      $label = $fieldDefn['label'] ?? $fieldDefn['title'] ?? $fieldName;
      return E::ts('%1 has a max length of %2.', [1 => $label, 2 => $maxlength]);
    }
  }

  /**
   * If a string value is shorter than the minlength, return an error message.
   */
  private static function getMinlengthError(string $fieldName, array $fieldDefn, $value) {
    // If we have no value, no need to check minlength (`required` has already been checked)
    if (!$value || !is_string($value) || ($fieldDefn['data_type'] ?? '') !== 'String') {
      return NULL;
    }

    $minlength = $fieldDefn['input_attrs']['minlength'] ?? NULL;

    // Use mb_strlen() which better matches the behavior of javascript's String.length
    if ($minlength && mb_strlen($value) < $minlength) {
      $label = $fieldDefn['label'] ?? $fieldDefn['title'] ?? $fieldName;
      return E::ts('%1 has a min length of %2.', [1 => $label, 2 => $minlength]);
    }
  }

  /**
   * If value is outside of min/max constraints, return an error message.
   */
  private static function getMinMaxError(string $fieldName, array $fieldDefn, $value) {
    // If we have no value, no need to check maxlength (`required` has already been checked)
    if (!$value) {
      return NULL;
    }

    $min = $fieldDefn['input_attrs']['min'] ?? NULL;
    $max = $fieldDefn['input_attrs']['max'] ?? NULL;

    if (!isset($min) && !isset($max)) {
      return NULL;
    }

    $label = $fieldDefn['label'] ?? $fieldDefn['title'] ?? $fieldName;
    $inputType = $fieldDefn['input_type'] ?? '';
    $dataType = $fieldDefn['data_type'] ?? '';

    if (is_array($value)) {
      $count = count($value);
      if (($inputType === 'CheckBox' || ($inputType === 'Select' && !empty($fieldDefn['input_attrs']['multiple'])))) {
        if (isset($min, $max) && $min === $max && $count !== $min) {
          return E::ts('%1 must have exactly %2 selected.', [1 => $label, 2 => $min]);
        }
        elseif (isset($min) && $count < $min) {
          return E::ts('%1 must have at least %2 selected.', [1 => $label, 2 => $min]);
        }
        elseif (isset($max) && $count > $max) {
          return E::ts('%1 must have at most %2 selected.', [1 => $label, 2 => $max]);
        }
      }
    }
    elseif ($inputType === 'Number' || in_array($dataType, ['Integer', 'Float', 'Money'], TRUE)) {
      if (isset($min, $max) && $min === $max && $value != $min) {
        return E::ts('%1 must be equal to %2.', [1 => $label, 2 => $min]);
      }
      elseif (isset($min) && $value < $min) {
        return E::ts('%1 must be greater than or equal to %2.', [1 => $label, 2 => $min]);
      }
      elseif (isset($max) && $value > $max) {
        return E::ts('%1 must be less than or equal to %2.', [1 => $label, 2 => $max]);
      }
    }

  }

  /**
   * Return an error if an EntityRef field is submitted with a value outside the range of its savedSearch filters
   *
   * @param string $formName
   * @param string $entityName
   * @param string|null $apiEntity
   * @param string $fieldName
   * @param array $attributes
   * @param mixed $value
   * @return string|null
   */
  private static function getEntityRefError(string $formName, string $entityName, ?string $apiEntity, string $fieldName, $attributes, $value) {
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
    if (!$entityType || !CoreUtil::isContact($entityType)) {
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
   * Preprocess submitted values to unset fields that are disabled.
   *
   * @param \Civi\Afform\Event\AfformSubmitEvent $event
   */
  public static function preprocessDisabledFields(AfformSubmitEvent $event): void {
    $afEntityName = $event->getEntityName();
    $afEntity = $event->getFormDataModel()->getEntity($afEntityName);
    if (!$afEntity) {
      return;
    }

    $records = $event->getRecords();
    foreach ($records as $index => &$record) {
      if (empty($record['fields'])) {
        continue;
      }
      // Check main entity fields
      foreach ($afEntity['fields'] as $fieldName => $attributes) {
        if (isset($record['fields'][$fieldName])) {
          $isDisabled = FALSE;
          $disabledConditionals = $attributes['af-disabled'] ?? [];
          foreach ($disabledConditionals as $conditional) {
            if (self::checkAfformConditional($conditional, $event->getSubmittedValues())) {
              $isDisabled = TRUE;
              break;
            }
          }
          if ($isDisabled) {
            unset($record['fields'][$fieldName]);
          }
        }
      }

      // Check join entity fields
      foreach ($afEntity['joins'] ?? [] as $joinEntity => $join) {
        if (!empty($record['joins'][$joinEntity])) {
          foreach ($record['joins'][$joinEntity] as $joinIndex => &$joinValues) {
            foreach ($join['fields'] ?? [] as $fieldName => $attributes) {
              if (isset($joinValues[$fieldName])) {
                $isDisabled = FALSE;
                $disabledConditionals = $attributes['af-disabled'] ?? [];
                foreach ($disabledConditionals as $conditional) {
                  if (self::checkAfformConditional($conditional, $event->getSubmittedValues())) {
                    $isDisabled = TRUE;
                    break;
                  }
                }
                if ($isDisabled) {
                  unset($joinValues[$fieldName]);
                }
              }
            }
          }
          unset($joinValues);
        }
      }
    }
    unset($record);
    $event->setRecords($records);
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
      if ($event->getEntityType() === 'Contribution') {
        // "Contribution" requires more specialised processing using Order API and is handled by extensions like Afform Payments.
        // We add a specific check here to ensure that it is not processed by the generic entity save.
        continue;
      }
      try {
        $idField = CoreUtil::getIdFieldName($event->getEntityType());
        $saved = $api4($event->getEntityType(), 'save', ['records' => [$record['fields']]])->first();
        $event->setEntityId($index, $saved[$idField]);
        $event->setSaved($index, $saved);
        self::saveJoins($event, $index, $saved[$idField], $record['joins'] ?? []);
      }
      catch (DBQueryException $e) {
        // The database rejected the statement, so data the user entered was not stored. That
        // is never the "optional entity left blank" case handled below, and reporting success
        // for it leaves the submitter believing their data is safe.
        \Civi::log('afform')->error('Afform: ' . $event->getAfform()['name'] . ': Database error on submit in processGenericEntity call for "' . $event->getEntityName() . '". Message: ' . $e->getMessage());
        throw $e;
      }
      catch (\CRM_Core_Exception $e) {
        // What to do here? Sometimes we should silently ignore errors, e.g. an optional entity
        // intentionally left blank. Other times it's a real error the user should know about.
        \Civi::log('afform')->debug('Afform: ' . $event->getAfform()['name'] . ': Silently ignoring exception on submit in processGenericEntity call for "' . $event->getEntityName() . '". Message: ' . $e->getMessage());
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
      // Expand a multi-value join field (e.g. a multi-select bound to a
      // single-value column such as EntityTag.tag_id) into one record per
      // value, so every selected value is saved as its own row instead of an
      // array being written into a single record. $expanded flags whether that
      // happened, which changes how the records must be saved below.
      [$values, $expanded] = self::expandMultivalueJoinRecords($joinEntityName, $values);
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
        // In update mode, reattach the ids of existing rows so submitted rows
        // update them. This matches by submitted index, so it is only valid
        // when nothing expanded: expanded records are a fresh, complete set
        // carrying no ids and are id-matched by natural key instead (see
        // `match` below), and a positional reattach would put an existing row's
        // id onto a different expanded row.
        if ($joinAllowedAction['update']) {
          if (!$expanded) {
            $existingJoinValues = $event->getApiRequest()->loadJoins($joinEntityName, $mainEntity, $entityId, $index);
            foreach ($existingJoinValues as $joinIndex => $existingJoin) {
              if (!empty($existingJoin[$joinIdField]) && !empty($values[$joinIndex])) {
                $values[$joinIndex][$joinIdField] = $existingJoin[$joinIdField];
              }
            }
          }
        }
        // Create-only container: never honour a client-submitted id. Done per
        // record rather than gated on $expanded, so a non-expanded row in a
        // mixed submit cannot keep an id and update an existing row here.
        else {
          foreach ($values as $key => $value) {
            unset($values[$key][$joinIdField]);
          }
        }
        // Seed defaults from the join where clause so a dynamic FK's control
        // field (e.g. EntityTag.entity_table) is set too, not just the entity_id
        // FK; otherwise rows save with an empty entity_table and the tags are
        // invisible everywhere.
        $fkField = self::getFkField($joinEntityName, $mainEntity['type']);
        $defaults = [$fkField['name'] => $entityId];
        foreach ($whereClause as $clause) {
          if (is_array($clause) && ($clause[1] ?? NULL) === '=') {
            $defaults[$clause[0]] = $clause[2];
          }
        }
        // Only a record expanded from a multi-value field carries no id and so
        // needs matching on its natural key; every other join save matches by id
        // as before, so match_fields stays empty for them.
        $matchFields = $expanded ? (CoreUtil::getInfoItem($joinEntityName, 'match_fields') ?? []) : [];
        // Use REPLACE action if update+delete are both allowed (only need to check for 'delete' as it implies 'update')
        if ($joinAllowedAction['delete']) {
          if ($matchFields) {
            // Expanded multi-value join (e.g. EntityTag): the records carry no
            // id, so replace() would have to id-match them on their natural key
            // to avoid re-inserting a duplicate. But replace()'s match-based
            // delete of de-selected rows is not reliable across versions (it can
            // leave a de-selected row behind), so manage the set explicitly
            // instead: load the existing rows, delete the ones the submitted set
            // no longer contains, then match-save the submitted records.
            $result = self::replaceExpandedJoinRecords($joinEntityName, $whereClause, $joinIdField, $matchFields, $defaults, $values);
          }
          else {
            $result = civicrm_api4($joinEntityName, 'replace', [
              // Disable permission checks because the main entity has already been vetted
              'checkPermissions' => FALSE,
              'where' => $whereClause,
              'records' => $values,
            ]);
          }
        }
        else {
          $result = civicrm_api4($joinEntityName, 'save', [
            // Disable permission checks because the main entity has already been vetted
            'checkPermissions' => FALSE,
            'defaults' => $defaults,
            // Same reason as the replace branch: when records were expanded,
            // id-match an existing row on its natural key so a re-submitted
            // multi-value record (e.g. EntityTag) updates it in place rather
            // than inserting a duplicate and hitting the unique index. Only
            // when update is allowed — on a create-only container match must
            // not turn an insert into an update of an existing row.
            //
            // Consequence: on a fully create-only container (neither update nor
            // delete allowed) this path assumes the row does not already exist.
            // If the form still resolves to an existing parent that already
            // carries the value, the INSERT hits the unique index. The correct
            // behaviour would be to skip an already-existing row (create-only
            // can neither update nor delete it), which is left for a follow-up.
            'match' => ($expanded && $joinAllowedAction['update']) ? $matchFields : [],
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
   * Save an expanded multi-value join (e.g. EntityTag) where update and delete
   * are both allowed, without relying on replace()'s match-based delete.
   *
   * The expanded records carry no id, so replace() would have to id-match them
   * on their natural key to avoid re-inserting a duplicate; but its delete of
   * de-selected rows is not reliable across versions. So manage the set
   * explicitly: load the existing rows (the same read prefill relies on), delete
   * the ones the submitted set no longer contains, then match-save the submitted
   * records so a surviving row updates in place rather than inserting a
   * duplicate and hitting the join's unique index. Returns the saved records,
   * as replace() would.
   *
   * @param string $joinEntityName
   * @param array $whereClause
   * @param string $joinIdField
   * @param array $matchFields
   * @param array $defaults
   * @param array $values
   * @return \Civi\Api4\Generic\Result
   */
  private static function replaceExpandedJoinRecords(string $joinEntityName, array $whereClause, string $joinIdField, array $matchFields, array $defaults, array $values) {
    // Natural-key signature: the match fields, taking a value from the record
    // or, for a field fixed by the where clause (e.g. entity_id, entity_table),
    // from the shared defaults. Cast so an int id and its string form compare
    // equal between the submitted records and the loaded rows.
    $signature = function (array $record) use ($matchFields, $defaults) {
      $key = [];
      foreach ($matchFields as $field) {
        $key[$field] = (string) ($record[$field] ?? $defaults[$field] ?? '');
      }
      return implode("\0", $key);
    };
    $submitted = [];
    foreach ($values as $record) {
      $submitted[$signature($record)] = TRUE;
    }
    $existing = civicrm_api4($joinEntityName, 'get', [
      // Disable permission checks because the main entity has already been vetted
      'checkPermissions' => FALSE,
      'where' => $whereClause,
      'select' => array_merge([$joinIdField], $matchFields),
    ]);
    $deleteIds = [];
    foreach ($existing as $row) {
      if (empty($submitted[$signature($row)])) {
        $deleteIds[] = $row[$joinIdField];
      }
    }
    if ($deleteIds) {
      civicrm_api4($joinEntityName, 'delete', [
        'checkPermissions' => FALSE,
        'where' => [[$joinIdField, 'IN', $deleteIds]],
      ]);
    }
    return civicrm_api4($joinEntityName, 'save', [
      'checkPermissions' => FALSE,
      'defaults' => $defaults,
      'match' => $matchFields,
      'records' => $values,
    ]);
  }

  /**
   * Expand any join record that holds multiple values for a single-value field
   * into one record per value.
   *
   * A multi-select widget bound to a join field that stores one value per row
   * (e.g. EntityTag.tag_id) submits a single record with an array value; the
   * join entity cannot store that, so without expansion nothing is saved. This
   * turns `[['tag_id' => [1, 2]]]` into `[['tag_id' => 1], ['tag_id' => 2]]`.
   * Fields that genuinely store a list (serialize set) are left untouched.
   *
   * @param string $joinEntityName
   * @param array $records
   * @return array
   *   Tuple of [records, expanded], where `expanded` is TRUE only when at least
   *   one record was split into several. When it is FALSE the records are
   *   returned unchanged, with their original keys intact.
   */
  private static function expandMultivalueJoinRecords(string $joinEntityName, array $records): array {
    static $scalarFields = [];
    if (!array_key_exists($joinEntityName, $scalarFields)) {
      try {
        $fields = [];
        foreach (civicrm_api4($joinEntityName, 'getFields', ['checkPermissions' => FALSE, 'action' => 'create']) as $field) {
          if (empty($field['serialize'])) {
            $fields[$field['name']] = TRUE;
          }
        }
        // Only populate the cache on success, so a transient getFields() failure
        // does not permanently disable expansion for the rest of the request.
        $scalarFields[$joinEntityName] = $fields;
      }
      catch (\Exception $e) {
        \Civi::log('afform')->warning('Afform: could not load fields for {entity}; multi-value join expansion skipped. {message}', [
          'entity' => $joinEntityName,
          'message' => $e->getMessage(),
        ]);
        return [$records, FALSE];
      }
    }
    $expanded = [];
    $didExpand = FALSE;
    $idField = CoreUtil::getIdFieldName($joinEntityName);
    foreach ($records as $key => $record) {
      $multiField = NULL;
      foreach ($record as $fieldName => $value) {
        if (is_array($value) && !empty($scalarFields[$joinEntityName][$fieldName])) {
          $multiField = $fieldName;
          break;
        }
      }
      if ($multiField === NULL) {
        // Append rather than write by original key: once a record has expanded,
        // $expanded holds appended integer keys, so a keyed write here could
        // land on a slot an expanded row already took and overwrite it. The
        // original keys only need to survive when nothing expanded, which the
        // early return below handles by returning $records untouched.
        $expanded[] = $record;
        continue;
      }
      $didExpand = TRUE;
      foreach ($record[$multiField] as $singleValue) {
        $newRecord = [$multiField => $singleValue] + $record;
        // Expanded rows are distinct records, so they must not keep the
        // original row's id (which would make replace() overwrite a row).
        unset($newRecord[$idField]);
        $expanded[] = $newRecord;
      }
    }
    // Preserve the original keys when nothing was expanded: filterEmptyJoins()
    // keeps the submitted indexes deliberately, and both the existing-row
    // matching in saveJoins() and file uploads in SubmitFile rely on them.
    if (!$didExpand) {
      return [$records, FALSE];
    }
    // Deduplicate identical rows across the whole expanded set, not within each
    // record: the same value repeated in one multi-select, or chosen again in a
    // second repeat row, would otherwise issue two inserts of the same natural
    // key and hit the join's unique index.
    $unique = [];
    foreach ($expanded as $record) {
      $signature = $record;
      ksort($signature);
      $unique[serialize($signature)] = $record;
    }
    return [array_values($unique), TRUE];
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
  public function getValues(): ?array {
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
  private function processVerificationEmail(int $submissionId):void {
    // check if email verification configured and message template is set
    if (empty($this->_afform['allow_verification_by_email']) || empty($this->_afform['email_confirmation_template_id'])) {
      return;
    }

    $emailValue = '';
    $submittedValues = $this->getValues();
    foreach ($this->_formDataModel->getEntities() as $entityName => $entity) {
      foreach ($submittedValues[$entityName] ?? [] as $values) {
        $values['joins'] = array_intersect_key($values['joins'] ?? [], $entity['joins'] ?? []);
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
