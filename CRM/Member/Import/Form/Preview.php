<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
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
    $skipColumnHeader = $this->controller->exportValue('DataSource', 'skipColumnHeader');

    //get the data from the session
    $dataValues = $this->get('dataValues');
    $mapper = $this->get('mapper');
    $invalidRowCount = $this->get('invalidRowCount');
    $conflictRowCount = $this->get('conflictRowCount');
    $mismatchCount = $this->get('unMatchCount');

    //get the mapping name displayed if the mappingId is set
    $mappingId = $this->get('loadMappingId');
    if ($mappingId) {
      $mapDAO = new CRM_Core_DAO_Mapping();
      $mapDAO->id = $mappingId;
      $mapDAO->find(TRUE);
      $this->assign('loadedMapping', $mappingId);
      $this->assign('savedName', $mapDAO->name);
    }

    if ($skipColumnHeader) {
      $this->assign('skipColumnHeader', $skipColumnHeader);
      $this->assign('rowDisplayCount', 3);
    }
    else {
      $this->assign('rowDisplayCount', 2);
    }

    if ($invalidRowCount) {
      $urlParams = 'type=' . CRM_Import_Parser::ERROR . '&parser=CRM_Member_Import_Parser';
      $this->set('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }

    if ($conflictRowCount) {
      $urlParams = 'type=' . CRM_Import_Parser::CONFLICT . '&parser=CRM_Member_Import_Parser';
      $this->set('downloadConflictRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }

    if ($mismatchCount) {
      $urlParams = 'type=' . CRM_Import_Parser::NO_MATCH . '&parser=CRM_Member_Import_Parser';
      $this->set('downloadMismatchRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }

    $properties = array(
      'mapper',
      'dataValues',
      'columnCount',
      'totalRowCount',
      'validRowCount',
      'invalidRowCount',
      'conflictRowCount',
      'downloadErrorRecordsUrl',
      'downloadConflictRecordsUrl',
      'downloadMismatchRecordsUrl',
    );

    foreach ($properties as $property) {
      $this->assign($property, $this->get($property));
    }
  }

  /**
   * Process the mapped fields and map it into the uploaded file
   * preview the file and extract some summary statistics
   *
   * @return void
   */
  public function postProcess() {
    $fileName = $this->controller->exportValue('DataSource', 'uploadFile');
    $skipColumnHeader = $this->controller->exportValue('DataSource', 'skipColumnHeader');
    $invalidRowCount = $this->get('invalidRowCount');
    $conflictRowCount = $this->get('conflictRowCount');
    $onDuplicate = $this->get('onDuplicate');

    $config = CRM_Core_Config::singleton();
    $seperator = $config->fieldSeparator;

    $mapper = $this->controller->exportValue('MapField', 'mapper');
    $mapperKeys = array();
    $mapperLocType = array();
    $mapperPhoneType = array();
    // Note: we keep the multi-dimension array (even thought it's not
    // needed in the case of memberships import) so that we can merge
    // the common code with contacts import later and subclass contact
    // and membership imports from there
    foreach ($mapper as $key => $value) {
      $mapperKeys[$key] = $mapper[$key][0];

      if (!empty($mapper[$key][1]) && is_numeric($mapper[$key][1])) {
        $mapperLocType[$key] = $mapper[$key][1];
      }
      else {
        $mapperLocType[$key] = NULL;
      }

      if (!empty($mapper[$key][2]) && (!is_numeric($mapper[$key][2]))) {
        $mapperPhoneType[$key] = $mapper[$key][2];
      }
      else {
        $mapperPhoneType[$key] = NULL;
      }
    }

    $parser = new CRM_Member_Import_Parser_Membership($mapperKeys, $mapperLocType, $mapperPhoneType);

    $mapFields = $this->get('fields');

    foreach ($mapper as $key => $value) {
      $header = array();
      if (isset($mapFields[$mapper[$key][0]])) {
        $header[] = $mapFields[$mapper[$key][0]];
      }
      $mapperFields[] = implode(' - ', $header);
    }
    $parser->run($fileName, $seperator,
      $mapperFields,
      $skipColumnHeader,
      CRM_Import_Parser::MODE_IMPORT,
      $this->get('contactType'),
      $onDuplicate
    );

    // add all the necessary variables to the form
    $parser->set($this, CRM_Import_Parser::MODE_IMPORT);

    // check if there is any error occured

    $errorStack = CRM_Core_Error::singleton();
    $errors = $errorStack->getErrors();
    $errorMessage = array();

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
      $urlParams = 'type=' . CRM_Import_Parser::ERROR . '&parser=CRM_Member_Import_Parser';
      $this->set('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
      $urlParams = 'type=' . CRM_Import_Parser::CONFLICT . '&parser=CRM_Member_Import_Parser';
      $this->set('downloadConflictRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
      $urlParams = 'type=' . CRM_Import_Parser::NO_MATCH . '&parser=CRM_Member_Import_Parser';
      $this->set('downloadMismatchRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }
  }

}
