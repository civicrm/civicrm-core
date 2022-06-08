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
    $invalidRowCount = $this->getRowCount(CRM_Import_Parser::VALID);

    $downloadURL = '';
    if ($invalidRowCount) {
      $urlParams = 'type=' . CRM_Import_Parser::ERROR . '&parser=CRM_Contribute_Import_Parser_Contribution';
      $downloadURL = CRM_Utils_System::url('civicrm/export', $urlParams);
    }

    $this->setStatusUrl();
    $this->assign('downloadErrorRecordsUrl', $downloadURL);
  }

  /**
   * Get the mapped fields as an array of labels.
   *
   * e.g
   * ['First Name', 'Employee Of - First Name', 'Home - Street Address']
   *
   * @return array
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  protected function getMappedFieldLabels(): array {
    $mapper = [];
    $parser = $this->getParser();
    foreach ($this->getSubmittedValue('mapper') as $columnNumber => $mappedField) {
      $mapper[$columnNumber] = $parser->getMappedFieldLabel($parser->getMappingFieldFromMapperInput($mappedField, 0, $columnNumber));
    }
    return $mapper;
  }

  /**
   * @return \CRM_Contribute_Import_Parser_Contribution
   */
  protected function getParser(): CRM_Contribute_Import_Parser_Contribution {
    if (!$this->parser) {
      $this->parser = new CRM_Contribute_Import_Parser_Contribution();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

}
