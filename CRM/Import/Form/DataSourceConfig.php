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
 * This class allows datasource specific fields to be added to the datasource form.
 */
class CRM_Import_Form_DataSourceConfig extends CRM_Import_Forms {

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess(): void {
    $dataSourcePath = explode('_', $this->getDataSourceClassName());
    $templateFile = 'CRM/Contact/Import/Form/' . $dataSourcePath[3] . '.tpl';
    $this->assign('dataSourceFormTemplateFile', $templateFile ?? NULL);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm(): void {
    $this->buildDataSourceFields();
  }

}
