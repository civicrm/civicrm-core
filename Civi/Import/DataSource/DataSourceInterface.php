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

/**
 * Objects that implement the DataSource interface can be used in CiviCRM imports.
 */
interface DataSourceInterface {

  /**
   * Determine if the current user has access to this data source.
   *
   * @return bool
   */
  public function checkPermission(): bool;

  /**
   * Provides information about the data source.
   *
   * @return array
   *   Description of this data source, including:
   *   - title: string, translated, required
   *   - permissions: array, optional
   */
  public function getInfo(): array;

  /**
   * This is function is called by the form object to get the DataSource's form snippet.
   *
   * It should add all fields necessary to get the data uploaded to the temporary table in the DB.
   *
   * @param \CRM_Import_Forms $form
   */
  public function buildQuickForm(\CRM_Import_Forms $form): void;

  /**
   * Get array array of field names that may be submitted for this data source.
   *
   * The quick form for the datasource is added by ajax - meaning that QuickForm
   * does not see them as part of the form. However, any fields listed in this array
   * will be taken from the `$_POST` and stored to the UserJob under metadata->submitted_values.
   *
   * @return array
   */
  public function getSubmittableFields(): array;

  /**
   * Initialize the datasource, based on the submitted values stored in the user job.
   *
   * Generally this will include transferring the data to a database table.
   *
   * @throws \CRM_Core_Exception
   */
  public function initialize(): void;

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
  public function purge(array $newParams = []) :array;

  /**
   * Get an array of column headers, if any.
   *
   * This is presented to the user in the MapField screen so
   * that can see what fields they are mapping.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getColumnHeaders(): array;

  /**
   * @param int $limit
   *
   * @return self
   */
  public function setLimit(int $limit): self;

  /**
   * Set the statuses to be retrieved.
   *
   * @param array $statuses
   *
   * @return self
   */
  public function setStatuses(array $statuses): self;

  /**
   * Get rows as an array.
   *
   * The array has all values.
   *
   * @param bool $nonAssociative
   *   Return as a non-associative array?
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public function getRows(bool $nonAssociative = TRUE): array;

}
