<?php

/**
 * Class CRM_Custom_Import_Form_MapField
 */
class CRM_Custom_Import_Form_MapField extends CRM_Contact_Import_Form_MapField {
  protected $_parser = 'CRM_Custom_Import_Parser_Api';
  protected $_mappingType = 'Import Multi value custom data';
  /**
   * Entity being imported to.
   * @var string
   */
  protected $_entity;

  /**
   * Set variables up before form is built.
   *
   * @return void
   */
  public function preProcess() {
    $this->_mapperFields = $this->get('fields');
    asort($this->_mapperFields);
    $this->_columnCount = $this->get('columnCount');
    $this->assign('columnCount', $this->_columnCount);
    $this->_dataValues = $this->get('dataValues');
    $highlightedFields = ['contact_id', 'external_identifier'];

    //Separate column names from actual values.
    $columnNames = $this->_dataValues[0];
    //actual values need to be in 2d array ($array[$i][$j]) format to be parsed by the template.
    $dataValues[] = $this->_dataValues[1];
    $this->assign('dataValues', $dataValues);

    $this->_entity = $this->_multipleCustomData = $this->get('multipleCustomData');
    $skipColumnHeader = $this->controller->exportValue('DataSource', 'skipColumnHeader');
    $this->_onDuplicate = $this->get('onDuplicate');
    if ($skipColumnHeader) {
      //showColNames needs to be true to show "Column Names" column
      $this->assign('showColNames', $skipColumnHeader);
      $this->assign('columnNames', $columnNames);
      /* if we had a column header to skip, stash it for later */
      $this->_columnHeaders = $this->_dataValues[0];
    }
    $this->assign('rowDisplayCount', 2);
    $this->assign('highlightedFields', $highlightedFields);
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->addFormRule(['CRM_Custom_Import_Form_MapField', 'formRule']);
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
    $fieldMessage = NULL;
    if (!array_key_exists('savedMapping', $fields)) {
      $importKeys = [];
      foreach ($fields['mapper'] as $mapperPart) {
        $importKeys[] = $mapperPart[0];
      }

      // check either contact id or external identifier
      if (!in_array('contact_id', $importKeys) && !in_array('external_identifier', $importKeys)) {
        if (!isset($errors['_qf_default'])) {
          $errors['_qf_default'] = '';
        }
        $errors['_qf_default'] .= ts('Missing required field: %1', [1 => ts('Contact ID or External Identifier')]);
      }
    }

    if (!empty($fields['saveMapping'])) {
      $nameField = CRM_Utils_Array::value('saveMappingName', $fields);
      if (empty($nameField)) {
        $errors['saveMappingName'] = ts('Name is required to save Import Mapping');
      }
      else {
        if (CRM_Core_BAO_Mapping::checkMapping($nameField, CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Mapping', 'mapping_type_id', 'Import Multi value custom data'))) {
          $errors['saveMappingName'] = ts('Duplicate ' . $self->_mappingType . 'Mapping Name');
        }
      }
    }

    //display Error if loaded mapping is not selected
    if (array_key_exists('loadMapping', $fields)) {
      $getMapName = CRM_Utils_Array::value('savedMapping', $fields);
      if (empty($getMapName)) {
        $errors['savedMapping'] = ts('Select saved mapping');
      }
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
   * preview the file and extract some summary statistics
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->controller->exportValues('MapField');
    $this->set('multipleCustomData', $this->_multipleCustomData);

    //reload the mapfield if load mapping is pressed
    if (!empty($params['savedMapping'])) {
      $this->set('savedMapping', $params['savedMapping']);
      $this->controller->resetPage($this->_name);
      return;
    }

    $fileName = $this->controller->exportValue('DataSource', 'uploadFile');
    $separator = $this->controller->exportValue('DataSource', 'fieldSeparator');
    $skipColumnHeader = $this->controller->exportValue('DataSource', 'skipColumnHeader');
    $this->_entity = $this->controller->exportValue('DataSource', 'entity');

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
    $this->set('loadMappingId', CRM_Utils_Array::value('mappingId', $params));

    //Updating Mapping Records
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

        $explodedValues = explode('_', $mapperKeys[$i][0]);
        $id = CRM_Utils_Array::value(0, $explodedValues);
        $first = CRM_Utils_Array::value(1, $explodedValues);
        $second = CRM_Utils_Array::value(2, $explodedValues);

        $updateMappingFields->name = $mapper[$i];
        $updateMappingFields->save();
      }
    }

    //Saving Mapping Details and Records
    if (!empty($params['saveMapping'])) {
      $mappingParams = [
        'name' => $params['saveMappingName'],
        'description' => $params['saveMappingDesc'],
        'mapping_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Mapping', 'mapping_type_id', $this->_mappingType),
      ];
      $saveMapping = CRM_Core_BAO_Mapping::add($mappingParams);

      for ($i = 0; $i < $this->_columnCount; $i++) {
        $saveMappingFields = new CRM_Core_DAO_MappingField();
        $saveMappingFields->mapping_id = $saveMapping->id;
        $saveMappingFields->column_number = $i;

        $explodedValues = explode('_', $mapperKeys[$i][0]);
        $id = CRM_Utils_Array::value(0, $explodedValues);
        $first = CRM_Utils_Array::value(1, $explodedValues);
        $second = CRM_Utils_Array::value(2, $explodedValues);

        $saveMappingFields->name = $mapper[$i];
        $saveMappingFields->save();
      }
      $this->set('savedMapping', $saveMappingFields->mapping_id);
    }
    $this->set('_entity', $this->_entity);

    $parser = new $this->_parser($mapperKeysMain);
    $parser->setEntity($this->_multipleCustomData);
    $parser->run($fileName, $separator, $mapper, $skipColumnHeader,
      CRM_Import_Parser::MODE_PREVIEW, $this->get('contactType')
    );
    // add all the necessary variables to the form
    $parser->set($this);
  }

}
