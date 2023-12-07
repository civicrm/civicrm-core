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
   * Default values for datasource fields.
   *
   * @var array
   */
  protected $dataSourceDefaults = [];

  /**
   * Set dataSource default values.
   *
   * @param array $dataSourceDefaults
   *
   * @return CRM_Import_Form_DataSourceConfig
   */
  public function setDataSourceDefaults(array $dataSourceDefaults): CRM_Import_Form_DataSourceConfig {
    $this->dataSourceDefaults = $dataSourceDefaults;
    return $this;
  }

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess(): void {
    $this->assign('dataSourceFormTemplateFile', $this->getDataSourceObject()->getInfo()['template']);
    if (CRM_Utils_Request::retrieveValue('user_job_id', 'Integer')) {
      $this->setUserJobID(CRM_Utils_Request::retrieveValue('user_job_id', 'Integer'));
    }
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    $this->buildDataSourceFields();
  }

  /**
   * Set defaults.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public function setDefaultValues() {
    $defaults = [];
    if ($this->userJobID) {
      foreach ($this->getDataSourceFields() as $fieldName) {
        $defaults[$fieldName] = $this->getSubmittedValue($fieldName);
      }
    }
    else {
      $defaults = array_merge($this->dataSourceDefaults, $defaults);
    }
    return $defaults;
  }

  /**
   * Get the submitted value, as saved in the user job.
   *
   * This form is not in the same flow as the DataSource but
   * the value we want is saved to the userJob so load it from there.
   *
   * @param string $fieldName
   *
   * @return mixed|null
   * @throws \CRM_Core_Exception
   */
  public function getSubmittedValue(string $fieldName) {
    $userJob = $this->getUserJob();
    return $userJob['metadata']['submitted_values'][$fieldName];
  }

}
