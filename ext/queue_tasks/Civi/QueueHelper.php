<?php

namespace Civi;

use Civi\Core\Exception\DBQueryException;

/**
 * Queue helper.
 *
 * This comes from https://gist.github.com/totten/fa830cfced9bb7a92dea485f5422055a
 * & will hopefully be moved to core.
 */
class QueueHelper {

  protected $queue;

  public const ITERATE_UNTIL_DONE = 1;

  public const ITERATE_RUN_ONCE = 0;

  public const ITERATE_UNTIL_TRUE = 2;

  /**
   * @var int|null
   */
  protected $runAs;

  public function __construct(\CRM_Queue_Queue $queue) {
    $this->queue = $queue;
  }

  /**
   * @param int|null|array $runAs
   *
   * @return $this
   */
  public function setRunAs($runAs): QueueHelper {
    $this->runAs = $runAs;
    return $this;
  }

  /**
   * @param string $sql
   * @param array $params
   * @param int $iterate
   * @param array $doneCondition
   * @param int $weight
   *
   * @return $this
   *
   * @params array $doneParameters
   */
  public function sql(string $sql, array $params = [], int $iterate = self::ITERATE_RUN_ONCE, $doneCondition = [], $weight = 0): QueueHelper {
    $task = new \CRM_Queue_Task([self::class, 'doSql'], [
      $sql,
      $params,
      $iterate,
      $doneCondition,
    ]);
    $task->runAs = $this->runAs;
    $this->queue->createItem($task, ['weight' => $weight]);
    return $this;
  }

  /**
   * Create a task to execute an api4 action in a queue context
   *
   * @param string $entity
   * @param string $action
   * @param array $params
   * @param array $options
   * @return $this
   */
  public function api4(string $entity, string $action, array $params = [], array $incrementParameters = [], array $options = []) {
    $task = new \CRM_Queue_Task([self::class, 'doApi4'], [$entity, $action, $params, $incrementParameters]);
    $task->runAs = $this->runAs;
    $this->queue->createItem($task, $options);
    return $this;
  }

  /**
   *
   *
   * public function api3(string $entity, string $action, array $params = []) {
   * $this->queue->createItem(new \CRM_Queue_Task([self::class, 'doApi3'], [$entity, $action, $params]));
   * return $this;
   * }
   */

  /**
   * Do SQL in a queue context.
   *
   * @param \CRM_Queue_TaskContext $taskContext
   * @param string $sql
   * @param array $queryParameters
   *   Values to interpolate into the sql. These are in the format
   *   [1 => [500, 'Integer], 2 => ['bob' => 'String']]. They can be incremented
   *   with the passing of incrementParams.
   * @param int $iterate
   *   Either self::ITERATE_ONCE or self::ITERATE_UNTIL DONE
   * @param array $incrementParameters
   *   Additional parameters when using ITERATE_UNTIL_DONE
   *   These are keyed the same as the queryParameters and will be added to the
   *   query parameters to provide batching. e.g if the queryParameters have a key
   *   [1 => [0, 'Integer']] and the incrementParameters have
   *   [1 => ['increment' => 200]] then on re-queueing the parameter will be set
   *   to 200 rather than 0, and 400 for the next iteration etc.
   * @param array $doneCondition
   *
   * @return bool
   * @internal only use from this class.
   */
  public static function doSql(\CRM_Queue_TaskContext $taskContext, string $sql, array $queryParameters, int $iterate, array $doneCondition = []): bool {
    try {
      $daoParams = [];
      foreach ($queryParameters as $index => $queryParameter) {
        // Flatten out the array.
        $daoParams[$index] = [$queryParameter['value'], $queryParameter['type']];
      }
      $result = \CRM_Core_DAO::executeQuery($sql, $daoParams);
    }
    catch (DBQueryException $e) {
      \Civi::log('queue')->error('queued action failed to run {sql} with parameters {params} sql error {sql_error_code} {message} {exception}', [
        'sql' => $sql,
        'params' => $queryParameters,
        // @todo - add isDeadLock? Maybe at the error level.
        'sql_error_code' => $e->getSQLErrorCode(),
        'message' => $e->getMessage(),
        'exception' => $e,
      ]);
      return FALSE;
    }
    if (($iterate === self::ITERATE_UNTIL_DONE && $result->affectedRows() > 0)
      || ($iterate === self::ITERATE_UNTIL_TRUE && !self::isIterationComplete($doneCondition))) {
      foreach ($queryParameters as $index => $queryParameter) {
        // Each loop we add on the value from the increment to our replacement params, if passed
        // note that there isn't validation at this stage as
        // to whether we are incrementing an Integer/DateTime. As this
        // code settles we might add it - just not sure this is the right place.
        if (!empty($queryParameter['increment'])) {
          $queryParameters[$index]['value'] += $queryParameter['increment'];
        }
      }
      try {
        // Not finished, queue another pass.
        $task = new \CRM_Queue_Task([self::class, 'doSql'], [
          $sql,
          $queryParameters,
          $iterate,
          $doneCondition,
        ]);

        $taskContext->queue->createItem($task);
      }
      catch (\CRM_Core_Exception $e) {
        \Civi::log('queue')->error('queued action failed to re-queue {message} {exception}', [
          'message' => $e->getMessage(),
          'exception' => $e,
        ]);
      }
    }
    return TRUE;
  }

  private static function isIterationComplete($doneParams): bool {
    if (empty($doneParams)) {
      return TRUE;
    }
    // We might nuance this so keying as 'sql_returns_none' for now to allow others.
    // when the sql returns nothing the iteration is complete.
    return !\CRM_Core_DAO::singleValueQuery($doneParams['sql_returns_none']);
  }

  /**
   * Queue calls using api v4, with optional increment parameters.
   *
   * Do apiv4 call in a queue context.
   *
   * @param \CRM_Queue_TaskContext $taskContext
   * @param string $entity
   * @param string $action
   * @param array $params api Parameters with (e.g.) '%1' in the where clause to be
   *   replaced with the increment parameter 1
   * @param array $incrementParameters Describe how to increment the values.
   * 1 => [
   *   'value' => 0,
   *   'type' => 'Integer',
   *   'increment' => 2,
   *   'max' => $maxContactID,
   *  ],
   *  2 => [
   *    'value' => 2,
   *    'type' => 'Integer',
   *    'increment' => 2,
   *   ],
   *
   * @return bool
   * @internal only use from this class.
   */
  public static function doApi4(\CRM_Queue_TaskContext $taskContext, string $entity, string $action, array $params, $incrementParameters): bool {
    try {
      $runParams = $params;
      $runParams['checkPermissions'] = FALSE;
      foreach ($incrementParameters as $incrementKey => $incrementParameter) {
        foreach ($params['where'] ?? [] as $index => $where) {
          if (is_array($where[2])) {
            foreach ($where[2] as $key => $criteria) {
              if ($criteria === '%' . $incrementKey) {
                $runParams['where'][$index][2][$key] = $incrementParameter['value'];
              }
            }
          }
          else {
            $criteria = $where[2];
            if ($criteria === '%' . $incrementKey) {
              $runParams['where'][$index][2] = $incrementParameter['value'];
            }
          }
        }
      }
      civicrm_api4($entity, $action, $runParams);

      $reQueue = TRUE;
      foreach ($incrementParameters as $key => $incrementParameter) {
        if (empty($incrementParameter['max']) || $incrementParameter['value'] < $incrementParameter['max']) {
          $incrementParameters[$key]['value'] += $incrementParameter['increment'];
        }
        else {
          // If max is set & met on any parameter we are done.
          $reQueue = FALSE;
        }
      }

      if ($reQueue) {
        // Not finished, queue another pass.
        $task = new \CRM_Queue_Task([self::class, 'doApi4'], [
          $entity,
          $action,
          $params,
          $incrementParameters,
        ]);

        $taskContext->queue->createItem($task);
      }
    }
    catch (\CRM_Core_Exception $e) {
      \Civi::log('queue')->error('queued action failed {entity} {action} {params} {message} {exception}', [
        'entity' => $entity,
        'action' => $action,
        'params' => $params,
        'message' => $e->getMessage(),
        'exception' => $e,
      ]);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Do apiv3 call in a queue context.
   *
   * @internal only use from this class.
   *
   *
   * public static function doApi3(\CRM_Queue_TaskContext $taskContext, string $entity, string $action, array $params): bool {
   * try {
   * civicrm_api3($entity, $action, $params);
   * }
   * catch (\CRM_Core_Exception $e) {
   * \Civi::log('queue')->error('queued action failed {entity} {action} {params} {message} {exception}', [
   * 'entity' => $entity,
   * 'action' => $action,
   * 'params' => $params,
   * 'message' => $e->getMessage(),
   * 'exception' => $e,
   * ]);
   * return FALSE;
   * }
   * return TRUE;
   * }
   */

}
