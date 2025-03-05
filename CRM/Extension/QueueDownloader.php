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
 *     $dl->createQueue(),
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

  /**
   * @param string|null $upId
   *   Ex: 20250607_abcd1234abcd1234
   */
  public function __construct(?string $upId = NULL) {
    $this->upId = $upId ?: (CRM_Utils_Time::date('Y-m-d') . '-' . CRM_Utils_String::createRandom(16, CRM_Utils_String::ALPHANUMERIC));
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
   * @param CRM_Queue_Queue $queue
   * @param array $downloads
   *   Ex: ['ext1' => 'https://example.com/ext1/releases/1.0.zip']
   * @param bool $upgradeDb
   *   Whether to run database updates
   * @param bool $cleanup
   *   Whether to delete temporary files and backup files at the end.
   * @return \CRM_Queue_Queue
   */
  public function fillQueue(CRM_Queue_Queue $queue, array $downloads, bool $upgradeDb = TRUE, bool $cleanup = TRUE): CRM_Queue_Queue {
    if (empty($downloads)) {
      throw new CRM_Core_Exception("Cannot build download queue. No downloads requested!");
    }

    // Store some metadata about what's going on. This may help with debugging.
    $this->mkdir($this->getStagingPath());
    file_put_contents($this->getStagingPath('details.json'), json_encode([
      'startTime' => CRM_Utils_Time::date('c'),
      'upId' => $this->upId,
      'queue' => $queue->getName(),
      'downloads' => $downloads,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    // Download and extract zip files. This is I/O dependent (error-prone), so we do each as a separate (retriable) step.
    foreach ($downloads as $ext => $url) {
      $queue->createItem(
        static::subtask(ts('Fetch "%1" from "%2"', [1 => $ext, 2 => $url]), 'fetch', [$ext, $url]),
        ['weight' => 100]
      );
    }

    // Verify all requirements with a single operation.
    // We won't be sensitive to (re)ordering of fetch-tasks, because we only care if the final set is coherent.
    $queue->createItem(
      static::subtask(ts('Verify requirements'), 'verify', [array_keys($downloads)]),
      ['weight' => 200]
    );

    // Swap-in new folders with a single operation. This should be similar to more sophisticated site-builder
    // workflows. (f you manage a site in git, then "git pull" swaps all code at the same time.) This
    // can't guarantee that all combinations of $downloads work, but at least they'll behave consistently.
    $queue->createItem(
      static::subtask(ts('Swap folders'), 'swap', [array_keys($downloads)]),
      ['weight' => 200]
    );

    // The "swap" and "rebuild" must happen in separate steps.
    $queue->createItem(
      static::subtask(ts('Rebuild system'), 'rebuild'),
      ['weight' => 300]
    );

    if ($upgradeDb) {
      $queue->createItem(
        static::subtask(ts('Upgrade database'), 'upgradeDb'),
        ['weight' => 400]
      );
    }

    if ($cleanup) {
      $queue->createItem(
        static::subtask(ts('Cleanup workspace'), 'cleanup'),
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
  protected function subtask(string $title, string $method, array $args = []): CRM_Queue_Task {
    return new CRM_Queue_Task(
      [static::class, 'runSubtask'],
      [$this->upId, $method, $args],
      $title
    );
  }

  public static function runSubtask(CRM_Queue_TaskContext $ctx, string $upId, string $name, array $args = []): bool {
    $instance = new static($upId);
    $method = 'subtask' . ucfirst($name);
    $instance->{$method}($ctx, ...$args);
    return TRUE;
  }

  /**
   * Download extension ($key) from $url and store it in {$stagingPath}/new/{$key}.
   */
  public function subtaskFetch(CRM_Queue_TaskContext $ctx, string $key, string $url): void {
    $tmpDir = $this->getStagingPath('tmp');
    $zipFile = $this->getStagingPath('fetch', $key . '.zip');
    $stageDir = $this->getStagingPath('new', $key);
    $this->mkdir([$tmpDir, dirname($zipFile), dirname($stageDir)]);

    if (file_exists($stageDir)) {
      // In case we're retrying from a prior failure.
      CRM_Utils_File::cleanDir($stageDir, TRUE, FALSE);
    }

    $downloader = CRM_Extension_System::singleton()->getDownloader();
    if (!$downloader->fetch($url, $zipFile)) {
      throw new CRM_Extension_Exception("Failed to download: $url");
    }

    $extractedZipPath = $downloader->extractFiles($key, $zipFile, $tmpDir);
    if (!$extractedZipPath) {
      throw new CRM_Extension_Exception("Failed to extract: $zipFile");
    }

    if (!$downloader->validateFiles($key, $extractedZipPath)) {
      throw new CRM_Extension_Exception("Failed to validate $extractedZipPath. Consult CiviCRM log for details.");
      // FIXME: Might be nice to show errors immediately, but we've got bigger fish to fry right now.
    }

    if (!rename($extractedZipPath, $stageDir)) {
      throw new CRM_Extension_Exception("Failed to rename $extractedZipPath to $stageDir");
    }
  }

  /**
   * Scan the downloaded extensions and verify that their requirements are satisfied.
   */
  public function subtaskVerify(CRM_Queue_TaskContext $ctx, array $keys): void {
    $infos = CRM_Extension_System::singleton()->getMapper()->getAllInfos();
    foreach ($keys as $key) {
      $infos[$key] = CRM_Extension_Info::loadFromFile($this->getStagingPath('new', $key, CRM_Extension_Info::FILENAME));
    }

    $errors = CRM_Extension_System::singleton()->getManager()->checkInstallRequirements($keys, $infos);
    if (!empty($errors)) {
      $path = $this->getStagingPath();
      Civi::log()->error('Failed to verify requirements for new downloads in {path}', [
        'path' => $path,
        'installKeys' => $keys,
        'errors' => $errors,
      ]);
      throw new CRM_Extension_Exception(implode("\n", [
        "Failed to verify requirements for new downloads in {$path}.",
        ...array_column($errors, 'title'),
        "Consult CiviCRM log for details.",
      ]));
    }
  }

  /**
   * Take the extracted code (`stagingDir/new/{key}`) and put it into its final place.
   * Move any old code to the backup (`stagingDir/old/{key}`).
   * Delete the container-cache
   */
  public function subtaskSwap(CRM_Queue_TaskContext $ctx, array $keys): void {
    $this->mkdir($this->getStagingPath('old'));
    try {
      foreach ($keys as $key) {
        $tmpCodeDir = $this->getStagingPath('new', $key);
        $backupCodeDir = $this->getStagingPath('old', $key);

        CRM_Extension_System::singleton()->getManager()->replace($tmpCodeDir, $backupCodeDir, FALSE);
        // What happens when you call replace(.., refresh: false)? Varies by type:
        // - For report/search/payment-extensions, it runs the uninstallation/reinstallation routines.
        // - For module-extensions, it swaps the folders and clears the class-index.

        // Arguably, for DownloadQueue, we should only clear class-index after all code is swapped,
        // but it's messier to write that patch, and it's not clear if it's needed.
      }
    }
    finally {
      // Delete `CachedCiviContainer.*.php`, `CachedExtLoader.*.php`, and similar.
      $config = CRM_Core_Config::singleton();
      // $config->cleanup(1);
      $config->cleanupCaches(FALSE);
    }
  }

  public function subtaskRebuild(CRM_Queue_TaskContext $ctx): void {
    CRM_Core_Invoke::rebuildMenuAndCaches(TRUE, FALSE);
    // FIXME: For 6.1+:, use: Civi::rebuild(['*' => TRUE, 'sessions' => FALSE]);
  }

  public function subtaskUpgradeDb(CRM_Queue_TaskContext $ctx): void {
    if (CRM_Extension_Upgrades::hasPending()) {
      CRM_Extension_Upgrades::fillQueue($ctx->queue);
    }
  }

  public function subtaskCleanup(): void {
    CRM_Utils_File::cleanDir($this->getStagingPath(), TRUE, FALSE);
  }

  private function mkdir($paths): void {
    $paths = (array) $paths;
    foreach ($paths as $path) {
      if (!is_dir($path)) {
        if (!mkdir($path, 0777, TRUE)) {
          throw new CRM_Core_Exception("Failed to create directory: $path");
        }
      }
    }
  }

}
