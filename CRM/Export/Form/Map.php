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
 * $Id$
 *
 */

/**
 * This class gets the name of the file to upload
 */
class CRM_Export_Form_Map extends CRM_Core_Form {

  /**
   * Mapper fields
   *
   * @var array
   */
  protected $_mapperFields;

  /**
   * Number of columns in import file
   *
   * @var int
   */
  protected $_exportColumnCount;

  /**
   * Loaded mapping ID
   *
   * @var int
   */
  protected $_mappingId;

  /**
   * Build the form object.
   *
   * @return void
   */
  public function preProcess() {
    $this->_exportColumnCount = $this->get('exportColumnCount');
    $this->_mappingId = $this->get('mappingId');

    if (!$this->_exportColumnCount) {
      // Set default from saved mapping
      if ($this->_mappingId) {
        $mapping = new CRM_Core_DAO_MappingField();
        $mapping->mapping_id = $this->_mappingId;
        $this->_exportColumnCount = $mapping->count();
      }
      else {
        $this->_exportColumnCount = 10;
      }
    }
    else {
      $this->_exportColumnCount += 10;
    }
  }

  public function buildQuickForm() {
    CRM_Core_BAO_Mapping::buildMappingForm($this,
      'Export',
      $this->_mappingId,
      $this->_exportColumnCount,
      2,
      $this->get('exportMode')
    );

    $this->addButtons([
      [
        'type' => 'back',
        'name' => ts('Previous'),
      ],
      [
        'type' => 'next',
        'name' => ts('Export'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
      ],
      [
        'type' => 'done',
        'icon' => 'fa-times',
        'name' => ts('Done'),
      ],
    ]);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @param $values
   * @param int $mappingTypeId
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields, $values, $mappingTypeId) {
    $errors = [];

    if (!empty($fields['saveMapping']) && !empty($fields['_qf_Map_next'])) {
      $nameField = CRM_Utils_Array::value('saveMappingName', $fields);
      if (empty($nameField)) {
        $errors['saveMappingName'] = ts('Name is required to save Export Mapping');
      }
      else {
        //check for Duplicate mappingName
        if (CRM_Core_BAO_Mapping::checkMapping($nameField, $mappingTypeId)) {
          $errors['saveMappingName'] = ts('Duplicate Export Mapping Name');
        }
      }
    }

    if (!empty($errors)) {
      $_flag = 1;
      $assignError = new CRM_Core_Page();
      $assignError->assign('mappingDetailsError', $_flag);
      return $errors;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Process the uploaded file.
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $exportParams = $this->controller->exportValues('Select');

    $currentPath = CRM_Utils_System::currentPath();

    $urlParams = NULL;
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams = "&qfKey=$qfKey";
    }

    //get the button name
    $buttonName = $this->controller->getButtonName('done');
    $buttonName1 = $this->controller->getButtonName('next');
    if ($buttonName == '_qf_Map_done') {
      $this->set('exportColumnCount', NULL);
      $this->controller->resetPage($this->_name);
      return CRM_Utils_System::redirect(CRM_Utils_System::url($currentPath, 'force=1' . $urlParams));
    }

    if ($this->controller->exportValue($this->_name, 'addMore')) {
      $this->set('exportColumnCount', $this->_exportColumnCount);
      return;
    }

    $mapperKeys = $params['mapper'][1];

    $mappedFields = [];
    foreach ((array) $mapperKeys as $field) {
      if (!empty($field[1])) {
        $mappedFields[] = CRM_Core_BAO_Mapping::getMappingParams([], $field);
      }
    }

    if (!$mappedFields) {
      $this->set('mappingId', NULL);
      CRM_Utils_System::redirect(CRM_Utils_System::url($currentPath, '_qf_Map_display=true' . $urlParams));
    }

    if ($buttonName1 == '_qf_Map_next') {
      if (!empty($params['updateMapping'])) {
        //save mapping fields
        CRM_Core_BAO_Mapping::saveMappingFields($params, $params['mappingId']);
      }

      if (!empty($params['saveMapping'])) {
        $mappingParams = [
          'name' => $params['saveMappingName'],
          'description' => $params['saveMappingDesc'],
          'mapping_type_id' => $this->get('mappingTypeId'),
        ];

        $saveMapping = CRM_Core_BAO_Mapping::add($mappingParams);

        //save mapping fields
        CRM_Core_BAO_Mapping::saveMappingFields($params, $saveMapping->id);
      }
    }

    //get the csv file
    CRM_Export_BAO_Export::exportComponents($this->get('selectAll'),
      $this->get('componentIds'),
      (array) $this->get('queryParams'),
      $this->get(CRM_Utils_Sort::SORT_ORDER),
      $mappedFields,
      $this->get('returnProperties'),
      $this->get('exportMode'),
      $this->get('componentClause'),
      $this->get('componentTable'),
      $this->get('mergeSameAddress'),
      $this->get('mergeSameHousehold'),
      $exportParams,
      $this->get('queryOperator')
    );
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Select Fields to Export');
  }

}
