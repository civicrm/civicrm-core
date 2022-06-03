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
 * This class previews the uploaded file and returns summary
 * statistics
 */
class CRM_Member_Import_Form_Preview extends CRM_Import_Form_Preview {

  /**
   * Set variables up before form is built.
   *
   * @return void
   */
  public function preProcess() {
    parent::preProcess();
    $invalidRowCount = $this->get('invalidRowCount');
    if ($invalidRowCount) {
      $urlParams = 'type=' . CRM_Import_Parser::ERROR . '&parser=CRM_Member_Import_Parser_Membership';
      $this->set('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }
    $this->setStatusUrl();
  }

  /**
   * Process the mapped fields and map it into the uploaded file
   * preview the file and extract some summary statistics
   *
   * @return void
   */
  public function postProcess() {
    $fileName = $this->getSubmittedValue('uploadFile');
    $invalidRowCount = $this->get('invalidRowCount');
    $onDuplicate = $this->get('onDuplicate');

    $mapper = $this->controller->exportValue('MapField', 'mapper');

    $parser = $this->getParser();

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
      NULL,
      CRM_Import_Parser::MODE_IMPORT,
      NULL,
      NULL,
      $this->get('statusID')
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
      $urlParams = 'type=' . CRM_Import_Parser::ERROR . '&parser=CRM_Member_Import_Parser_Membership';
      $this->set('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }
  }

  /**
   * @return \CRM_Member_Import_Parser_Membership
   */
  protected function getParser(): CRM_Member_Import_Parser_Membership {
    if (!$this->parser) {
      $this->parser = new CRM_Member_Import_Parser_Membership();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

}
