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
 * This class acts like a psuedo-BAO for transient import job tables.
 */
class CRM_Contact_Import_ImportJob {

  protected $_onDuplicate;
  protected $_dedupe;
  protected $_newGroupName;
  protected $_groups;
  protected $_allGroups;
  protected $_newTagName;
  protected $_tag;

  protected $_mapper;
  protected $_mapperKeys = [];
  protected $_mapFields;

  /**
   * @var CRM_Contact_Import_Parser_Contact
   */
  protected $_parser;

  protected $_userJobID;

  /**
   * Has the job completed.
   *
   * @return bool
   */
  public function isComplete(): bool {
    return $this->_parser->isComplete();
  }

  /**
   * @param array $params
   */
  public function setJobParams(&$params) {
    foreach ($params as $param => $value) {
      $fldName = "_$param";
      $this->$fldName = $value;
    }
  }

  /**
   * @param CRM_Core_Form $form
   * @param int $timeout
   */
  public function runImport(&$form, $timeout = 55) {
    $mapper = $this->_mapper;
    foreach ($mapper as $key => $value) {
      $this->_mapperKeys[$key] = $mapper[$key][0] ?? NULL;
    }

  }

  /**
   * @param $form
   */
  public function setFormVariables($form) {
    $this->_parser->set($form, CRM_Import_Parser::MODE_IMPORT);
  }

}
