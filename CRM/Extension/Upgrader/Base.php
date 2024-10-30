<?php

/**
 * Base class which provides helpers to execute upgrade logic.
 *
 * @since 5.38
 * LIFECYCLE METHODS: Subclasses may optionally define
 *   - preInstall() [5.75+]
 *   - install(), postInstall(), uninstall(), enable(), disable() [5.38+]
 *
 * UPGRADE METHODS: Subclasses may define any number of methods named "upgrade_NNNN()".
 * Each value of NNNN is treated as a new schema revision. (See also: RevisionsTrait)
 *
 * QUEUE METHODS: Upgrade tasks execute within a queue. If an upgrader needs to perform
 * a large amount of work, it can use "addTask()" / "prependTask()" / "appendTask()".
 * (See also: QueueTrait)
 *
 * EXECUTE METHODS: When writing lifecycle methods, upgrade methods, or queue
 * tasks, you may wish to execute common steps like "run a SQL file".
 * (See also: TasksTrait)
 */
class CRM_Extension_Upgrader_Base implements CRM_Extension_Upgrader_Interface {

  use CRM_Extension_Upgrader_IdentityTrait;
  use CRM_Extension_Upgrader_QueueTrait;
  use CRM_Extension_Upgrader_RevisionsTrait;
  use CRM_Extension_Upgrader_TasksTrait;
  use CRM_Extension_Upgrader_SchemaTrait;

  /**
   * {@inheritDoc}
   */
  public function notify(string $event, array $params = []) {
    $cb = [$this, 'on' . ucfirst($event)];
    return is_callable($cb) ? call_user_func_array($cb, $params) : NULL;
  }

  // ******** Hook delegates ********

  /**
   * Run pre-installation steps. Ex: Validate system environment
   *
   * This runs BEFORE creating any sql tables.
   *
   * @return void
   */
  public function onPreInstall() {
    if (is_callable([$this, 'preInstall'])) {
      $this->preInstall();
    }
  }

  /**
   * Run early installation steps. Ex: Create new MySQL table.
   *
   * This dispatches directly to each new extension. You will only receive notices for your own installation.
   *
   * If multiple extensions are installed simultaneously, they will all run
   * `hook_install`/`hook_enable` back-to-back (in order of dependency).
   *
   * This runs AFTER creating tables, but
   * BEFORE refreshing major caches and services (such as
   * `ManagedEntities` and `CRM_Logging_Schema`).
   *
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
   */
  public function onInstall() {
    $files = glob($this->getExtensionDir() . '/sql/*_install.sql');
    if (is_array($files)) {
      foreach ($files as $file) {
        CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $file);
      }
    }
    $files = glob($this->getExtensionDir() . '/sql/*_install.mysql.tpl');
    if (is_array($files)) {
      foreach ($files as $file) {
        $this->executeSqlTemplate($file);
      }
    }
    $files = glob($this->getExtensionDir() . '/xml/*_install.xml');
    if (is_array($files)) {
      foreach ($files as $file) {
        $this->executeCustomDataFileByAbsPath($file);
      }
    }
    if (is_callable([$this, 'install'])) {
      $this->install();
    }
  }

  /**
   * Run later installation steps. Ex: Call a bespoke API-job for the first time.
   *
   * This dispatches directly to each new extension. You will only receive notices for your own installation.
   *
   * If multiple extensions are installed simultaneously, they will all run
   * `hook_postInstall` back-to-back (in order of dependency).
   *
   * This runs AFTER refreshing major caches and services (such as
   * `ManagedEntities` and `CRM_Logging_Schema`).
   *
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
   */
  public function onPostInstall() {
    $revisions = $this->getRevisions();
    if (!empty($revisions)) {
      $this->setCurrentRevision(max($revisions));
    }
    if (is_callable([$this, 'postInstall'])) {
      $this->postInstall();
    }
  }

  /**
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_unnstall
   */
  public function onUninstall() {
    $files = glob($this->getExtensionDir() . '/sql/*_uninstall.mysql.tpl');
    if (is_array($files)) {
      foreach ($files as $file) {
        $this->executeSqlTemplate($file);
      }
    }
    if (is_callable([$this, 'uninstall'])) {
      $this->uninstall();
    }
    $files = glob($this->getExtensionDir() . '/sql/*_uninstall.sql');
    if (is_array($files)) {
      foreach ($files as $file) {
        CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $file);
      }
    }
  }

  /**
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
   */
  public function onEnable() {
    // stub for possible future use
    if (is_callable([$this, 'enable'])) {
      $this->enable();
    }
  }

  /**
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
   */
  public function onDisable() {
    // stub for possible future use
    if (is_callable([$this, 'disable'])) {
      $this->disable();
    }
  }

  /**
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
   */
  public function onUpgrade($op, ?CRM_Queue_Queue $queue = NULL) {
    switch ($op) {
      case 'check':
        return [$this->hasPendingRevisions()];

      case 'enqueue':
        $this->setQueue($queue);
        return $this->enqueuePendingRevisions();

      default:
    }
  }

}
