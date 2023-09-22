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

namespace Civi\UserJob;

interface UserJobInterface {

  /**
   * Get information about the provided job.
   *  - name
   *  - id (generally the same as name)
   *  - label
   *  - entity
   *  - url
   *
   *  e.g. ['activity_import' => ['id' => 'activity_import', 'label' => ts('Activity Import'), 'name' => 'activity_import']]
   *
   * @return array
   */
  public static function getUserJobInfo(): array;

  /**
   * Run import.
   *
   * @param \CRM_Queue_TaskContext $taskContext
   * @param int $userJobID
   *   The id in the civicrm_user_job table.
   * @param int $limit
   *   A value of zero means no limit
   * @param int $offset
   *
   * @return bool
   */
  public static function runJob(\CRM_Queue_TaskContext $taskContext, int $userJobID, int $limit, int $offset): bool;

}
