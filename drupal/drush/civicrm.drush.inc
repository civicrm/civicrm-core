<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

// $Id: example.drush.inc,v 1.5 2009/09/30 13:15:54 weitzman Exp $


/**
 * @file
 *   Example drush command.
 *
 *   Shows how to make your own drush command.
 *
 *   You can copy this file to any of the following
 *     1. A .drush folder in your HOME folder.
 *     2. Anywhere in a folder tree below an active module on your site.
 *     3. In an arbitrary folder specified with the --include option.
 */

/**
 * Implementation of hook_drush_command().
 *
 * In this hook, you specify which commands your
 * drush module makes available, what it does and
 * description.
 *
 * Notice how this structure closely resembles how
 * you define menu hooks.
 *
 * @See drush_parse_command() for a list of recognized keys.
 *
 * @return
 *   An associative array describing your command(s).
 */
function civicrm_drush_command() {
  $items = array();

  // the key in the $items array is the name of the command.
  $items['civicrm-api'] = array(
    'description' => 'CLI access to CiviCRM APIs. It can return pretty-printor json formatted data.',
    'examples' =>
    array(
      'drush civicrm-api contact.create first_name=John last_name=Doe contact_type=Individual' => 'Create a new contact named John Doe',
      'drush civicrm-api contact.create id=1 --out=json' => 'Find/display a contact in JSON format',
    ),
    'options' => array(
      'in' => 'Input type: "args" (command-line), "json" (STDIN)',
      'out' => 'Output type: "pretty" (STDOUT), "json" (STDOUT)',
    ),
    'aliases' => array('cvapi'),
  );
  $items['civicrm-install'] = array(
    'description' => "Install a new instance of CiviCRM.",
    'options' =>
    array(
      'dbuser' => 'MySQL username for your Drupal/CiviCRM database.',
      'dbpass' => 'MySQL password for your Drupal/CiviCRM database.',
      'dbhost' => 'MySQL host for your Drupal/CiviCRM database. Defaults to localhost.',
      'dbname' => 'MySQL database name of your Drupal/CiviCRM database.',
      'tarfile' => 'Path to your CiviCRM tar.gz file.',
      'destination' => 'Destination modules path to extract CiviCRM (eg : sites/all/modules ).',
      'lang' => 'Default language to use for installation.',
      'langtarfile' => 'Path to your l10n tar.gz file.',
      'site_url' => 'Base Url for your drupal/CiviCRM website without http (e.g. mysite.com)',
      'ssl' => 'Using ssl for your drupal/CiviCRM website if set to on (e.g. --ssl=on)',
      'load_generated_data' => 'Loads the demo generated data. Defaults to FALSE.',
    ),
    'aliases' => array('cvi'),
  );
  $items['civicrm-ext-list'] = array(
    'description' => "List of CiviCRM extensions enabled.",
    'examples' => array(
      'Standard example' => 'drush civicrm-ext-list',
    ),
    'aliases' => array('cel'),
  );
  $items['civicrm-ext-install'] = array(
    'description' => "Install a CiviCRM extension.",
    'arguments' => array(
      'ename' => 'Extension name.',
    ),
    'required-arguments' => TRUE,
    'examples' => array(
      'Standard example' => 'drush civicrm-ext-install civimobile',
    ),
    'aliases' => array('cei'),
  );
  $items['civicrm-ext-disable'] = array(
    'description' => "Disable a CiviCRM extension.",
    'arguments' => array(
      'ename' => 'Extension name.',
    ),
    'required-arguments' => TRUE,
    'examples' => array(
      'Standard example' => 'drush civicrm-ext-disable civimobile',
    ),
    'aliases' => array('ced'),
  );
  $items['civicrm-ext-uninstall'] = array(
    'description' => "Uninstall a CiviCRM extension.",
    'arguments' => array(
      'ename' => 'Extension name.',
    ),
    'required-arguments' => TRUE,
    'examples' => array(
      'Standard example' => 'drush civicrm-ext-uninstall civimobile',
    ),
    'aliases' => array('ceui'),
  );

  $items['civicrm-upgrade-db'] = array(
    'description' => "Execute the civicrm/upgrade?reset=1 process from the command line.",
    'aliases' => array('cvupdb'),
  );
  $items['civicrm-update-cfg'] = array(
    'description' => "Update config_backend to correct config settings, especially when the CiviCRM site has been cloned / migrated.",
    'examples' =>
    array(
      'drush -l http://example.com/civicrm civicrm-update-cfg' =>
      'Update config_backend to correct config settings for civicrm installation on example.com site.',
    ),
    'aliases' => array('cvupcfg'),
  );
  $items['civicrm-enable-debug'] = array(
    'description' => "Enable CiviCRM Debugging.",
  );
  $items['civicrm-upgrade'] = array(
    'description' => "Replace CiviCRM codebase with new specified tarfile and upgrade database by executing the CiviCRM upgrade process - civicrm/upgrade?reset=1.",
    'examples' =>
    array(
      'drush civicrm-upgrade --tarfile=~/tarballs/civicrm-4.1.2-drupal.tar.gz' =>
      'Replace old CiviCRM codebase with new v4.1.2 and run upgrade process.',
    ),
    'options' =>
    array(
      'tarfile' =>
      'Path of new CiviCRM tarfile, with which current CiviCRM codebase is to be replaced.',
      'backup-dir' =>
      'Specify a directory to backup current CiviCRM codebase and database into, defaults to a backup directory above your Drupal root.',
    ),
    'aliases' => array('cvup'),
  );
  $items['civicrm-restore'] = array(
    'description' => 'Restore CiviCRM codebase and database back from the specified backup directory.',
    'examples' =>
    array(
      'drush civicrm-restore --restore-dir=../backup/modules/20100309200249' =>
      'Replace current civicrm codebase with the $restore-dir/civicrm codebase, and reload the database with $restore-dir/civicrm.sql file',
    ),
    'options' =>
    array(
      'restore-dir' =>
      'Path of directory having backed up CiviCRM codebase and database.',
      'backup-dir' =>
      'Specify a directory to backup current CiviCRM codebase and database into, defaults to a backup directory above your Drupal root.',
    ),
  );
  $items['civicrm-rest'] = array(
    'description' => "Rest interface for accessing CiviCRM APIs. It can return xml or json formatted data.",
    'examples' =>
    array(
      "drush civicrm-rest --query='civicrm/contact/search&json=1&key=7decb879f28ac4a0c6a92f0f7889a0c9&api_key=7decb879f28ac4a0c6a92f0f7889a0c9'" =>
      'Use contact search api to return data in json format.',
    ),
    // TODO: This really makes more sense as an argument.
    'options' =>
    array('query' => 'Query part of url. Refer CiviCRM wiki doc for more details.'),
    'aliases' => array('cvr'),
  );
  $items['civicrm-sql-conf'] = array(
    // explicit callback declaration and non-standard name to avoid collision with "sql-conf"
    'callback' => 'drush_civicrm_sqlconf',
    'description' => 'Print CiviCRM database connection details.',
  );
  $items['civicrm-sql-connect'] = array(
    // explicit callback declaration and non-standard name to avoid collision with "sql-connect"
    'callback' => 'drush_civicrm_sqlconnect',
    'description' => 'A string for connecting to the CiviCRM DB.',
  );
  $items['civicrm-sql-dump'] = array(
    // explicit callback declaration and non-standard name to avoid collision with "sql-dump"
    'callback' => 'drush_civicrm_sqldump',
    'description' => 'Exports the CiviCRM DB as SQL using mysqldump.',
    'examples' => array(
      'drush civicrm-sql-dump --result-file=../CiviCRM.sql' => 'Save SQL dump to the directory above Drupal root.',
    ),
    'options' => array(
      'data-only' => 'Dump data without statements to create any of the schema.',
      'gzip' => 'Compress the dump using the gzip program which must be in your $PATH.',
      'result-file' => 'Save to a file.',
      'tables-list' => 'comma-separated list of tables to transfer.',
    ),
  );
  $items['civicrm-sql-query'] = array(
    // explicit callback declaration and non-standard name to avoid collision with "sql-query"
    'callback' => 'drush_civicrm_sqlquery',
    'description' => 'Execute a query against the CiviCRM database.',
    'examples' => array(
      'drush civicrm-sql-query "SELECT * FROM civicrm_contact WHERE id=1"' => 'Browse user record',
      'drush civicrm-sql-query --file=example.sql' => 'Alternate way to import sql statements from a file.',
    ),
    'arguments' => array(
      'query' => 'A SQL query. Ignored if \'file\' is provided.',
    ),
    'options' => array(
      'file' => 'Path to a file containing the SQL to be run. ',
    ),
  );
  $items['civicrm-sql-cli'] = array(
    // explicit callback declaration and non-standard name to avoid collision with "sql-cli"
    'callback' => 'drush_civicrm_sqlcli',
    'description' => "Open a SQL command-line interface using CiviCRM's credentials.",
    'aliases' => array('cvsqlc'),
  );
  $items['civicrm-process-mail-queue'] = array(
    'description' => "Process pending CiviMail mailing jobs.",
    'examples' =>
    array(
      'drush civicrm-process-mail-queue -u admin' =>
      'Process CiviMail queue with admin credentials.',
    ),
  );
  $items['civicrm-member-records'] = array(
    'description' => "Run the CiviMember UpdateMembershipRecord cron (civicrm-member-records).",
  );

  return $items;
}

/**
 * Implementation of database specification for the active DB connection
 */
function drush_civicrm_get_db_spec() {
  if (version_compare(DRUSH_VERSION, 7, '>=')) {
      $sql = drush_sql_get_class();
      $db_spec = $sql->db_spec();
  }
  else {
    $db_spec = _drush_sql_get_db_spec();
  }
  return $db_spec;
}

/**
 * Implementation of drush_hook_COMMAND_validate for command 'civicrm-install'
 */
function drush_civicrm_install_validate() {
  // TODO: Replace these with required options (Drush 5).
  // Get the drupal credentials in case civi specific db info is not passed.
  if (drush_get_option('db-url', FALSE)) {
    $db_spec['db-url'] = $GLOBALS['db_url'];
  }
  elseif (drush_get_option('all', FALSE)) {
    $db_spec = _drush_sql_get_all_db_specs();
  }
  if (!isset($db_spec)) {
    $db_spec = drush_civicrm_get_db_spec();
  }

  if (!drush_get_option('dbuser', FALSE)) {
    drush_set_option('dbuser', $db_spec['username']);
  }
  if (!drush_get_option('dbpass', FALSE)) {
    drush_set_option('dbpass', $db_spec['password']);
  }
  if (!drush_get_option('dbhost', FALSE)) {
    drush_set_option('dbhost', $db_spec['host']);
  }
  if (!drush_get_option('dbname', FALSE)) {
    drush_set_option('dbname', $db_spec['database']);
  }

  $crmpath    = _civicrm_get_crmpath();
  $drupalRoot = drush_get_context('DRUSH_DRUPAL_ROOT');
  $modPath    = "$drupalRoot/$crmpath";

  if (!is_dir("$modPath/civicrm") && !drush_get_option('tarfile', FALSE)) {
    return drush_set_error('CIVICRM_INSTALL_TARFILE_NOT_SPECIFIED', dt('CiviCRM tarfile not specified.'));
  }
  if (drush_get_option('lang', FALSE) && !drush_get_option('langtarfile', FALSE)) {
    return drush_set_error('CIVICRM_INSTALL_LANGTARFILE_NOT_SPECIFIED', dt('CiviCRM language tarfile not specified.'));
  }

  return TRUE;
}


/**
 * Implementation of command 'civicrm-install'
 */
function drush_civicrm_install() {
  $dbuser = drush_get_option('dbuser', FALSE);
  $dbpass = drush_get_option('dbpass', FALSE);
  $dbhost = drush_get_option('dbhost', FALSE);
  $dbname = drush_get_option('dbname', FALSE);

  $crmpath    = _civicrm_get_crmpath();
  $drupalRoot = drush_get_context('DRUSH_DRUPAL_ROOT');
  $modPath    = "$drupalRoot/$crmpath";
  $lang       = drush_get_option('lang', '');
  $loadGeneratedData = drush_get_option('load_generated_data', FALSE);

  if (!is_dir("$modPath/civicrm")) {
    // extract tarfile at right place
    _civicrm_extract_tarfile($modPath);
    drush_log(dt("Tarfile unpacked."), 'ok');
  }

  // include civicrm installer helper file
  $civicrmInstallerHelper = "$modPath/civicrm/install/civicrm.php";
  if (!file_exists($civicrmInstallerHelper)) {
    return drush_set_error('CIVICRM_NOT_PRESENT', dt("CiviCRM installer helper file is missing."));
  }

  if ($lang != '') {
    _civicrm_extract_tarfile($modPath, "langtarfile");
  }

  // setup all required files/civicrm/* directories
  if (!_civicrm_create_files_dirs($civicrmInstallerHelper, $modPath)) {
    return FALSE;
  }

  // install database
  _civicrm_install_db($dbuser, $dbpass, $dbhost, $dbname, $modPath, $lang, $loadGeneratedData);

  // generate civicrm.settings.php file
  _civicrm_generate_settings_file($dbuser, $dbpass, $dbhost, $dbname, $modPath);

  module_enable(array('civicrm'));

  drush_log(dt("CiviCRM installed."), 'ok');
}

function _civicrm_extract_tarfile($destinationPath, $option = 'tarfile') {
  $tarpath = drush_get_option($option, FALSE);
  if (drush_shell_exec("gzip -d " . $tarfile)) {
    $tarpath = substr($tarfile, 0, strlen($tarfile) - 3);
  }
  drush_shell_exec("tar -xf $tarpath -C \"$destinationPath\"");
}

function _civicrm_install_db($dbuser, $dbpass, $dbhost, $dbname,
  $modPath, $lang, $loadGeneratedData
) {
  $drupalRoot = drush_get_context('DRUSH_DRUPAL_ROOT');
  $siteRoot = drush_get_context('DRUSH_DRUPAL_SITE_ROOT', FALSE);

  $sqlPath = "$modPath/civicrm/sql";
  $conn = @mysql_connect($dbhost, $dbuser, $dbpass);
  if (!@mysql_select_db($dbname) &&
    !@mysql_query("CREATE DATABASE $database")
  ) {
    drush_die(dt('CiviCRM database was not found. Failed to create one.'));
  }

  // setup database with civicrm structure and data
  $dsn = "mysql://{$dbuser}:{$dbpass}@{$dbhost}/{$dbname}?new_link=true";
  drush_log(dt("Loading CiviCRM database structure .."));
  civicrm_source($dsn, $sqlPath . '/civicrm.mysql');
  drush_log(dt("Loading CiviCRM database with required data .."));
  // testing the translated sql files availability
  $data_file = $sqlPath . '/civicrm_data.mysql';
  $acl_file = $sqlPath . '/civicrm_acl.mysql';
  if ($lang != '') {
    if (file_exists($sqlPath . '/civicrm_data.' . $lang . '.mysql')
      and file_exists($sqlPath . '/civicrm_acl.' . $lang . '.mysql')
      and $lang != ''
    ) {
      $data_file = $sqlPath . '/civicrm_data.' . $lang . '.mysql';
      $acl_file = $sqlPath . '/civicrm_acl.' . $lang . '.mysql';
    }
    else {
      drush_log(dt("No sql files could be retrieved for \"" . $lang .
          "\", using default language."
        ), 'warning');
    }
  }
  civicrm_source($dsn, $data_file);
  civicrm_source($dsn, $acl_file);

  if ($loadGeneratedData) {
    civicrm_source($dsn, $sqlPath . DIRECTORY_SEPARATOR . 'civicrm_generated.mysql', TRUE);
  }
  drush_log(dt("CiviCRM database loaded successfully."), 'ok');
}

function _civicrm_create_files_dirs($civicrmInstallerHelper, $modPath) {
  $drupalRoot = drush_get_context('DRUSH_DRUPAL_ROOT');
  $siteRoot = drush_get_context('DRUSH_DRUPAL_SITE_ROOT', FALSE);

  if (!file_exists($civicrmInstallerHelper)) {
    return drush_set_error('CIVICRM_INSTALLER_HELP_MISSING', dt("CiviCRM installer helper file is missing."));
  }
  require_once "$civicrmInstallerHelper";

  // create files/civicrm/* dirs
  global $crmPath;
  $crmPath = "$modPath/civicrm";
  civicrm_setup("$drupalRoot/$siteRoot/files");
  @drush_op('chmod', "$drupalRoot/$siteRoot/files/civicrm", 0777);

  return TRUE;
}

// generates civicrm.settings.php file
function _civicrm_generate_settings_file($dbuser, $dbpass, $dbhost, $dbname, $modPath) {
  $drupalRoot = drush_get_context('DRUSH_DRUPAL_ROOT');
  $siteRoot   = drush_get_context('DRUSH_DRUPAL_SITE_ROOT', FALSE);
  $crmPath    = "$modPath/civicrm";

  $files = array(
    "$crmPath/templates/CRM/common/civicrm.settings.php.template",
    "$crmPath/templates/CRM/common/civicrm.settings.php.tpl"
  );

  $settingsTplFile = NULL;
  foreach ($files as $file) {
    if (file_exists($file)) {
      $settingsTplFile = $file;
    }
  }

  if (!$settingsTplFile) {
    drush_die(dt("Could not find CiviCRM settings template and therefore could not create settings file."));
  }

  drush_log(dt("Generating civicrm settings file .."));
  if ($baseUrl = drush_get_option('site_url', FALSE)) {
    $ssl = drush_get_option('ssl', FALSE);
    if ($ssl == 'on') {
      $protocol = 'https';
    }
    else {
      $protocol = 'http';
    }
  }

  $baseUrl = !$baseUrl ? ($GLOBALS['base_url']) : ($protocol . '://' . $baseUrl);
  $db_spec = drush_civicrm_get_db_spec();

  // Check version: since 4.1, Drupal6 must be used for the UF in D6
  // The file civicrm-version.php appeared around 4.0, so it is safe to assume
  // that if it's not there, it's 3.x, in which case the CMS 'Drupal' is D6.
  $cms = 'Drupal';

  if (file_exists("$crmPath/civicrm-version.php")) {
    require_once "$crmPath/civicrm-version.php";
    $v = civicrmVersion();
    $cms = $v['cms'];
  }

  $params = array(
    'crmRoot' => $crmPath,
    'templateCompileDir' => "$drupalRoot/$siteRoot/files/civicrm/templates_c",
    'frontEnd' => 0,
    'cms' => $cms,
    'baseURL' => $baseUrl,
    'dbUser' => $dbuser,
    'dbPass' => $dbpass,
    'dbHost' => $dbhost,
    'dbName' => $dbname,
    'CMSdbUser' => $db_spec['username'],
    'CMSdbPass' => $db_spec['password'],
    'CMSdbHost' => $db_spec['host'],
    'CMSdbName' => $db_spec['database'],
    'siteKey' => md5(uniqid('', TRUE) . $baseUrl),
  );
  $str = file_get_contents($settingsTplFile);
  foreach ($params as $key => $value) {
    $str = str_replace('%%' . $key . '%%', $value, $str);
  }
  $str = trim($str);

  $configFile = "$drupalRoot/$siteRoot/civicrm.settings.php";
  civicrm_write_file($configFile, $str);
  @drush_op('chmod', "$configFile", 0644);
  drush_log(dt("Settings file generated: !file", array('!file' => $configFile)), 'ok');
}

/**
 * Implementation of hook_drush_help().
 *
 * This function is called whenever a drush user calls
 * 'drush help <name-of-your-command>'
 *
 * @param
 *   A string with the help section (prepend with 'drush:')
 *
 * @return
 *   A string with the help text for your command.
 */
function civicrm_drush_help($section) {
  switch ($section) {
    case 'drush:civicrm-upgrade-db':
      return dt("Run civicrm/upgrade?reset=1 just as a web browser would.");

    case 'drush:civicrm-update-cfg':
      return dt("Update config_backend to correct config settings, especially when the CiviCRM site has been cloned / migrated.");

    case 'drush:civicrm-upgrade':
      return dt("Take backups, replace CiviCRM codebase with new specified tarfile and upgrade database by executing the CiviCRM upgrade process - civicrm/upgrade?reset=1. Use civicrm-restore to revert to previous state in case anything goes wrong.");

    case 'drush:civicrm-restore':
      return dt("Restore CiviCRM codebase and database back from the specified backup directory.");

    case 'drush:civicrm-rest':
      return dt("Rest interface for accessing CiviCRM APIs. It can return xml or json formatted data.");

    case 'drush:civicrm-sql-conf':
      return dt('Show civicrm database connection details.');

    case 'drush:civicrm-sql-connect':
      return dt('A string which connects to the civicrm database.');

    case 'drush:civicrm-sql-cli':
      return dt('Quickly enter the mysql command line.');

    case 'drush:civicrm-sql-dump':
      return dt('Prints the whole CiviCRM database to STDOUT or save to a file.');

    case 'drush:civicrm-sql-query':
      return dt("Usage: drush [options] civicrm-sql-query <query>...\n<query> is a SQL statement. Any additional arguments are passed to the mysql command directly.");
  }
}

/**
 * Implementation of command 'civicrm-ext-list'
 */
function drush_civicrm_ext_list() {
  civicrm_initialize();
  try{
    $result = civicrm_api('extension', 'get', array('version' => 3));
    $rows = array(array(dt('App name'), dt('Status')));
    foreach ($result['values'] as $k => $extension_data) {
      $rows[] = array(
        $extension_data['key'],
        $extension_data['status'],
      );
    }
    unset($result);
    drush_print_table($rows, TRUE);
  }
  catch (CiviCRM_API3_Exception $e) {
    // handle error here
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    drush_log(dt("!error", array('!error' => $errorData), 'error'));
  }
}

/**
 * Implementation of command 'civicrm-ext-install'
 */
function drush_civicrm_ext_install($extension_name) {
  civicrm_initialize();
  try{
    $result = civicrm_api('extension', 'install', array('key' => $extension_name, 'version' => 3));
    if($result['values'] && $result['values'] == 1) {
      drush_print(dt("Extension !ename installed.", array('!ename' => $extension_name)));
    } else {
      drush_log(t('Extension !ename could not be installed.', array('!ename' => $extension_name)), 'error');
    }
  }
  catch (CiviCRM_API3_Exception $e) {
    // handle error here
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    drush_log(dt("!error", array('!error' => $errorData), 'error'));
  }
}

/**
 * Implementation of command 'civicrm-ext-disable'
 */
function drush_civicrm_ext_disable($extension_name) {
  civicrm_initialize();
  try{
    $result = civicrm_api('extension', 'disable', array('key' => $extension_name, 'version' => 3));
    if($result['values'] && $result['values'] == 1) {
      drush_print(dt("Extension !ename disabled.", array('!ename' => $extension_name)));
    } else {
      drush_log(t('Extension !ename could not be disabled.', array('!ename' => $extension_name)), 'error');
    }
  }
  catch (CiviCRM_API3_Exception $e) {
    // handle error here
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    drush_log(dt("!error", array('!error' => $errorData), 'error'));
  }
}

/**
 * Implementation of command 'civicrm-ext-uninstall'
 */
function drush_civicrm_ext_uninstall($extension_name) {
  civicrm_initialize();
  try{
    $result = civicrm_api('extension', 'uninstall', array('key' => $extension_name, 'version' => 3));
    if($result['values'] && $result['values'] == 1) {
      drush_print(dt("Extension !ename uninstalled.", array('!ename' => $extension_name)));
    } else {
      drush_log(t('Extension !ename could not be uninstalled.', array('!ename' => $extension_name)), 'error');
    }
  }
  catch (CiviCRM_API3_Exception $e) {
    // handle error here
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    drush_log(dt("!error", array('!error' => $errorData), 'error'));
  }
}

/**
 * Implementation of drush_hook_COMMAND_validate for command 'civicrm-upgrade-db'
 */
function drush_civicrm_upgrade_db_validate() {
  if (!defined('CIVICRM_UPGRADE_ACTIVE')) {
    define('CIVICRM_UPGRADE_ACTIVE', 1);
  }
  $_GET['q'] = 'civicrm/upgrade';

  if (!_civicrm_init()) {
    return FALSE;
  }

  $_POST['upgrade'] = 1;
  $_GET['q'] = 'civicrm/upgrade';
  require_once 'CRM/Core/Config.php';

  require_once 'CRM/Utils/System.php';
  require_once 'CRM/Core/BAO/Domain.php';
  $codeVer = CRM_Utils_System::version();
  $dbVer = CRM_Core_BAO_Domain::version();
  if (!$dbVer) {
    return drush_set_error('CIVICRM_VERSION_MISSING_DATABASE', dt('Version information missing in civicrm database.'));
  }
  elseif (stripos($dbVer, 'upgrade')) {
    return drush_set_error('CIVICRM_DATABASE_CHECK_FAIL', dt('Database check failed - the database looks to have been partially upgraded. You may want to reload the database with the backup and try the upgrade process again.'));
  }
  elseif (!$codeVer) {
    return drush_set_error('CIVICRM_VERSION_MISSING_CODE', dt('Version information missing in civicrm codebase.'));
  }
  elseif (version_compare($codeVer, $dbVer) > 0) {
    drush_log(dt("Starting with v!dbVer -> v!codeVer upgrade ..", array('!dbVer' => $dbVer, '!codeVer' => $codeVer)));
  }
  elseif (version_compare($codeVer, $dbVer) < 0) {
    return drush_set_error('CIVICRM_VERSION_UNEXPECTED', dt("Database is marked with an unexpected version '!dbVer' which is higher than that of codebase version '!codeVer'.", array('!dbVer' => $dbVer, '!codeVer' => $codeVer)));
  }

  return TRUE;
}

/**
 * Implementation of command 'civicrm-upgrade-db'
 */
function drush_civicrm_upgrade_db() {
  if (class_exists('CRM_Upgrade_Headless')) {
    // Note: CRM_Upgrade_Headless introduced in 4.2 -- at the same time as class auto-loading
    $codeVer = CRM_Utils_System::version();
    $dbVer = CRM_Core_BAO_Domain::version();
    if (version_compare($codeVer, $dbVer) == 0) {
      drush_print(dt("You are already upgraded to CiviCRM @version", array('@version' => $codeVer)));
      return TRUE;
    }
    $upgradeHeadless = new CRM_Upgrade_Headless();
    // FIXME Exception handling?
    $result = $upgradeHeadless->run();
    drush_print("Upgrade outputs:\n" . $result['text']);
  }
  else {
    require_once 'CRM/Core/Smarty.php';
    $template = CRM_Core_Smarty::singleton();

    require_once ('CRM/Upgrade/Page/Upgrade.php');
    $upgrade = new CRM_Upgrade_Page_Upgrade();

    // new since CiviCRM 4.1
    if (is_callable(array(
      $upgrade, 'setPrint'))) {
      $upgrade->setPrint(TRUE);
    }

    // to suppress html output /w source code.
    ob_start();
    $upgrade->run();
    // capture the required message.
    $result = $template->get_template_vars('message');
    ob_end_clean();
    drush_print("Upgrade outputs: " . "\"$result\"");
  }
}

/**
 * Implementation of drush_hook_COMMAND_validate for command 'civicrm-update-cfg'
 */
function drush_civicrm_update_cfg_validate() {
  return _civicrm_init();
}


/**
 * Implementation of command 'civicrm-update-cfg'
 */
function drush_civicrm_update_cfg() {
  $defaultValues = array();
  $states = array('old', 'new');
  for ($i = 1; $i <= 3; $i++) {
    foreach ($states as $state) {
      $name = "{$state}Val_{$i}";
      $value = drush_get_option($name, NULL);
      if ($value) {
        $defaultValues[$name] = $value;
      }
    }
  }

  require_once 'CRM/Core/I18n.php';
  require_once 'CRM/Core/BAO/ConfigSetting.php';
  $result = CRM_Core_BAO_ConfigSetting::doSiteMove($defaultValues);

  if ($result) {

    drush_log(dt('Config successfully updated.'), 'completed');

  }
  else drush_log(dt('Config update failed.'), 'failed');
}

/**
 * Implements hook_drush_cache_clear.
 */
function civicrm_drush_cache_clear(&$types) {
  if (_civicrm_init(FALSE)) {
    $types['civicrm'] = 'drush_civicrm_cacheclear';
  }
}

/**
 * Cache clear callback
 *
 * Warning: do not name drush_civicrm_cache_clear() otherwise it will
 * conflict with hook_drush_cache_clear() and be called systematically
 * when "drush cc" is called.
 */
function drush_civicrm_cacheclear() {
  _civicrm_init();

  // Clear the classloader cache variable
  // Should be done in CiviCRM core so that the system flush always deletes
  // the variable, however, it needs to be done early enough before the
  // ClassLoader initialization. FIXME.
  variable_del('civicrm_class_loader');

  // Flush all caches using the API
  $params = array('version' => 3);
  if (drush_get_option('triggers', FALSE)) {
    $params['triggers'] = 1;
  }

  if (drush_get_option('sessions', FALSE)) {
    $params['session'] = 1;
  }

  // Need to set API version or drush cc civicrm fails
  $params['version'] = 3;
  $result = civicrm_api('System', 'flush', $params);

  if ($result['is_error']) {
    drush_log(dt('An error occurred: !message', array('!message' => $result['error_message'])), 'error');
    return;
  }

  drush_log(dt('The CiviCRM cache has been cleared.'), 'ok');
}

/**
 * Implementation of drush_hook_COMMAND_validate for command 'civicrm-enable-debug'
 */
function drush_civicrm_enable_debug_validate() {
  return _civicrm_init();
}

function drush_civicrm_enable_debug() {
  $settings = array(
    'debug_enabled' => 1,
    'backtrace' => 1,
  );

  foreach ($settings as $key => $val) {
    $result = civicrm_api('Setting', 'create', array('version' => 3, $key => $val));

    if ($result['is_error']) {
      drush_log(dt('An error occurred: !message', array('!message' => $result['error_message'])), 'error');
      return;
    }
  }

  drush_log(dt('CiviCRM debug setting enabled.'), 'ok');
}

/**
 * Implementation of drush_hook_COMMAND_validate for command 'civicrm-disable-debug'
 */
function drush_civicrm_disable_debug_validate() {
  return _civicrm_init();
}

function drush_civicrm_disable_debug() {
  $settings = array(
    'debug_enabled' => 0,
    'backtrace' => 0,
  );

  foreach ($settings as $key => $val) {
    $result = civicrm_api('Setting', 'create', array('version' => 3, $key => $val));

    if ($result['is_error']) {
      drush_log(dt('An error occurred: !message', array('!message' => $result['error_message'])), 'error');
      return;
    }
  }

  drush_log(dt('CiviCRM debug setting disabled.'), 'ok');
}

/**
 * Implementation of drush_hook_COMMAND_validate for command 'civicrm-upgrade'
 */
function drush_civicrm_upgrade_validate() {
  // TODO: use Drush to download tarfile.
  // TODO: if tarfile is not specified, see if the code already exists and use that instead.
  $tarfile = drush_get_option('tarfile', FALSE);
  if (!$tarfile) {
    return drush_set_error('CIVICRM_TAR_NOT_SPECIFIED', dt('Tarfile not specified.'));
  }
  //FIXME: throw error if tarfile is not in a valid format.

  if (!defined('CIVICRM_UPGRADE_ACTIVE')) {
    define('CIVICRM_UPGRADE_ACTIVE', 1);
  }
  return _civicrm_init();
}

/**
 * Implementation of command 'civicrm-upgrade'
 */
function drush_civicrm_upgrade() {
  global $civicrm_root;

  $tarfile     = drush_get_option('tarfile', FALSE);
  $date        = date('YmdHis');
  $backup_file = "civicrm";

  $basepath = explode('/', $civicrm_root);
  array_pop($basepath);
  $project_path = implode('/', $basepath) . '/';

  $drupal_root = drush_get_context('DRUSH_DRUPAL_ROOT');
  $backup_dir  = drush_get_option('backup-dir', $drupal_root . '/../backup');
  $backup_dir  = rtrim($backup_dir, '/');

  drush_print(dt("\nThe upgrade process involves - "));
  drush_print(dt("1. Backing up current CiviCRM code as => !path",
      array('!path' => "$backup_dir/modules/$date/$backup_file")
    ));
  drush_print(dt("2. Backing up database as => !path",
      array('!path' => "$backup_dir/modules/$date/$backup_file.sql")
    ));
  drush_print(dt("3. Unpacking tarfile to => !path",
      array('!path' => "$project_path")
    ));
  drush_print(dt("4. Executing civicrm/upgrade?reset=1 just as a browser would.\n"));
  if (!drush_confirm(dt('Do you really want to continue?'))) {
    return drush_user_abort();
  }

  @drush_op('mkdir', $backup_dir, 0777);
  $backup_dir .= '/modules';
  @drush_op('mkdir', $backup_dir, 0777);
  $backup_dir .= "/$date";
  @drush_op('mkdir', $backup_dir, 0777);
  $backup_target = $backup_dir . '/' . $backup_file;
  if (!drush_op('rename', $civicrm_root, $backup_target)) {
    return drush_set_error('CIVICRM_BACKUP_FAILED', dt('Failed to backup CiviCRM project directory !source to !backup_target',
        array('!source' => $civicrm_root, '!backup_target' => $backup_target)
      ));
  }
  drush_log(dt("\n1. Code backed up."), 'ok');

  drush_set_option('result-file', $backup_target . '.sql');
  drush_civicrm_sqldump();
  drush_log(dt('2. Database backed up.'), 'ok');

  // Decompress & Untar
  _civicrm_extract_tarfile($project_path);
  drush_log(dt('3. Tarfile unpacked.'), 'ok');

  drush_log(dt("4. "));

  if (drush_civicrm_upgrade_db_validate()) {
    drush_civicrm_upgrade_db();
  }
  drush_log(dt("\nProcess completed."), 'completed');
}

/**
 * Implementation of drush_hook_COMMAND_validate for command 'civicrm-restore'
 */
function drush_civicrm_restore_validate() {
  _civicrm_dsn_init();

  $restore_dir = drush_get_option('restore-dir', FALSE);
  $restore_dir = rtrim($restore_dir, '/');
  if (!$restore_dir) {
    return drush_set_error('CIVICRM_RESTORE_NOT_SPECIFIED', dt('Restore-dir not specified.'));
  }
  $sql_file = $restore_dir . '/civicrm.sql';
  if (!file_exists($sql_file)) {
    return drush_set_error('CIVICRM_RESTORE_CIVICRM_SQL_NOT_FOUND', dt('Could not locate civicrm.sql file in the restore directory.'));
  }
  $code_dir = $restore_dir . '/civicrm';
  if (!is_dir($code_dir)) {
    return drush_set_error('CIVICRM_RESTORE_DIR_NOT_FOUND', dt('Could not locate civicrm directory inside restore-dir.'));
  }
  elseif (!file_exists("$code_dir/civicrm-version.php")) {
    return drush_set_error('CIVICRM_RESTORE_DIR_NOT_VALID', dt('civicrm directory inside restore-dir, doesn\'t look to be a valid civicrm codebase.'));
  }

  return TRUE;
}

/**
 * Implementation of command 'civicrm-restore'
 */
function drush_civicrm_restore() {
  $restore_dir = drush_get_option('restore-dir', FALSE);
  $restore_dir = rtrim($restore_dir, '/');
  $sql_file    = $restore_dir . '/civicrm.sql';
  $code_dir    = $restore_dir . '/civicrm';
  $date        = date('YmdHis');

  global $civicrm_root;
  $civicrm_root_base = explode('/', $civicrm_root);
  array_pop($civicrm_root_base);
  $civicrm_root_base = implode('/', $civicrm_root_base) . '/';

  $drupal_root        = drush_get_context('DRUSH_DRUPAL_ROOT');
  $restore_backup_dir = drush_get_option('backup-dir', $drupal_root . '/../backup');
  $restore_backup_dir = rtrim($restore_backup_dir, '/');

  // get confirmation from user -
  $db_spec = drush_civicrm_get_db_spec();
  drush_print(dt("\nProcess involves :"));
  drush_print(dt("1. Restoring '\$restore-dir/civicrm' directory to '!toDir'.", array('!toDir' => $civicrm_root_base)));
  drush_print(dt("2. Dropping and creating '!db' database.", array('!db' => $db_spec['database'])));
  drush_print(dt("3. Loading '\$restore-dir/civicrm.sql' file into the database."));
  drush_print();
  drush_print(dt("Note: Before restoring a backup will be taken in '!path' directory.",
      array('!path' => "$restore_backup_dir/modules/restore")
    ));
  drush_print();
  if (!drush_confirm(dt('Do you really want to continue?'))) {
    return drush_user_abort();
  }

  // create restore-backup-dir if not already exists
  @drush_op('mkdir', $restore_backup_dir, 0777);
  $restore_backup_dir .= '/modules';
  @drush_op('mkdir', $restore_backup_dir, 0777);
  $restore_backup_dir .= '/restore';
  @drush_op('mkdir', $restore_backup_dir, 0777);
  $restore_backup_dir .= "/$date";
  @drush_op('mkdir', $restore_backup_dir, 0777);

  // 1. backup and restore codebase
  drush_log(dt('Restoring civicrm codebase ..'));
  if (is_dir($civicrm_root) && !drush_op('rename', $civicrm_root, $restore_backup_dir . '/civicrm')) {
    return drush_set_error('CIVICRM_RESTORE_CODE_FAILED', dt("Failed to take backup for '!destination' directory",
        array('!destination' => $civicrm_root)
      ));
  }
  if (!drush_op('rename', $code_dir, $civicrm_root)) {
    return drush_set_error('CIVICRM_RESTORE_DESTINATION_FAILED', dt("Failed to restore civicrm directory '!source' to '!dest'",
        array('!source' => $code_dir, '!dest' => $civicrm_root_base)
      ));
  }
  drush_log(dt('Codebase restored.'), 'ok');

  // 2. backup, drop and create database
  drush_set_option('result-file', $restore_backup_dir . '/civicrm.sql');
  drush_civicrm_sqldump();

  drush_log(dt('Database backed up.'), 'ok');

  if (version_compare(DRUSH_VERSION, 7, '>=')) {
    $sql = drush_sql_get_class();
    $exec = 'mysql ' . $sql->creds() . ' -e ';
    $dbDriver = $db_spec['driver'];
  }
  else {
    $exec = 'mysql' . _drush_sql_get_credentials() . ' -e ';
    $dbDriver = _drush_sql_get_scheme();
  }
  drush_log(dt("\nDropping database '!db' ..", array('!db' => $db_spec['database'])));
  if (drush_op('system', $exec . '"DROP DATABASE IF EXISTS ' . $db_spec['database'] . '"')) {
    return drush_set_error('CIVICRM_RESTORE_DATABASE_DROP_FAILED', dt('Could not drop database: @name', array('@name' => $db_spec['database'])));
  }
  drush_log(dt('Database dropped.'), 'ok');
  $exec = str_replace($db_spec['database'], '', $exec);
  if (drush_op('system', $exec . '"CREATE DATABASE ' . $db_spec['database'] . '"')) {
    return drush_set_error('CIVICRM_RESTORE_DATABASE_CREATE_FAILED', dt('Could not create new database: @name', array('@name' => $db_spec['database'])));
  }
  drush_log(dt('Database created.'), 'ok');

  // 3. restore database
  switch ($dbDriver) {
    case 'mysql':
      if (version_compare(DRUSH_VERSION, 7, '>=')) {
        $send = 'mysql ' . $sql->creds();
      }
      else {
        $send = 'mysql' . _drush_sql_get_credentials();
      }
      break;

    case 'pgsql':
      if (version_compare(DRUSH_VERSION, 7, '>=')) {
        $send .= 'psql -d ' . $sql->creds() . ' --file -';
      }
      else {
        $send .= 'psql -d ' . _drush_sql_get_credentials() . ' --file -';
      }
      break;
  }
  $exec = "$send < $sql_file";
  drush_log(dt('Loading civicrm.sql file from restore-dir ..'));
  drush_op('system', $exec);
  drush_log(dt('Database restored.'), 'ok');

  drush_log(dt('Restore process completed.'), 'completed');

  _civicrm_dsn_close();
}

/**
 * ?? for command 'civicrm-civimail'
 */
function drush_civicrm_civimail_cron() {
  civicrm_api('Mailing', 'Process', array('version' => 3));
}

/**
 * Implementation of command 'civicrm-member-records'
 */
function drush_civicrm_member_records() {
  _civicrm_init();

  $_REQUEST['name'] = drush_get_option('civicrm_cron_username', NULL);
  $_REQUEST['pass'] = drush_get_option('civicrm_cron_password', NULL);
  $_REQUEST['key']  = drush_get_option('civicrm_sitekey', NULL);

  global $argv;
  $argv = array(
    0 => "drush",
    1 => "-u" . $_REQUEST['name'],
    2 => "-p" . $_REQUEST['pass'],
    3 => "-s" . drush_get_option('uri', FALSE),
  );

  if (!defined('CIVICRM_CONFDIR')) {
    define('CIVICRM_CONFDIR', drush_get_context('DRUSH_DRUPAL_ROOT') . '/sites/');
  }

  include "bin/UpdateMembershipRecord.php";
}

/**
 * Implementation of drush_hook_COMMAND_validate for command 'civicrm-rest' ('cvr')
 */
function drush_civicrm_rest_validate() {
  $query = drush_get_option('query', FALSE);
  if (!$query) {
    drush_set_error('CIVICRM_REST_EMPTY_QUERY', dt('query not specified.'));
  }

  return _civicrm_init();
}

/**
 * Implementation of command 'civicrm-rest' ('cvr')
 */
function drush_civicrm_rest() {
  $query     = drush_get_option('query', FALSE);
  $query     = explode('&', $query);
  $_GET['q'] = array_shift($query);
  foreach ($query as $keyVal) {
    list($key, $val) = explode('=', $keyVal);
    $_REQUEST[$key] = $val;
    $_GET[$key] = $val;
  }

  require_once 'CRM/Utils/REST.php';
  $rest = new CRM_Utils_REST();

  require_once 'CRM/Core/Config.php';
  $config = CRM_Core_Config::singleton();

  global $civicrm_root;
  // adding dummy script, since based on this api file path is computed.
  $_SERVER['SCRIPT_FILENAME'] = "$civicrm_root/extern/rest.php";

  if (isset($_GET['json']) &&
    $_GET['json']
  ) {
    header('Content-Type: text/javascript');
  }
  else {
    header('Content-Type: text/xml');
  }
  echo $rest->run($config);
}

/**
 * Implements drush_hook_pre_COMMAND().
 *
 * this function is called when using drush 6.
 */
function drush_civicrm_pre_civicrm_sql_dump() {
  _civicrm_dsn_init();
}

/**
 * Implements drush_hook_pre_COMMAND().
 *
 * this function is called when using drush 5.
 */
function drush_civicrm_pre_civicrm_sqldump() {
  _civicrm_dsn_init();
}

/**
 * Implementation of command 'civicrm-sql-dump'
 */
function drush_civicrm_sqldump() {
  if (version_compare(DRUSH_VERSION, 7, '>=')) {
    drush_sql_dump();
  }
  else {
    drush_sql_dump_execute();
  }
}

/**
 * Implements drush_hook_post_COMMAND().
 *
 * this function is called when using drush 6.
 */
function drush_civicrm_post_civicrm_sql_dump() {
  _civicrm_dsn_close();
}

/**
 * Implements drush_hook_post_COMMAND().
 *
 * this function is called when using drush 5.
 */
function drush_civicrm_post_civicrm_sqldump() {
  _civicrm_dsn_close();
}

/**
 * Implements drush_hook_pre_COMMAND().
 *
 * this function is called when using drush 6.
 */
function drush_civicrm_pre_civicrm_sql_conf() {
  _civicrm_dsn_init();
}

/**
 * Implements drush_hook_post_COMMAND().
 *
 * this function is called when using drush 5.
 */
function drush_civicrm_pre_civicrm_sqlconf() {
  _civicrm_dsn_init();
}

/**
 * Implementation of command 'civicrm-sql-conf'
 */
function drush_civicrm_sqlconf() {
  $conf = drush_sql_conf();
  // Before drush 6 drush_sql_conf already does drush_print_r, so it shouldn't
  // be called again.
  if (version_compare(DRUSH_VERSION, 6, '>=')) {
    drush_print_r($conf);
  }

  // Return the conf array too, so it can be used as array when called through
  // a php function (e.g. drush_invoke_process).
  return $conf;
}

/**
 * Implements drush_hook_post_COMMAND().
 *
 * this function is called when using drush 6.
 */
function drush_civicrm_post_civicrm_sql_conf() {
  _civicrm_dsn_close();
}

/**
 * Implements drush_hook_post_COMMAND().
 *
 * this function is called when using drush 5.
 */
function drush_civicrm_post_civicrm_sqlconf() {
  _civicrm_dsn_close();
}

/**
 * Implements drush_hook_pre_COMMAND().
 *
 * this function is called when using drush 6.
 */
function drush_civicrm_pre_civicrm_sql_connect() {
  _civicrm_dsn_init();
}

/**
 * Implements drush_hook_pre_COMMAND().
 *
 * this function is called when using drush 5.
 */
function drush_civicrm_pre_civicrm_sqlconnect() {
  _civicrm_dsn_init();
}

/**
 * Implementation of command 'civicrm-sql-connect'
 */
function drush_civicrm_sqlconnect() {
  return drush_sql_connect();
}

/**
 * Implements drush_hook_post_COMMAND().
 *
 * this function is called when using drush 6.
 */
function drush_civicrm_post_civicrm_sql_connect() {
  _civicrm_dsn_close();
}

/**
 * Implements drush_hook_post_COMMAND().
 *
 * this function is called when using drush 5.
 */
function drush_civicrm_post_civicrm_sqlconnect() {
  _civicrm_dsn_close();
}

/**
 * Implements drush_hook_pre_COMMAND().
 *
 * this function is called when using drush 6.
 */
function drush_civicrm_pre_civicrm_sql_query() {
  _civicrm_dsn_init();
}

/**
 * Implements drush_hook_pre_COMMAND().
 *
 * this function is called when using drush 5.
 */
function drush_civicrm_pre_civicrm_sqlquery() {
  _civicrm_dsn_init();
}

/**
 * Implementation of command 'civicrm-sql-query'
 */
function drush_civicrm_sqlquery($query = '') {
  return drush_sql_query($query);
}

/**
 * Implements drush_hook_post_COMMAND().
 *
 * this function is called when using drush 6.
 */
function drush_civicrm_post_civicrm_sql_query() {
  _civicrm_dsn_close();
}

/**
 * Implements drush_hook_post_COMMAND().
 *
 * this function is called when using drush 5.
 */
function drush_civicrm_post_civicrm_sqlquery() {
  _civicrm_dsn_close();
}

/**
 * Implements drush_hook_pre_COMMAND().
 *
 * this function is called when using drush 6.
 */
function drush_civicrm_pre_civicrm_sql_cli() {
  _civicrm_dsn_init();
}

/**
 * Implements drush_hook_pre_COMMAND().
 *
 * this function is called when using drush 5.
 */
function drush_civicrm_pre_civicrm_sqlcli() {
  _civicrm_dsn_init();
}

/**
 * Implementation of command 'civicrm-sql-cli'
 */
function drush_civicrm_sqlcli() {
  drush_sql_cli();
}

/**
 * Implements drush_hook_post_COMMAND().
 *
 * this function is called when using drush 6.
 */
function drush_civicrm_post_civicrm_sql_cli() {
  _civicrm_dsn_close();
}

/**
 * Implements drush_hook_post_COMMAND().
 *
 * this function is called when using drush 5.
 */
function drush_civicrm_post_civicrm_sqlcli() {
  _civicrm_dsn_close();
}

function _civicrm_dsn_init($reset = FALSE) {
  static $globalDbUrl = NULL;

  if (!_civicrm_init()) {
    return FALSE;
  }

  // check if we're using the old-style $GLOBALS['db_url']
  // or the new style ( > drupal 7 )
  if (drush_drupal_major_version() >= 7) {
    $database = drush_get_option('database', 'default');
    $target = drush_get_option('target', 'default');
    if (!$globalDbUrl && CIVICRM_DSN) {
      if (isset($GLOBALS['databases'][$database][$target])) {
        // keep a copy so that we can put it back.
        $globalDbUrl = $GLOBALS['databases'][$database][$target];
      }
      // now modify $GLOBALS so that drush works on CIVICRM_DSN instead of drupal's
      $GLOBALS['databases'][$database][$target] = drush_convert_db_from_db_url(CIVICRM_DSN);
    }
  }
  else {
    if (!$globalDbUrl && CIVICRM_DSN) {
      // keep a copy so that we can put it back.
      $globalDbUrl = $GLOBALS['db_url'];
    }
    // now modify $GLOBALS so that drush works on CIVICRM_DSN instead of drupal's
    $GLOBALS['db_url'] = CIVICRM_DSN;
  }
  $dbUrl = $globalDbUrl;
  $globalDbUrl = $reset ? NULL : $globalDbUrl;

  return $dbUrl;
}

function _civicrm_dsn_close() {
  $globalDbUrl = _civicrm_dsn_init(TRUE);
  if ($globalDbUrl) {
    if (drush_drupal_major_version() >= 7) {
      $database = drush_get_option('database', 'default');
      $target = drush_get_option('target', 'default');
      $GLOBALS['databases'][$database][$target] = $globalDbUrl;
    }
    else {
      $GLOBALS['db_url'] = $globalDbUrl;
    }
  }
}

/**
 * Initializes the CiviCRM environment and configuration.
 * TODO: document why we can't call civicrm_initialize() directly.
 *
 * @param  bool fail
 *   If true, will halt drush. Otherwise, return false but do not interrupt.
 *
 * @return bool
 *   Returns TRUE if CiviCRM was initialized.
 */
function _civicrm_init($fail = TRUE) {
  static $init = NULL;

  // return if already initialized
  if ($init) {
    return $init;
  }

  global $cmsPath;
  $cmsPath             = $drupal_root = drush_get_context('DRUSH_DRUPAL_ROOT');
  $site_root           = drush_get_context('DRUSH_DRUPAL_SITE_ROOT', FALSE);
  $civicrmSettingsFile = "$drupal_root/$site_root/civicrm.settings.php";

  if (!file_exists($civicrmSettingsFile)) {
    $sites_subdir = drush_get_option('sites-subdir', 'default');
    $civicrmSettingsFile = "$drupal_root/sites/$sites_subdir/civicrm.settings.php";
    if (!file_exists($civicrmSettingsFile)) {
      if ($fail) {
        return drush_set_error('CIVICRM_INIT_SETTINGS_NOT_FOUND', dt('Could not locate civicrm settings file.'));
      }
      else {
        return FALSE;
      }
    }
  }
  // include settings file
  define('CIVICRM_SETTINGS_PATH', $civicrmSettingsFile);
  include_once $civicrmSettingsFile;
  global $civicrm_root;
  if (!is_dir($civicrm_root)) {
    return drush_set_error('CIVICRM_INIT_CODEBASE_NOT_FOUND', dt('Could not locate CiviCRM codebase. Make sure CiviCRM settings file has correct information.'));
  }

  // Autoload was added in 4.2
  require_once 'CRM/Utils/System.php';
  $codeVer = CRM_Utils_System::version();

  if (substr($codeVer, 0, 3) >= '4.2') {
    require_once $civicrm_root . '/CRM/Core/ClassLoader.php';
    CRM_Core_ClassLoader::singleton()->register();
  }

  // also initialize config object
  require_once 'CRM/Core/Config.php';
  $config = CRM_Core_Config::singleton();

  $init = TRUE;
  return $init;
}

function _civicrm_get_crmpath() {
  if (!$crmpath = drush_get_option('destination', FALSE)) {
    $crmpath = drush_get_context('DRUSH_DRUPAL_SITE_ROOT', FALSE) . '/modules/';
    if (!is_dir($crmpath)) {
      $crmpath = "sites/all/modules";
    }
  }
  return $crmpath;
}

/**
 * (Drush callback)
 *
 * Implementation of command 'civicrm-api'
 */
function drush_civicrm_api() {
  $DEFAULTS = array('version' => 3);

  $args = func_get_args();
  list($entity, $action) = explode('.', $args[0]);
  array_shift($args);

  // Parse $params
  switch (drush_get_option('in', 'args')) {
    case 'args':
      $params = $DEFAULTS;
      foreach ($args as $arg) {
        preg_match('/^([^=]+)=(.*)$/', $arg, $matches);
        $params[$matches[1]] = $matches[2];
      }
      break;

    case 'json':
      $json = stream_get_contents(STDIN);
      if (empty($json)) {
        $params = $DEFAULTS;
      }
      else {
        $params = array_merge($DEFAULTS, json_decode($json, TRUE));
      }
      break;

    default:
      drush_set_error(dt('Unknown format: @format', array('@format' => $format)));
      break;
  }

  civicrm_initialize();

  global $user;
  CRM_Core_BAO_UFMatch::synchronize($user, FALSE, 'Drupal',
    civicrm_get_ctype('Individual')
  );

  $result = civicrm_api($entity, $action, $params);

  switch (drush_get_option('out', 'pretty')) {
    case 'pretty':
      drush_print_r($result);
      break;

    case 'json':
      drush_print(json_encode($result));
      break;

    default:
      return drush_set_error('CIVICRM_UNKNOWN_FORMAT', dt('Unknown format: @format', array('@format' => $format)));
  }
}
