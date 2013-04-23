<?php
ini_set('include_path', '.' . PATH_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'packages' . PATH_SEPARATOR . '..');
ini_set('memory_limit', '512M');
date_default_timezone_set('UTC'); // avoid php warnings if timezone is not set - CRM-10844

define('CIVICRM_UF', 'Drupal');

require_once 'CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();

$genCode = new CRM_GenCode_Main('../CRM/Core/DAO/', '../sql/', '../', '../templates/');
$genCode->main(
  @$argv[2],
  @$argv[3],
  empty($argv[1]) ? 'schema/Schema.xml' : $argv[1]
);

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

    if (file_exists($newTempDir)) {
      self::removeDir($newTempDir);
    }
    self::createDir($newTempDir);

    return $newTempDir;
  }
}

class CRM_GenCode_Main {
  var $buildVersion;
  var $compileDir;
  var $classNames;

  var $CoreDAOCodePath;
  var $sqlCodePath;
  var $phpCodePath;
  var $tplCodePath;

  var $smarty;

  function __construct($CoreDAOCodePath, $sqlCodePath, $phpCodePath, $tplCodePath) {
    $this->CoreDAOCodePath = $CoreDAOCodePath;
    $this->sqlCodePath = $sqlCodePath;
    $this->phpCodePath = $phpCodePath;
    $this->tplCodePath = $tplCodePath;

    require_once 'Smarty/Smarty.class.php';
    $this->smarty = new Smarty();
    $this->smarty->template_dir = './templates';
    $this->smarty->plugins_dir = array('../packages/Smarty/plugins', '../CRM/Core/Smarty/plugins');
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
  }

  function __destruct() {
    CRM_GenCode_Util_File::removeDir($this->compileDir);
  }

  /**
   * Automatically generate a variety of files
   *
   * @param $argVersion string, optional
   * @param $argCms string, optional; "drupal" or "joomla"
   * @param $file, the path to the XML schema file
   */
  function main($argVersion, $argCms, $file) {
    $versionFile        = "version.xml";
    $versionXML         = &$this->parseInput($versionFile);
    $db_version         = $versionXML->version_no;
    $this->buildVersion = preg_replace('/^(\d{1,2}\.\d{1,2})\.(\d{1,2}|\w{4,7})$/i', '$1', $db_version);
    if (isset($argVersion)) {
      // change the version to that explicitly passed, if any
      $db_version = $argVersion;
    }
    echo "\ncivicrm_domain.version := $db_version\n\n";
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

    $this->generateTemplateVersion($argVersion);

    $this->setupCms($argCms, $db_version);

    echo "Parsing input file $file\n";
    $dbXML = $this->parseInput($file);
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
    $this->generateLocalDataSql($db_version, $this->findLocales());
    $this->generateSample();
    $this->generateInstallLangs();
    $this->generateDAOs($tables);
    $this->generateSchemaStructure($tables);
  }

  function generateListAll($tables) {
    $this->smarty->clear_all_assign();
    $this->smarty->assign('tables', $tables);
    file_put_contents($this->CoreDAOCodePath . "../AllCoreTables.php", $this->smarty->fetch('listAll.tpl'));
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
    $this->smarty->clear_all_assign();
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
    $this->smarty->clear_all_assign();
    file_put_contents($this->sqlCodePath . "civicrm_navigation.mysql", $this->smarty->fetch('civicrm_navigation.tpl'));
  }

  function generateLocalDataSql($db_version, $locales) {
    $this->smarty->clear_all_assign();

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

      $data[] = " UPDATE civicrm_domain SET version = '$db_version';";

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
    $this->smarty->clear_all_assign();
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
    $this->smarty->clear_all_assign();
    foreach (array_keys($tables) as $name) {
      $this->smarty->clear_all_cache();
      echo "Generating $name as " . $tables[$name]['fileName'] . "\n";
      $this->smarty->clear_all_assign();

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

    $this->smarty->clear_all_cache();
    $this->smarty->clear_all_assign();
    $this->smarty->assign_by_ref('columns', $columns);
    $this->smarty->assign_by_ref('indices', $indices);

    $this->beautifier->setInputString($this->smarty->fetch('schema_structure.tpl'));
    $this->beautifier->setOutputFile($this->phpCodePath . "/CRM/Core/I18n/SchemaStructure.php");
    $this->beautifier->process();
    $this->beautifier->save();
  }

  function generateTemplateVersion($argVersion) {
    // add the Subversion revision to templates
    // use svnversion if the version was not specified explicitely on the commandline
    if (isset($argVersion) and $argVersion != '') {
      $svnversion = $argVersion;
    }
    else {
      $svnversion = `svnversion .`;
    }
    file_put_contents($this->tplCodePath . "/CRM/common/version.tpl", $svnversion);
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

  function setupCms($argCms, $db_version) {
    // default cms is 'drupal', if not specified
    $cms = isset($argCms) ? strtolower($argCms) : 'drupal';
    if (!in_array($cms, array(
      'drupal', 'joomla'))) {
      echo "Config file for '{$cms}' not known.";
      exit();
    }
    elseif ($cms !== 'joomla') {
      echo "Generating civicrm.config.php\n";
      copy("../{$cms}/civicrm.config.php.{$cms}", '../civicrm.config.php');
    }

    echo "Generating civicrm-version file\n";
    $this->smarty->assign('db_version', $db_version);
    $this->smarty->assign('cms', ucwords($cms));
    file_put_contents($this->phpCodePath . "civicrm-version.php", $this->smarty->fetch('civicrm_version.tpl'));
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
    $base = $this->value('base', $tableXML) . '/DAO/';
    $pre = str_replace('/', '_', $base);
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
      'base' => $base,
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
    if(!empty($fieldXML->pseudoconstant)){
      //ok this is a bit long-winded but it gets there & is consistent with above approach
      $field['pseudoconstant'] = array();
      $validOptions = array('name', 'optionGroupName', 'table', 'keyColumn', 'labelColumn','class');
      foreach ($validOptions as $pseudoOption){
        if(!empty($fieldXML->pseudoconstant->$pseudoOption)){
          $field['pseudoconstant'][$pseudoOption] = $this->value($pseudoOption, $fieldXML->pseudoconstant);
        }
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
}
