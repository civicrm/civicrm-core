<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
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

    $this->addButtons(array(
        array(
          'type' => 'back',
          'name' => ts('Previous'),
        ),
        array(
          'type' => 'next',
          'name' => ts('Export'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        ),
        array(
          'type' => 'done',
          'icon' => 'fa-times',
          'name' => ts('Done'),
        ),
      )
    );
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
    $errors = array();

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

    $greetingOptions = CRM_Export_Form_Select::getGreetingOptions();

    if (!empty($greetingOptions)) {
      foreach ($greetingOptions as $key => $value) {
        if ($option = CRM_Utils_Array::value($key, $exportParams)) {
          if ($greetingOptions[$key][$option] == ts('Other')) {
            $exportParams[$key] = $exportParams["{$key}_other"];
          }
          elseif ($greetingOptions[$key][$option] == ts('List of names')) {
            $exportParams[$key] = '';
          }
          else {
            $exportParams[$key] = $greetingOptions[$key][$option];
          }
        }
      }
    }

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

    $checkEmpty = 0;
    foreach ($mapperKeys as $value) {
      if ($value[0]) {
        $checkEmpty++;
      }
    }

    if (!$checkEmpty) {
      $this->set('mappingId', NULL);
      CRM_Utils_System::redirect(CRM_Utils_System::url($currentPath, '_qf_Map_display=true' . $urlParams));
    }

    if ($buttonName1 == '_qf_Map_next') {
      if (!empty($params['updateMapping'])) {
        //save mapping fields
        CRM_Core_BAO_Mapping::saveMappingFields($params, $params['mappingId']);
      }

      if (!empty($params['saveMapping'])) {
        $mappingParams = array(
          'name' => $params['saveMappingName'],
          'description' => $params['saveMappingDesc'],
          'mapping_type_id' => $this->get('mappingTypeId'),
        );

        $saveMapping = CRM_Core_BAO_Mapping::add($mappingParams);

        //save mapping fields
        CRM_Core_BAO_Mapping::saveMappingFields($params, $saveMapping->id);
      }
    }

    //get the csv file
    CRM_Export_BAO_Export::exportComponents($this->get('selectAll'),
      $this->get('componentIds'),
      $this->get('queryParams'),
      $this->get(CRM_Utils_Sort::SORT_ORDER),
      $mapperKeys,
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
