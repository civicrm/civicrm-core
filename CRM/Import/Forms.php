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
 * This class helps the forms within the import flow access submitted & parsed values.
 */
class CRM_Import_Forms extends CRM_Core_Form {

  /**
   * Get the submitted value, accessing it from whatever form in the flow it is submitted on.
   * @param string $fieldName
   *
   * @return mixed|null
   */
  public function getSubmittedValue(string $fieldName) {
    $mappedValues = [
      'skipColumnHeader' => 'DataSource',
      'fieldSeparator' => 'DataSource',
      'uploadFile' => 'DataSource',
      'contactType' => 'DataSource',
      'dateFormats' => 'DataSource',
      'savedMapping' => 'DataSource',
      'dataSource' => 'DataSource',
    ];
    if (array_key_exists($fieldName, $mappedValues)) {
      return $this->controller->exportValue($mappedValues[$fieldName], $fieldName);
    }
    return parent::getSubmittedValue($fieldName);

  }

  /**
   * Get the available datasource.
   *
   * Permission dependent, this will look like
   * [
   *   'CRM_Import_DataSource_CSV' => 'Comma-Separated Values (CSV)',
   *   'CRM_Import_DataSource_SQL' => 'SQL Query',
   * ]
   *
   * The label is translated.
   *
   * @return array
   */
  protected function getDataSources(): array {
    $dataSources = [];
    foreach (['CRM_Import_DataSource_SQL', 'CRM_Import_DataSource_CSV'] as $dataSourceClass) {
      $object = new $dataSourceClass();
      if ($object->checkPermission()) {
        $dataSources[$dataSourceClass] = $object->getInfo()['title'];
      }
    }
    return $dataSources;
  }

  /**
   * Get the name of the datasource class.
   *
   * This function prioritises retrieving from GET and POST over 'submitted'.
   * The reason for this is the submitted array will hold the previous submissions
   * data until after buildForm is called.
   *
   * This is problematic in the forward->back flow & option changing flow. As in....
   *
   * 1) Load DataSource form - initial default datasource is set to CSV and the
   * form is via ajax (this calls DataSourceConfig to get the data).
   * 2) User changes the source to SQL - the ajax updates the html but the
   * form was built with the expectation that the csv-specific fields would be
   * required.
   * 3) When the user submits Quickform calls preProcess and buildForm and THEN
   * retrieves the submitted values based on what has been added in buildForm.
   * Only the submitted values for fields added in buildForm are available - but
   * these have to be added BEFORE the submitted values are determined. Hence
   * we look in the POST or GET to get the updated value.
   *
   * Note that an imminent refactor will involve storing the values in the
   * civicrm_user_job table - this will hopefully help with a known (not new)
   * issue whereby the previously submitted values (eg. skipColumnHeader has
   * been checked or sql has been filled in) are not loaded via the ajax request.
   *
   * @return string|null
   *
   * @throws \CRM_Core_Exception
   */
  protected function getDataSourceClassName(): ?string {
    $className = CRM_Utils_Request::retrieveValue(
      'dataSource',
      'String'
    );
    if (!$className) {
      $className = $this->getSubmittedValue('dataSource');
    }
    if (!$className) {
      $className = $this->getDefaultDataSource();
    }
    if ($this->getDataSources()[$className]) {
      return $className;
    }
    throw new CRM_Core_Exception('Invalid data source');
  }

  /**
   * Allow the datasource class to add fields.
   *
   * This is called as a snippet in DataSourceConfig and
   * also from DataSource::buildForm to add the fields such
   * that quick form picks them up.
   *
   * @throws \CRM_Core_Exception
   */
  protected function buildDataSourceFields(): void {
    $className = $this->getDataSourceClassName();
    if ($className) {
      $dataSourceClass = new $className();
      $dataSourceClass->buildQuickForm($this);
    }
  }

  /**
   * Get the default datasource.
   *
   * @return string
   */
  protected function getDefaultDataSource(): string {
    return 'CRM_Import_DataSource_CSV';
  }

}
