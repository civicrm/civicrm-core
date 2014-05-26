<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Import_DataSource_SQL extends CRM_Import_DataSource {

  /**
   * Provides information about the data source
   *
   * @return array collection of info about this data source
   *
   * @access public
   *
   */
  public function getInfo() {
    return array('title' => ts('SQL Query'));
  }

  /**
   * Function to set variables up before form is built
   *
   * @access public
   */
  public function preProcess(&$form) {}

  /**
   * This is function is called by the form object to get the DataSource's
   * form snippet. It should add all fields necesarry to get the data
   * uploaded to the temporary table in the DB.
   *
   * @param $form
   *
   * @return void (operates directly on form argument)
   * @access public
   */
  public function buildQuickForm(&$form) {
    $form->add('hidden', 'hidden_dataSource', 'CRM_Import_DataSource_SQL');
    $form->add('textarea', 'sqlQuery', ts('Specify SQL Query'), 'rows=10 cols=45', TRUE);
    $form->addFormRule(array('CRM_Import_DataSource_SQL', 'formRule'), $form);
  }

  /**
   * @param $fields
   * @param $files
   * @param $form
   *
   * @return array|bool
   */
  static function formRule($fields, $files, $form) {
    $errors = array();

    // poor man's query validation (case-insensitive regex matching on word boundaries)
    $forbidden = array('ALTER', 'CREATE', 'DELETE', 'DESCRIBE', 'DROP', 'SHOW', 'UPDATE', 'information_schema');
    foreach ($forbidden as $pattern) {
      if (preg_match("/\\b$pattern\\b/i", $fields['sqlQuery'])) {
        $errors['sqlQuery'] = ts('The query contains the forbidden %1 command.', array(1 => $pattern));
      }
    }

    return $errors ? $errors : TRUE;
  }

  /**
   * Function to process the form
   *
   * @access public
   */
  public function postProcess(&$params, &$db, &$form) {
    $importJob = new CRM_Contact_Import_ImportJob(
      CRM_Utils_Array::value( 'import_table_name', $params ),
      $params['sqlQuery'], true
    );

    $form->set('importTableName', $importJob->getTableName());
  }
}

