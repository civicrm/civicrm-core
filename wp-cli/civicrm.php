<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
if (!defined('CIVICRM_WPCLI_LOADED')) {
    define('CIVICRM_WPCLI_LOADED', 1);

    /**
     * WP-CLI port of drush-civicrm integration
     * andyw@circle, 08/03/2014
     *
     * Distributed under the GNU Affero General Public License, version 3
     * http://www.gnu.org/licenses/agpl-3.0.html
     */

    class CiviCRM_Command extends WP_CLI_Command {

        private $args, $assoc_args;

        /**
         * WP-CLI integration with CiviCRM.
         *
         * wp civicrm api
         * ===============
         * Command for accessing CiviCRM APIs. Syntax is identical to drush cvap.
         *
         * wp civicrm cache-clear
         * ===============
         * Command for accessing clearing cache.  Equivilant of running civicrm/admin/setting/updateConfigBackend&reset=1
         *
         * wp civicrm enable-debug
         * ===============
         * Command for to turn debug on.
         *
         * wp civicrm member-records
         * ===============
         * Run the CiviMember UpdateMembershipRecord cron (civicrm member-records).
         *
         * wp civicrm process-mail-queue
         * ===============
         * Process pending CiviMail mailing jobs.
         * Example:
         * wp civicrm process-mail-queue -u admin
         *
         * wp civicrm rest
         * ===============
         * Rest interface for accessing CiviCRM APIs. It can return xml or json formatted data.
         *
         * wp civicrm restore
         * ==================
         * Restore CiviCRM codebase and database back from the specified backup directory
         *
         * wp civicrm sql-conf
         * ===================
         * Show civicrm database connection details.
         *
         * wp civicrm sql-connect
         * ======================
         * A string which connects to the civicrm database.
         *
         * wp civicrm sql-cli
         * ==================
         * Quickly enter the mysql command line.
         *
         * wp civicrm sql-dump
         * ===================
         * Prints the whole CiviCRM database to STDOUT or save to a file.
         *
         * wp civicrm sql-query
         * ====================
         * Usage: wp civicrm sql-query <query> <options>...
         * <query> is a SQL statement, which can alternatively be passed via STDIN. Any additional arguments are passed to the mysql command directly.";
         *
         * wp civicrm update-cfg
         * =====================
         * Update config_backend to correct config settings, especially when the CiviCRM site has been cloned / migrated.
         *
         * wp civicrm upgrade
         * ==================
         * Take backups, replace CiviCRM codebase with new specified tarfile and upgrade database by executing the CiviCRM upgrade process - civicrm/upgrade?reset=1. Use civicrm-restore to revert to previous state in case anything goes wrong.
         *
         * wp civicrm upgrade-db
         * =====================
         * Run civicrm/upgrade?reset=1 just as a web browser would.
         *
         * wp civicrm install
         * ===============
         * Command for to install CiviCRM.  The install command requires that you have downloaded a tarball or zip file first.
         * Options:
         * --dbhost            MySQL host for your WordPress/CiviCRM database. Defaults to localhost.
         * --dbname            MySQL database name of your WordPress/CiviCRM database.
         * --dbpass            MySQL password for your WordPress/CiviCRM database.
         * --dbuser            MySQL username for your WordPress/CiviCRM database.
         * --lang              Default language to use for installation.
         * --langtarfile       Path to your l10n tar.gz file.
         * --site_url          Base Url for your WordPress/CiviCRM website without http (e.g. mysite.com)
         * --ssl               Using ssl for your WordPress/CiviCRM website if set to on (e.g. --ssl=on)
         * --tarfile           Path to your CiviCRM tar.gz file.
         *
         */

        public function __invoke($args, $assoc_args) {

            $this->args       = $args;
            $this->assoc_args = $assoc_args;

            # define command router
            $command_router = array(
                'api'                => 'api',
                'cache-clear'        => 'cacheClear',
                'enable-debug'       => 'enableDebug',
                'install'            => 'install',
                'member-records'     => 'memberRecords',
                'process-mail-queue' => 'processMailQueue',
                'rest'               => 'rest',
                'restore'            => 'restore',
                'sql-cli'            => 'sqlCLI',
                'sql-conf'           => 'sqlConf',
                'sql-connect'        => 'sqlConnect',
                'sql-dump'           => 'sqlDump',
                'sql-query'          => 'sqlQuery',
                'update-cfg'         => 'updateConfig',
                'upgrade'            => 'upgrade',
                'upgrade-db'         => 'upgradeDB'
            );

            # get command
            $command = array_shift($args);

            # check for existence of Civi (except for command 'install')
            if (!function_exists('civicrm_initialize') and $command != 'install')
                return WP_CLI::error("Unable to find CiviCRM install.");

            # check existence of router entry / handler method
            if (!isset($command_router[$command]) or !method_exists($this, $command_router[$command]))
                return WP_CLI::error("Unrecognized command - '$command'");

            # run command
            return $this->{$command_router[$command]}();

        }

        /**
         * Implementation of command 'api'
         */
        private function api() {

            $defaults = array('version' => 3);

            array_shift($this->args);
            list($entity, $action) = explode('.', $this->args[0]);

            # parse $params

            switch ($this->getOption('in', 'args')) {

                # input params supplied via args ..
                case 'args':
                    $params = $defaults;
                    foreach ($this->args as $arg) {
                        preg_match('/^([^=]+)=(.*)$/', $arg, $matches);
                        $params[$matches[1]] = $matches[2];
                    }
                    break;

                # input params supplied via json ..
                case 'json':
                    $json   = stream_get_contents(STDIN);
                    $params = (empty($json) ? $defaults : array_merge($defaults, json_decode($json, true)));
                    break;

                default:
                    WP_CLI::error('Unknown format: ' . $format);
                    break;
            }

            civicrm_initialize();
            $result = civicrm_api($entity, $action, $params);

            switch ($this->getOption('out', 'pretty')) {

                # pretty-print output (default)
                case 'pretty':
                    WP_CLI::line(print_r($result, true));
                    break;

                # display output as json
                case 'json':
                    WP_CLI::line(json_encode($result));
                    break;

                default:
                    return WP_CLI::error('Unknown format: ' . $format);

            }

        }

        /**
         * Implementation of command 'cache-clear'
         */
        private function cacheClear() {

            civicrm_initialize();
            require_once 'CRM/Core/Config.php';
            $config = CRM_Core_Config::singleton();

            # clear db caching
            $config->clearDBCache();

            # also cleanup the templates_c directory
            $config->cleanup(1, FALSE);

            # also cleanup the session object
            $session = CRM_Core_Session::singleton();
            $session->reset(1);

        }

        /**
         * Implementation of command 'enable-debug'
         */
        private function enableDebug() {

            civicrm_initialize();

            require_once 'CRM/Core/DAO/Domain.php';

            $domain = new CRM_Core_DAO_Domain();
            $domain->id = CRM_Core_Config::domainID();
            $domain->find(TRUE);

            if ($domain->config_backend) {

                $config = unserialize($domain->config_backend);
                $config['debug_enabled']     = 1;
                $config['debug']     = 1;
                $config['backtrace'] = 1;

                require_once 'CRM/Core/BAO/ConfigSetting.php';
                CRM_Core_BAO_ConfigSetting::add($config);

                WP_CLI::success('Debug setting enabled.');

            } else {
                WP_CLI::error('Error retrieving current config_backend.');
            }

        }

        /**
         * Implementation of command 'install'
         */
        private function install() {

            # validate

            if (!$dbuser = $this->getOption('dbuser', false))
                return WP_CLI::error('CiviCRM database username not specified.');

            if (!$dbpass = $this->getOption('dbpass', false))
                return WP_CLI::error('CiviCRM database password not specified.');

            if (!$dbhost = $this->getOption('dbhost', false))
                return WP_CLI::error('CiviCRM database host not specified.');

            if (!$dbname = $this->getOption('dbname', false))
                return WP_CLI::error('CiviCRM database name not specified.');

            if (!$this->getOption('tarfile', false) and !$this->getOption('zipfile', false))
                return WP_CLI::error('Must specify either --tarfile or --zipfile');

            if ($lang = $this->getOption('lang', false) and !$langtarfile = $this->getOption('langtarfile', FALSE))
                return WP_CLI::error('CiviCRM language tarfile not specified.');

            # begin install

            $wp_root = ABSPATH;

            if ($pluginPath = $this->getOption('destination', FALSE))
                $pluginPath = $wp_root . $pluginPath;
            else
                $pluginPath = $wp_root . 'wp-content/plugins';

            if (is_dir($pluginPath . '/civicrm'))
                return WP_CLI::error("Existing CiviCRM found. No action taken.");

            # extract the archive
            if ($this->getOption('tarfile', false)) {
                # should probably never get to here as Wordpress Civi comes in a zip file, but
                # just in case that ever changes ..
                if (!$this->untar($pluginPath))
                    return WP_CLI::error("Error extracting tarfile");

            } elseif ($this->getOption('zipfile', false)) {

                if (!$this->unzip($pluginPath))
                    return WP_CLI::error("Error extracting zipfile");

            } else {
                return WP_CLI::error("No zipfile specified, use --zipfile=path/to/zipfile");
            }

            # include civicrm installer helper file
            global $crmPath;

            $crmPath                = "$pluginPath/civicrm/civicrm";
            $civicrmInstallerHelper = "$crmPath/install/civicrm.php";

            if (!file_exists($civicrmInstallerHelper))
                return WP_CLI::error("Archive could not be unpacked OR CiviCRM installer helper file is missing.");

            WP_CLI::success("Archive unpacked.");
            require_once $civicrmInstallerHelper;

            if ($lang != '')
                if (!$this->untar($pluginPath, 'langtarfile'))
                    return WP_CLI::error("No language tarfile specified, use --langtarfile=path/to/tarfile");

            # create files dirs
            civicrm_setup("$pluginPath/files");
            WP_CLI::launch("chmod 0777 $pluginPath/files/civicrm -R");

            # now we've got some files in place, require PEAR DB and check db setup
            $dsn = "mysql://{$dbuser}:{$dbpass}@{$dbhost}/{$dbname}?new_link=true";
            $dsn_nodb = "mysql://{$dbuser}:{$dbpass}@{$dbhost}";

            require_once ABSPATH . '/wp-content/plugins/civicrm/civicrm/packages/DB.php';

            $db = DB::connect($dsn);
            if (DB::iserror($db)) {
                $db = DB::connect($dsn_nodb);
                if (DB::iserror($db))
                    return WP_CLI::error("Unable to connect to database. Please re-check credentials.");
                $db->query("CREATE DATABASE $dbname");
                if (DB::iserror($db))
                    return WP_CLI::error('CiviCRM database was not found. Failed to create one.');
                $db->disconnect();
            }

            # install db
            $sqlPath = "$crmPath/sql";

            # setup database with civicrm structure and data
            WP_CLI::line("Loading CiviCRM database structure ..");
            civicrm_source($dsn, $sqlPath . '/civicrm.mysql');
            WP_CLI::line("Loading CiviCRM database with required data ..");

            # testing the translated sql files availability
            $data_file = $sqlPath . '/civicrm_data.mysql';
            $acl_file  = $sqlPath . '/civicrm_acl.mysql';

            if ($lang != '') {

                if (file_exists($sqlPath . '/civicrm_data.' . $lang . '.mysql')
                    and file_exists($sqlPath . '/civicrm_acl.' . $lang . '.mysql')
                    and $lang != ''
                ) {
                    $data_file = $sqlPath . '/civicrm_data.' . $lang . '.mysql';
                    $acl_file = $sqlPath . '/civicrm_acl.' . $lang . '.mysql';
                } else {
                    WP_CLI::warning("No sql files could be retrieved for '$lang' using default language.");
                }

            }

            civicrm_source($dsn, $data_file);
            civicrm_source($dsn, $acl_file);

            WP_CLI::success("CiviCRM database loaded successfully.");

            # generate civicrm.settings.php file
            $settingsTplFile = "$crmPath/templates/CRM/common/civicrm.settings.php.tpl";
            if (!file_exists($settingsTplFile)) {
                $settingsTplFile = "$crmPath/templates/CRM/common/civicrm.settings.php.template";
                if (!file_exists($settingsTplFile))
                    return WP_CLI::error("Could not find CiviCRM settings template and therefore could not create settings file.");
            }

            WP_CLI::line("Generating civicrm settings file ..");

            if ($baseUrl = $this->getOption('site_url', false)) {
                $ssl      = $this->getOption('ssl', false);
                $protocol = ($ssl == 'on' ? 'https' : 'http');
            }

            $baseUrl = !$baseUrl ? get_bloginfo('url') : $protocol . '://' . $baseUrl;
            if (substr($baseUrl, -1) != '/')
                $baseUrl .= '/';

            $params = array(
                'crmRoot'            => $crmPath . '/',
                'templateCompileDir' => "$pluginPath/files/civicrm/templates_c",
                'frontEnd'           => 0,
                'cms'                => 'WordPress',
                'baseURL'            => $baseUrl,
                'dbUser'             => $dbuser,
                'dbPass'             => $dbpass,
                'dbHost'             => $dbhost,
                'dbName'             => $dbname,
                'CMSdbUser'          => DB_USER,
                'CMSdbPass'          => DB_PASSWORD,
                'CMSdbHost'          => DB_HOST,
                'CMSdbName'          => DB_NAME,
                'siteKey'            => md5(uniqid('', TRUE) . $baseUrl),
            );

            $str = file_get_contents($settingsTplFile);
            foreach ($params as $key => $value)
                $str = str_replace('%%' . $key . '%%', $value, $str);

            $str = trim($str);

            $configFile = "$pluginPath/civicrm/civicrm.settings.php";
            civicrm_write_file($configFile, $str);
            WP_CLI::launch("chmod 0644 $configFile");
            WP_CLI::success(sprintf("Settings file generated: %s", $configFile));

            # activate plugin and we're done
            @WP_CLI::run_command(array('plugin', 'activate', 'civicrm'), array());
            WP_CLI::success("CiviCRM installed.");

        }

        /**
         * Implementation of command 'member-records'
         */
        private function memberRecords() {

            civicrm_initialize();

            if (substr(CRM_Utils_System::version(), 0, 3) >= '4.3') {

                $job = new CRM_Core_JobManager();
                $job->executeJobByAction('job', 'process_membership');
                WP_CLI::success("Executed 'process_membership' job.");

            } else {

                $_REQUEST['name'] = $this->getOption('civicrm_cron_username', NULL);
                $_REQUEST['pass'] = $this->getOption('civicrm_cron_password', NULL);
                $_REQUEST['key']  = $this->getOption('civicrm_sitekey', NULL);

                global $argv;
                $argv = array(
                    0 => "drush",
                    1 => "-u" . $_REQUEST['name'],
                    2 => "-p" . $_REQUEST['pass'],
                    3 => "-s" . $this->getOption('uri', FALSE),
                );

                # if (!defined('CIVICRM_CONFDIR')) {
                #     define('CIVICRM_CONFDIR', ABSPATH . '/wp-content/plugins/civicrm');
                # }

                include "bin/UpdateMembershipRecord.php";

            }

        }

        /**
         * Implementation of command 'process-mail-queue'
         */
        private function processMailQueue() {

            civicrm_initialize();

            if (substr(CRM_Utils_System::version(), 0, 3) >= '4.3') {

                $job = new CRM_Core_JobManager();
                $job->executeJobByAction('job', 'process_mailing');
                WP_CLI::success("Executed 'process_mailing' job.");

            } else {

                $result = civicrm_api('Mailing', 'Process', array('version' => 3));
                if ($result['is_error'])
                    WP_CLI::error($result['error_message']);

            }

        }

        /**
         * Implementation of command 'rest'
         */
        private function rest() {

            civicrm_initialize();

            if (!$query = $this->getOption('query', false))
                return WP_CLI::error('query not specified.');

            $query     = explode('&', $query);
            $_GET['q'] = array_shift($query);

            foreach ($query as $keyVal) {
                list($key, $val) = explode('=', $keyVal);
                $_REQUEST[$key]  = $val;
                $_GET[$key]      = $val;
            }

            require_once 'CRM/Utils/REST.php';
            $rest = new CRM_Utils_REST();

            require_once 'CRM/Core/Config.php';
            $config = CRM_Core_Config::singleton();

            global $civicrm_root;
            // adding dummy script, since based on this api file path is computed.
            $_SERVER['SCRIPT_FILENAME'] = "$civicrm_root/extern/rest.php";

            if (isset($_GET['json']) && $_GET['json']) {
                header('Content-Type: text/javascript');
            } else {
                header('Content-Type: text/xml');
            }

            echo $rest->run($config);

        }

        /**
         * Implementation of command 'restore'
         */
        private function restore() {

            # validate ..
            $restore_dir = $this->getOption('restore-dir', false);
            $restore_dir = rtrim($restore_dir, '/');
            if (!$restore_dir)
                return WP_CLI::error('Restore-dir not specified.');

            $sql_file = $restore_dir . '/civicrm.sql';
            if (!file_exists($sql_file))
                return WP_CLI::error('Could not locate civicrm.sql file in the restore directory.');

            $code_dir = $restore_dir . '/civicrm';
            if (!is_dir($code_dir))
                return WP_CLI::error('Could not locate civicrm directory inside restore-dir.');
            elseif (!file_exists("$code_dir/civicrm/civicrm-version.txt") and !file_exists("$code_dir/civicrm/civicrm-version.php"))
                return WP_CLI::error('civicrm directory inside restore-dir, doesn\'t look to be a valid civicrm codebase.');


            # prepare to restore ..
            $date = date('YmdHis');

            civicrm_initialize();
            global $civicrm_root;

            $civicrm_root_base = explode('/', $civicrm_root);
            array_pop($civicrm_root_base);
            $civicrm_root_base = implode('/', $civicrm_root_base) . '/';

            $basepath = explode('/', $civicrm_root);

            if (!end($basepath))
                array_pop($basepath);

            array_pop($basepath);
            $project_path = implode('/', $basepath) . '/';

            $wp_root = ABSPATH;
            $restore_backup_dir = $this->getOption('backup-dir', $wp_root . '/../backup');
            $restore_backup_dir = rtrim($restore_backup_dir, '/');

            # get confirmation from user -

            if (!defined('CIVICRM_DSN'))
                WP_CLI::error('CIVICRM_DSN is not defined.');

            $db_spec = DB::parseDSN(CIVICRM_DSN);
            WP_CLI::line('');
            WP_CLI::line("Process involves:");
            WP_CLI::line(sprintf("1. Restoring '\$restore-dir/civicrm' directory to '%s'.", $civicrm_root_base));
            WP_CLI::line(sprintf("2. Dropping and creating '%s' database.", $db_spec['database']));
            WP_CLI::line("3. Loading '\$restore-dir/civicrm.sql' file into the database.");
            WP_CLI::line('');
            WP_CLI::line(sprintf("Note: Before restoring a backup will be taken in '%s' directory.", "$restore_backup_dir/plugins/restore"));
            WP_CLI::line('');

            WP_CLI::confirm('Do you really want to continue?');

            $restore_backup_dir .= '/plugins/restore/' . $date;

            if (!mkdir($restore_backup_dir, 777, true))
                return WP_CLI::error('Failed creating directory: ' . $restore_backup_dir);

            # 1. backup and restore codebase
            WP_CLI::line('Restoring civicrm codebase ..');
            if (is_dir($project_path) && !rename($project_path, $restore_backup_dir . '/civicrm'))
                return WP_CLI::error(sprintf("Failed to take backup for '%s' directory", $project_path));

            if (!rename($code_dir, $project_path))
                return WP_CLI::error("Failed to restore civicrm directory '%s' to '%s'", $code_dir, $project_path);

            WP_CLI::success('Codebase restored.');

            # 2. backup, drop and create database
            WP_CLI::run_command(
                array('civicrm', 'sql-dump'),
                array('result-file' => $restore_backup_dir . '/civicrm.sql')
            );

            WP_CLI::success('Database backed up.');

            # prepare a mysql command-line string for issuing
            # db drop / create commands
            $command = sprintf(
                "mysql --user=%s --password=%s",
                $db_spec['username'],
                $db_spec['password']
            );

            if (isset($db_spec['hostspec']))
                $command .= ' --host=' . $db_spec['hostspec'];

            if (isset($dsn['port']) and !empty($dsn['port']))
                $command .= ' --port=' . $db_spec['port'];

            # attempt to drop old database
            if (system($command . sprintf(' --execute="DROP DATABASE IF EXISTS %s"', $db_spec['database'])))
                return WP_CLI::error('Could not drop database: ' . $db_spec['database']);

            WP_CLI::success('Database dropped.');

            # attempt to create new database
            if (system($command . sprintf(' --execute="CREATE DATABASE %s"', $db_spec['database'])))
                WP_CLI::error('Could not create new database: ' . $db_spec['database']);

            WP_CLI::success('Database created.');

            # 3. restore database
            WP_CLI::line('Loading civicrm.sql file from restore-dir ..');
            system($command . ' ' . $db_spec['database'] . ' < ' . $sql_file);

            WP_CLI::success('Database restored.');

            WP_CLI::line('Clearing caches..');
            WP_CLI::run_command(array('civicrm', 'cache-clear'));

            WP_CLI::success('Restore process completed.');

        }

        /**
         * Implementation of command 'sql-conf'
         */
        private function sqlConf() {

            civicrm_initialize();
            if (!defined('CIVICRM_DSN'))
                WP_CLI::error('CIVICRM_DSN is not defined.');

            WP_CLI::line(print_r(DB::parseDSN(CIVICRM_DSN), true));

        }

        /**
         * Implementation of command 'sql-connect'
         */
        private function sqlConnect() {

            civicrm_initialize();
            if (!defined('CIVICRM_DSN'))
                return WP_CLI::error('CIVICRM_DSN is not defined.');

            $dsn = DB::parseDSN(CIVICRM_DSN);

            $command = sprintf(
                "mysql --database=%s --host=%s --user=%s --password=%s",
                $dsn['database'],
                $dsn['hostspec'],
                $dsn['username'],
                $dsn['password']
            );

            if (isset($dsn['port']) and !empty($dsn['port']))
                $command .= ' --port=' . $dsn['port'];

            return WP_CLI::line($command);

        }

        /**
         * Implementation of command 'sql-dump'
         */

        private function sqlDump() {

            # bootstrap Civi when we're not being called as part of an upgrade
            if (!defined('CIVICRM_UPGRADE_ACTIVE'))
                civicrm_initialize();

            if (!defined('CIVICRM_DSN') and !defined('CIVICRM_OLD_DSN'))
                WP_CLI::error('DSN is not defined.');

            $dsn = self::parseDSN(defined('CIVICRM_DSN') ? CIVICRM_DSN : CIVICRM_OLD_DSN);

            $assoc_args       = $this->assoc_args;
            $stdout           = !isset($assoc_args['result-file']);
            $command          = "mysqldump --no-defaults --host={$dsn['hostspec']} --user={$dsn['username']} --password='{$dsn['password']}' %s";
            $command_esc_args = array($dsn['database']);

            if (isset($assoc_args['tables'])) {
                $tables = explode(',', $assoc_args['tables'] );
                unset($assoc_args['tables']);
                $command .= ' --tables';
                foreach ($tables as $table) {
                    $command .= ' %s';
                    $command_esc_args[] = trim($table);
                }
            }

            $escaped_command = call_user_func_array(
                '\WP_CLI\Utils\esc_cmd',
                array_merge(
                    array($command),
                    $command_esc_args
                )
            );

            \WP_CLI\Utils\run_mysql_command($escaped_command, $assoc_args);

            if (!$stdout)
                WP_CLI::success(sprintf('Exported to %s', $assoc_args['result-file']));

        }

        /**
         * Implementation of command 'sql-query'
         */
        private function sqlQuery() {

            if (!isset($this->args[0])) {
                WP_CLI::error("No query specified.");
                return;
            }

            $query = $this->args[0];

            civicrm_initialize();
            if (!defined('CIVICRM_DSN'))
                WP_CLI::error('CIVICRM_DSN is not defined.');

            $dsn = DB::parseDSN(CIVICRM_DSN);

            $mysql_args = array(
                'host'     => $dsn['hostspec'],
                'database' => $dsn['database'],
                'user'     => $dsn['username'],
                'password' => $dsn['password'],
                'execute'  => $query
            );

            \WP_CLI\Utils\run_mysql_command('mysql --no-defaults', $mysql_args);

        }

        /**
         * Implementation of command 'sql-cli'
         */
        private function sqlCLI() {

            civicrm_initialize();
            if (!defined('CIVICRM_DSN'))
                WP_CLI::error('CIVICRM_DSN is not defined.');

            $dsn = DB::parseDSN(CIVICRM_DSN);

            $mysql_args = array(
                'host'     => $dsn['hostspec'],
                'database' => $dsn['database'],
                'user'     => $dsn['username'],
                'password' => $dsn['password']
            );

            \WP_CLI\Utils\run_mysql_command('mysql --no-defaults', $mysql_args);

        }

        /**
         * Implementation of command 'update-cfg'
         */
        private function updateConfig() {

            civicrm_initialize();

            $defaultValues = array();
            $states        = array('old', 'new');

            for ($i = 1; $i <= 3; $i++) {
                foreach ($states as $state) {
                    $name = "{$state}Val_{$i}";
                    $value = $this->getOption($name, NULL);
                    if ($value)
                        $defaultValues[$name] = $value;

                }
            }

            $webserver_user  = $this->getWebServerUser();
            $webserver_group = $this->getWebServerGroup();

            require_once 'CRM/Core/I18n.php';
            require_once 'CRM/Core/BAO/ConfigSetting.php';
            $result = CRM_Core_BAO_ConfigSetting::doSiteMove($defaultValues);

            if ($result) {

                # attempt to preserve webserver ownership of templates_c, civicrm/upload
                if ($webserver_user and $webserver_group) {
                    $civicrm_files_dir = ABSPATH . '/wp-content/plugins/files/civicrm';
                    system(sprintf('chown -R %s:%s %s/templates_c', $webserver_user, $webserver_group, $civicrm_files_dir));
                    system(sprintf('chown -R %s:%s %s/upload', $webserver_user, $webserver_group, $civicrm_files_dir));
                }

                WP_CLI::success('Config successfully updated.');

            } else {
                WP_CLI::error('Config update failed.');
            }

        }

        /**
         * Implementation of command 'upgrade'
         */
        private function upgrade() {

            # todo: use wp-cli to download tarfile.
            # todo: if tarfile is not specified, see if the code already exists and use that instead.
            if (!$this->getOption('tarfile', false) and !$this->getOption('zipfile', false))
                return WP_CLI::error('Must specify either --tarfile or --zipfile');

            # fixme: throw error if tarfile is not in a valid format.
            if (!defined('CIVICRM_UPGRADE_ACTIVE'))
                define('CIVICRM_UPGRADE_ACTIVE', 1);

            $wp_root       = ABSPATH;
            $settings_path = ABSPATH . '/wp-content/plugins/civicrm/civicrm.settings.php';
            if (!file_exists($settings_path))
                return WP_CLI::error('Unable to locate settings file at ' . $settings_path);

            # nb: we don't want to require civicrm.settings.php here, because ..
            #
            # a) this is the old environment we're going to replace
            # b) upgrade-db needs to bootstrap the new environment, so requiring the file
            #    now will create multiple inclusion problems later on
            #
            # however, all we're really after is $civicrm_root and CIVICRM_DSN, so we're going to
            # pull out the lines we need using a regex and run them - yes, it's pretty silly ..
            # don't try this at home, kids.

            $settings = file_get_contents($settings_path);
            $settings = str_replace("\r", '', $settings);
            $settings = explode("\n", $settings);

            if ($civicrm_root_code = reset(preg_grep('/^\s*\$civicrm_root\s*=.*$/', $settings))) {
                eval($civicrm_root_code);
            } else {
                return WP_CLI::error('Unable to read $civicrm_root from civicrm.settings.php');
            }

            if ($civicrm_dsn_code = reset(preg_grep('/^\s*define.*CIVICRM_DSN.*$/', $settings))) {
                $civicrm_dsn_code = str_replace('CIVICRM_DSN', 'CIVICRM_OLD_DSN', $civicrm_dsn_code);
                eval($civicrm_dsn_code);
            } else {
                return WP_CLI::error('Unable to read CIVICRM_DSN from civicrm.settings.php');
            }

            if (!defined('CIVICRM_OLD_DSN'))
                return WP_CLI::error('Unable to set CIVICRM_OLD_DSN');

            $date        = date('YmdHis');
            $backup_file = "civicrm";

            $basepath = explode('/', $civicrm_root);

            if (!end($basepath))
                array_pop($basepath);

            array_pop($basepath);
            $project_path = implode('/', $basepath) . '/';
            array_pop($basepath);
            $plugin_path = implode('/', $basepath) . '/';


            $backup_dir = $this->getOption('backup-dir', $wp_root . '../backup');
            $backup_dir = rtrim($backup_dir, '/');

            WP_CLI::line("\nThe upgrade process involves - ");
            WP_CLI::line(sprintf("1. Backing up current CiviCRM code as => %s", "$backup_dir/plugins/$date/$backup_file"));
            WP_CLI::line(sprintf("2. Backing up database as => %s", "$backup_dir/plugins/$date/$backup_file.sql"));
            WP_CLI::line(sprintf("3. Unpacking tarfile to => %s", $plugin_path));
            WP_CLI::line("4. Executing civicrm/upgrade?reset=1 just as a browser would.\n");

            WP_CLI::confirm('Do you really want to continue?');

            # begin upgrade

            $backup_dir .= '/plugins/' . $date;
            if (!mkdir($backup_dir, 777, true))
                return WP_CLI::error('Failed creating directory: ' . $backup_dir);

            $backup_target = $backup_dir . '/' . $backup_file;

            if (!rename($project_path, $backup_target))
                return WP_CLI::error(sprintf(
                    "Failed to backup CiviCRM project directory %s to %s",
                    $project_path,
                    $backup_target
                ));

            WP_CLI::line();
            WP_CLI::success("1. Code backed up.");

            WP_CLI::run_command(
                array('civicrm', 'sql-dump'),
                array('result-file' => $backup_target . '.sql')
            );

            WP_CLI::success('2. Database backed up.');

            # decompress
            if ($this->getOption('tarfile', false)) {
                # should probably never get to here, as looks like Wordpress Civi comes
                # in a zip file
                if (!$this->untar($plugin_path))
                    return WP_CLI::error("Error extracting tarfile");

            } elseif ($this->getOption('zipfile', false)) {

                if (!$this->unzip($plugin_path))
                    return WP_CLI::error("Error extracting zipfile");

            } else {
                return WP_CLI::error("No zipfile specified, use --zipfile=path/to/zipfile");
            }

            WP_CLI::success('3. Archive unpacked.');

            WP_CLI::line('Copying civicrm.settings.php to ' . $project_path . '..');
            define('CIVICRM_SETTINGS_PATH', $project_path . 'civicrm.settings.php');

            if (!copy($backup_dir . '/civicrm/civicrm.settings.php', CIVICRM_SETTINGS_PATH))
                return WP_CLI::error('Failed to copy file');

            WP_CLI::success("4. ");

            WP_CLI::run_command(array('civicrm', 'upgrade-db'), array());

            WP_CLI::success("Process completed.");

        }

        /**
         * Implementation of command 'upgrade-db'
         */
        private function upgradeDB() {

            civicrm_initialize();

            if (class_exists('CRM_Upgrade_Headless')) {
                # Note: CRM_Upgrade_Headless introduced in 4.2 -- at the same time as class auto-loading
                try {
                    $upgradeHeadless = new CRM_Upgrade_Headless();
                    $result = $upgradeHeadless->run();
                    WP_CLI::line("Upgrade outputs: " . "\"" . $result['message'] . "\"");
                } catch (Exception $e) {
                    WP_CLI::error($e->getMessage());
                }

            } else {

                require_once 'CRM/Core/Smarty.php';
                $template = CRM_Core_Smarty::singleton();

                require_once ('CRM/Upgrade/Page/Upgrade.php');
                $upgrade = new CRM_Upgrade_Page_Upgrade();

                // new since CiviCRM 4.1
                if (is_callable(array(
                    $upgrade, 'setPrint'))) {
                    $upgrade->setPrint(TRUE);
                }

                # to suppress html output /w source code.
                ob_start();
                $upgrade->run();
                # capture the required message.
                $result = $template->get_template_vars('message');
                ob_end_clean();
                WP_CLI::line("Upgrade outputs: " . "\"$result\"");

            }

        }

        /**
         * DSN parser - this has been stolen from PEAR DB since we don't always have a
         * bootstrapped environment we can access this from, eg: when doing an upgrade
         * @param  $dsn (string)
         * @return array containing db connection details
         */
        private static function parseDSN($dsn) {

            $parsed = array(
                'phptype'  => false,
                'dbsyntax' => false,
                'username' => false,
                'password' => false,
                'protocol' => false,
                'hostspec' => false,
                'port'     => false,
                'socket'   => false,
                'database' => false,
            );

            if (is_array($dsn)) {
                $dsn = array_merge($parsed, $dsn);
                if (!$dsn['dbsyntax']) {
                    $dsn['dbsyntax'] = $dsn['phptype'];
                }
                return $dsn;
            }

            // Find phptype and dbsyntax
            if (($pos = strpos($dsn, '://')) !== false) {
                $str = substr($dsn, 0, $pos);
                $dsn = substr($dsn, $pos + 3);
            } else {
                $str = $dsn;
                $dsn = null;
            }

            // Get phptype and dbsyntax
            // $str => phptype(dbsyntax)
            if (preg_match('|^(.+?)\((.*?)\)$|', $str, $arr)) {
                $parsed['phptype']  = $arr[1];
                $parsed['dbsyntax'] = !$arr[2] ? $arr[1] : $arr[2];
            } else {
                $parsed['phptype']  = $str;
                $parsed['dbsyntax'] = $str;
            }

            if (!count($dsn)) {
                return $parsed;
            }

            // Get (if found): username and password
            // $dsn => username:password@protocol+hostspec/database
            if (($at = strrpos($dsn,'@')) !== false) {
                $str = substr($dsn, 0, $at);
                $dsn = substr($dsn, $at + 1);
                if (($pos = strpos($str, ':')) !== false) {
                    $parsed['username'] = rawurldecode(substr($str, 0, $pos));
                    $parsed['password'] = rawurldecode(substr($str, $pos + 1));
                } else {
                    $parsed['username'] = rawurldecode($str);
                }
            }

            // Find protocol and hostspec

            if (preg_match('|^([^(]+)\((.*?)\)/?(.*?)$|', $dsn, $match)) {
                // $dsn => proto(proto_opts)/database
                $proto       = $match[1];
                $proto_opts  = $match[2] ? $match[2] : false;
                $dsn         = $match[3];

            } else {
                // $dsn => protocol+hostspec/database (old format)
                if (strpos($dsn, '+') !== false) {
                    list($proto, $dsn) = explode('+', $dsn, 2);
                }
                if (strpos($dsn, '/') !== false) {
                    list($proto_opts, $dsn) = explode('/', $dsn, 2);
                } else {
                    $proto_opts = $dsn;
                    $dsn = null;
                }
            }

            // process the different protocol options
            $parsed['protocol'] = (!empty($proto)) ? $proto : 'tcp';
            $proto_opts = rawurldecode($proto_opts);
            if (strpos($proto_opts, ':') !== false) {
                list($proto_opts, $parsed['port']) = explode(':', $proto_opts);
            }
            if ($parsed['protocol'] == 'tcp') {
                $parsed['hostspec'] = $proto_opts;
            } elseif ($parsed['protocol'] == 'unix') {
                $parsed['socket'] = $proto_opts;
            }

            // Get dabase if any
            // $dsn => database
            if ($dsn) {
                if (($pos = strpos($dsn, '?')) === false) {
                    // /database
                    $parsed['database'] = rawurldecode($dsn);
                } else {
                    // /database?param1=value1&param2=value2
                    $parsed['database'] = rawurldecode(substr($dsn, 0, $pos));
                    $dsn = substr($dsn, $pos + 1);
                    if (strpos($dsn, '&') !== false) {
                        $opts = explode('&', $dsn);
                    } else { // database?param1=value1
                        $opts = array($dsn);
                    }
                    foreach ($opts as $opt) {
                        list($key, $value) = explode('=', $opt);
                        if (!isset($parsed[$key])) {
                            // don't allow params overwrite
                            $parsed[$key] = rawurldecode($value);
                        }
                    }
                }
            }

            return $parsed;
        }

        /**
         * Helper function to replicate functionality of drush_get_option
         * @param  $name (string)
         * @return mixed - value if found or $default
         */
        private function getOption($name, $default) {
            return isset($this->assoc_args[$name]) ? $this->assoc_args[$name] : $default;
        }

        /**
         * Get the user the web server runs as, used to preserve file permissions on templates_c, civicrm/upload
         * etc when running as root. This is not a very good check, but is good enough for what we want to do,
         * which is preserve file permissions
         * @return string - the user which owns templates_c / empty string if not found
         */
        private function getWebServerUser() {

            $tplPath = ABSPATH . '/wp-content/plugins/files/civicrm/templates_c';
            if (is_dir($tplPath)) {
                $owner = posix_getpwuid(fileowner($tplPath));
                if (isset($owner['name']))
                    return $owner['name'];
            }
            return '';

        }

        /**
         * Get the group the webserver runs as - as above, but for group
         */
        private function getWebServerGroup() {

            $tplPath = ABSPATH . '/wp-content/plugins/files/civicrm/templates_c';
            if (is_dir($tplPath)) {
                $group = posix_getgrgid(filegroup($tplPath));
                if (isset($group['name']))
                    return $group['name'];
            }
            return '';

        }

        /**
         * Extract a tar.gz archive
         * @param  $destinationPath - the path to extract to
         * @param  $option          - command line option to get input filename from, defaults to 'tarfile'
         * @return bool
         */
        private function untar($destinationPath, $option='tarfile') {

            if ($tarfile = $this->getOption($option, false)) {
                WP_CLI::launch("gzip -d " . $tarfile);
                $tarfile = substr($tarfile, 0, strlen($tarfile) - 3);
                $this->exec("tar -xf $tarfile -C \"$destinationPath\"");
                return true;
            } else {
                return false;
            }

        }

        /**
         * Extract a zip archive
         * @param  $destinationPath - the path to extract to
         * @param  $option          - command line option to get zip filename from, defaults to 'zipfile'
         * @return bool
         */
        private function unzip($destinationPath, $option='zipfile') {

            if ($zipfile = $this->getOption($option, false)) {
                WP_CLI::line('Extracting zip archive ...');
                WP_CLI::launch("unzip -q " . $zipfile . " -d " . $destinationPath);
                return true;
            } else {
                return false;
            }

        }

    }

    WP_CLI::add_command('civicrm', 'CiviCRM_Command');
    WP_CLI::add_command('cv', 'CiviCRM_Command');

}
