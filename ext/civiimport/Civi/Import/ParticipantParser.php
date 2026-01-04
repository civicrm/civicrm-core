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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
use Civi\Api4\Participant;

/**
 * class to parse membership csv files
 */
class ParticipantParser extends ImportParser {

  protected string $baseEntity = 'Participant';

  /**
   * Get information about the provided job.
   *
   *  - name
   *  - id (generally the same as name)
   *  - label
   *
   * @return array
   */
  public static function getUserJobInfo(): array {
    return [
      'participant_import' => [
        'id' => 'participant_import',
        'name' => 'participant_import',
        'label' => ts('Participant Import'),
        'entity' => 'Participant',
        'url' => 'civicrm/import/participant',
      ],
    ];
  }

  /**
   * The initializer code, called before the processing.
   *
   * @throws \CRM_Core_Exception
   */
  public function init(): void {
    unset($this->userJob);
    $this->setFieldMetadata();
  }

  /**
   * Get a list of entities this import supports.
   *
   * @return array
   */
  public function getImportEntities() : array {
    return [
      'Participant' => [
        'text' => ts('Participant Fields'),
        'entity_type' => 'Participant',
        'required_fields_update' => $this->getRequiredFieldsForMatch(),
        'required_fields_create' => $this->getRequiredFieldsForCreate(),
        'is_base_entity' => TRUE,
        'supports_multiple' => FALSE,
        'is_required' => TRUE,
        // For now we stick with the action selected on the DataSource page.
        'actions' => [
          ['id' => 'update', 'text' => ts('Update existing'), 'description' => ts('Skip if no match found')],
          ['id' => 'create', 'text' => ts('Create'), 'description' => ts('Skip if already exists')],
        ],
        'default_action' => 'create',
        'entity_name' => 'Participant',
        'entity_title' => ts('Participant'),
        'selected' => ['action' => $this->isUpdateExisting() ? 'update' : 'create'],
      ],
      'Contact' => [
        'text' => ts('Contact Fields'),
        'entity_type' => 'Contact',
        'unique_fields' => ['external_identifier', 'id'],
        'supports_multiple' => FALSE,
        'actions' => $this->isUpdateExisting() ? $this->getActions(['ignore', 'update']) : $this->getActions(['select', 'update', 'save']),
        'selected' => [
          'action' => $this->isUpdateExisting() ? 'ignore' : 'select',
          'contact_type' => 'Individual',
          'dedupe_rule' => (array) $this->getDedupeRule('Individual')['name'],
        ],
        'default_action' => 'select',
        'entity_name' => 'Contact',
        'entity_title' => ts('Participant Contact'),
      ],
    ];
  }

  /**
   * Handle the values in import mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   */
  public function import(array $values): void {
    $values = array_values($values);
    $rowNumber = (int) ($values[array_key_last($values)]);
    try {
      $params = $this->getMappedRow($values);
      $this->removeEmptyValues($params);
      $participantParams = $params['Participant'];
      $contactParams = $params['Contact'] ?? [];
      if (!empty($participantParams['id'])) {
        $existingParticipant = $this->checkEntityExists('Participant', $participantParams['id']);
        if (!$this->isUpdateExisting()) {
          throw new \CRM_Core_Exception(ts('%1 record found and update not selected', [1 => 'Participant']));
        }
        $participantParams['contact_id'] = !empty($participantParams['contact_id']) ? (int) $participantParams['contact_id'] : $existingParticipant['contact_id'];
      }

      $participantParams['contact_id'] = $params['Contact']['id'] = $this->getContactID($contactParams, $participantParams['contact_id'] ?? $contactParams['id'] ?? NULL, 'Contact', $this->getDedupeRulesForEntity('Contact'));
      $participantParams['contact_id'] = $this->saveContact('Contact', $params['Contact'] ?? []) ?: $participantParams['contact_id'];
      // don't add to recent items, CRM-4399
      $participantParams['skipRecentView'] = TRUE;

      if (!empty($participantParams['id'])) {
        $this->checkEntityExists('Participant', $participantParams['id']);
        if (!$this->isUpdateExisting()) {
          throw new \CRM_Core_Exception(ts('% record found and update not selected', [1 => 'Participant']));
        }
        $newParticipant = Participant::update(FALSE)
          ->setValues($participantParams)
          ->execute()->first();
        $this->setImportStatus($rowNumber, 'IMPORTED', '', $newParticipant['id']);
        return;
      }

      if ($this->isSkipDuplicates()) {
        $existingParticipant = Participant::get(FALSE)
          ->addWhere('contact_id', '=', $participantParams['contact_id'])
          ->addWhere('event_id', '=', $participantParams['event_id'])
          ->execute()->first();

        if ($existingParticipant) {
          $url = \CRM_Utils_System::url('civicrm/contact/view/participant',
            "reset=1&id={$existingParticipant['id']}&cid={$existingParticipant['contact_id']}&action=view", TRUE
          );

          $this->setImportStatus($rowNumber, 'DUPLICATE', $url);
          return;
        }
      }
      $newParticipant = Participant::create(FALSE)
        ->setValues($participantParams)
        ->execute()->first();
    }
    catch (\CRM_Core_Exception $e) {
      $this->setImportStatus($rowNumber, 'ERROR', $e->getMessage());
      return;
    }
    $this->setImportStatus($rowNumber, 'IMPORTED', '', $newParticipant['id']);
  }

  /**
   * Set up field metadata.
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  protected function setFieldMetadata(): void {
    if (empty($this->importableFieldsMetadata)) {
      $fields = ['' => ['title' => ts('- do not import -')]];
      $allParticipantFields = (array) Participant::getFields()
        // Exclude readonly fields, except for the id
        ->addClause('OR', ['readonly', '=', FALSE], ['name', '=', 'id'])
        ->addWhere('usage', 'CONTAINS', 'import')
        ->setAction('save')
        ->addOrderBy('title')
        ->execute()->indexBy('name');
      $allParticipantFields = array_merge(
        [
          'note' => [
            'title' => ts('Participant Note'),
            'name' => 'note',
            'headerPattern' => '/(participant.)?note$/i',
            'data_type' => \CRM_Utils_Type::T_TEXT,
            'options' => FALSE,
          ],
        ],
        $allParticipantFields
      );
      foreach ($allParticipantFields as $fieldName => $field) {
        $field['entity_instance'] = 'Participant';
        $field['entity_prefix'] = 'Participant.';
        $fields['Participant.' . $fieldName] = $field;
      }
      $contactFields = $this->getContactFields($this->getContactType(), 'Contact');
      $fields['Participant.contact_id'] = $contactFields['Contact.id'];
      unset($contactFields['Contact.id']);
      $fields['Participant.contact_id']['title'] .= ' ' . ts('(match to contact)');
      $fields['Participant.contact_id']['html']['label'] = $fields['Participant.contact_id']['title'];
      $fields += $contactFields;
      $this->importableFieldsMetadata = $fields;
    }
  }

  /**
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   */
  protected function validateParams(array $params): void {
    if (empty($params['Participant']['id'])) {
      $this->validateRequiredFields($this->getRequiredFields(), $params['Participant'], 'Participant');
    }
    $errors = [];
    foreach ($params as $key => $value) {
      $errors = array_merge($this->getInvalidValues($value, $key), $errors);
    }
    if ($errors) {
      throw new \CRM_Core_Exception('Invalid value for field(s) : ' . implode(',', $errors));
    }
  }

  /**
   * Get the required fields.
   *
   * @return array
   */
  public function getRequiredFields(): array {
    return [[$this->getRequiredFieldsForMatch(), $this->getRequiredFieldsForCreate()]];
  }

  /**
   * Get required fields to create a contribution.
   *
   * @return array
   */
  public function getRequiredFieldsForCreate(): array {
    return ['Participant.event_id', 'Participant.status_id'];
  }

  /**
   * Get required fields to match a contribution.
   *
   * @return array
   */
  public function getRequiredFieldsForMatch(): array {
    return [['Participant.id']];
  }

}
