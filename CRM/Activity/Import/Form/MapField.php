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
 * This class gets the name of the file to upload.
 */
class CRM_Activity_Import_Form_MapField extends CRM_Import_Form_MapField {

  /**
   * Get the name of the type to be stored in civicrm_user_job.type_id.
   *
   * @return string
   */
  public function getUserJobType(): string {
    return 'activity_import';
  }

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Validate that required fields are present.
   *
   * @param array $importKeys
   *
   * return string|null
   */
  protected static function validateRequiredFields(array $importKeys): ?string {
    $fieldMessage = NULL;
    if (in_array('activity_id', $importKeys, TRUE)) {
      return NULL;
    }
    $requiredFields = [
      'target_contact_id' => ts('Contact ID'),
      'activity_date_time' => ts('Activity Date'),
      'activity_subject' => ts('Activity Subject'),
      'activity_type_id' => ts('Activity Type ID'),
    ];

    $contactFieldsBelowWeightMessage = self::validateRequiredContactMatchFields('Individual', $importKeys);
    foreach ($requiredFields as $field => $title) {
      if (!in_array($field, $importKeys, TRUE)) {
        if ($field === 'target_contact_id') {
          if (!$contactFieldsBelowWeightMessage || in_array('external_identifier', $importKeys, TRUE)) {
            continue;
          }
          $fieldMessage .= ts('Missing required contact matching fields.')
            . $contactFieldsBelowWeightMessage
            . '<br />';
        }
        $fieldMessage .= ts('Missing required field: %1', [1 => $title]) . '<br />';
      }
    }
    return $fieldMessage;
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    $this->addSavedMappingFields();
    $this->addFormRule(['CRM_Activity_Import_Form_MapField', 'formRule']);

    //-------- end of saved mapping stuff ---------

    $defaults = [];
    $headerPatterns = $this->getHeaderPatterns();
    $fieldMappings = $this->getFieldMappings();
    $columnHeaders = $this->getColumnHeaders();
    $hasHeaders = $this->getSubmittedValue('skipColumnHeader');

    $sel1 = $this->_mapperFields;

    $js = "<script type='text/javascript'>\n";
    $formName = 'document.forms.' . $this->_name;

    foreach ($columnHeaders as $i => $columnHeader) {
      $sel = &$this->addElement('hierselect', "mapper[$i]", ts('Mapper for Field %1', [1 => $i]), NULL);
      $jsSet = FALSE;
      if ($this->getSubmittedValue('savedMapping')) {
        $fieldMapping = $fieldMappings[$i] ?? NULL;
        if (isset($fieldMappings[$i])) {
          if ($fieldMapping['name'] !== ts('do_not_import')) {
            $js .= "{$formName}['mapper[$i][3]'].style.display = 'none';\n";
            $defaults["mapper[$i]"] = [$fieldMapping['name']];
            $jsSet = TRUE;
          }
          else {
            $defaults["mapper[$i]"] = [];
          }
          if (!$jsSet) {
            for ($k = 1; $k < 4; $k++) {
              $js .= "{$formName}['mapper[$i][$k]'].style.display = 'none';\n";
            }
          }
        }
        else {
          // this load section to help mapping if we ran out of saved columns when doing Load Mapping
          $js .= "swapOptions($formName, 'mapper[$i]', 0, 3, 'hs_mapper_" . $i . "_');\n";

          if ($hasHeaders) {
            $defaults["mapper[$i]"] = [$this->defaultFromHeader($columnHeader, $headerPatterns)];
          }
        }
        // End of load mapping.
      }
      else {
        $js .= "swapOptions($formName, 'mapper[$i]', 0, 3, 'hs_mapper_" . $i . "_');\n";
        if ($hasHeaders) {
          // Infer the default from the skipped headers if we have them
          $defaults["mapper[$i]"] = [
            $this->defaultFromHeader($columnHeader, $headerPatterns),
            0,
          ];
        }
      }

      $sel->setOptions([$sel1]);
    }
    $js .= "</script>\n";
    $this->assign('initHideBoxes', $js);

    $this->setDefaults($defaults);

    $this->addFormButtons();
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @return array|bool
   *   list of errors to be posted back to the form
   */
  public static function formRule(array $fields) {
    $errors = [];

    if (!array_key_exists('savedMapping', $fields)) {
      $importKeys = [];
      foreach ($fields['mapper'] as $mapperPart) {
        $importKeys[] = $mapperPart[0];
      }
      $missingFields = self::validateRequiredFields($importKeys);
      if ($missingFields) {
        $errors['_qf_default'] = $missingFields;
      }
    }
    return $errors ?: TRUE;
  }

  /**
   * @return CRM_Activity_Import_Parser_Activity
   */
  protected function getParser(): CRM_Activity_Import_Parser_Activity {
    if (!$this->parser) {
      $this->parser = new CRM_Activity_Import_Parser_Activity();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

  protected function getHighlightedFields(): array {
    $highlightedFields = [];
    $requiredFields = [
      'activity_date_time',
      'activity_type_id',
      'target_contact_id',
      'activity_subject',
    ];
    foreach ($requiredFields as $val) {
      $highlightedFields[] = $val;
    }
    return $highlightedFields;
  }

  public function getImportType(): string {
    return 'Import Activity';
  }

  /**
   * Get the mapping name per the civicrm_mapping_field.type_id option group.
   *
   * @return string
   */
  public function getMappingTypeName(): string {
    return 'Import Activity';
  }

}
