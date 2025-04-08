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
 * Prepare a set of steps for downloading extension upgrades.
 *
 * The general idea is:
 *
 *   $dl = new CRM_Extension_QueueDownloader();
 *   $queue = $dl->fillQueue(
 *     ['ext-1' => 'https://example.com/ext-1/releases/1.2.3./zip']
 *   );
 *   $runner = new CRM_Queue_Runner(...$queue...);
 *   $runner->runAllViaWeb();
 *
 * NOTE: When upgrading extensions, you MUST have a chance to reset the PHP process (loading new PHP files).
 * We will assume that every task runs in a new PHP process. This is compatible with runAllViaWeb() not but runAll().
 * Headless clients (like `cv`) will need to use a different implementation that spawns new subprocesses.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Extension_QueueDownloader {

  const QUEUE_PREFIX = 'ext_upgrade_';

  /**
   * Unique ID for this batch of downloads.
   *
   * @var string
   *   Ex: 20250607_abcd1234abcd1234
   */
  protected string $upId;

  protected bool $autoApply;

  protected CRM_Queue_Queue $queue;

  protected bool $cleanup;

  /**
   * @param bool $autoApply
   *   TRUE if the downloader should also execute the installation/upgrade routines
   * @param bool $cleanup
   *    Whether to delete temporary files and backup files at the end.
   * @param CRM_Queue_Queue|null $queue
   */
  public function __construct(bool $autoApply = TRUE, bool $cleanup = TRUE, ?CRM_Queue_Queue $queue = NULL) {
    $this->upId = (CRM_Utils_Time::date('Y-m-d') . '-' . CRM_Utils_String::createRandom(16, CRM_Utils_String::ALPHANUMERIC));
    $this->autoApply = $autoApply;
    $this->cleanup = $cleanup;
    $this->queue = $queue ?: $this->createQueue();
  }

  public function createQueue(): CRM_Queue_Queue {
    return Civi::queue(static::QUEUE_PREFIX . $this->upId, [
      'type' => 'Sql',
      'runner' => 'task',
      'is_autorun' => FALSE,
      'retry_limit' => 0,
      'error' => 'abort',
      'reset' => TRUE,
    ]);
  }

  protected function getStagingPath(...$moreParts): string {
    // The staging path is basically "{extensionsDir}/.civicrm-staging/{upId}". Note that:
    // - This puts the staging files in the right filesystem (close to their final location).
    //   Thus, `rename()` can be used to rearrange folders.
    // - `uploadDir` might be viable, but it's also at greater risk of getting auto-cleaned
    //   and being on separate filesystem.

    $baseDir = CRM_Extension_System::singleton()->getDefaultContainer()->baseDir;
    $prefix = [
      rtrim($baseDir, DIRECTORY_SEPARATOR . '/'),
      '.civicrm-staging',
      $this->upId,
    ];
    return implode('/', array_merge($prefix, $moreParts));
  }

  public function getTitle(): string {
    return ts('Download and Install (<em><small>ID: %1</small></em>)', [1 => $this->upId]);
  }

  /**
   * @param array $downloads
   *   Ex: ['ext1' => 'https://example.com/ext1/releases/1.0.zip']
   * @return \CRM_Queue_Queue
   */
  public function fillQueue(array $downloads): CRM_Queue_Queue {
    $queue = $this->queue;
    if (empty($downloads)) {
      throw new CRM_Core_Exception("Cannot build download queue. No downloads requested!");
    }

    // Store some metadata about what's going on. This may help with debugging.
    CRM_Utils_File::createDir($this->getStagingPath(), 'exception');
    file_put_contents($this->getStagingPath('details.json'), json_encode([
      'startTime' => CRM_Utils_Time::date('c'),
      'upId' => $this->upId,
      'queue' => $queue->getName(),
      'downloads' => $downloads,
      'statuses' => CRM_Extension_System::singleton()->getManager()->getStatuses(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    // Download and extract zip files. This is I/O dependent (error-prone), so we do each as a separate (retriable) step.
    foreach ($downloads as $ext => $url) {
      $queue->createItem(
        static::task(ts('Fetch "%1" from "%2"', [1 => $ext, 2 => $url]), 'fetch', [$ext, $url]),
        ['weight' => 100]
      );
    }

    // Verify all requirements with a single operation.
    // We won't be sensitive to (re)ordering of fetch-tasks, because we only care if the final set is coherent.
    $queue->createItem(
      static::task(ts('Verify requirements'), 'preverify', [array_keys($downloads)]),
      ['weight' => 200]
    );

    // Swap-in new folders with a single operation. This should be similar to more sophisticated site-builder
    // workflows. (f you manage a site in git, then "git pull" swaps all code at the same time.) This
    // can't guarantee that all combinations of $downloads work, but at least they'll behave consistently.
    $queue->createItem(
      static::task(ts('Swap folders'), 'swap', [array_keys($downloads)]),
      ['weight' => 200]
    );

    // The "swap" and "rebuild" must happen in separate steps.
    $queue->createItem(
      static::task(ts('Rebuild system'), 'rebuild'),
      ['weight' => 300]
    );

    if ($this->autoApply) {
      // We need to figure out the mix of activation-steps (enable/upgrade).
      // If you have multiple (e.g. enable $X and also upgrade $Y), then... which runs first?
      // In theory, there is no simple rule of ordering that will work for all imaginable scenarios.
      // The main mitigating factor is that actual usage will be biased toward simple-cases.
      // (Ex: The web UI only lets you add one extension at a time. The CLI allows multiple -- but user must explicitly choose each.)
      // This implementation defers to the user -- applying extensions in the order requested.

      $statuses = CRM_Extension_System::singleton()->getManager()->getStatuses();
      $todos = [];
      foreach (array_keys($downloads) as $key) {
        switch ($statuses[$key] ?? CRM_Extension_Manager::STATUS_UNINSTALLED) {
          case CRM_Extension_Manager::STATUS_UNINSTALLED:
            $todos[] = ['enable', $key];
            break;

          case CRM_Extension_Manager::STATUS_DISABLED:
          case CRM_Extension_Manager::STATUS_DISABLED_MISSING:
            $todos[] = ['enable', $key];
            $todos[] = ['upgrade', $key];
            break;

          case CRM_Extension_Manager::STATUS_INSTALLED:
          case CRM_Extension_Manager::STATUS_INSTALLED_MISSING:
            $todos[] = ['upgrade', $key];
            break;

          default:
            throw new \CRM_Extension_Exception('Unknown status: ' . $statuses[$key]);
        }
      }

      // Optimization: Combine any adjacent todos of the same type (e.g. "enable A + enable B ==> enable A+B").
      $nextType = fn() => $todos[0][0];
      $nextKey = fn() => $todos[0][1];
      while (!empty($todos)) {
        $targetType = $nextType();

        $contiguousSegment = [];
        while (!empty($todos) && $nextType() === $targetType) {
          $contiguousSegment[] = $nextKey();
          array_shift($todos);
        }

        switch ($targetType) {
          case 'enable':
            $queue->createItem(
              static::task(ts('Enable %1', [1 => '"' . implode('", "', $contiguousSegment) . '"']), 'enable', [$contiguousSegment]),
              ['weight' => 400]
            );

            break;

          case 'upgrade':
            $queue->createItem(
              static::task(ts('Upgrade database'), 'upgradeDb'),
              ['weight' => 400]
            );
            break;
        }
      }
    }

    if ($this->cleanup) {
      $queue->createItem(
        static::task(ts('Cleanup workspace'), 'cleanup'),
        ['weight' => 2000]
      );
    }

    return $queue;
  }

  /**
   * Create a CRM_Queue_Task that executes on this class.
   *
   * @param string $title
   * @param string $method
   * @param array $args
   *
   * @return \CRM_Queue_Task
   */
  protected function task(string $title, string $method, array $args = []): CRM_Queue_Task {
    return new CRM_Queue_Task(
      [CRM_Extension_QueueTasks::class, $method],
      [$this->getStagingPath(), ...$args],
      $title
    );
  }

}
