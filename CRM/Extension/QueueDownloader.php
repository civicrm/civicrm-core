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
 *   $dl->addDownloads(
 *     ['ext-1' => 'https://example.com/ext-1/releases/1.2.3./zip']
 *   );
 *   $runner = new CRM_Queue_Runner([
 *     'queue' => $dl->fillQueue(), ...
 *   ]);
 *   $runner->runAllViaWeb();
 *
 * == NOTE: Using subprocesses
 *
 * When upgrading extensions, you MUST provide a chance to reset the PHP process (loading new PHP files).
 *
 * We will assume that every task runs in a new PHP process. This is compatible with runAllViaWeb() not but runAll().
 *
 * Headless clients (like `cv`) will need to use a suitable runner that spawns new subprocesses.
 *
 * == NOTE: Sequencing
 *
 * When you have multiple extensions to download/enable (and each may come with different start-state
 * and version; and each may have differing versioned-dependencies)... then there is an interesting
 * question about how to sequence/group the operations.
 *
 * Some operations target multiple ext's concurrently (like "rebuild" or "hook_upgrade" or "enable(keys=>A,B,C)").
 * It's nice to lean into this style ("fetch A+B+C" then "rebuild system" then "upgrade A+B+C")
 * because it's a good facsimile of the behavior in SCM (git/gzr/svn)-based workflows.
 *
 * However, it's not perfect, and there may still be edge-cases where that doesn't work. I'm pessimistic
 * that this class will be able to automatically form perfect+universal plans based only on a declared
 * list of downloads.
 *
 * So if a problematic edge-case comes up, how could you resolve it? The caller can decide sequencing/batching.
 * Compare:
 *
 * ## Ex 1: Download 'a' and 'b' in the same batch. They will be fetched, swapped, and rebuilt in tandem.
 *   $dl->addDownloads(['a' => ..., 'b' => ...]);
 *
 * ## Ex 2: Download 'a' and 'b' as separate batches. 'a' will be fully handled before 'b'.
 *   $dl->addDownloads(['a' => ...]);
 *   $dl->addDownloads(['b' => ...]);
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

  protected CRM_Queue_Queue $queue;

  protected bool $cleanup;

  /**
   * @var array
   *   Ex: [0 => ['type' => 'download', 'urls' => ['my.extension' => 'https://example/my.extension-1.0.zip']]]
   *   Ex: [0 => ['type' => 'enable', 'keys' => ['my.extension']]]
   */
  protected array $batches = [];

  /**
   * @param bool $cleanup
   *    Whether to delete temporary files and backup files at the end.
   * @param CRM_Queue_Queue|null $queue
   */
  public function __construct(bool $cleanup = TRUE, ?CRM_Queue_Queue $queue = NULL) {
    $this->upId = (CRM_Utils_Time::date('Y-m-d') . '-' . CRM_Utils_String::createRandom(16, CRM_Utils_String::ALPHANUMERIC));
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
   * Add a set of extensions to download and enable.
   *
   * @param array $downloads
   *   Ex: ['ext1' => 'https://example.com/ext1/releases/1.0.zip']
   * @param bool $autoApply
   *   TRUE if the downloader should execute the installation/upgrade routines
   *   FALSE if the downloader should only get the files and put them in place
   * @return $this
   */
  public function addDownloads(array $downloads, bool $autoApply = TRUE) {
    $this->batches[] = ['type' => 'download', 'urls' => $downloads, 'autoApply' => $autoApply];
    return $this;
  }

  /**
   * Add a set of keys which should be enabled. (Use this if you -only- want to enable. If you are actually downloading, then use addDownloads().)
   *
   * @param array $keys
   *   Ex: ['my.ext1', 'my.ext2']
   * @return $this
   */
  public function addEnable(array $keys) {
    $this->batches[] = ['type' => 'enable', 'keys' => $keys];
    return $this;
  }

  /**
   * Take the list of pending updates (from addDownload, addEnable)
   */
  public function fillQueue(): CRM_Queue_Queue {
    $queue = $this->queue;

    // Store some metadata about what's going on. This may help with debugging.
    CRM_Utils_File::createDir($this->getStagingPath(), 'exception');
    file_put_contents($this->getStagingPath('details.json'), json_encode([
      'startTime' => CRM_Utils_Time::date('c'),
      'upId' => $this->upId,
      'queue' => $queue->getName(),
      'batches' => $this->batches,
      'statuses' => CRM_Extension_System::singleton()->getManager()->getStatuses(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    foreach ($this->batches as $batch) {
      switch ($batch['type']) {
        case 'enable':
          $queue->createItem(static::task(ts('Enable %1', [1 => $this->quotedList($batch['keys'])]), 'enable', [$batch['keys']]));
          break;

        case 'download':
          $downloads = $batch['urls'];

          // Download and extract zip files. This is I/O dependent (error-prone), so we do each as a separate (retriable) step.
          foreach ($downloads as $ext => $url) {
            $queue->createItem(static::task(ts('Fetch "%1" from "%2"', [1 => $ext, 2 => $url]), 'fetch', [$ext, $url]));
          }

          // Verify all requirements with a single operation -- _before_ loading the new code.
          // We won't be sensitive to (re)ordering of fetch-tasks, because we only care if the final set is coherent.
          $queue->createItem(static::task(ts('Verify requirements'), 'preverify', [array_keys($downloads)]));

          // Swap-in new folders with a single operation. This should be similar to more sophisticated site-builder
          // workflows. (f you manage a site in git, then "git pull" swaps all code at the same time.) This
          // can't guarantee that all combinations of $downloads work, but at least they'll behave consistently.
          $queue->createItem(static::task(ts('Swap folders'), 'swap', [array_keys($downloads)]));

          // The "swap" and "rebuild" must happen in separate steps.
          if ($batch['autoApply']) {
            $queue->createItem(static::task(ts('Rebuild system'), 'rebuild'));
          }

          $statuses = CRM_Extension_System::singleton()->getManager()->getStatuses();
          $findByStatus = fn(array $matchStatuses) => array_filter(
            array_keys($downloads),
            fn($key) => in_array($statuses[$key] ?? CRM_Extension_Manager::STATUS_UNINSTALLED, $matchStatuses, TRUE)
          );
          $needEnable = $findByStatus([CRM_Extension_Manager::STATUS_UNINSTALLED, CRM_Extension_Manager::STATUS_DISABLED, CRM_Extension_Manager::STATUS_DISABLED_MISSING]);
          $needUpgrade = $findByStatus([CRM_Extension_Manager::STATUS_INSTALLED, CRM_Extension_Manager::STATUS_DISABLED, CRM_Extension_Manager::STATUS_DISABLED_MISSING]);
          if ($batch['autoApply'] && $needEnable) {
            $queue->createItem(static::task(ts('Enable %1', [1 => $this->quotedList($needEnable)]), 'enable', [$needEnable]));
          }
          if ($batch['autoApply'] && $needUpgrade) {
            $queue->createItem(static::task(ts('Upgrade database'), 'upgradeDb'));
          }

          break;

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

  private function quotedList(array $items) {
    // This can at least adapt to quotes and guillemets... we should probably have some more general helpers for lists and conjunctions...
    $template = ts('"%1"');
    return implode(', ', array_map(fn($item) => str_replace('%1', $item, $template), $items));
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
