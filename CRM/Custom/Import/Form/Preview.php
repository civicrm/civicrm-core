<?php

/**
 * Class CRM_Custom_Import_Form_Preview
 */
class CRM_Custom_Import_Form_Preview extends CRM_Import_Form_Preview {
  public $_parser = 'CRM_Custom_Import_Parser_Api';
  protected $_importParserUrl = '&parser=CRM_Custom_Import_Parser_Api';

  /**
   * Set variables up before form is built.
   *
   * @return void
   */
  public function preProcess() {
    parent::preProcess();
    //get the data from the session
    $dataValues = $this->get('dataValues');
    $mapper = $this->get('mapper');
    $invalidRowCount = $this->get('invalidRowCount');
    $mismatchCount = $this->get('unMatchCount');
    $entity = $this->get('_entity');

    //get the mapping name displayed if the mappingId is set
    $mappingId = $this->get('loadMappingId');
    if ($mappingId) {
      $mapDAO = new CRM_Core_DAO_Mapping();
      $mapDAO->id = $mappingId;
      $mapDAO->find(TRUE);
    }
    $this->assign('savedMappingName', $mappingId ? $mapDAO->name : NULL);

    if ($invalidRowCount) {
      $urlParams = 'type=' . CRM_Import_Parser::ERROR . $this->_importParserUrl;
      $this->set('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }

    if ($mismatchCount) {
      $urlParams = 'type=' . CRM_Import_Parser::NO_MATCH . $this->_importParserUrl;
      $this->set('downloadMismatchRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }

    $properties = [
      'mapper',
      'dataValues',
      'columnCount',
      'totalRowCount',
      'validRowCount',
      'invalidRowCount',
      'downloadErrorRecordsUrl',
      'downloadMismatchRecordsUrl',
    ];

    foreach ($properties as $property) {
      $this->assign($property, $this->get($property));
    }
  }

  /**
   * Process the mapped fields and map it into the uploaded file.
   * preview the file and extract some summary statistics
   *
   * @return void
   */
  public function postProcess() {
    $fileName = $this->getSubmittedValue('uploadFile');
    $invalidRowCount = $this->get('invalidRowCount');
    $onDuplicate = $this->get('onDuplicate');
    $entity = $this->get('_entity');

    $mapper = $this->controller->exportValue('MapField', 'mapper');
    $mapperKeys = [];

    foreach ($mapper as $key => $value) {
      $mapperKeys[$key] = $mapper[$key][0];
    }

    $parser = new $this->_parser($mapperKeys);
    $parser->setEntity($entity);

    $mapFields = $this->get('fields');

    foreach ($mapper as $key => $value) {
      $header = [];
      if (isset($mapFields[$mapper[$key][0]])) {
        $header[] = $mapFields[$mapper[$key][0]];
      }
      $mapperFields[] = implode(' - ', $header);
    }
    $parser->run($this->getSubmittedValue('uploadFile'), $this->getSubmittedValue('fieldSeparator'),
      $mapperFields,
      $this->getSubmittedValue('skipColumnHeader'),
      CRM_Import_Parser::MODE_IMPORT,
      $this->get('contactType'),
      $onDuplicate
    );

    // add all the necessary variables to the form
    $parser->set($this, CRM_Import_Parser::MODE_IMPORT);

    // check if there is any error occurred

    $errorStack = CRM_Core_Error::singleton();
    $errors = $errorStack->getErrors();
    $errorMessage = [];

    if (is_array($errors)) {
      foreach ($errors as $key => $value) {
        $errorMessage[] = $value['message'];
      }

      $errorFile = $fileName['name'] . '.error.log';

      if ($fd = fopen($errorFile, 'w')) {
        fwrite($fd, implode('\n', $errorMessage));
      }
      fclose($fd);

      $this->set('errorFile', $errorFile);
      $urlParams = 'type=' . CRM_Import_Parser::ERROR . $this->_importParserUrl;
      $this->set('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
      $urlParams = 'type=' . CRM_Import_Parser::NO_MATCH . $this->_importParserUrl;
      $this->set('downloadMismatchRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }
  }

}
