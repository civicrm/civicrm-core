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
 * The revisions trait automatically enqueues any functions named 'upgrade_NNNN()'
 * (where NNNN is taken to be a revision number).
 */
trait CRM_Extension_Upgrader_RevisionsTrait {

  /**
   * @return string
   */
  abstract public function getExtensionKey();

  abstract protected function appendTask(string $title, string $funcName, ...$options);

  /**
   * @var array
   *   sorted numerically
   */
  private $revisions;

  /**
   * @var bool
   *   Flag to clean up extension revision data in civicrm_setting
   */
  private $revisionStorageIsDeprecated = FALSE;

  /**
   * Determine if there are any pending revisions.
   *
   * @return bool
   */
  public function hasPendingRevisions() {
    $revisions = $this->getRevisions();
    $currentRevision = $this->getCurrentRevision();

    if (empty($revisions)) {
      return FALSE;
    }
    if (empty($currentRevision)) {
      return TRUE;
    }

    return ($currentRevision < max($revisions));
  }

  /**
   * Add any pending revisions to the queue.
   */
  public function enqueuePendingRevisions() {
    $currentRevision = $this->getCurrentRevision();
    foreach ($this->getRevisions() as $revision) {
      if ($revision > $currentRevision) {
        $titleA = ts('Upgrade %1 to revision %2 (main)', [
          1 => $this->getExtensionKey(),
          2 => $revision,
        ]);
        $titleB = ts('Upgrade %1 to revision %2 (set revision)', [
          1 => $this->getExtensionKey(),
          2 => $revision,
        ]);

        // note: don't use addTask() because it sets weight=-1

        $this->appendTask($titleA, 'upgrade_' . $revision);
        $this->appendTask($titleB, 'setCurrentRevision', $revision);
      }
    }
  }

  /**
   * Get a list of revisions.
   *
   * @return array
   *   revisionNumbers sorted numerically
   */
  public function getRevisions() {
    if (!is_array($this->revisions)) {
      $this->revisions = [];

      $clazz = new \ReflectionClass(get_class($this));
      $methods = $clazz->getMethods();
      foreach ($methods as $method) {
        if (preg_match('/^upgrade_(.*)/', $method->name, $matches)) {
          $this->revisions[] = $matches[1];
        }
      }
      sort($this->revisions, SORT_NUMERIC);
    }

    return $this->revisions;
  }

  public function getCurrentRevision() {
    $revision = CRM_Core_BAO_Extension::getSchemaVersion($this->getExtensionKey());
    if (!$revision) {
      $revision = $this->getCurrentRevisionDeprecated();
    }
    return $revision;
  }

  private function getCurrentRevisionDeprecated() {
    $key = $this->getExtensionKey() . ':version';
    if ($revision = \Civi::settings()->get($key)) {
      $this->revisionStorageIsDeprecated = TRUE;
    }
    return $revision;
  }

  public function setCurrentRevision($revision) {
    CRM_Core_BAO_Extension::setSchemaVersion($this->getExtensionKey(), $revision);
    // clean up legacy schema version store (CRM-19252)
    $this->deleteDeprecatedRevision();
    return TRUE;
  }

  private function deleteDeprecatedRevision() {
    if ($this->revisionStorageIsDeprecated) {
      $setting = new \CRM_Core_BAO_Setting();
      $setting->name = $this->getExtensionKey() . ':version';
      $setting->delete();
      CRM_Core_Error::debug_log_message("Migrated extension schema revision ID for {$this->getExtensionKey()} from civicrm_setting (deprecated) to civicrm_extension.\n");
    }
  }

}
