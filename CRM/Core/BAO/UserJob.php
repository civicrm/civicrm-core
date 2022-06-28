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
class CRM_Core_BAO_UserJob extends CRM_Core_DAO_UserJob implements \Civi\Core\HookInterface {

  /**
   * Check on the status of a queue.
   *
   * Queues that are attached to a UserJob are necessarily finite - so we can mark them 'completed'
   * when the task-list reaches empty.
   *
   * Note that this runs after processing *every item* in *every queue* (foreground, background,
   * import, upgrade, ad nauseum). The capacity to handle heavy tasks here is subjective (depending
   * on the specific queue/use-case). We try to be conservative about I/O until we know that
   * we're in a suitable context.
   */
  public static function on_civi_queue_check(\Civi\Core\Event\GenericHookEvent $e) {
    /** @var \CRM_Queue_Queue $queue */
    $queue = $e->queue;
    $userJobId = static::findUserJobId($queue->getName());
    if ($userJobId && $queue->numberOfItems() < 1) {
      $queue->setStatus('completed');
    }
  }

  /**
   * If the `civicrm_queue` changes status, then the `civicrm_user_job` should also change status.
   *
   * @param \CRM_Queue_Queue $queue
   * @param string $status
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   * @see \CRM_Utils_Hook::queueStatus()
   */
  public static function hook_civicrm_queueStatus(CRM_Queue_Queue $queue, string $status) {
    $userJobId = static::findUserJobId($queue->getName());
    if ($userJobId && $status === 'completed') {
      \Civi\Api4\UserJob::update()
        ->addWhere('id', '=', $userJobId)
        ->setValues(['status_id' => 1])
        ->execute();
    }
  }

  private static function findUserJobId(string $queueName): ?int {
    if (CRM_Core_Config::isUpgradeMode()) {
      return NULL;
    }

    $key = 'userJobId_' . $queueName;
    if (!isset(Civi::$statics[__CLASS__][$key])) {
      // Part of the primary structure/purpose of the queue. Shouldn't change.
      $userJobId = CRM_Core_DAO::singleValueQuery('
        SELECT uj.id FROM civicrm_queue q
        INNER JOIN civicrm_user_job uj ON q.id = uj.queue_id
        WHERE q.name = %1
      ', [
        1 => [$queueName, 'String'],
      ]);
      Civi::$statics[__CLASS__][$key] = $userJobId;
    }
    return Civi::$statics[__CLASS__][$key];
  }

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
    $clauses = [];
    if (!\CRM_Core_Permission::check('administer queues')) {
      $clauses['created_id'] = '= ' . (int) CRM_Core_Session::getLoggedInContactID();
    }
    CRM_Utils_Hook::selectWhereClause($this, $clauses);
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
        'id' => 'contact_import',
        'name' => 'contact_import',
        'label' => ts('Contact Import'),
        'class' => 'CRM_Contact_Import_Parser_Contact',
      ],
      [
        'id' => 'contribution_import',
        'name' => 'contribution_import',
        'label' => ts('Contribution Import'),
        'class' => 'CRM_Contribute_Import_Parser_Contribution',
      ],
      [
        'id' => 'membership_import',
        'name' => 'membership_import',
        'label' => ts('Membership Import'),
        'class' => 'CRM_Member_Import_Parser_Membership',
      ],
      [
        'id' => 'activity_import',
        'name' => 'activity_import',
        'label' => ts('Activity Import'),
        'class' => 'CRM_Activity_Import_Parser_Activity',
      ],
      [
        'id' => 'participant_import',
        'name' => 'participant_import',
        'label' => ts('Participant Import'),
        'class' => 'CRM_Event_Import_Parser_Participant',
      ],
      [
        'id' => 'custom_field_import',
        'name' => 'custom_field_import',
        'label' => ts('Multiple Value Custom Field Import'),
        'class' => 'CRM_Custom_Import_Parser_Api',
      ],
    ];
  }

}
