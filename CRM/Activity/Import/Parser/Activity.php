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


/**
 * Class to parse activity csv files.
 */
class CRM_Activity_Import_Parser_Activity extends CRM_Import_Parser {

  protected $_newActivity;

  /**
   * Get information about the provided job.
   *  - name
   *  - id (generally the same as name)
   *  - label
   *
   *  e.g. ['activity_import' => ['id' => 'activity_import', 'label' => ts('Activity Import'), 'name' => 'activity_import']]
   *
   * @return array
   */
  public static function getUserJobInfo(): array {
    return [
      'activity_import' => [
        'id' => 'activity_import',
        'name' => 'activity_import',
        'label' => ts('Activity Import'),
        'entity' => 'Activity',
        'url' => 'civicrm/import/activity',
      ],
    ];
  }

  /**
   * The initializer code, called before the processing.
   */
  public function init() {
    $this->setFieldMetadata();
  }

  /**
   * Handle the values in import mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   */
  public function import($values) {
    $rowNumber = (int) ($values[array_key_last($values)]);
    // First make sure this is a valid line
    try {
      $params = $this->getMappedRow($values);

      if (!empty($params['source_contact_external_identifier'])) {
        $params['source_contact_id'] = $this->lookupExternalIdentifier($params['source_contact_external_identifier'], $this->getContactType(), $params['contact_id'] ?? NULL);
      }

      if (empty($params['external_identifier']) && empty($params['target_contact_id'])) {

        // Retrieve contact id using contact dedupe rule.
        // Since we are supporting only individual's activity import.
        $params['contact_type'] = 'Individual';
        $params['version'] = 3;
        $matchedIDs = CRM_Contact_BAO_Contact::getDuplicateContacts($params, 'Individual');

        if (!empty($matchedIDs)) {
          if (count($matchedIDs) > 1) {
            throw new CRM_Core_Exception('Multiple matching contact records detected for this row. The activity was not imported');
          }
          $cid = $matchedIDs[0];
          $params['target_contact_id'] = $cid;
          $params['version'] = 3;
          $newActivity = civicrm_api('activity', 'create', $params);
          if (!empty($newActivity['is_error'])) {
            throw new CRM_Core_Exception($newActivity['error_message']);
          }

          $this->_newActivity[] = $newActivity['id'];
          $this->setImportStatus($rowNumber, 'IMPORTED', '', $newActivity['id']);
          return;

        }
        // Using new Dedupe rule.
        $ruleParams = [
          'contact_type' => 'Individual',
          'used' => 'Unsupervised',
        ];
        $fieldsArray = CRM_Dedupe_BAO_DedupeRule::dedupeRuleFields($ruleParams);

        $disp = NULL;
        foreach ($fieldsArray as $value) {
          if (array_key_exists(trim($value), $params)) {
            $paramValue = $params[trim($value)];
            if (is_array($paramValue)) {
              $disp .= $params[trim($value)][0][trim($value)] . " ";
            }
            else {
              $disp .= $params[trim($value)] . " ";
            }
          }
        }

        if (!empty($params['external_identifier'])) {
          if ($disp) {
            $disp .= "AND {$params['external_identifier']}";
          }
          else {
            $disp = $params['external_identifier'];
          }
        }
        if (empty($params['id'])) {
          throw new CRM_Core_Exception('No matching Contact found for (' . $disp . ')');
        }
      }
      if (!empty($params['external_identifier'])) {
        $targetContactId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $params['external_identifier'], 'id', 'external_identifier'
        );

        if (!empty($params['target_contact_id']) &&
          $params['target_contact_id'] != $targetContactId
        ) {
          throw new CRM_Core_Exception('Mismatch of External ID:' . $params['external_identifier'] . ' and Contact Id:' . $params['target_contact_id']);
        }
        if ($targetContactId) {
          $params['target_contact_id'] = $targetContactId;
        }
        else {
          throw new CRM_Core_Exception('No Matching Contact for External ID:' . $params['external_identifier']);
        }
      }

      $params['version'] = 3;
      $newActivity = civicrm_api('activity', 'create', $params);
      if (!empty($newActivity['is_error'])) {
        throw new CRM_Core_Exception($newActivity['error_message']);
      }
    }
    catch (CRM_Core_Exception $e) {
      $this->setImportStatus($rowNumber, 'ERROR', $e->getMessage());
      return;
    }
    $this->_newActivity[] = $newActivity['id'];
    $this->setImportStatus($rowNumber, 'IMPORTED', '', $newActivity['id']);
  }

  /**
   * Get the row from the csv mapped to our parameters.
   *
   * @param array $values
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getMappedRow(array $values): array {
    $params = [];
    foreach ($this->getFieldMappings() as $i => $mappedField) {
      if ($mappedField['name'] === 'do_not_import') {
        continue;
      }
      if ($mappedField['name']) {
        $fieldName = $this->getFieldMetadata($mappedField['name'])['name'];
        if (in_array($mappedField['name'], ['target_contact_id', 'source_contact_id', 'source_contact_external_identifier'])) {
          $fieldName = $mappedField['name'];
        }
        $params[$fieldName] = $this->getTransformedFieldValue($mappedField['name'], $values[$i]);
      }
    }
    return $params;
  }

  /**
   * @return array
   */
  protected function getRequiredFields(): array {
    return [['activity_type_id', 'activity_date_time']];
  }

  /**
   * Ensure metadata is loaded.
   */
  protected function setFieldMetadata(): void {
    if (empty($this->importableFieldsMetadata)) {
      $activityContact = CRM_Activity_BAO_ActivityContact::import();
      $fields = ['' => ['title' => ts('- do not import -')]];

      $tmpFields = CRM_Activity_DAO_Activity::import();
      $contactFields = CRM_Contact_BAO_Contact::importableFields('Individual', NULL);

      // Using new Dedupe rule.
      $ruleParams = [
        'contact_type' => 'Individual',
        'used' => 'Unsupervised',
      ];
      $fieldsArray = CRM_Dedupe_BAO_DedupeRule::dedupeRuleFields($ruleParams);

      $tmpContactField = [];
      if (is_array($fieldsArray)) {
        foreach ($fieldsArray as $value) {
          $customFieldId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
            $value,
            'id',
            'column_name'
          );
          $value = trim($customFieldId ? 'custom_' . $customFieldId : $value);
          $tmpContactField[$value] = $contactFields[$value];
          $tmpContactField[$value]['title'] = $tmpContactField[$value]['title'] . " (match to contact)";
        }
      }
      $tmpContactField['external_identifier'] = $contactFields['external_identifier'];
      $tmpContactField['external_identifier']['title'] = $contactFields['external_identifier']['title'] . ' (target contact)' . ' (match to contact)';
      $tmpContactField['source_contact_external_identifier'] = $contactFields['external_identifier'];
      $tmpContactField['source_contact_external_identifier']['title'] = $contactFields['external_identifier']['title'] . ' (source contact)' . ' (match to contact)';

      $fields = array_merge($fields, $tmpContactField);
      $fields = array_merge($fields, $tmpFields);
      $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Activity'));

      $fields = array_merge($fields, [
        'source_contact_id' => [
          'title' => ts('Source Contact'),
          'headerPattern' => '/Source.Contact?/i',
          'name' => 'source_type_id',
          'options' => FALSE,
          'type' => CRM_Utils_Type::T_INT,
        ],
        'target_contact_id' => [
          'title' => ts('Target Contact'),
          'headerPattern' => '/Target.Contact?/i',
          'name' => 'target_type_id',
          'options' => FALSE,
          'type' => CRM_Utils_Type::T_INT,
        ],
      ]);
      $this->importableFieldsMetadata = $fields;
    }
  }

}
