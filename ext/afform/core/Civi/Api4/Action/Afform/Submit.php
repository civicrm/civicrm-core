<?php

namespace Civi\Api4\Action\Afform;

use Civi\API\Exception\UnauthorizedException;
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
   * Submitted values
   * @var array
   * @required
   */
  protected $values;

  protected function validate(): array {
    // preprocess submitted values
    $this->_entityValues = $this->preprocessSubmittedValues($this->values);

    // A stored submission is completed only by someone allowed to see it, and only once.
    if (!empty($this->args['sid'])) {
      $storedSubmission = AfformSubmission::get($this->getCheckPermissions())
        ->addWhere('id', '=', $this->args['sid'])
        ->addWhere('afform_name', '=', $this->name)
        ->addSelect('status_id:name')
        ->execute()->first();
      if (!$storedSubmission) {
        throw new UnauthorizedException(E::ts('Submission %1 is not available to process.', [1 => $this->args['sid']]));
      }
      if ($storedSubmission['status_id:name'] === 'Processed') {
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

    \CRM_Core_Transaction::create()->run(function () {
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
    });

    if (empty($this->_afform['manual_processing']) || !empty($this->args['sid'])) {
      $this->dispatchSubmitted();
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
      $redirect = $this->replaceTokens($this->_afform['redirect'], 'url');
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
    $disabledWhen = $attributes['af-disabled'] ?? NULL;
    $isDisabled = $disabledWhen && $event->getApiRequest()->checkAfformConditional($disabledWhen);
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

    $isAlwaysRequired = !empty($fieldDefn['required']);
    $requiredWhen = $attributes['af-required'] ?? NULL;
    $isRequired = $isAlwaysRequired || ($requiredWhen && $event->getApiRequest()->checkAfformConditional($requiredWhen));
    if (!$isRequired) {
      return NULL;
    }

    // Check if element is visible
    $visibleWhen = $attributes['af-if'] ?? NULL;
    $isVisible = !$visibleWhen || $event->getApiRequest()->checkAfformConditional($visibleWhen);
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
    // Only a quick-add form takes over values from the form it was opened from.
    if (!str_starts_with($event->getAfform()['server_route'] ?? '', 'civicrm/quick-add/')) {
      return;
    }
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
        // Values preset on this form are not replaced
        $parentData = array_diff_key($entity['data'], $event->getEntity()['data'] ?? []);
        $records = $event->getRecords();
        foreach ($records as &$record) {
          $record['fields'] = $parentData + $record['fields'];
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
   * Drop new records the user left blank and which lack required values, e.g. an optional
   * activity block. Records complete from form defaults alone are still created.
   *
   * @param \Civi\Afform\Event\AfformSubmitEvent $event
   * @see afform_civicrm_config
   */
  public static function preprocessBlankRecords(AfformSubmitEvent $event): void {
    $entityType = $event->getEntityType();
    if (!$entityType || CoreUtil::isContact($entityType) || $entityType === 'Contribution') {
      return;
    }
    $entity = $event->getEntity();
    $idField = CoreUtil::getIdFieldName($entityType);
    foreach ($event->records as $index => $record) {
      $fields = $record['fields'] ?? [];
      if (!$fields || !empty($fields[$idField]) || $event->getEntityId($index)) {
        continue;
      }
      if (self::hasEnteredValue($fields, $entity['fields'] ?? [], $entity['data'] ?? [], $entityType, TRUE)) {
        continue;
      }
      foreach ($record['joins'] ?? [] as $joinEntity => $joinRecords) {
        foreach ($joinRecords as $joinValues) {
          if (self::hasEnteredValue($joinValues ?? [], $entity['joins'][$joinEntity]['fields'] ?? [], $entity['joins'][$joinEntity]['data'] ?? [], $joinEntity, FALSE)) {
            continue 3;
          }
        }
      }
      $values = [];
      foreach ($fields as $key => $value) {
        $values[explode(':', $key)[0]] = $value;
      }
      $createAction = \Civi\API\Request::create($entityType, 'create', ['version' => 4, 'checkPermissions' => FALSE]);
      if ($createAction->checkRequiredFields($values)) {
        $event->records[$index]['fields'] = NULL;
      }
    }
  }

  /**
   * Whether the user entered a value in a field shown on the form.
   *
   * @param array $values
   * @param array $formFields
   *   Field definitions from the form markup, keyed by field name.
   * @param array $predetermined
   *   Values from the entity or join `data` attribute, keyed by field name.
   *   These override submitted values in preprocessSubmittedValues(), so the
   *   user cannot have entered them.
   * @param string $entityType
   * @param bool $resolveDates
   *   Whether replaceRelativeDates() processed these values (it skips joins).
   * @return bool
   */
  private static function hasEnteredValue(array $values, array $formFields, array $predetermined, string $entityType, bool $resolveDates): bool {
    foreach ($formFields as $fieldName => $formField) {
      $inputType = $formField['defn']['input_type'] ?? NULL;
      if (in_array($inputType, ['Hidden', 'DisplayOnly'], TRUE)) {
        continue;
      }
      if (array_key_exists($fieldName, $predetermined)) {
        continue;
      }
      $value = $values[$fieldName] ?? NULL;
      if ($value === NULL || $value === '' || $value === []) {
        continue;
      }
      if (array_key_exists('afform_default', $formField['defn'] ?? [])) {
        $field = self::getEntityField($entityType, explode(':', $fieldName)[0]) ?? [];
        $inputType = $formField['defn']['input_type'] ?? $field['input_type'] ?? NULL;
        $isMulti = in_array($inputType, ['CheckBox', 'Toggle', 'Multi-Select'], TRUE)
          || !empty($formField['defn']['input_attrs']['multiple']) || !empty($field['input_attrs']['multiple']);
        $default = $formField['defn']['afform_default'];
        if (self::matchesDefault($value, $default, $resolveDates && in_array($field['data_type'] ?? NULL, ['Date', 'Timestamp'], TRUE), $isMulti)
          || self::matchesClientResolvedDefault($value, $default, $inputType, $field['fk_entity'] ?? NULL, $isMulti)) {
          continue;
        }
      }
      return TRUE;
    }
    return FALSE;
  }

  private static function matchesDefault($value, $default, bool $resolveDates, bool $isMulti): bool {
    if (is_bool($default) || is_bool($value)) {
      return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === filter_var($default, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
    // replaceRelativeDates() has already replaced a relative date filter default
    // (e.g. "this.day") with the absolute date it represents; compare against the
    // resolved value too, not just the raw string from the markup.
    if ($resolveDates && is_string($default) && array_key_exists($default, (array) \CRM_Core_OptionGroup::values('relative_date_filters'))) {
      [$from, $to] = \CRM_Utils_Date::getFromTo($default);
      $resolved = $from ?: $to;
      if ($resolved && $value == $resolved) {
        return TRUE;
      }
    }
    if ($isMulti && is_array($value) && is_array($default) && array_is_list($value) && array_is_list($default)) {
      // The order of selected options is not significant, unlike other arrays.
      sort($value);
      sort($default);
    }
    return $value == $default;
  }

  /**
   * Compare against a default that afField resolves in the browser before submitting:
   * "user_contact_id" becomes the current contact, "now [+-N day|week|year]" a date.
   */
  private static function matchesClientResolvedDefault($value, $default, ?string $inputType, ?string $fkEntity, bool $isMulti): bool {
    if ($inputType === 'EntityRef' && in_array($fkEntity, ['Contact', 'Individual'], TRUE)) {
      $userId = \CRM_Core_Session::getLoggedInContactID();
      $replace = fn($id) => $id === 'user_contact_id' ? $userId : $id;
      $resolved = is_array($default) ? array_map($replace, $default) : $replace($default);
      return $resolved !== $default && self::matchesDefault($value, $resolved, FALSE, $isMulti);
    }
    if ($inputType === 'Date' && is_string($default) && is_string($value) && preg_match('/^now(?: ([-+]?\d+) (day|week|year))?$/', $default, $match)) {
      // Mirrors getRelativeDate() in afField; the browser's time zone may put its date one day off.
      $days = (int) ($match[1] ?? 0) * ['day' => 1, 'week' => 7, 'year' => 365][$match[2] ?? 'day'];
      $entered = strtotime(substr($value, 0, 10));
      return $entered !== FALSE && abs($entered - strtotime(date('Y-m-d', strtotime("$days days")))) <= 86400;
    }
    return FALSE;
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
          $disabledWhen = $attributes['af-disabled'] ?? NULL;
          $isDisabled = $disabledWhen && $event->getApiRequest()->checkAfformConditional($disabledWhen);
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
                $disabledWhen = $attributes['af-disabled'] ?? NULL;
                $isDisabled = $disabledWhen && $event->getApiRequest()->checkAfformConditional($disabledWhen);
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
      // An existing record found by e.g. a dedupe rule stays as it is on a create-only form
      if (!empty($record['fields'][CoreUtil::getIdFieldName($event->getEntityType())]) && empty($event->getEntity()['actions']['update'])) {
        continue;
      }
      $saved = $api4($event->getEntityType(), 'save', ['records' => [$record['fields']]])->first();
      $event->setSaved($index, $saved);
      self::saveJoins($event, $index, $event->getEntityId($index), $record['joins'] ?? []);
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
      // For handling suffixed values, particularly relationship_type_id:name since we do reference relationship_type_id specifically here.
      $event->getApiRequest()->formatWriteValues($relationship, 'Relationship', 'create');
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
            if ($existing['contact_id_a'] != $contact_id_a) {
              $params['is_permission_a_b'] = $relationship['is_permission_b_a'] ?? NULL;
              $params['is_permission_b_a'] = $relationship['is_permission_a_b'] ?? NULL;
            }
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
      // The submitter of a dedupe match never saw the stored records, so none are removed.
      if (!empty($event->dedupeMatches[$index])) {
        $joinAllowedAction['delete'] = FALSE;
      }

      // Forward FK e.g. Event.loc_block_id => LocBlock
      $forwardFkField = self::getFkField($mainEntity['type'], $joinEntityName);
      if ($forwardFkField && $values) {
        $existingId = $whereClause[0][2] ?? NULL;
        $hasOtherFields = count(array_diff(array_keys($values[0]), [$joinIdField])) > 0;
        // Add id to values for update op if id is not on the form
        if ($existingId && $joinAllowedAction['update'] && empty($mainEntity['joins'][$joinEntityName]['fields'][$joinIdField])) {
          $values[0][$joinIdField] = $existingId;
        }
        // If modifying fields, do not allow updating a record that does not belong to this entity
        if ($hasOtherFields && !empty($values[0][$joinIdField]) && (!$joinAllowedAction['update'] || $values[0][$joinIdField] != $existingId)) {
          unset($values[0][$joinIdField]);
        }
        $joinAction = empty($values[0][$joinIdField]) ? 'create' : 'update';
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
        $event->setJoinActions($index, $joinEntityName, [array_key_first($values) => $joinAction]);
      }

      // Reverse FK e.g. Contact <= Email.contact_id
      elseif ($values) {
        // In update mode, set ids of existing values
        if ($joinAllowedAction['update']) {
          $existingJoinValues = $event->getApiRequest()->loadJoins($joinEntityName, $mainEntity, $entityId, $index);
          $existingIds = array_filter(array_column($existingJoinValues, $joinIdField));
          foreach ($existingJoinValues as $joinIndex => $existingJoin) {
            if (!empty($existingJoin[$joinIdField]) && !empty($values[$joinIndex])) {
              $values[$joinIndex][$joinIdField] = $existingJoin[$joinIdField];
            }
          }
          // Do not allow updating a record that does not belong to this entity
          foreach ($values as $key => $value) {
            if (!empty($value[$joinIdField]) && !in_array($value[$joinIdField], $existingIds)) {
              unset($values[$key][$joinIdField]);
            }
          }
        }
        else {
          foreach ($values as $key => $value) {
            unset($values[$key][$joinIdField]);
          }
        }
        $joinActions = array_map(
          fn($value) => empty($value[$joinIdField]) ? 'create' : 'update',
          $values
        );
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
        $event->setJoinActions($index, $joinEntityName, $joinActions);
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
      'civiAfformSubmission' => [
        'name' => $this->name,
        'data' => $this->_entityIds,
        'actions' => $this->_entityActions,
      ],
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
