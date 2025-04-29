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

namespace Civi\Import\DataSource;

use Civi\Api4\UserJob;

/**
 * Provides all the helpers to add a datasource easily.
 */
trait DataSourceTrait {

  /**
   * User job id.
   *
   * This is the primary key of the civicrm_user_job table which is used to
   * track the import.
   *
   * @var int
   */
  protected $userJobID;

  /**
   * User job details.
   *
   * This is the relevant row from civicrm_user_job.
   *
   * @var array
   */
  protected $userJob;

  /**
   * Class constructor.
   *
   * @param int|null $userJobID
   */
  public function __construct(?int $userJobID = NULL) {
    if ($userJobID) {
      $this->setUserJobID($userJobID);
    }
  }

  /**
   * Get the ID of the user job being acted on.
   *
   * @return int|null
   */
  public function getUserJobID(): ?int {
    return $this->userJobID;
  }

  /**
   * Set user job ID.
   *
   * @param int $userJobID
   */
  public function setUserJobID(int $userJobID): void {
    $this->userJobID = $userJobID;
  }

  /**
   * Determine if the current user has access to this data source.
   *
   * @return bool
   */
  public function checkPermission(): bool {
    $info = $this->getInfo();
    return empty($info['permissions']) || \CRM_Core_Permission::check($info['permissions']);
  }

  /**
   * Purge any datasource related assets when the datasource is dropped.
   *
   * This is the datasource's chance to delete any tables etc that it created
   * which will now not be used.
   *
   * @param array $newParams
   *   If the dataSource is being updated to another variant of the same
   *   class (eg. the csv upload was set to no column headers and they
   *   have resubmitted WITH skipColumnHeader (first row is a header) then
   *   the dataSource is still CSV and the params for the new instance
   *   are passed in. When changing from csv to SQL (for example) newParams is
   *   empty.
   *
   * @return array
   *   The details to update the DataSource key in the userJob metadata to.
   *   Generally and empty array but it the datasource decided (for example)
   *   that the table it created earlier is still consistent with the new params
   *   then it might decided not to drop the table and would want to retain
   *   some metadata.
   *
   * @throws \CRM_Core_Exception
   */
  public function purge(array $newParams = []) :array {
    // The old name is still stored...
    $oldTableName = $this->getTableName();
    if ($oldTableName) {
      \CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS ' . $oldTableName);
    }
    return [];
  }

  /**
   * Update the data stored in the User Job about the Data Source.
   *
   * @param array $data
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function updateUserJobDataSource(array $data): void {
    $this->updateUserJobMetadata('DataSource', $data);
  }

  /**
   * Update the UserJob Metadata.
   *
   * @param string $key
   * @param array $data
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function updateUserJobMetadata(string $key, array $data): void {
    $metaData = array_merge(
      $this->getUserJob()['metadata'],
      [$key => $data]
    );
    UserJob::update(FALSE)
      ->addWhere('id', '=', $this->getUserJobID())
      ->setValues(['metadata' => $metaData])
      ->execute();
    $this->userJob['metadata'] = $metaData;
  }

  /**
   * Get an array of column headers, if any.
   *
   * This is presented to the user in the MapField screen so
   * that can see what fields they are mapping.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getColumnHeaders(): array {
    return $this->getUserJob()['metadata']['DataSource']['column_headers'];
  }

  /**
   * Get User Job.
   *
   * API call to retrieve the userJob row.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getUserJob(): array {
    if (!$this->userJob) {
      $this->userJob = UserJob::get()
        ->addWhere('id', '=', $this->getUserJobID())
        ->execute()
        ->first();
    }
    return $this->userJob;
  }

  /**
   * Get submitted value.
   *
   * Get a value submitted on the form.
   *
   * @return mixed
   *
   * @throws \CRM_Core_Exception
   */
  protected function getSubmittedValue(string $valueName) {
    return $this->getUserJob()['metadata']['submitted_values'][$valueName];
  }

  /**
   * Get column names from the headers - munging to lower case etc.
   *
   * @param array $headers
   *
   * @return array
   */
  protected function getColumnNamesFromHeaders(array $headers): array {
    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
    $columns = array_map($strtolower, $headers);
    $columns = array_map('trim', $columns);
    $columns = str_replace(' ', '_', $columns);
    $columns = preg_replace('/[^a-z_]/', '', $columns);

    // need to truncate values per mysql field name length limits
    // mysql allows 64, but we need to account for appending colKey
    // CRM-9079
    foreach ($columns as &$colName) {
      if (strlen($colName) > 58) {
        $colName = substr($colName, 0, 58);
      }
    }
    $hasDuplicateColumnName = count($columns) !== count(array_unique($columns));
    if ($hasDuplicateColumnName || in_array('', $columns, TRUE)) {
      foreach ($columns as $colKey => & $colName) {
        if (!$colName) {
          $colName = "col_$colKey";
        }
        elseif ($hasDuplicateColumnName) {
          $colName .= "_$colKey";
        }
      }
    }

    // CRM-4881: we need to quote column names, as they may be MySQL reserved words
    foreach ($columns as & $column) {
      $column = "`$column`";
    }
    return $columns;
  }

  /**
   * Get suitable column names for when no header row is in use.
   *
   * The result is an array like 'column_1', column_2'. SQL columns
   * cannot start with a number.
   *
   * @param array $row
   *
   * @return array
   */
  protected function getColumnNamesForUnnamedColumns(array $row): array {
    $columns = [];
    foreach ($row as $i => $_) {
      $columns[] = "column_$i";
    }
    return $columns;
  }

  /**
   *
   * @param array $columns
   *
   * @return string
   *   Temp table name.
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  protected function createTempTableFromColumns(array $columns): string {
    $table = \CRM_Utils_SQL_TempTable::build()->setDurable();
    $tableName = $table->getName();
    \CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS $tableName");
    $table->createWithColumns(implode(' text, ', $columns) . ' text');
    return $tableName;
  }

  /**
   * Trim non-breaking spaces in a multibyte-safe way.
   * See also dev/core#2127 - avoid breaking strings ending in à or any other
   * unicode character sharing the same 0xA0 byte as a non-breaking space.
   *
   * See https://lab.civicrm.org/dev/core/-/issues/5843 for history, discussion.
   * We can probably switch to mb_trim but need the 8.4 polyfill.
   *
   * @internal Note there is one known extension calling this directly
   * (import_extensions, which offers importing an already-uploaded csv), but that
   * will be fixed by 6.3. Extensions should expect this function to be removed/
   * consolidated into trimWhiteSpace.
   *
   * @param string $string
   * @return string The trimmed string
   */
  public static function trimNonBreakingSpaces(string $string): string {
    $encoding = mb_detect_encoding($string, NULL, TRUE);
    if ($encoding === FALSE) {
      // This could mean a couple things. One is that the string is
      // ASCII-encoded but contains a non-breaking space, which causes
      // php to fail to detect the encoding. So let's just do what we
      // did before which works in that situation and is at least no
      // worse in other situations.
      return trim($string, chr(0xC2) . chr(0xA0));
    }
    if ($encoding !== 'UTF-8') {
      $string = mb_convert_encoding($string, 'UTF-8', [$encoding]);
    }
    return preg_replace("/^(\u{a0})+|(\u{a0})+$/", '', $string);
  }

  public static function trimWhitespace(string $string): string {
    return trim(self::trimNonBreakingSpaces($string), " \t\r\n");
  }

}
