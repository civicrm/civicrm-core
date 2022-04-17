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

}
