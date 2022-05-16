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
 * This class contains user jobs functionality.
 */
class CRM_Core_BAO_UserJob extends CRM_Core_DAO_UserJob {

  /**
   * Restrict access to the relevant user.
   *
   * Note that it is likely we might want to permit other users such as
   * sysadmins to access other people's user_jobs in future but it has been
   * kept tightly restricted for initial simplicity (ie do we want to
   * use an existing permission? a new permission ? do they require
   * 'view all contacts' etc.
   *
   * @inheritDoc
   */
  public function addSelectWhereClause(): array {
    $clauses['created_id'] = '= ' . (int) CRM_Core_Session::getLoggedInContactID();
    return $clauses;
  }

  /**
   * Get the statuses for Import Jobs.
   *
   * @return array
   */
  public static function getStatuses(): array {
    return [
      [
        'id' => 1,
        'name' => 'completed',
        'label' => ts('Completed'),
      ],
      [
        'id' => 2,
        'name' => 'draft',
        'label' => ts('Draft'),
      ],
      [
        'id' => 3,
        'name' => 'scheduled',
        'label' => ts('Scheduled'),
      ],
      [
        'id' => 4,
        'name' => 'in_progress',
        'label' => ts('In Progress'),
      ],
    ];
  }

  /**
   * Get the types Import Jobs.
   *
   * This is largely a placeholder at this stage. It will likely wind
   * up as an option value so extensions can add different types.
   *
   * However, for now it just holds the one type being worked on.
   *
   * @return array
   */
  public static function getTypes(): array {
    return [
      [
        'id' => 1,
        'name' => 'contact_import',
        'label' => ts('Contact Import'),
      ],
    ];
  }

}
