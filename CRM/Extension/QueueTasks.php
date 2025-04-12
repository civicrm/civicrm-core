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
 * Library of queue-tasks which are useful for extension-management.
 */
class CRM_Extension_QueueTasks {

  /**
   * Download extension ($key) from $url and store it in {$stagingPath}/new/{$key}.
   */
  public static function fetch(CRM_Queue_TaskContext $ctx, string $stagingPath, string $key, string $url): bool {
    $tmpDir = "$stagingPath/tmp";
    $zipFile = "$stagingPath/fetch/$key.zip";
    $stageDir = "$stagingPath/new/$key";

    CRM_Utils_File::createDir($tmpDir, 'exception');
    CRM_Utils_File::createDir(dirname($zipFile), 'exception');
    CRM_Utils_File::createDir(dirname($stageDir), 'exception');

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

    return TRUE;
  }

  /**
   * Scan the downloaded extensions and verify that their requirements are satisfied.
   * This checks requirements as declared in the staging area.
   */
  public static function preverify(CRM_Queue_TaskContext $ctx, string $stagingPath, array $keys): bool {
    $infos = CRM_Extension_System::singleton()->getMapper()->getAllInfos();
    foreach ($keys as $key) {
      $infos[$key] = CRM_Extension_Info::loadFromFile("$stagingPath/new/$key/" . CRM_Extension_Info::FILENAME);
    }

    $errors = CRM_Extension_System::singleton()->getManager()->checkInstallRequirements($keys, $infos);
    if (!empty($errors)) {
      Civi::log()->error('Failed to verify requirements for new downloads in {path}', [
        'path' => $stagingPath,
        'installKeys' => $keys,
        'errors' => $errors,
      ]);
      throw new CRM_Extension_Exception(implode("\n", [
        "Failed to verify requirements for new downloads in {$stagingPath}.",
        ...array_column($errors, 'title'),
        "Consult CiviCRM log for details.",
      ]));
    }

    return TRUE;
  }

  /**
   * Take the extracted code (`stagingDir/new/{key}`) and put it into its final place.
   * Move any old code to the backup (`stagingDir/old/{key}`).
   * Delete the container-cache
   */
  public static function swap(CRM_Queue_TaskContext $ctx, string $stagingPath, array $keys): bool {
    CRM_Utils_File::createDir("$stagingPath/old", 'exception');
    try {
      foreach ($keys as $key) {
        $tmpCodeDir = "$stagingPath/new/$key";
        $backupCodeDir = "$stagingPath/old/$key";

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

    return TRUE;
  }

  public static function rebuild(CRM_Queue_TaskContext $ctx): bool {
    CRM_Core_Invoke::rebuildMenuAndCaches(TRUE, FALSE);
    // FIXME: For 6.1+:, use: Civi::rebuild(['*' => TRUE, 'sessions' => FALSE]);
    return TRUE;
  }

  /**
   * Scan the downloaded extensions and verify that their requirements are satisfied.
   */
  public static function enable(CRM_Queue_TaskContext $ctx, string $stagingPath, array $keys): bool {
    CRM_Extension_System::singleton()->getManager()->enable($keys);
    return TRUE;
  }

  public static function upgradeDb(CRM_Queue_TaskContext $ctx): bool {
    if (CRM_Extension_Upgrades::hasPending()) {
      CRM_Extension_Upgrades::fillQueue($ctx->queue);
    }
    return TRUE;
  }

  public static function cleanup(CRM_Queue_TaskContext $ctx, string $stagingPath): bool {
    CRM_Utils_File::cleanDir($stagingPath, TRUE, FALSE);
    $parent = dirname($stagingPath);
    $siblings = preg_grep('/^\.\.?$/', scandir($parent), PREG_GREP_INVERT);
    if (empty($siblings)) {
      rmdir($parent);
    }
    return TRUE;
  }

}
