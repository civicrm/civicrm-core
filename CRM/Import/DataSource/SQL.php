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
class CRM_Import_DataSource_SQL extends CRM_Import_DataSource {

  /**
   * Form fields declared for this datasource.
   *
   * @var string[]
   */
  protected $submittableFields = ['sqlQuery'];

  /**
   * Provides information about the data source.
   *
   * @return array
   *   collection of info about this data source
   */
  public function getInfo(): array {
    return [
      'title' => ts('SQL Query'),
      'permissions' => ['import SQL datasource'],
      'template' => 'CRM/Contact/Import/Form/SQL.tpl',
    ];
  }

  /**
   * This is function is called by the form object to get the DataSource's
   * form snippet. It should add all fields necesarry to get the data
   * uploaded to the temporary table in the DB.
   *
   * @param CRM_Import_Forms $form
   */
  public function buildQuickForm(CRM_Import_Forms $form): void {
    $form->add('hidden', 'hidden_dataSource', 'CRM_Import_DataSource_SQL');
    $form->add('textarea', 'sqlQuery', ts('Specify SQL Query'), ['rows' => 10, 'cols' => 45], TRUE);
    $form->addFormRule(['CRM_Import_DataSource_SQL', 'formRule'], $form);
  }

  /**
   * @param $fields
   * @param $files
   * @param CRM_Core_Form $form
   *
   * @return array|bool
   */
  public static function formRule($fields, $files, $form) {
    $errors = [];

    // Makeshift query validation (case-insensitive regex matching on word boundaries)
    $forbidden = ['ALTER', 'CREATE', 'DELETE', 'DESCRIBE', 'DROP', 'SHOW', 'UPDATE', 'REPLACE', 'information_schema'];
    foreach ($forbidden as $pattern) {
      if (preg_match("/\\b$pattern\\b/i", $fields['sqlQuery'])) {
        $errors['sqlQuery'] = ts('The query contains the forbidden %1 command.', [1 => $pattern]);
      }
    }

    return $errors ?: TRUE;
  }

  /**
   * Initialize the datasource, based on the submitted values stored in the user job.
   *
   * @throws \CRM_Core_Exception
   */
  public function initialize(): void {
    $table = CRM_Utils_SQL_TempTable::build()->setDurable();
    $tableName = $table->getName();
    $table->createWithQuery($this->restoreOperators($this->getSubmittedValue('sqlQuery')));

    // Get the names of the fields to be imported.
    $columnsResult = CRM_Core_DAO::executeQuery(
      'SHOW FIELDS FROM ' . $tableName);

    $columnNames = [];
    while ($columnsResult->fetch()) {
      if (strpos($columnsResult->Field, ' ') !== FALSE) {
        // Remove spaces as the Database object does this
        // $keys = str_replace(array(".", " "), "_", array_keys($array));
        // https://lab.civicrm.org/dev/core/-/issues/1337
        $usableColumnName = str_replace(' ', '_', $columnsResult->Field);
        CRM_Core_DAO::executeQuery('ALTER TABLE ' . $tableName . ' CHANGE `' . $columnsResult->Field . '` ' . $usableColumnName . ' ' . $columnsResult->Type);
        $columnNames[] = $usableColumnName;
      }
      else {
        $columnNames[] = $columnsResult->Field;
      }
    }

    $this->addTrackingFieldsToTable($tableName);
    $this->updateUserJobDataSource([
      'table_name' => $tableName,
      'column_headers' => $columnNames,
      'number_of_columns' => count($columnNames),
    ]);
  }

  /**
   * Restore greater than & equal operators that the form html_encoded.
   *
   * @param string $string
   *
   * @return string
   */
  public function restoreOperators(string $string): string {
    return str_replace(['&lt;', '&gt;'], ['<', '>'], $string);
  }

}
