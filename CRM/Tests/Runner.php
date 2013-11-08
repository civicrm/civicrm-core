<?php

class CRM_Tests_Runner {
  const DRUPAL_SOURCE_URL = "http://ftp.drupal.org/files/projects/drupal-7.23.tar.gz";
  const TEST_SITE_URL = "http://civicrm-tests.local/";
  public $base_path;
  public $civicrm_db_settings;
  public $drupal_db_settinsg;
  public $db = NULL;
  public $phpunit_args;
  public $settings_file_path;
  public $use_mysql_ram_sever;
  public $use_selenium;

  function __construct($options = array()) {
    $this->base_path = CRM_Utils_Array::fetch('base_path', $options, getcwd());
    $this->tmp_path = CRM_Utils_Path::join($this->base_path, "..", "tmp");
    CRM_Utils_Path::mkdir_p_if_not_exists($this->tmp_path);
    $this->tmp_path = realpath($this->tmp_path);
    $this->drupal_path = CRM_Utils_Path::join($this->tmp_path, 'drupal');
    $this->phpunit_args = CRM_Utils_Array::fetch('php-unit', $options, 'AllTests');
    $this->settings_file_path = CRM_Utils_Path::join($this->base_path, 'tests', 'phpunit', 'CiviTest', 'civicrm.settings.local.php');
    $this->dist_settings_file_path = CRM_Utils_Path::join($this->base_path, 'tests', 'phpunit', 'CiviTest', 'civicrm.settings.dist.php');
    $this->use_mysql_ram_server = CRM_Utils_Array::fetch('mysql-ram-server', $options, FALSE);
    $this->use_selenium = !CRM_Utils_Array::fetch('no-selenium', $options, FALSE);
  }

  function build_db_files() {
    $orig_dir = CRM_Utils_Dir::getcwd();
    $xml_dir_path = CRM_Utils_Path::join($this->base_path, 'xml');
    $this->shell("php GenCode.php", array('directory' => $xml_dir_path));
  }

  function clean() {
    $this->mysql_ram_server = new CRM_DB_MySQLRAMServer($this->civicrm_db_settings, $this->mysql_ram_server_options());
    $this->mysql_ram_server->clean();
    unlink(CRM_Utils_Path::join($this->base_path, 'sql', 'civicrm.mysql'));
    $this->shell("rm -r {$this->tmp_path}");
  }

  function clear_database() {
    $this->db->exec("DROP DATABASE IF EXISTS {$this->civicrm_db_settings->database}");
    $this->db->exec("CREATE DATABASE {$this->civicrm_db_settings->database}");
  }

  function connect_to_db() {
    if ($this->db == NULL) {
      try {
        $this->db = new PDO($this->civicrm_db_settings->toPDODSN(array('no_database' => TRUE)), $this->civicrm_db_settings->username, $this->civicrm_db_settings->password);
      } catch (PDOException $e) {
        if ($e->getCode() != 1045) {
          throw $e;
        } else {
          throw new Exception("Unable to login to the test database ({$this->civicrm_db_settings->database}) using user name '{$this->civicrm_db_settings->username}' and password '{$this->civicrm_db_settings->password}'. Please update the settings in {$this->settings_file_path} to point to the database you want to use.");
        }
      }
      $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $this->db;
  }

  function fetch_civicrm_drupal() {
    $this->shell("git clone https://github.com/civicrm/civicrm-drupal.git drupal");
  }

  function install_drupal() {
    $civicrm_drupal_path = CRM_Utils_Path::join($this->base_path, 'drupal');
    if (!file_exists($civicrm_drupal_path)) {
      $this->fetch_civicrm_drupal();
    }
    $canary_file_path = CRM_Utils_Path::join($this->drupal_path, 'index.php');
    if (!file_exists($canary_file_path)) {
      $this->unpack_drupal();
      $this->setup_apache();
    }
    $drupal_settings_path = CRM_Utils_Path::join($this->drupal_path, 'sites', 'default', 'settings.php');
    if (!file_exists($drupal_settings_path)) {
      $this->setup_drupal_settings();
    }
    $civicrm_settings_path = CRM_Utils_Path::join($this->drupal_path, 'sites', 'default', 'civicrm.settings.php');
    if (!file_exists($civicrm_settings_path)) {
      $this->setup_civicrm_drupal_module();
    }
  }

  function load_civicrm_database() {
    $this->mysql_base_command = "mysql -u '{$this->civicrm_db_settings->username}' --password='{$this->civicrm_db_settings->password}' -h {$this->civicrm_db_settings->host}";
    if ($this->civicrm_db_settings->port) {
      $this->mysql_base_command .= " -P {$this->civicrm_db_settings->port}";
    }
    $this->mysql_base_command .= " {$this->civicrm_db_settings->database}";
    $this->mysql_load_from_file_path(CRM_Utils_Path::join($this->base_path, 'sql', 'civicrm.mysql'));
    $this->mysql_load_from_file_path(CRM_Utils_Path::join($this->base_path, 'sql', 'civicrm_generated.mysql'));
  }

  function mysql_ram_server_options() {
    return array(
      'base_path' => $this->base_path,
    );
  }

  function mysql_load_from_file_path($file_path) {
    $this->shell("{$this->mysql_base_command} < $file_path");
  }

  function run() {
    if (!file_exists($this->settings_file_path)) {
      $settings_file = CRM_Utils_File::open($this->settings_file_path, 'w');
      $host = 'localhost';
      $port = '3306';
      if ($this->use_mysql_ram_server) {
        $host = '127.0.0.1';
        $port = '3307';
      }
      $civicrm_database_settings = array(
        'database' => 'civicrm_tests_dev',
        'driver' => 'mysql',
        'host' => $host,
        'password' => '',
        'port' => $port,
        'username' => 'root',
      );
      $db_settings = new CRM_DB_Settings($civicrm_database_settings);
      $civicrm_dsn = $db_settings->toCiviDSN();
      $settings_file_contents = <<<EOS
<?php
define('CIVICRM_DSN', "$civicrm_dsn");
EOS;
      CRM_Utils_File::write($settings_file, $settings_file_contents);
      CRM_Utils_File::close($settings_file);
    }
    require_once($this->settings_file_path);
    require_once($this->dist_settings_file_path);
    $this->civicrm_db_settings = new CRM_DB_Settings();
    $civicrm_database_settings['database'] = 'civicrm_tests_drupal';
    $this->drupal_db_settings = new CRM_DB_Settings($civicrm_database_settings);
    if ($this->use_mysql_ram_server) {
      $this->start_mysql_ram_server();
    }
    if (!file_exists(CRM_Utils_Path::join($this->base_path, "sql", "civicrm.mysql"))) {
      $this->build_db_files();
    }
    $this->connect_to_db();
    $this->clear_database();
    $this->load_civicrm_database();
    if ($this->use_selenium) {
      $this->install_drupal();
      $this->setup_selenium();
    }
    $this->run_tests();
  }

  function run_tests() {
    $command = "./tools/scripts/phpunit AllTests";
    $GLOBALS['base_dir'] = $this->base_path;
    $include_paths = array();
    $include_paths[] = $this->base_path;
    $include_paths[] = CRM_Utils_Path::join($this->base_path, 'tests', 'phpunit');
    $include_paths[] = CRM_Utils_Path::join($this->base_path, 'packages');
    ini_set('safe_mode', 0);
    ini_set('include_path', implode(PATH_SEPARATOR, $include_paths) . PATH_SEPARATOR . ini_get('include_path'));
    #  Relying on system timezone setting produces a warning,
    #  doing the following prevents the warning message
    if ( file_exists( '/etc/timezone' ) ) {
      $timezone = trim( file_get_contents( '/etc/timezone' ) );
      if ( ini_set('date.timezone', $timezone ) === false ) {
        echo "ini_set( 'date.timezone', '$timezone' ) failed\n";
      }
    }
    ini_set('memory_limit', '2G');
    error_reporting( E_ALL );
    require 'PHPUnit/TextUI/Command.php';
    define('PHPUnit_MAIN_METHOD', 'PHPUnit_TextUI_Command::main');
    $autoloader_path = CRM_Utils_Path::join($this->base_path, 'packages', 'PHPUnit', 'Autoload.php');
    require_once($autoloader_path);
    $phpunit_args = array('phpunit');
    $phpunit_args = array_merge($phpunit_args, explode(' ', $this->phpunit_args));
    $_SERVER['argv'] = $phpunit_args;
    $phpunit_command = new PHPUnit_TextUI_Command();
    $this->db = NULL;
    $phpunit_command->run($phpunit_args, TRUE);
  }

  function setup_selenium() {
    $selenium_settings_file_path = CRM_Utils_Path::join($this->base_path, 'tests', 'phpunit', 'CiviTest', 'CiviSeleniumSettings.php');
    if (!file_exists($selenium_settings_file_path)) {
      $selenium_settings_template_file_path = CRM_Utils_Path::join($this->base_path, 'tests', 'phpunit', 'CiviTest', 'CiviSeleniumSettings.php.txt');
      $result = copy($selenium_settings_template_file_path, $selenium_settings_file_path);
      if ($result === FALSE) {
        throw new Exception("Error copying '$selenium_settings_template_file_path' to '$selenium_settings_file_path': " . print_r(error_get_last(), TRUE));
      }
    }
    $selenium_base_path = CRM_Utils_Path::join($this->base_path, 'packages', 'SeleniumRC');
    $selenium_firefox_profile_path = CRM_Utils_Path::join($selenium_base_path, 'BrowserProfiles', 'firefox');
    $selenium_jar_path = CRM_Utils_Path::join($selenium_base_path, 'selenium-server', 'selenium-server-standalone-2.35.0.jar');
    $selenium_cmd = "java -jar $selenium_jar_path -firefoxProfileTemplate $selenium_firefox_profile_path > selenium.log 2>&1";
    print("$selenium_cmd\n");
    $this->selenium_pid = pcntl_fork();
    if ($this->selenium_pid == -1)
    {
      $errno = posix_get_last_error();
      throw new Exception("Error forking off selenium: $errno: " . posix_strerror($errno) . "\n");
    }
    elseif ($this->selenium_pid == 0)
    {
      $cmd = '/bin/sh';
      $args = array('-c', $selenium_cmd);
      pcntl_exec($cmd, $args);
      throw new Execption("Error executing '$cmd' " . implode(' ', $args));
    }
    $result = CRM_Utils_Network::waitForServiceStartup('127.0.0.1', 4444, 10);
    if ($result === FALSE) {
      throw new Exception("Error launching Selenium server, expected to see it on port 4444, but can't connect to it. Check selenium.log for details.");
    }
    print("********************************************************************************\n");
    print(" There is now a Selenium server on port 4444.\n");
    print("********************************************************************************\n");
  }

  function setup_apache() {
    $url_components = parse_url(self::TEST_SITE_URL);
    $apache_settings = <<<EOS
<VirtualHost *:80>
  DocumentRoot {$this->drupal_path}
  ServerName {$url_components['host']}
</VirtualHost>
EOS;
    $apache_settings_path = CRM_Utils_Path::join($this->tmp_path, 'civicrm-test.apache.conf');
    $result = file_put_contents($apache_settings_path, $apache_settings);
    if ($result === FALSE) {
      throw new Exception("Error creating example apache file at '$apache_settings_path': " . print_r(error_get_last, TRUE));
    }
    print("********************************************************************************\n");
    print(" You need to have an HTTP server that can serve PHP from:\n {$this->drupal_path}\n\n at the URL:\n " . self::TEST_SITE_URL . "\n\n");
    print(" We have provided a sample Apache configuration file at:\n {$apache_settings_path}\n\n");
    print(" You may also need to add this to your /etc/hosts file:\n 127.0.0.1 {$url_components['host']}\n");
    print("********************************************************************************\n");
  }

  function setup_civicrm_drupal_module() {
    $civicrm_module_path = CRM_Utils_Path::join($this->drupal_path, 'sites', 'all', 'modules', 'civicrm');
    if (file_exists($civicrm_module_path)) {
      if (!is_link($civicrm_module_path)) {
        throw new Exception("We expect $civicrm_module_path to be a link to {$this->base_path}, but it isn't a symlink.");
      }
    } else {
      $result = symlink($this->base_path, $civicrm_module_path);
      if ($result === FALSE) {
        throw new Exception("Error creating symlink to {$civicrm_module_path} from {$this->base_path}: " . print_r(error_get_last(), TRUE));
      }
    }
    $template_values = array(
      'cms' => 'Drupal',
      'CMSdbUser' => $this->drupal_db_settings->username,
      'CMSdbPass' => $this->drupal_db_settings->password,
      'CMSdbHost' => "{$this->drupal_db_settings->host}:{$this->drupal_db_settings->port}",
      'CMSdbName' => $this->drupal_db_settings->database,
      'dbUser' => $this->civicrm_db_settings->username,
      'dbPass' => $this->civicrm_db_settings->password,
      'dbHost' => "{$this->civicrm_db_settings->host}:{$this->civicrm_db_settings->port}",
      'dbName' => $this->civicrm_db_settings->database,
      'crmRoot' => $this->base_path,
      'templateCompileDir' => CRM_Utils_Path::join($this->tmp_path, 'templates_c'),
      'baseURL' => self::TEST_SITE_URL,
      'siteKey' => 'phpunittestfakekey',
    );
    $settings_template = new CRM_Utils_SettingsTemplate($this->base_path, $template_values);
    $civicrm_settings_path = CRM_Utils_Path::join($this->drupal_path, 'sites', 'default', 'civicrm.settings.php');
    $settings_template->install($civicrm_settings_path);
    $command = "drush -y pm-enable civicrm";
    $this->shell($command, array('directory' => $this->drupal_path, 'throw_exception_on_nonzero' => FALSE));
  }

  function setup_drupal_settings() {
    $drupal_dsn = $this->drupal_db_settings->toDrupalDSN();
    $drush_install_cmd = "drush -y site-install standard --db-url='{$drupal_dsn}' --site-name='CiviCRM Tests' --account-name=admin --account-pass=admin";
    $this->shell($drush_install_cmd, array('directory' => $this->drupal_path));
    $drupal_settings_path = CRM_Utils_Path::join($this->drupal_path, 'sites', 'default');
    $result = chmod($drupal_settings_path, 0755);
    if ($result === FALSE) {
      throw new Exception("Error settings permissions on $drupal_settings_path to 0755: " . print_r(error_get_last, TRUE));
    }
  }

  function shell($cmd, $options = array()) {
    /*
     * We can't rely on autoload because if CRM_Utils_Shell isn't already
     * loaded here, the chdir will mess the paths up and the autoloader
     * won't be able to find it.
     */
    require_once('CRM/Utils/Shell.php');
    $orig_dir_path = NULL;
    if (array_key_exists('directory', $options)) {
      $exec_dir_path = $options['directory'];
      unset($options['directory']);
      $orig_dir_path = getcwd();
      CRM_Utils_Dir::chdir($exec_dir_path);
    }
    $options['print_command'] = TRUE;
    try {
      $result = CRM_Utils_Shell::run($cmd, $options);
    } catch (Exception $e) {
      if ($orig_dir_path != NULL) {
        CRM_Utils_Dir::chdir($orig_dir_path);
      }
      throw $e;
    }
    if ($orig_dir_path != NULL) {
      CRM_Utils_Dir::chdir($orig_dir_path);
    }
    return $result;
  }

  function start_mysql_ram_server() {
    $this->mysql_ram_server = new CRM_DB_MySQLRAMServer($this->civicrm_db_settings, $this->mysql_ram_server_options());
    $this->mysql_ram_server->run();
  }

  function unpack_drupal() {
    $url_components = parse_url(self::DRUPAL_SOURCE_URL);
    $file_name = basename($url_components['path']);
    $target_path = CRM_Utils_Path::join($this->tmp_path, $file_name);
    if (!file_exists($target_path)) {
      print("Downloading Drupal from " . self::DRUPAL_SOURCE_URL . "\n");
      $result = file_get_contents(self::DRUPAL_SOURCE_URL);
      if ($result === FALSE) {
        throw new Exception("Error downloading Drupal from " . self::DRUPAL_SOURCE_URL . ": " . print_r(error_get_last(), TRUE));
      }
      $result = file_put_contents($target_path, $result);
      if ($result === FALSE) {
        throw new Exception("Error creating Drupal source file {$target_path}: " . print_r(error_get_last(), TRUE));
      }
    }
    $this->shell("tar --directory={$this->tmp_path} -xzf {$target_path}");
    $drupal_dir_name = preg_replace("/\.tar\.gz$/", "", $file_name);
    $unpacked_dir_path = CRM_Utils_Path::join($this->tmp_path, $drupal_dir_name);
    $result = rename($unpacked_dir_path, $this->drupal_path);
    if ($result === FALSE) {
      throw new Exception("Error renaming {$upacked_dir_path} to {$this->drupal_path}: " . print_r(error_get_last(), TRUE));
    }
  }
}
