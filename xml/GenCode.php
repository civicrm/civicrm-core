<?php
ini_set('include_path', '.' . PATH_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'packages' . PATH_SEPARATOR . '..');
// make sure the memory_limit is at least 512 MB
$memLimitString = trim(ini_get('memory_limit'));
$memLimitUnit   = strtolower(substr($memLimitString, -1));
$memLimit       = (int) $memLimitString;
switch ($memLimitUnit) {
    case 'g': $memLimit *= 1024;
    case 'm': $memLimit *= 1024;
    case 'k': $memLimit *= 1024;
}

if ($memLimit >= 0 and $memLimit < 536870912) {
    // Note: When processing all locales, CRM_Core_I18n::singleton() eats a lot of RAM.
    ini_set('memory_limit', -1);
}
date_default_timezone_set('UTC'); // avoid php warnings if timezone is not set - CRM-10844

define('CIVICRM_UF', 'Drupal');

require_once 'CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();

$genCode = new CRM_GenCode_Main(
  '../CRM/Core/DAO/',                                                         // $CoreDAOCodePath
  '../sql/',                                                                  // $sqlCodePath
  '../',                                                                      // $phpCodePath
  '../templates/',                                                            // $tplCodePath
  array('../packages/Smarty/plugins', '../CRM/Core/Smarty/plugins'),          // smarty plugin dirs
  @$argv[3],                                                                  // cms
  empty($argv[2]) ? NULL : $argv[2],                                          // db version
  empty($argv[1]) ? 'schema/Schema.xml' : $argv[1],                           // schema file
  getenv('CIVICRM_GENCODE_DIGEST') ? getenv('CIVICRM_GENCODE_DIGEST') : NULL  // path to digest file
);
$genCode->main();

class CRM_GenCode_Util_File {
  static function createDir($dir, $perm = 0755) {
    if (!is_dir($dir)) {
      mkdir($dir, $perm, TRUE);
    }
  }

  static function removeDir($dir) {
    foreach (glob("$dir/*") as $tempFile) {
      unlink($tempFile);
    }
    rmdir($dir);
  }

  static function createTempDir($prefix) {
    if (isset($_SERVER['TMPDIR'])) {
      $tempDir = $_SERVER['TMPDIR'];
    }
    else {
      $tempDir = '/tmp';
    }

    $newTempDir = $tempDir . '/' . $prefix . rand(1, 10000);
    if (function_exists('posix_geteuid')) {
      $newTempDir .= '_' . posix_geteuid();
    }

    if (file_exists($newTempDir)) {
      self::removeDir($newTempDir);
    }
    self::createDir($newTempDir);

    return $newTempDir;
  }

  /**
   * Calculate a cumulative digest based on a collection of files
   *
   * @param array $files list of file names (strings)
   * @param callable $digest a one-way hash function (string => string)
   * @return string
   */
  static function digestAll($files, $digest = 'md5') {
    $buffer = '';
    foreach ($files as $file) {
      $buffer .= $digest(file_get_contents($file));
    }
    return $digest($buffer);
  }

  /**
   * Find the path to the main Civi source tree
   *
   * @return string
   * @throws RuntimeException
   */
  static function findCoreSourceDir() {
    $path = str_replace(DIRECTORY_SEPARATOR, '/', __DIR__);
    if (!preg_match(':(.*)/xml:', $path, $matches)) {
      throw new RuntimeException("Failed to determine path of code-gen");
    }

    return $matches[1];
  }

  /**
   * Find files in several directories using several filename patterns
   *
   * @param array $pairs each item is an array(0 => $searchBaseDir, 1 => $filePattern)
   * @return array of file paths
   */
  static function findManyFiles($pairs) {
    $files = array();
    foreach ($pairs as $pair) {
      list ($dir, $pattern) = $pair;
      $files = array_merge($files, CRM_Utils_File::findFiles($dir, $pattern));
    }
    return $files;
  }
}

class CRM_GenCode_Main {
  var $buildVersion;
  var $db_version;
  var $compileDir;
  var $classNames;
  var $cms; // drupal, joomla, wordpress

  var $CoreDAOCodePath;
  var $sqlCodePath;
  var $phpCodePath;
  var $tplCodePath;
  var $schemaPath; // ex: schema/Schema.xml

  /**
   * @var string|NULL path in which to store a marker that indicates the last execution of
   * GenCode. If a matching marker already exists, GenCode doesn't run.
   */
  var $digestPath;

  /**
   * @var string|NULL a digest of the inputs to the code-generator (eg the properties and source files)
   */
  var $digest;

  var $smarty;

  function __construct($CoreDAOCodePath, $sqlCodePath, $phpCodePath, $tplCodePath, $smartyPluginDirs, $argCms, $argVersion, $schemaPath, $digestPath) {
    $this->CoreDAOCodePath = $CoreDAOCodePath;
    $this->sqlCodePath = $sqlCodePath;
    $this->phpCodePath = $phpCodePath;
    $this->tplCodePath = $tplCodePath;
    $this->cms = $argCms;
    $this->digestPath = $digestPath;
    $this->digest = NULL;

    require_once 'Smarty/Smarty.class.php';
    $this->smarty = new Smarty();
    $this->smarty->template_dir = './templates';
    $this->smarty->plugins_dir = $smartyPluginDirs;
    $this->compileDir = CRM_GenCode_Util_File::createTempDir('templates_c_');
    $this->smarty->compile_dir = $this->compileDir;
    $this->smarty->clear_all_cache();

    // CRM-5308 / CRM-3507 - we need {localize} to work in the templates
    require_once 'CRM/Core/Smarty/plugins/block.localize.php';
    $this->smarty->register_block('localize', 'smarty_block_localize');

    require_once 'PHP/Beautifier.php';
    // create a instance
    $this->beautifier = new PHP_Beautifier();
    $this->beautifier->addFilter('ArrayNested');
    // add one or more filters
    $this->beautifier->addFilter('Pear');
    // add one or more filters
    $this->beautifier->addFilter('NewLines', array('after' => 'class, public, require, comment'));
    $this->beautifier->setIndentChar(' ');
    $this->beautifier->setIndentNumber(2);
    $this->beautifier->setNewLine("\n");

    CRM_GenCode_Util_File::createDir($this->sqlCodePath);

    $versionFile        = "version.xml";
    $versionXML         = &$this->parseInput($versionFile);
    $this->db_version         = $versionXML->version_no;
    $this->buildVersion = preg_replace('/^(\d{1,2}\.\d{1,2})\.(\d{1,2}|\w{4,7})$/i', '$1', $this->db_version);
    if (isset($argVersion)) {
      // change the version to that explicitly passed, if any
      $this->db_version = $argVersion;
    }

    $this->schemaPath = $schemaPath;
  }

  function __destruct() {
    CRM_GenCode_Util_File::removeDir($this->compileDir);
  }

  /**
   * Automatically generate a variety of files
   *
   */
  function main() {
    if (!empty($this->digestPath) && file_exists($this->digestPath) && $this->hasExpectedFiles()) {
      if ($this->getDigest() === file_get_contents($this->digestPath)) {
        echo "GenCode has previously executed. To force execution, please (a) omit CIVICRM_GENCODE_DIGEST\n";
        echo "or (b) remove {$this->digestPath} or (c) call GenCode with new parameters.\n";
        exit();
      }
      // Once we start GenCode, the old build is invalid
      unlink($this->digestPath);
    }


    echo "\ncivicrm_domain.version := ". $this->db_version . "\n\n";
    if ($this->buildVersion < 1.1) {
      echo "The Database is not compatible for this version";
      exit();
    }

    if (substr(phpversion(), 0, 1) != 5) {
      echo phpversion() . ', ' . substr(phpversion(), 0, 1) . "\n";
      echo "
CiviCRM requires a PHP Version >= 5
Please upgrade your php / webserver configuration
Alternatively you can get a version of CiviCRM that matches your PHP version
";
      exit();
    }

    $this->generateTemplateVersion();

    $this->setupCms($this->db_version);

    echo "Parsing input file ".$this->schemaPath."\n";
    $dbXML = $this->parseInput($this->schemaPath);
    // print_r( $dbXML );

    echo "Extracting database information\n";
    $database = &$this->getDatabase($dbXML);
    // print_r( $database );

    $this->classNames = array();

    echo "Extracting table information\n";
    $tables = &$this->getTables($dbXML, $database);

    $this->resolveForeignKeys($tables, $this->classNames);
    $tables = $this->orderTables($tables);

    // add archive tables here
    $archiveTables = array( );
    foreach ($tables as $name => $table ) {
      if ( $table['archive'] == 'true' ) {
        $name = 'archive_' . $table['name'];
        $table['name'] = $name;
        $table['archive'] = 'false';
        if ( isset($table['foreignKey']) ) {
          foreach ($table['foreignKey'] as $fkName => $fkValue) {
            if ($tables[$fkValue['table']]['archive'] == 'true') {
              $table['foreignKey'][$fkName]['table'] = 'archive_' . $table['foreignKey'][$fkName]['table'];
              $table['foreignKey'][$fkName]['uniqName'] =
                str_replace( 'FK_', 'FK_archive_', $table['foreignKey'][$fkName]['uniqName'] );
            }
          }
          $archiveTables[$name] = $table;
        }
      }
    }

    $this->generateListAll($tables);
    $this->generateCiviTestTruncate($tables);
    $this->generateCreateSql($database, $tables, 'civicrm.mysql');
    $this->generateDropSql($tables, 'civicrm_drop.mysql');

    // also create the archive tables
    // $this->generateCreateSql($database, $archiveTables, 'civicrm_archive.mysql' );
    // $this->generateDropSql($archiveTables, 'civicrm_archive_drop.mysql');

    $this->generateNavigation();
    $this->generateLocalDataSql($this->findLocales());
    $this->generateSample();
    $this->generateInstallLangs();
    $this->generateDAOs($tables);
    $this->generateSchemaStructure($tables);

    if (!empty($this->digestPath)) {
      file_put_contents($this->digestPath, $this->getDigest());
    }
  }

  function generateListAll($tables) {
    $this->smarty->clear_all_assign();
    $this->smarty->assign('tables', $tables);
    file_put_contents($this->CoreDAOCodePath . "AllCoreTables.php", $this->smarty->fetch('listAll.tpl'));
  }

  function generateCiviTestTruncate($tables) {
    echo "Generating tests truncate file\n";

    $truncate = '<?xml version="1.0" encoding="UTF-8" ?>
        <!--  Truncate all tables that will be used in the tests  -->
        <dataset>';
    $tbls = array_keys($tables);
    foreach ($tbls as $d => $t) {
      $truncate = $truncate . "\n  <$t />\n";
    }

    $truncate = $truncate . "</dataset>\n";
    file_put_contents($this->sqlCodePath . "../tests/phpunit/CiviTest/truncate.xml", $truncate);
    unset($truncate);
  }

  function generateCreateSql($database, $tables, $fileName = 'civicrm.mysql') {
    echo "Generating sql file\n";
    $this->reset_smarty_assignments();
    $this->smarty->assign_by_ref('database', $database);
    $this->smarty->assign_by_ref('tables', $tables);
    $dropOrder = array_reverse(array_keys($tables));
    $this->smarty->assign_by_ref('dropOrder', $dropOrder);
    $this->smarty->assign('mysql', 'modern');
    file_put_contents($this->sqlCodePath . $fileName, $this->smarty->fetch('schema.tpl'));
  }

  function generateDropSql($tables, $fileName = 'civicrm_drop.mysql') {
    echo "Generating sql drop tables file\n";
    $dropOrder = array_reverse(array_keys($tables));
    $this->smarty->assign_by_ref('dropOrder', $dropOrder);
    file_put_contents($this->sqlCodePath . $fileName, $this->smarty->fetch('drop.tpl'));
  }

  function generateNavigation() {
    echo "Generating navigation file\n";
    $this->reset_smarty_assignments();
    file_put_contents($this->sqlCodePath . "civicrm_navigation.mysql", $this->smarty->fetch('civicrm_navigation.tpl'));
  }

  function generateLocalDataSql($locales) {
    $this->reset_smarty_assignments();

    global $tsLocale;
    $oldTsLocale = $tsLocale;
    foreach ($locales as $locale) {
      echo "Generating data files for $locale\n";
      $tsLocale = $locale;
      $this->smarty->assign('locale', $locale);

      $data   = array();
      $data[] = $this->smarty->fetch('civicrm_country.tpl');
      $data[] = $this->smarty->fetch('civicrm_state_province.tpl');
      $data[] = $this->smarty->fetch('civicrm_currency.tpl');
      $data[] = $this->smarty->fetch('civicrm_data.tpl');
      $data[] = $this->smarty->fetch('civicrm_navigation.tpl');

      $data[] = " UPDATE civicrm_domain SET version = '" . $this->db_version . "';";

      $data = implode("\n", $data);

      $ext = ($locale != 'en_US' ? ".$locale" : '');
      // write the initialize base-data sql script
      file_put_contents($this->sqlCodePath . "civicrm_data$ext.mysql", $data);

      // write the acl sql script
      file_put_contents($this->sqlCodePath . "civicrm_acl$ext.mysql", $this->smarty->fetch('civicrm_acl.tpl'));
    }
    $tsLocale = $oldTsLocale;
  }

  function generateSample() {
    $this->reset_smarty_assignments();
    $sample = $this->smarty->fetch('civicrm_sample.tpl');
    $sample .= $this->smarty->fetch('civicrm_acl.tpl');
    file_put_contents($this->sqlCodePath . 'civicrm_sample.mysql', $sample);
  }

  function generateInstallLangs() {
    // CRM-7161: generate install/langs.php from the languages template
    // grep it for enabled languages and create a 'xx_YY' => 'Language name' $langs mapping
    $matches = array();
    preg_match_all('/, 1, \'([a-z][a-z]_[A-Z][A-Z])\', \'..\', \{localize\}\'\{ts escape="sql"\}(.+)\{\/ts\}\'\{\/localize\}, /', file_get_contents('templates/languages.tpl'), $matches);
    $langs = array();
    for ($i = 0; $i < count($matches[0]); $i++) {
      $langs[$matches[1][$i]] = $matches[2][$i];
    }
    file_put_contents('../install/langs.php', "<?php \$langs = unserialize('" . serialize($langs) . "');");
  }

  function generateDAOs($tables) {
    foreach (array_keys($tables) as $name) {
      $this->smarty->clear_all_cache();
      echo "Generating $name as " . $tables[$name]['fileName'] . "\n";
      $this->reset_smarty_assignments();

      $this->smarty->assign_by_ref('table', $tables[$name]);
      $php = $this->smarty->fetch('dao.tpl');

      $this->beautifier->setInputString($php);

      if (empty($tables[$name]['base'])) {
        echo "No base defined for $name, skipping output generation\n";
        continue;
      }

      $directory = $this->phpCodePath . $tables[$name]['base'];
      CRM_GenCode_Util_File::createDir($directory);
      $this->beautifier->setOutputFile($directory . $tables[$name]['fileName']);
      // required
      $this->beautifier->process();

      $this->beautifier->save();
    }
  }

  function generateSchemaStructure($tables) {
    echo "Generating CRM_Core_I18n_SchemaStructure...\n";
    $columns = array();
    $indices = array();
    foreach ($tables as $table) {
      if ($table['localizable']) {
        $columns[$table['name']] = array();
      }
      else {
        continue;
      }
      foreach ($table['fields'] as $field) {
        if ($field['localizable']) {
          $columns[$table['name']][$field['name']] = $field['sqlType'];
        }
      }
      if (isset($table['index'])) {
        foreach ($table['index'] as $index) {
          if ($index['localizable']) {
            $indices[$table['name']][$index['name']] = $index;
          }
        }
      }
    }

    $this->reset_smarty_assignments();
    $this->smarty->assign_by_ref('columns', $columns);
    $this->smarty->assign_by_ref('indices', $indices);

    $this->beautifier->setInputString($this->smarty->fetch('schema_structure.tpl'));
    $this->beautifier->setOutputFile($this->phpCodePath . "/CRM/Core/I18n/SchemaStructure.php");
    $this->beautifier->process();
    $this->beautifier->save();
  }

  function generateTemplateVersion() {
    file_put_contents($this->tplCodePath . "/CRM/common/version.tpl", $this->db_version);
  }

  function findLocales() {
    require_once 'CRM/Core/Config.php';
    $config = CRM_Core_Config::singleton(FALSE);
    $locales = array();
    if (substr($config->gettextResourceDir, 0, 1) === '/') {
      $localeDir = $config->gettextResourceDir;
    }
    else {
      $localeDir = '../' . $config->gettextResourceDir;
    }
    if (file_exists($localeDir)) {
      $config->gettextResourceDir = $localeDir;
      $locales = preg_grep('/^[a-z][a-z]_[A-Z][A-Z]$/', scandir($localeDir));
    }

    $localesMask = getenv('CIVICRM_LOCALES');
    if (!empty($localesMask)) {
      $mask = explode(',', $localesMask);
      $locales = array_intersect($locales, $mask);
    }

    if (!in_array('en_US', $locales)) {
      array_unshift($locales, 'en_US');
    }

    return $locales;
  }

  function setupCms() {
    // default cms is 'drupal', if not specified
    $this->cms = isset($this->cms) ? strtolower($this->cms) : 'drupal';
    if (!in_array($this->cms, array(
      'drupal', 'joomla', 'wordpress'))) {
      echo "Config file for '{$this->cms}' not known.";
      exit();
    }
    elseif ($this->cms !== 'joomla') {
      $configTemplate = $this->findConfigTemplate($this->cms);
      if ($configTemplate) {
        echo "Generating civicrm.config.php\n";
        copy($configTemplate, '../civicrm.config.php');
      } else {
        throw new Exception("Failed to locate template for civicrm.config.php");
      }
    }

    echo "Generating civicrm-version file\n";
    $this->smarty->assign('db_version', $this->db_version);
    $this->smarty->assign('cms', ucwords($this->cms));
    file_put_contents($this->phpCodePath . "civicrm-version.php", $this->smarty->fetch('civicrm_version.tpl'));
  }

  /**
   * @param string $cms "drupal"|"wordpress"
   * @return null|string path to config template
   */
  public function findConfigTemplate($cms) {
    $candidates = array();
    switch ($cms) {
      case 'drupal':
        $candidates[] = "../drupal/civicrm.config.php.drupal";
        $candidates[] =  "../../drupal/civicrm.config.php.drupal";
        break;
      case 'wordpress':
        $candidates[] = "../../civicrm.config.php.wordpress";
        $candidates[] = "../WordPress/civicrm.config.php.wordpress";
        $candidates[] = "../drupal/civicrm.config.php.drupal";
        break;
    }
    foreach ($candidates as $candidate) {
      if (file_exists($candidate)) {
        return $candidate;
        break;
      }
    }
    return NULL;
  }

  // -----------------------------
  // ---- Schema manipulation ----
  // -----------------------------
  function &parseInput($file) {
    $dom = new DomDocument();
    $dom->load($file);
    $dom->xinclude();
    $dbXML = simplexml_import_dom($dom);
    return $dbXML;
  }

  function &getDatabase(&$dbXML) {
    $database = array('name' => trim((string ) $dbXML->name));

    $attributes = '';
    $this->checkAndAppend($attributes, $dbXML, 'character_set', 'DEFAULT CHARACTER SET ', '');
    $this->checkAndAppend($attributes, $dbXML, 'collate', 'COLLATE ', '');
    $database['attributes'] = $attributes;

    $tableAttributes_modern = $tableAttributes_simple = '';
    $this->checkAndAppend($tableAttributes_modern, $dbXML, 'table_type', 'ENGINE=', '');
    $this->checkAndAppend($tableAttributes_simple, $dbXML, 'table_type', 'TYPE=', '');
    $database['tableAttributes_modern'] = trim($tableAttributes_modern . ' ' . $attributes);
    $database['tableAttributes_simple'] = trim($tableAttributes_simple);

    $database['comment'] = $this->value('comment', $dbXML, '');

    return $database;
  }

  function &getTables(&$dbXML, &$database) {
    $tables = array();
    foreach ($dbXML->tables as $tablesXML) {
      foreach ($tablesXML->table as $tableXML) {
        if ($this->value('drop', $tableXML, 0) > 0 and $this->value('drop', $tableXML, 0) <= $this->buildVersion) {
          continue;
        }

        if ($this->value('add', $tableXML, 0) <= $this->buildVersion) {
          $this->getTable($tableXML, $database, $tables);
        }
      }
    }

    return $tables;
  }

  function resolveForeignKeys(&$tables, &$classNames) {
    foreach (array_keys($tables) as $name) {
      $this->resolveForeignKey($tables, $classNames, $name);
    }
  }

  function resolveForeignKey(&$tables, &$classNames, $name) {
    if (!array_key_exists('foreignKey', $tables[$name])) {
      return;
    }

    foreach (array_keys($tables[$name]['foreignKey']) as $fkey) {
      $ftable = $tables[$name]['foreignKey'][$fkey]['table'];
      if (!array_key_exists($ftable, $classNames)) {
        echo "$ftable is not a valid foreign key table in $name\n";
        continue;
      }
      $tables[$name]['foreignKey'][$fkey]['className'] = $classNames[$ftable];
      $tables[$name]['foreignKey'][$fkey]['fileName'] = str_replace('_', '/', $classNames[$ftable]) . '.php';
      $tables[$name]['fields'][$fkey]['FKClassName'] = $classNames[$ftable];
    }
  }

  function orderTables(&$tables) {
    $ordered = array();

    while (!empty($tables)) {
      foreach (array_keys($tables) as $name) {
        if ($this->validTable($tables, $ordered, $name)) {
          $ordered[$name] = $tables[$name];
          unset($tables[$name]);
        }
      }
    }
    return $ordered;
  }

  function validTable(&$tables, &$valid, $name) {
    if (!array_key_exists('foreignKey', $tables[$name])) {
      return TRUE;
    }

    foreach (array_keys($tables[$name]['foreignKey']) as $fkey) {
      $ftable = $tables[$name]['foreignKey'][$fkey]['table'];
      if (!array_key_exists($ftable, $valid) && $ftable !== $name) {
        return FALSE;
      }
    }
    return TRUE;
  }

  function getTable($tableXML, &$database, &$tables) {
    $name = trim((string ) $tableXML->name);
    $klass = trim((string ) $tableXML->class);
    $base = $this->value('base', $tableXML);
    $sourceFile = "xml/schema/{$base}/{$klass}.xml";
    $daoPath = "{$base}/DAO/";
    $pre = str_replace('/', '_', $daoPath);
    $this->classNames[$name] = $pre . $klass;

    $localizable = FALSE;
    foreach ($tableXML->field as $fieldXML) {
      if ($fieldXML->localizable) {
        $localizable = TRUE;
        break;
      }
    }

    $table = array(
      'name' => $name,
      'base' => $daoPath,
      'sourceFile' => $sourceFile,
      'fileName' => $klass . '.php',
      'objectName' => $klass,
      'labelName' => substr($name, 8),
      'className' => $this->classNames[$name],
      'attributes_simple' => trim($database['tableAttributes_simple']),
      'attributes_modern' => trim($database['tableAttributes_modern']),
      'comment' => $this->value('comment', $tableXML),
      'localizable' => $localizable,
      'log' => $this->value('log', $tableXML, 'false'),
      'archive' => $this->value('archive', $tableXML, 'false'),
    );

    $fields = array();
    foreach ($tableXML->field as $fieldXML) {
      if ($this->value('drop', $fieldXML, 0) > 0 and $this->value('drop', $fieldXML, 0) <= $this->buildVersion) {
        continue;
      }

      if ($this->value('add', $fieldXML, 0) <= $this->buildVersion) {
        $this->getField($fieldXML, $fields);
      }
    }

    $table['fields'] = &$fields;
    $table['hasEnum'] = FALSE;
    foreach ($table['fields'] as $field) {
      if ($field['crmType'] == 'CRM_Utils_Type::T_ENUM') {
        $table['hasEnum'] = TRUE;
        break;
      }
    }

    if ($this->value('primaryKey', $tableXML)) {
      $this->getPrimaryKey($tableXML->primaryKey, $fields, $table);
    }

    // some kind of refresh?
    CRM_Core_Config::singleton(FALSE);
    if ($this->value('index', $tableXML)) {
      $index = array();
      foreach ($tableXML->index as $indexXML) {
        if ($this->value('drop', $indexXML, 0) > 0 and $this->value('drop', $indexXML, 0) <= $this->buildVersion) {
          continue;
        }

        $this->getIndex($indexXML, $fields, $index);
      }
      $table['index'] = &$index;
    }

    if ($this->value('foreignKey', $tableXML)) {
      $foreign = array();
      foreach ($tableXML->foreignKey as $foreignXML) {
        // print_r($foreignXML);

        if ($this->value('drop', $foreignXML, 0) > 0 and $this->value('drop', $foreignXML, 0) <= $this->buildVersion) {
          continue;
        }
        if ($this->value('add', $foreignXML, 0) <= $this->buildVersion) {
          $this->getForeignKey($foreignXML, $fields, $foreign, $name);
        }
      }
      $table['foreignKey'] = &$foreign;
    }

    if ($this->value('dynamicForeignKey', $tableXML)) {
      $dynamicForeign = array();
      foreach ($tableXML->dynamicForeignKey as $foreignXML) {
        if ($this->value('drop', $foreignXML, 0) > 0 and $this->value('drop', $foreignXML, 0) <= $this->buildVersion) {
          continue;
        }
        if ($this->value('add', $foreignXML, 0) <= $this->buildVersion) {
          $this->getDynamicForeignKey($foreignXML, $dynamicForeign, $name);
        }
      }
      $table['dynamicForeignKey'] = $dynamicForeign;
    }

    $tables[$name] = &$table;
    return;
  }

  function getField(&$fieldXML, &$fields) {
    $name  = trim((string ) $fieldXML->name);
    $field = array('name' => $name, 'localizable' => $fieldXML->localizable);
    $type  = (string ) $fieldXML->type;
    switch ($type) {
      case 'varchar':
      case 'char':
        $field['length'] = (int) $fieldXML->length;
        $field['sqlType'] = "$type({$field['length']})";
        $field['phpType'] = 'string';
        $field['crmType'] = 'CRM_Utils_Type::T_STRING';
        $field['size'] = $this->getSize($fieldXML);
        break;

      case 'enum':
        $value               = (string ) $fieldXML->values;
        $field['sqlType']    = 'enum(';
        $field['values']     = array();
        $field['enumValues'] = $value;
        $values              = explode(',', $value);
        $first               = TRUE;
        foreach ($values as $v) {
          $v = trim($v);
          $field['values'][] = $v;

          if (!$first) {
            $field['sqlType'] .= ', ';
          }
          $first = FALSE;
          $field['sqlType'] .= "'$v'";
        }
        $field['sqlType'] .= ')';
        $field['phpType'] = $field['sqlType'];
        $field['crmType'] = 'CRM_Utils_Type::T_ENUM';
        break;

      case 'text':
        $field['sqlType'] = $field['phpType'] = $type;
        $field['crmType'] = 'CRM_Utils_Type::T_' . strtoupper($type);
        $field['rows']    = $this->value('rows', $fieldXML);
        $field['cols']    = $this->value('cols', $fieldXML);
        break;

      case 'datetime':
        $field['sqlType'] = $field['phpType'] = $type;
        $field['crmType'] = 'CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME';
        break;

      case 'boolean':
        // need this case since some versions of mysql do not have boolean as a valid column type and hence it
        // is changed to tinyint. hopefully after 2 yrs this case can be removed.
        $field['sqlType'] = 'tinyint';
        $field['phpType'] = $type;
        $field['crmType'] = 'CRM_Utils_Type::T_' . strtoupper($type);
        break;

      case 'decimal':
        $length = $fieldXML->length ? $fieldXML->length : '20,2';
        $field['sqlType'] = 'decimal(' . $length . ')';
        $field['phpType'] = 'float';
        $field['crmType'] = 'CRM_Utils_Type::T_MONEY';
        break;

      case 'float':
        $field['sqlType'] = 'double';
        $field['phpType'] = 'float';
        $field['crmType'] = 'CRM_Utils_Type::T_FLOAT';
        break;

      default:
        $field['sqlType'] = $field['phpType'] = $type;
        if ($type == 'int unsigned') {
          $field['crmType'] = 'CRM_Utils_Type::T_INT';
        }
        else {
          $field['crmType'] = 'CRM_Utils_Type::T_' . strtoupper($type);
        }
        break;
    }

    $field['required'] = $this->value('required', $fieldXML);
    $field['collate']  = $this->value('collate', $fieldXML);
    $field['comment']  = $this->value('comment', $fieldXML);
    $field['default']  = $this->value('default', $fieldXML);
    $field['import']   = $this->value('import', $fieldXML);
    if ($this->value('export', $fieldXML)) {
      $field['export'] = $this->value('export', $fieldXML);
    }
    else {
      $field['export'] = $this->value('import', $fieldXML);
    }
    $field['rule'] = $this->value('rule', $fieldXML);
    $field['title'] = $this->value('title', $fieldXML);
    if (!$field['title']) {
      $field['title'] = $this->composeTitle($name);
    }
    $field['headerPattern'] = $this->value('headerPattern', $fieldXML);
    $field['dataPattern'] = $this->value('dataPattern', $fieldXML);
    $field['uniqueName'] = $this->value('uniqueName', $fieldXML);
    $field['pseudoconstant'] = $this->value('pseudoconstant', $fieldXML);
    if(!empty($field['pseudoconstant'])){
      //ok this is a bit long-winded but it gets there & is consistent with above approach
      $field['pseudoconstant'] = array();
      $validOptions = array(
        // Fields can specify EITHER optionGroupName OR table, not both
        // (since declaring optionGroupName means we are using the civicrm_option_value table)
        'optionGroupName',
        'table',
        // If table is specified, keyColumn and labelColumn are also required
        'keyColumn',
        'labelColumn',
        // Non-translated machine name for programmatic lookup. Defaults to 'name' if that column exists
        'nameColumn',
        // Where clause snippet (will be joined to the rest of the query with AND operator)
        'condition',
      );
      foreach ($validOptions as $pseudoOption) {
        if(!empty($fieldXML->pseudoconstant->$pseudoOption)){
          $field['pseudoconstant'][$pseudoOption] = $this->value($pseudoOption, $fieldXML->pseudoconstant);
        }
      }
      // For now, fields that have option lists that are not in the db can simply
      // declare an empty pseudoconstant tag and we'll add this placeholder.
      // That field's BAO::buildOptions fn will need to be responsible for generating the option list
      if (empty($field['pseudoconstant'])) {
        $field['pseudoconstant'] = 'not in database';
      }
    }
    $fields[$name] = &$field;
  }

  function composeTitle($name) {
    $names = explode('_', strtolower($name));
    $title = '';
    for ($i = 0; $i < count($names); $i++) {
      if ($names[$i] === 'id' || $names[$i] === 'is') {
        // id's do not get titles
        return NULL;
      }

      if ($names[$i] === 'im') {
        $names[$i] = 'IM';
      }
      else {
        $names[$i] = ucfirst(trim($names[$i]));
      }

      $title = $title . ' ' . $names[$i];
    }
    return trim($title);
  }

  function getPrimaryKey(&$primaryXML, &$fields, &$table) {
    $name = trim((string ) $primaryXML->name);

    /** need to make sure there is a field of type name */
    if (!array_key_exists($name, $fields)) {
        echo "primary key $name in $table->name does not have a field definition, ignoring\n";
      return;
    }

    // set the autoincrement property of the field
    $auto = $this->value('autoincrement', $primaryXML);
    $fields[$name]['autoincrement'] = $auto;
    $primaryKey = array(
      'name' => $name,
      'autoincrement' => $auto,
    );
    $table['primaryKey'] = &$primaryKey;
  }

  function getIndex(&$indexXML, &$fields, &$indices) {
    //echo "\n\n*******************************************************\n";
    //echo "entering getIndex\n";

    $index = array();
    // empty index name is fine
    $indexName      = trim((string)$indexXML->name);
    $index['name']  = $indexName;
    $index['field'] = array();

    // populate fields
    foreach ($indexXML->fieldName as $v) {
      $fieldName = (string)($v);
      $length = (string)($v['length']);
      if (strlen($length) > 0) {
        $fieldName = "$fieldName($length)";
      }
      $index['field'][] = $fieldName;
    }

    $index['localizable'] = FALSE;
    foreach ($index['field'] as $fieldName) {
      if (isset($fields[$fieldName]) and $fields[$fieldName]['localizable']) {
        $index['localizable'] = TRUE;
        break;
      }
    }

    // check for unique index
    if ($this->value('unique', $indexXML)) {
      $index['unique'] = TRUE;
    }

    //echo "\$index = \n";
    //print_r($index);

    // field array cannot be empty
    if (empty($index['field'])) {
      echo "No fields defined for index $indexName\n";
      return;
    }

    // all fieldnames have to be defined and should exist in schema.
    foreach ($index['field'] as $fieldName) {
      if (!$fieldName) {
        echo "Invalid field defination for index $indexName\n";
        return;
      }
      $parenOffset = strpos($fieldName, '(');
      if ($parenOffset > 0) {
        $fieldName = substr($fieldName, 0, $parenOffset);
      }
      if (!array_key_exists($fieldName, $fields)) {
        echo "Table does not contain $fieldName\n";
        print_r($fields);
        CRM_GenCode_Util_File::removeDir($this->compileDir);
        exit();
      }
    }
    $indices[$indexName] = &$index;
  }

  function getForeignKey(&$foreignXML, &$fields, &$foreignKeys, &$currentTableName) {
    $name = trim((string ) $foreignXML->name);

    /** need to make sure there is a field of type name */
    if (!array_key_exists($name, $fields)) {
        echo "foreign $name in $currentTableName does not have a field definition, ignoring\n";
      return;
    }

    /** need to check for existence of table and key **/
    $table = trim($this->value('table', $foreignXML));
    $foreignKey = array(
      'name' => $name,
      'table' => $table,
      'uniqName' => "FK_{$currentTableName}_{$name}",
      'key' => trim($this->value('key', $foreignXML)),
      'import' => $this->value('import', $foreignXML, FALSE),
      'export' => $this->value('import', $foreignXML, FALSE),
      // we do this matching in a seperate phase (resolveForeignKeys)
      'className' => NULL,
      'onDelete' => $this->value('onDelete', $foreignXML, FALSE),
    );
    $foreignKeys[$name] = &$foreignKey;
  }

  function getDynamicForeignKey(&$foreignXML, &$dynamicForeignKeys) {
    $foreignKey = array(
      'idColumn' => trim($foreignXML->idColumn),
      'typeColumn' => trim($foreignXML->typeColumn),
      'key' => trim($this->value('key', $foreignXML)),
    );
    $dynamicForeignKeys[] = $foreignKey;
  }

  protected function value($key, &$object, $default = NULL) {
    if (isset($object->$key)) {
      return (string ) $object->$key;
    }
    return $default;
  }

  protected function checkAndAppend(&$attributes, &$object, $name, $pre = NULL, $post = NULL) {
    if (!isset($object->$name)) {
      return;
    }

    $value = $pre . trim($object->$name) . $post;
    $this->append($attributes, ' ', trim($value));
  }

  protected function append(&$str, $delim, $name) {
    if (empty($name)) {
      return;
    }

    if (is_array($name)) {
      foreach ($name as $n) {
        if (empty($n)) {
          continue;
        }
        if (empty($str)) {
          $str = $n;
        }
        else {
          $str .= $delim . $n;
        }
      }
    }
    else {
      if (empty($str)) {
        $str = $name;
      }
      else {
        $str .= $delim . $name;
      }
    }
  }

  /**
   * Sets the size property of a textfield
   * See constants defined in CRM_Utils_Type for possible values
   */
  protected function getSize($fieldXML) {
    // Extract from <size> tag if supplied
    if ($this->value('size', $fieldXML)) {
      $const = 'CRM_Utils_Type::' . strtoupper($fieldXML->size);
      if (defined($const)) {
        return $const;
      }
    }
    // Infer from <length> tag if <size> was not explicitly set or was invalid

    // This map is slightly different from CRM_Core_Form_Renderer::$_sizeMapper
    // Because we usually want fields to render as smaller than their maxlength
    $sizes = array(
      2 => 'TWO',
      4 => 'FOUR',
      6 => 'SIX',
      8 => 'EIGHT',
      16 => 'TWELVE',
      32 => 'MEDIUM',
      64 => 'BIG',
    );
    foreach ($sizes as $length => $name) {
      if ($fieldXML->length <= $length) {
        return "CRM_Utils_Type::$name";
      }
    }
    return 'CRM_Utils_Type::HUGE';
  }

  /**
   * Clear the smarty cache and assign default values
   */
  function reset_smarty_assignments() {
    $this->smarty->clear_all_assign();
    $this->smarty->clear_all_cache();
    $this->smarty->assign('generated', "DO NOT EDIT.  Generated by " . basename(__FILE__));
  }


  /**
   * Compute a digest based on the inputs to the code-generator (ie the properties
   * of the codegen and the source files loaded by the codegen).
   *
   * @return string
   */
  function getDigest() {
    if ($this->digest === NULL) {
      $srcDir = CRM_GenCode_Util_File::findCoreSourceDir();
      $files = CRM_GenCode_Util_File::findManyFiles(array(
        // array("$srcDir/CRM/Core/CodeGen", '*.php'),
        array("$srcDir/xml", "*.php"),
        array("$srcDir/xml", "*.tpl"),
        array("$srcDir/xml", "*.xml"),
      ));

      $properties = var_export(array(
        CRM_GenCode_Util_File::digestAll($files),
        $this->buildVersion,
        $this->db_version,
        $this->cms,
        $this->CoreDAOCodePath,
        $this->sqlCodePath,
        $this->phpCodePath,
        $this->tplCodePath,
        $this->schemaPath,
        // $this->getTasks(),
      ), TRUE);

      $this->digest = md5($properties);
    }
    return $this->digest;
  }

  function getExpectedFiles() {
    return array(
      $this->sqlCodePath . '/civicrm.mysql',
      $this->phpCodePath . '/CRM/Contact/DAO/Contact.php',
    );
  }

  function hasExpectedFiles() {
    foreach ($this->getExpectedFiles() as $file) {
      if (!file_exists($file)) {
        return FALSE;
      }
    }
    return TRUE;
  }
}
