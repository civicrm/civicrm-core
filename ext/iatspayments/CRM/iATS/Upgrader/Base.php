<?php

/**
 * @file
 * AUTO-GENERATED FILE -- Civix may overwrite any changes made to this file.
 */

/**
 * Base class which provides helpers to execute upgrade logic.
 */
class CRM_iATS_Upgrader_Base {

  /**
   * @var variessubclassofhtis
   */
  static $instance;

  /**
   * @var CRM_Queue_TaskContext
   */
  protected $ctx;

  /**
   * @var stringegcomexamplemyextension
   */
  protected $extensionName;

  /**
   * @var stringfullpathtotheextensionssourcetree
   */
  protected $extensionDir;

  /**
   * @var arrayrevisionNumbersortednumerically
   */
  private $revisions;

  /**
   * Obtain a refernece to the active upgrade handler.
   */
  static public function instance() {
    if (!self::$instance) {
      // FIXME auto-generate.
      self::$instance = new CRM_iATS_Upgrader(
        'com.iatspayments.civicrm',
        realpath(__DIR__ . '/../../../')
      );
    }
    return self::$instance;
  }

  /**
   * Adapter that lets you add normal (non-static) member functions to the queue.
   *
   * Note: Each upgrader instance should only be associated with one
   * task-context; otherwise, this will be non-reentrant.
   *
   * @code
   * CRM_iATS_Upgrader_Base::_queueAdapter($ctx, 'methodName', 'arg1', 'arg2');
   * @endcode
   */
  static public function _queueAdapter() {
    $instance = self::instance();
    $args = func_get_args();
    $instance->ctx = array_shift($args);
    $instance->queue = $instance->ctx->queue;
    $method = array_shift($args);
    return call_user_func_array(array($instance, $method), $args);
  }

  /**
   *
   */
  public function __construct($extensionName, $extensionDir) {
    $this->extensionName = $extensionName;
    $this->extensionDir = $extensionDir;
  }

  // ******** Task helpers ********.
  /**
   * Run a CustomData file.
   *
   * @param string $relativePath
   *   the CustomData XML file path (relative to this extension's dir)
   *
   * @return bool
   */
  public function executeCustomDataFile($relativePath) {
    $xml_file = $this->extensionDir . '/' . $relativePath;
    return $this->executeCustomDataFileByAbsPath($xml_file);
  }

  /**
   * Run a CustomData file.
   *
   * @param string $xml_file
   *   the CustomData XML file path (absolute path)
   *
   * @return bool
   */
  protected static function executeCustomDataFileByAbsPath($xml_file) {
    require_once 'CRM/Utils/Migrate/Import.php';
    $import = new CRM_Utils_Migrate_Import();
    $import->run($xml_file);
    return TRUE;
  }

  /**
   * Run a SQL file.
   *
   * @param string $relativePath
   *   the SQL file path (relative to this extension's dir)
   *
   * @return bool
   */
  public function executeSqlFile($relativePath) {
    CRM_Utils_File::sourceSQLFile(
      CIVICRM_DSN,
      $this->extensionDir . '/' . $relativePath
    );
    return TRUE;
  }

  /**
   * Run one SQL query.
   *
   * This is just a wrapper for CRM_Core_DAO::executeSql, but it
   * provides syntatic sugar for queueing several tasks that
   * run different queries.
   */
  public function executeSql($query, $params = array()) {
    // FIXME verify that we raise an exception on error.
    CRM_Core_DAO::executeSql($query, $params);
    return TRUE;
  }

  /**
   * Syntatic sugar for enqueuing a task which calls a function
   * in this class. The task is weighted so that it is processed
   * as part of the currently-pending revision.
   *
   * After passing the $funcName, you can also pass parameters that will go to
   * the function. Note that all params must be serializable.
   */
  public function addTask($title) {
    $args = func_get_args();
    $title = array_shift($args);
    $task = new CRM_Queue_Task(
      array(get_class($this), '_queueAdapter'),
      $args,
      $title
    );
    return $this->queue->createItem($task, array('weight' => -1));
  }

  // ******** Revision-tracking helpers ********.
  
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
  public function enqueuePendingRevisions(CRM_Queue_Queue $queue) {
    $this->queue = $queue;

    $currentRevision = $this->getCurrentRevision();
    foreach ($this->getRevisions() as $revision) {
      if ($revision > $currentRevision) {
        $title = ts('Upgrade %1 to revision %2', array(
          1 => $this->extensionName,
          2 => $revision,
        ));

        // note: don't use addTask() because it sets weight=-1.
        $task = new CRM_Queue_Task(
          array(get_class($this), '_queueAdapter'),
          array('upgrade_' . $revision),
          $title
        );
        $this->queue->createItem($task);

        $task = new CRM_Queue_Task(
          array(get_class($this), '_queueAdapter'),
          array('setCurrentRevision', $revision),
          $title
        );
        $this->queue->createItem($task);
      }
    }
  }

  /**
   * Get a list of revisions.
   *
   * @return array(revisionNumbers) sorted numerically
   */
  public function getRevisions() {
    if (!is_array($this->revisions)) {
      $this->revisions = array();

      $clazz = new ReflectionClass(get_class($this));
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

  /**
   *
   */
  public function getCurrentRevision() {
    // Return CRM_Core_BAO_Extension::getSchemaVersion($this->extensionName);.
    $key = $this->extensionName . ':version';
    return CRM_Core_BAO_Setting::getItem('Extension', $key);
  }

  /**
   *
   */
  public function setCurrentRevision($revision) {
    // We call this during hook_civicrm_install, but the underlying SQL
    // UPDATE fails because the extension record hasn't been INSERTed yet.
    // Instead, track revisions in our own namespace.
    // CRM_Core_BAO_Extension::setSchemaVersion($this->extensionName, $revision);.
    $key = $this->extensionName . ':version';
    CRM_Core_BAO_Setting::setItem($revision, 'Extension', $key);
    return TRUE;
  }

  /**
   * Hook delegates ********.
   */
  public function onInstall() {
    foreach (glob($this->extensionDir . '/sql/*_install.sql') as $file) {
      CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $file);
    }
    foreach (glob($this->extensionDir . '/xml/*_install.xml') as $file) {
      $this->executeCustomDataFileByAbsPath($file);
    }
    if (is_callable(array($this, 'install'))) {
      $this->install();
    }
    $revisions = $this->getRevisions();
    if (!empty($revisions)) {
      $this->setCurrentRevision(max($revisions));
    }
  }

  /**
   *
   */
  public function onUninstall() {
    if (is_callable(array($this, 'uninstall'))) {
      $this->uninstall();
    }
    foreach (glob($this->extensionDir . '/sql/*_uninstall.sql') as $file) {
      CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $file);
    }
    $this->setCurrentRevision(NULL);
  }

  /**
   *
   */
  public function onEnable() {
    // Stub for possible future use.
    if (is_callable(array($this, 'enable'))) {
      $this->enable();
    }
  }

  /**
   *
   */
  public function onDisable() {
    // Stub for possible future use.
    if (is_callable(array($this, 'disable'))) {
      $this->disable();
    }
  }

  /**
   *
   */
  public function onUpgrade($op, CRM_Queue_Queue $queue = NULL) {
    switch ($op) {
      case 'check':
        return array($this->hasPendingRevisions());

      case 'enqueue':
        return $this->enqueuePendingRevisions($queue);

      default:
    }
  }

}
