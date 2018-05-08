<?php
namespace Civi;

use Civi\Setup\Event\CheckAuthorizedEvent;
use Civi\Setup\Event\CheckRequirementsEvent;
use Civi\Setup\Event\CheckInstalledEvent;
use Civi\Setup\UI\Event\UIConstructEvent;
use Civi\Setup\Event\InitEvent;
use Civi\Setup\Event\InstallDatabaseEvent;
use Civi\Setup\Event\InstallFilesEvent;
use Civi\Setup\Event\UninstallDatabaseEvent;
use Civi\Setup\Event\UninstallFilesEvent;
use Civi\Setup\Exception\InitException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Setup {

  const PROTOCOL = '1.0';

  const PRIORITY_START = 2000;
  const PRIORITY_PREPARE = 1000;
  const PRIORITY_MAIN = 0;
  const PRIORITY_LATE = -1000;
  const PRIORITY_END = -2000;

  private static $instance;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * @var \Civi\Setup\Model
   */
  protected $model;

  /**
   * @var LoggerInterface
   */
  protected $log;

  // ----- Static initialization -----

  /**
   * The initialization process loads any `*.civi-setup.php` files and
   * fires the `civi.setup.init` event.
   *
   * @param array $modelValues
   *   List of default configuration options.
   *   Recommended fields: 'srcPath', 'cms'
   * @param callable $pluginCallback
   *   Function which manipulates the list of plugin files.
   *   Use this to add, remove, or re-order callbacks.
   *   function(array $files) => array
   *   Ex: ['hello' => '/var/www/plugins/hello.civi-setup.php']
   * @param LoggerInterface $log
   */
  public static function init($modelValues = array(), $pluginCallback = NULL, $log = NULL) {
    if (!defined('CIVI_SETUP')) {
      define('CIVI_SETUP', 1);
    }

    self::$instance = new Setup();
    self::$instance->model = new \Civi\Setup\Model();
    self::$instance->model->setValues($modelValues);
    self::$instance->dispatcher = new EventDispatcher();
    self::$instance->log = $log ? $log : new NullLogger();

    $pluginDir = dirname(__DIR__) . '/plugins';
    $pluginFiles = array();
    foreach (['*.civi-setup.php', '*/*.civi-setup.php'] as $pattern) {
      foreach ((array) glob("$pluginDir/$pattern") as $file) {
        $key = substr($file, strlen($pluginDir) + 1);
        $key = preg_replace('/\.civi-setup\.php$/', '', $key);
        $pluginFiles[$key] = $file;
      }
    }
    ksort($pluginFiles);

    if ($pluginCallback) {
      $pluginFiles = $pluginCallback($pluginFiles);
    }

    foreach ($pluginFiles as $pluginFile) {
      self::$instance->log->debug('[Setup.php] Load plugin {file}', array(
        'file' => $pluginFile,
      ));
      require $pluginFile;
    }

    $event = new InitEvent(self::$instance->getModel());
    self::$instance->getDispatcher()->dispatch('civi.setup.init', $event);
    // return $event; ...or... return self::$instance;
  }

  /**
   * Assert that this copy of civicrm-setup is compatible with the client.
   *
   * @param string $expectedVersion
   * @throws \Exception
   */
  public static function assertProtocolCompatibility($expectedVersion) {
    if (version_compare(self::PROTOCOL, $expectedVersion, '<')) {
      throw new InitException(sprintf("civicrm-setup is running protocol v%s. This application expects civicrm-setup to support protocol v%s.", self::PROTOCOL, $expectedVersion));
    }
    list ($actualFirst) = explode('.', self::PROTOCOL);
    list ($expectedFirst) = explode('.', $expectedVersion);
    if ($actualFirst > $expectedFirst) {
      throw new InitException(sprintf("civicrm-setup is running protocol v%s. This application expects civicrm-setup to support protocol v%s.", self::PROTOCOL, $expectedVersion));
    }
  }

  /**
   * Assert that the "Setup" subsystem is running.
   *
   * This function is mostly just a placeholder -- in practice, if
   * someone makes a failed call to `assertRunning()`, it will probably
   * manifest as an unknown class/function. But this gives us a pretty,
   * one-line, syntactically-valid way to make the assertion.
   */
  public static function assertRunning() {
    if (!defined('CIVI_SETUP')) {
      exit("Installation plugins must only be loaded by the installer.\n");
    }
  }

  /**
   * @return Setup
   */
  public static function instance() {
    if (self::$instance === NULL) {
      throw new InitException('\Civi\Setup has not been initialized.');
    }
    return self::$instance;
  }

  /**
   * @return \Psr\Log\LoggerInterface
   */
  public static function log() {
    return self::instance()->getLog();
  }

  /**
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  public static function dispatcher() {
    return self::instance()->getDispatcher();
  }

  // ----- Logic ----

  /**
   * Determine whether the current CMS user is authorized to perform
   * installation.
   *
   * @return \Civi\Setup\Event\CheckAuthorizedEvent
   */
  public function checkAuthorized() {
    $event = new CheckAuthorizedEvent($this->getModel());
    return $this->getDispatcher()->dispatch('civi.setup.checkAuthorized', $event);
  }

  /**
   * Determine whether the local environment meets system requirements.
   *
   * @return \Civi\Setup\Event\CheckRequirementsEvent
   */
  public function checkRequirements() {
    $event = new CheckRequirementsEvent($this->getModel());
    return $this->getDispatcher()->dispatch('civi.setup.checkRequirements', $event);
  }

  /**
   * Determine whether the setting and/or schema are already installed.
   *
   * @return \Civi\Setup\Event\CheckInstalledEvent
   */
  public function checkInstalled() {
    $event = new CheckInstalledEvent($this->getModel());
    return $this->getDispatcher()->dispatch('civi.setup.checkInstalled', $event);
  }

  /**
   * Create the settings file.
   *
   * @return \Civi\Setup\Event\InstallFilesEvent
   */
  public function installFiles() {
    $event = new InstallFilesEvent($this->getModel());
    return $this->getDispatcher()->dispatch('civi.setup.installFiles', $event);
  }

  /**
   * Create the database schema.
   *
   * @return \Civi\Setup\Event\InstallDatabaseEvent
   */
  public function installDatabase() {
    $event = new InstallDatabaseEvent($this->getModel());
    return $this->getDispatcher()->dispatch('civi.setup.installDatabase', $event);
  }

  /**
   * Remove the settings file.
   *
   * @return \Civi\Setup\Event\UninstallFilesEvent
   */
  public function uninstallFiles() {
    $event = new UninstallFilesEvent($this->getModel());
    return $this->getDispatcher()->dispatch('civi.setup.uninstallFiles', $event);
  }

  /**
   * Remove the database schema.
   *
   * @return \Civi\Setup\Event\UninstallDatabaseEvent
   */
  public function uninstallDatabase() {
    $event = new UninstallDatabaseEvent($this->getModel());
    return $this->getDispatcher()->dispatch('civi.setup.uninstallDatabase', $event);
  }

  /**
   * Create a page-controller for a web-based installation form.
   *
   * @return \Civi\Setup\UI\Event\UIConstructEvent
   */
  public function createController() {
    $event = new UIConstructEvent($this->getModel());
    return $this->getDispatcher()->dispatch('civi.setupui.construct', $event);
  }

  // ----- Accessors -----

  /**
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  public function getDispatcher() {
    return $this->dispatcher;
  }

  /**
   * @return \Civi\Setup\Model
   */
  public function getModel() {
    return $this->model;
  }

  /**
   * @return \Psr\Log\LoggerInterface
   */
  public function getLog() {
    return $this->log;
  }

}
