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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
use Civi\Api4\Participant;

/**
 * class to parse membership csv files
 */
class CRM_Event_Import_Parser_Participant extends CRM_Import_Parser {

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
   */
  public function init() {
    unset($this->userJob);
    $this->setFieldMetadata();
  }

  /**
   * Handle the values in import mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   */
  public function import(array $values): void {
    $rowNumber = (int) ($values[array_key_last($values)]);
    try {
      $params = $this->getMappedRow($values);
      $this->removeEmptyValues($params);
      $participantParams = $params['Participant'];
      $contactParams = $params['Contact'] ?? [];
      if (!empty($participantParams['id'])) {
        $existingParticipant = $this->checkEntityExists('Participant', $participantParams['id']);
        if (!$this->isUpdateExisting()) {
          throw new CRM_Core_Exception(ts('% record found and update not selected', [1 => 'Participant']));
        }
        $participantParams['contact_id'] = !empty($participantParams['contact_id']) ? (int) $participantParams['contact_id'] : $existingParticipant['contact_id'];
      }

      $participantParams['contact_id'] = $this->getContactID($contactParams, $participantParams['contact_id'] ?? $contactParams['id'] ?? NULL, 'Contact', $this->getDedupeRulesForEntity('Contact'));
      // don't add to recent items, CRM-4399
      $participantParams['skipRecentView'] = TRUE;

      if (!empty($participantParams['id'])) {
        $this->checkEntityExists('Participant', $participantParams['id']);
        if (!$this->isUpdateExisting()) {
          throw new CRM_Core_Exception(ts('% record found and update not selected', [1 => 'Participant']));
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
          $url = CRM_Utils_System::url('civicrm/contact/view/participant',
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
    catch (CRM_Core_Exception $e) {
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
      $allParticipantFields = (array) Participant::getFields()
        ->addWhere('readonly', '=', FALSE)
        ->addWhere('usage', 'CONTAINS', 'import')
        ->setAction('save')
        ->addOrderBy('title')
        ->execute()->indexBy('name');
      $fields = array_merge(
        [
          '' => ['title' => ts('- do not import -')],
          'participant_note' => [
            'title' => ts('Participant Note'),
            'name' => 'participant_note',
            'headerPattern' => '/(participant.)?note$/i',
            'data_type' => CRM_Utils_Type::T_TEXT,
            'options' => FALSE,
          ],
        ],
        $allParticipantFields
      );
      $contactFields = $this->getContactFields($this->getContactType());
      $fields['contact_id'] = $contactFields['id'];
      unset($contactFields['id']);
      $fields['contact_id']['title'] .= ' ' . ts('(match to contact)');
      $fields['contact_id']['html']['label'] = $fields['contact_id']['title'];
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
      $this->validateRequiredFields($this->getRequiredFields(), $params['Participant']);
    }
    $errors = [];
    foreach ($params as $key => $value) {
      $errors = array_merge($this->getInvalidValues($value, $key), $errors);
    }
    if ($errors) {
      throw new CRM_Core_Exception('Invalid value for field(s) : ' . implode(',', $errors));
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
    return ['event_id', 'status_id'];
  }

  /**
   * Get required fields to match a contribution.
   *
   * @return array
   */
  public function getRequiredFieldsForMatch(): array {
    return [['id']];
  }

}
