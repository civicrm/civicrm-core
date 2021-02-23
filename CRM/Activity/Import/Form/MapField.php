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
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    $this->_mapperFields = $this->get('fields');
    unset($this->_mapperFields['id']);
    asort($this->_mapperFields);

    $this->_columnCount = $this->get('columnCount');
    $this->assign('columnCount', $this->_columnCount);
    $this->_dataValues = $this->get('dataValues');
    $this->assign('dataValues', $this->_dataValues);

    $skipColumnHeader = $this->controller->exportValue('DataSource', 'skipColumnHeader');

    if ($skipColumnHeader) {
      $this->assign('skipColumnHeader', $skipColumnHeader);
      $this->assign('rowDisplayCount', 3);
      // If we had a column header to skip, stash it for later.

      $this->_columnHeaders = $this->_dataValues[0];
    }
    else {
      $this->assign('rowDisplayCount', 2);
    }
    $highlightedFields = [];
    $requiredFields = [
      'activity_date_time',
      'activity_type_id',
      'activity_label',
      'target_contact_id',
      'activity_subject',
    ];
    foreach ($requiredFields as $val) {
      $highlightedFields[] = $val;
    }
    $this->assign('highlightedFields', $highlightedFields);
  }

  /**
   * Build the form object.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function buildQuickForm() {
    // To save the current mappings.
    if (!$this->get('savedMapping')) {
      $saveDetailsName = ts('Save this field mapping');
      $this->applyFilter('saveMappingName', 'trim');
      $this->add('text', 'saveMappingName', ts('Name'));
      $this->add('text', 'saveMappingDesc', ts('Description'));
    }
    else {
      $savedMapping = $this->get('savedMapping');
      // Mapping is to be loaded from database.

      // Get an array of the name values for mapping fields associated with this mapping_id.
      $mappingName = CRM_Core_BAO_Mapping::getMappingFieldValues($savedMapping, 'name');

      $this->assign('loadedMapping', $savedMapping);
      $this->set('loadedMapping', $savedMapping);

      $params = ['id' => $savedMapping];
      $temp = [];
      $mappingDetails = CRM_Core_BAO_Mapping::retrieve($params, $temp);

      $this->assign('savedName', $mappingDetails->name);

      $this->add('hidden', 'mappingId', $savedMapping);

      $this->addElement('checkbox', 'updateMapping', ts('Update this field mapping'), NULL);
      $saveDetailsName = ts('Save as a new field mapping');
      $this->add('text', 'saveMappingName', ts('Name'));
      $this->add('text', 'saveMappingDesc', ts('Description'));
    }

    $this->addElement('checkbox', 'saveMapping', $saveDetailsName, NULL, ['onclick' => "showSaveDetails(this)"]);

    $this->addFormRule(['CRM_Activity_Import_Form_MapField', 'formRule']);

    //-------- end of saved mapping stuff ---------

    $defaults = [];
    $mapperKeys = array_keys($this->_mapperFields);

    $hasHeaders = !empty($this->_columnHeaders);
    $headerPatterns = $this->get('headerPatterns');
    $dataPatterns = $this->get('dataPatterns');

    // Initialize all field usages to false.

    foreach ($mapperKeys as $key) {
      $this->_fieldUsed[$key] = FALSE;
    }
    $sel1 = $this->_mapperFields;

    $js = "<script type='text/javascript'>\n";
    $formName = 'document.forms.' . $this->_name;

    // Used to warn for mismatch column count or mismatch mapping.
    $warning = 0;

    for ($i = 0; $i < $this->_columnCount; $i++) {
      $sel = &$this->addElement('hierselect', "mapper[$i]", ts('Mapper for Field %1', [1 => $i]), NULL);
      $jsSet = FALSE;
      if ($this->get('savedMapping')) {
        if (isset($mappingName[$i])) {
          if ($mappingName[$i] != ts('- do not import -')) {

            $mappingHeader = array_keys($this->_mapperFields, $mappingName[$i]);

            $js .= "{$formName}['mapper[$i][3]'].style.display = 'none';\n";
            $defaults["mapper[$i]"] = [
              $mappingHeader[0],
              '',
              '',
            ];
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
            $defaults["mapper[$i]"] = [$this->defaultFromHeader($this->_columnHeaders[$i], $headerPatterns)];
          }
          else {
            $defaults["mapper[$i]"] = [$this->defaultFromData($dataPatterns, $i)];
          }
        }
        // End of load mapping.
      }
      else {
        $js .= "swapOptions($formName, 'mapper[$i]', 0, 3, 'hs_mapper_" . $i . "_');\n";
        if ($hasHeaders) {
          // Infer the default from the skipped headers if we have them
          $defaults["mapper[$i]"] = [
            $this->defaultFromHeader($this->_columnHeaders[$i], $headerPatterns),
            0,
          ];
        }
        else {
          // Otherwise guess the default from the form of the data
          $defaults["mapper[$i]"] = [$this->defaultFromData($dataPatterns, $i), 0];
        }
      }

      $sel->setOptions([
        $sel1,
        ['' => NULL],
        '',
        '',
      ]);
    }
    $js .= "</script>\n";
    $this->assign('initHideBoxes', $js);

    // Set warning if mismatch in more than.
    if (isset($mappingName)) {
      if (($this->_columnCount != count($mappingName))) {
        $warning++;
      }
    }
    if ($warning != 0 && $this->get('savedMapping')) {
      CRM_Core_Session::singleton()->setStatus(ts('The data columns in this import file appear to be different from the saved mapping. Please verify that you have selected the correct saved mapping before continuing.'));
    }
    else {
      CRM_Core_Session::singleton()->setStatus(NULL);
    }

    $this->setDefaults($defaults);

    $this->addButtons([
        [
          'type' => 'back',
          'name' => ts('Previous'),
        ],
        [
          'type' => 'next',
          'name' => ts('Continue'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
    ]
    );
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields) {
    $errors = [];
    // define so we avoid notices below
    $errors['_qf_default'] = '';

    $fieldMessage = NULL;
    if (!array_key_exists('savedMapping', $fields)) {
      $importKeys = [];
      foreach ($fields['mapper'] as $mapperPart) {
        $importKeys[] = $mapperPart[0];
      }
      // FIXME: should use the schema titles, not redeclare them
      $requiredFields = [
        'target_contact_id' => ts('Contact ID'),
        'activity_date_time' => ts('Activity Date'),
        'activity_subject' => ts('Activity Subject'),
        'activity_type_id' => ts('Activity Type ID'),
      ];

      $contactFieldsBelowWeightMessage = self::validateRequiredContactMatchFields('Individual', $importKeys);
      foreach ($requiredFields as $field => $title) {
        if (!in_array($field, $importKeys)) {
          if ($field === 'target_contact_id') {
            if (!$contactFieldsBelowWeightMessage || in_array('external_identifier', $importKeys)) {
              continue;
            }
            else {
              $errors['_qf_default'] .= ts('Missing required contact matching fields.')
                . $contactFieldsBelowWeightMessage
                . '<br />';
            }
          }
          elseif ($field === 'activity_type_id') {
            if (in_array('activity_label', $importKeys)) {
              continue;
            }
            else {
              $errors['_qf_default'] .= ts('Missing required field: Provide %1 or %2',
                  [
                    1 => $title,
                    2 => 'Activity Type Label',
                  ]) . '<br />';
            }
          }
          else {
            $errors['_qf_default'] .= ts('Missing required field: %1', [1 => $title]) . '<br />';
          }
        }
      }
    }

    if (!empty($fields['saveMapping'])) {
      $nameField = $fields['saveMappingName'] ?? NULL;
      if (empty($nameField)) {
        $errors['saveMappingName'] = ts('Name is required to save Import Mapping');
      }
      else {
        if (CRM_Core_BAO_Mapping::checkMapping($nameField, CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Mapping', 'mapping_type_id', 'Import Activity'))) {
          $errors['saveMappingName'] = ts('Duplicate Import Mapping Name');
        }
      }
    }

    if (empty($errors['_qf_default'])) {
      unset($errors['_qf_default']);
    }
    if (!empty($errors)) {
      if (!empty($errors['saveMappingName'])) {
        $_flag = 1;
        $assignError = new CRM_Core_Page();
        $assignError->assign('mappingDetailsError', $_flag);
      }
      return $errors;
    }

    return TRUE;
  }

  /**
   * Process the mapped fields and map it into the uploaded file.
   *
   * Preview the file and extract some summary statistics
   */
  public function postProcess() {
    $params = $this->controller->exportValues('MapField');
    // Reload the mapfield if load mapping is pressed.
    if (!empty($params['savedMapping'])) {
      $this->set('savedMapping', $params['savedMapping']);
      $this->controller->resetPage($this->_name);
      return;
    }

    $fileName = $this->controller->exportValue('DataSource', 'uploadFile');
    $separator = $this->controller->exportValue('DataSource', 'fieldSeparator');
    $skipColumnHeader = $this->controller->exportValue('DataSource', 'skipColumnHeader');

    $mapperKeys = [];
    $mapper = [];
    $mapperKeys = $this->controller->exportValue($this->_name, 'mapper');
    $mapperKeysMain = [];

    for ($i = 0; $i < $this->_columnCount; $i++) {
      $mapper[$i] = $this->_mapperFields[$mapperKeys[$i][0]];
      $mapperKeysMain[$i] = $mapperKeys[$i][0];
    }

    $this->set('mapper', $mapper);
    // store mapping Id to display it in the preview page
    if (!empty($params['mappingId'])) {
      $this->set('loadMappingId', $params['mappingId']);
    }

    // Updating Mapping Records.
    if (!empty($params['updateMapping'])) {

      $mappingFields = new CRM_Core_DAO_MappingField();
      $mappingFields->mapping_id = $params['mappingId'];
      $mappingFields->find();

      $mappingFieldsId = [];
      while ($mappingFields->fetch()) {
        if ($mappingFields->id) {
          $mappingFieldsId[$mappingFields->column_number] = $mappingFields->id;
        }
      }

      for ($i = 0; $i < $this->_columnCount; $i++) {
        $updateMappingFields = new CRM_Core_DAO_MappingField();
        $updateMappingFields->id = $mappingFieldsId[$i];
        $updateMappingFields->mapping_id = $params['mappingId'];
        $updateMappingFields->column_number = $i;

        $updateMappingFields->name = $mapper[$i];
        $updateMappingFields->save();
      }
    }

    // Saving Mapping Details and Records.
    if (!empty($params['saveMapping'])) {
      $mappingParams = [
        'name' => $params['saveMappingName'],
        'description' => $params['saveMappingDesc'],
        'mapping_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Mapping', 'mapping_type_id', 'Import Activity'),
      ];
      $saveMapping = CRM_Core_BAO_Mapping::add($mappingParams);

      for ($i = 0; $i < $this->_columnCount; $i++) {
        $saveMappingFields = new CRM_Core_DAO_MappingField();
        $saveMappingFields->mapping_id = $saveMapping->id;
        $saveMappingFields->column_number = $i;

        $saveMappingFields->name = $mapper[$i];
        $saveMappingFields->save();
      }
      $this->set('savedMapping', $saveMappingFields->mapping_id);
    }

    $parser = new CRM_Activity_Import_Parser_Activity($mapperKeysMain);
    $parser->run($fileName, $separator, $mapper, $skipColumnHeader,
      CRM_Import_Parser::MODE_PREVIEW
    );

    // add all the necessary variables to the form
    $parser->set($this);
  }

}
