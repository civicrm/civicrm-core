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
 * This class previews the uploaded file and returns summary statistics.
 */
class CRM_Contribute_Import_Form_Preview extends CRM_Import_Form_Preview {

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    parent::preProcess();
    //get the data from the session
    $dataValues = $this->get('dataValues');
    $mapper = $this->get('mapper');
    $softCreditFields = $this->get('softCreditFields');
    $mapperSoftCreditType = $this->get('mapperSoftCreditType');
    $invalidRowCount = $this->get('invalidRowCount');
    $mismatchCount = $this->get('unMatchCount');

    //get the mapping name displayed if the mappingId is set
    $mappingId = $this->get('loadMappingId');
    if ($mappingId) {
      $mapDAO = new CRM_Core_DAO_Mapping();
      $mapDAO->id = $mappingId;
      $mapDAO->find(TRUE);
    }
    $this->assign('savedMappingName', $mappingId ? $mapDAO->name : NULL);

    if ($invalidRowCount) {
      $urlParams = 'type=' . CRM_Import_Parser::ERROR . '&parser=CRM_Contribute_Import_Parser_Contribution';
      $this->set('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }

    if ($mismatchCount) {
      $urlParams = 'type=' . CRM_Import_Parser::NO_MATCH . '&parser=CRM_Contribute_Import_Parser_Contribution';
      $this->set('downloadMismatchRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }

    $properties = [
      'mapper',
      'softCreditFields',
      'mapperSoftCreditType',
      'dataValues',
      'columnCount',
      'totalRowCount',
      'validRowCount',
      'invalidRowCount',
      'downloadErrorRecordsUrl',
      'downloadMismatchRecordsUrl',
    ];
    $this->setStatusUrl();

    foreach ($properties as $property) {
      $this->assign($property, $this->get($property));
    }
  }

  /**
   * Process the mapped fields and map it into the uploaded file preview the file and extract some summary statistics.
   */
  public function postProcess() {
    $fileName = $this->controller->exportValue('DataSource', 'uploadFile');
    $invalidRowCount = $this->get('invalidRowCount');
    $onDuplicate = $this->get('onDuplicate');
    $mapperSoftCreditType = $this->get('mapperSoftCreditType');

    $mapper = $this->controller->exportValue('MapField', 'mapper');
    $mapperKeys = [];
    $mapperSoftCredit = [];
    $mapperPhoneType = [];

    foreach ($mapper as $key => $value) {
      $mapperKeys[$key] = $mapper[$key][0];
      if (isset($mapper[$key][0]) && $mapper[$key][0] == 'soft_credit' && isset($mapper[$key])) {
        $mapperSoftCredit[$key] = $mapper[$key][1] ?? '';
        $mapperSoftCreditType[$key] = $mapperSoftCreditType[$key]['value'];
      }
      else {
        $mapperSoftCredit[$key] = $mapperSoftCreditType[$key] = NULL;
      }
    }

    $parser = new CRM_Contribute_Import_Parser_Contribution($mapperKeys, $mapperSoftCredit, $mapperPhoneType, $mapperSoftCreditType);

    $mapFields = $this->get('fields');

    foreach ($mapper as $key => $value) {
      $header = [];
      if (isset($mapFields[$mapper[$key][0]])) {
        $header[] = $mapFields[$mapper[$key][0]];
      }
      $mapperFields[] = implode(' - ', $header);
    }
    $parser->run(
      $this->getSubmittedValue('uploadFile'),
      $this->getSubmittedValue('fieldSeparator'),
      $mapperFields,
      $this->getSubmittedValue('skipColumnHeader'),
      CRM_Import_Parser::MODE_IMPORT,
      $this->get('contactType'),
      $onDuplicate,
      $this->get('statusID'),
      $this->get('totalRowCount')
    );

    // Add all the necessary variables to the form.
    $parser->set($this, CRM_Import_Parser::MODE_IMPORT);

    // Check if there is any error occurred.

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
      $urlParams = 'type=' . CRM_Import_Parser::ERROR . '&parser=CRM_Contribute_Import_Parser_Contribution';
      $this->set('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
      $urlParams = 'type=' . CRM_Import_Parser::NO_MATCH . '&parser=CRM_Contribute_Import_Parser_Contribution';
      $this->set('downloadMismatchRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }
  }

}
