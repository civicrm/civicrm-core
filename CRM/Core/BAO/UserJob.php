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

use Civi\Api4\Mapping;
use Civi\Api4\Queue;
use Civi\Api4\UserJob;
use Civi\Core\ClassScanner;
use Civi\Core\Event\PreEvent;
use Civi\Core\HookInterface;
use Civi\UserJob\UserJobInterface;

/**
 * This class contains user jobs functionality.
 */
class CRM_Core_BAO_UserJob extends CRM_Core_DAO_UserJob implements HookInterface {

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
    if ($userJobId && $queue->getStatistic('total') < 1) {
      $queue->setStatus('completed');
    }
  }

  /**
   * If the `civicrm_queue` changes status, then the `civicrm_user_job` should also change status.
   *
   * @param \CRM_Queue_Queue $queue
   * @param string $status
   * @throws \CRM_Core_Exception
   *
   * @see \CRM_Utils_Hook::queueStatus()
   */
  public static function hook_civicrm_queueStatus(CRM_Queue_Queue $queue, string $status): void {
    $userJobId = static::findUserJobId($queue->getName());
    if ($userJobId && $status === 'completed') {
      UserJob::update(FALSE)
        ->addWhere('id', '=', $userJobId)
        ->setValues(['status_id' => 1, 'end_date' => 'now'])
        ->execute();
    }
  }

  /**
   * Enforce template expectations by unsetting non-template variables.
   *
   * Also delete the template if the Mapping is deleted.
   *
   * @param \Civi\Core\Event\PreEvent $event
   *
   * @noinspection PhpUnused
   * @throws \CRM_Core_Exception
   */
  public static function on_hook_civicrm_pre(PreEvent $event): void {
    if ($event->entity === 'UserJob' &&
      (!empty($event->params['is_template'])
      || ($event->action === 'edit' && self::isTemplate($event->params['id']))
    )) {
      $params = &$event->params;
      if (empty($params['name']) && empty($params['id'])) {
        throw new CRM_Core_Exception('Name is required for template user job');
      }
      if ($params['metadata']['submitted_values']['dataSource'] ?? NULL === 'CRM_Import_DataSource_SQL') {
        // This contains path information that we are better to ditch at this point.
        // Ideally we wouldn't save this in submitted values - but just use it.
        unset($params['metadata']['submitted_values']['uploadFile']);
      }
      // This contains information about the import-specific data table.
      unset($params['metadata']['DataSource']['table_name']);
      // Do not keep values about updating the Mapping/UserJob template.
      unset($params['metadata']['MapField']['saveMapping'], $params['metadata']['MapField']['updateMapping']);
    }

    // If the related mapping is deleted then delete the UserJob template
    // This almost never happens in practice...
    if ($event->entity === 'Mapping' && $event->action === 'delete') {
      $mappingName = Mapping::get(FALSE)->addWhere('id', '=', $event->id)->addSelect('name')->execute()->first()['name'];
      UserJob::delete(FALSE)->addWhere('name', '=', 'import_' . $mappingName)->execute();
    }
    if ($event->entity === 'UserJob' && $event->action === 'delete') {
      Queue::delete(FALSE)->addWhere('name', '=', 'user_job_' . $event->id)->execute();
    }
  }

  /**
   * Is this id a Template.
   *
   * @param int $id
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  private static function isTemplate(int $id) : bool {
    return (bool) UserJob::get(FALSE)->addWhere('id', '=', $id)
      ->addWhere('is_template', '=', 1)
      ->selectRowCount()->execute()->rowCount;
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
   * @param string|null $entityName
   * @param int|null $userId
   * @param array $conditions
   * @inheritDoc
   */
  public function addSelectWhereClause(?string $entityName = NULL, ?int $userId = NULL, array $conditions = []): array {
    $clauses = [];
    if (!\CRM_Core_Permission::check('administer queues', $userId)) {
      // It was ok to have $userId = NULL for the permission check but must be an int for the query
      $cid = $userId ?? (int) CRM_Core_Session::getLoggedInContactID();
      // Only allow access to users' own jobs (or templates)
      $clauses['created_id'][] = "= $cid OR {is_template} = 1";
    }
    CRM_Utils_Hook::selectWhereClause($this, $clauses, $userId, $conditions);
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
   * Each type is keyed by it's id and has
   *   -id
   *   -name
   *   -label
   *   -class
   *   -entity
   *   -url
   *
   * @return array
   */
  public static function getTypes(): array {
    $types = Civi::cache('metadata')->get('UserJobTypes');
    if ($types === NULL) {
      $types = [];
      $classes = ClassScanner::get(['interface' => UserJobInterface::class]);
      /** @var \Civi\UserJob\UserJobInterface $class */
      foreach ($classes as $class) {
        $declaredTypes = $class::getUserJobInfo();
        foreach ($declaredTypes as $index => $declaredType) {
          $declaredTypes[$index]['class'] = $class;
        }
        $types = array_merge($types, $declaredTypes);
      }
      Civi::cache('metadata')->set('UserJobTypes', $types);
    }
    // Rekey to numeric to prevent https://lab.civicrm.org/dev/core/-/issues/3719
    return array_values($types);
  }

  /**
   * Get user job type.
   *
   * @param string $type
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function getType(string $type): array {
    foreach (self::getTypes() as $importType) {
      if ($importType['id'] === $type) {
        return $importType;
      }
    }
    throw new CRM_Core_Exception($type . 'not found');
  }

  /**
   * Get the specified value for the import job type.
   *
   * @param string $type
   * @param string $key
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  public static function getTypeValue(string $type, string $key) {
    return self::getType($type)[$key];
  }

}
