<?php

class CRM_DB_MySQLRAMServer {
  public $paths;
  public $mysqld_base_command;

  function __construct($civicrm_db_settings, $options = array()) {
    $this->civicrm_db_settings = $civicrm_db_settings;
    $this->base_path = CRM_Utils_Array::value($options, 'base_path', getcwd());
    $this->port = CRM_Utils_Array::value($options, 'port', 3307);
    $this->paths = self::paths($this->base_path);
    $this->mysqld_base_command = "mysqld --no-defaults --tmpdir={$this->paths['tmp_dir']} --datadir={$this->paths['mysql_data_dir']} --port={$this->port} --socket={$this->paths['mysql_socket']} --pid-file={$this->paths['pid_file']}";
  }

  function launch_mysqld() {
    if (!file_exists($this->paths['pid_file'])) {
      $this->run_command("{$this->mysqld_base_command} > {$this->paths['tmp_dir']}/mysql-drupal-test.log 2>&1 &");
    }
  }

  function clean() {
    $this->kill();
    if ($this->ram_disk_is_mounted()) {
      $this->unmount_ram_disk();
    }
  }

  static function paths($base_path) {
    $paths = array();
    $paths['base_dir'] = $base_path;
    $paths['tmpfs_dir'] = "{$paths['base_dir']}/tmpfs";
    $paths['pid_file'] = "{$paths['tmpfs_dir']}/mysqld.pid";
    $paths['setup_done'] = "{$paths['tmpfs_dir']}/setup_done";
    $paths['mysql_data_dir'] = "{$paths['tmpfs_dir']}/mysql";
    $paths['mysql_socket'] = "{$paths['tmpfs_dir']}/mysqld.sock";
    $paths['tmp_dir'] = "/tmp";
    return $paths;
  }

  function kill() {
    if (file_exists($this->paths['pid_file'])) {
      $pid_file = CRM_Utils_File::open($this->paths['pid_file'], 'r');
      $pid = CRM_Utils_File::fgets($pid_file);
      $result = @posix_kill($pid, SIGTERM);
      if ($result === FALSE) {
        $error_info = error_get_last();
        throw new Exception("Error killing mysqld at pid $pid: " . print_r($error_info, TRUE));
      }
      $i = 0;
      while (file_exists($this->paths['pid_file']) && $i < 10) {
        $i++;
        sleep(1);
      }
      if ($i == 10) {
        throw new Exception("Tried to kill mysqld at pid $pid, but the pid file hasn't gone away after 10 seconds. Please clear out pid file if daemon is dead.");
      }
    }
  }

  function ram_disk_is_mounted() {
    $result = $this->run_command("stat -f -c '%T' {$this->paths['tmpfs_dir']}");
    if (trim($result[0]) != 'tmpfs') {
      return FALSE;
    }
    return TRUE;
  }

  function run() {
    $setup_done = FALSE;
    if (is_dir($this->paths['tmpfs_dir'])) {
      if (file_exists($this->paths['setup_done'])) {
        $setup_done = TRUE;
      }
    }
    if ($setup_done) {
      $this->launch_mysqld();
    }
    else {
      $this->setup();
    }
    print("********************************************************************************\n");
    print(" There is now a MySQL server running on a RAM disc on port {$this->port}\n");
    print("*******************************************************************************\n");
  }

  function run_command($command, $options = array()) {
    $options['print_command'] = true;
    return CRM_Utils_Shell::run($command, $options);
  }

  function setup() {
    $this->run_command("rm -rf {$this->paths['tmpfs_dir']}/*");
    if (!is_dir($this->paths['tmpfs_dir'])) {
      mkdir($this->paths['tmpfs_dir'], 0755, TRUE);
    }
    if (!$this->ram_disk_is_mounted()) {
      $this->run_command("sudo mount -t tmpfs -o size=500m tmpfs {$this->paths['tmpfs_dir']}");
      $uid = getmyuid();
      $gid = getmygid();
      $this->run_command("sudo chown $uid:$gid {$this->paths['tmpfs_dir']}");
      $this->run_command("chmod 0755 {$this->paths['tmpfs_dir']}");
    }
    if (!is_dir($this->paths['tmp_dir'])) {
      mkdir($this->paths['tmp_dir'], 0755, TRUE);
    }
    $temp_file_path = CRM_Utils_Path::join($this->paths['tmp_dir'], "apparmor-usr.sbin.mysqld");
    $lines = array (
      "{$this->paths['tmpfs_dir']}/ r,\n",
      "{$this->paths['tmpfs_dir']}/** rwk,\n",
    );
    if (file_exists("/etc/apparmor.d/local/usr.sbin.mysqld")) {
      if (CRM_Utils_File::appendLines("/etc/apparmor.d/local/usr.sbin.mysqld", $temp_file_path, $lines)) {
        $this->run_command("sudo cp /etc/apparmor.d/local/usr.sbin.mysqld /etc/apparmor.d/local/usr.sbin.mysqld.orig");
        $this->run_command("sudo mv $temp_file_path /etc/apparmor.d/local/usr.sbin.mysqld");
        $this->run_command("sudo /etc/init.d/apparmor restart", array('throw_exception_on_nonzero' => FALSE));
      }
    }
    if (!is_dir($this->paths['mysql_data_dir'])) {
      mkdir($this->paths['mysql_data_dir'], 0755, TRUE);
    }
    $mysql_system_dir_path = "{$this->paths['mysql_data_dir']}/mysql";
    if (!is_dir($mysql_system_dir_path)) {
      mkdir($mysql_system_dir_path, 0755, TRUE);
    }
    $this->run_command("echo \"use mysql;\" > {$this->paths['tmp_dir']}/install_mysql.sql");
    $this->run_command("cat /usr/share/mysql/mysql_system_tables.sql /usr/share/mysql/mysql_system_tables_data.sql >> {$this->paths['tmp_dir']}/install_mysql.sql");
    $this->run_command("{$this->mysqld_base_command} --log-warnings=0 --bootstrap --loose-skip-innodb --max_allowed_packet=8M --default-storage-engine=myisam --net_buffer_length=16K < {$this->paths['tmp_dir']}/install_mysql.sql");
    $this->run_command("{$this->mysqld_base_command} > {$this->paths['tmp_dir']}/mysql-drupal-test.log 2>&1 &");
    $setup_done_file = fopen($this->paths['setup_done'], "w");
    fwrite($setup_done_file, "Done!!\n");
    fclose($setup_done_file);
    $this->wait_for_mysql_server(); 
    $i = 0;
    if (!file_exists($this->paths['mysql_socket']) or $i > 9) {
      $i++;
      sleep(1);
    }
    if ($i == 9) {
      throw new Exception("There was a problem starting the MySQLRAM server. We expect to see a socket file at {$this->path['mysql_socket']} but it hasn't appeared after 10 waiting seconds.");
    }
  }

  function wait_for_mysql_server() {
    $result = CRM_Utils_Network::waitForServiceStartup('127.0.0.1', $this->port, 10);
    if ($result === FALSE) {
      throw new Exception("Error trying to connect to mysqld at 127.0.0.1:{$this->port}, gave up after 10 seconds.");
    }
  }

  function unmount_ram_disk() {
    if (is_dir($this->paths['tmpfs_dir'])) {
      $this->run_command("sudo umount {$this->paths['tmpfs_dir']}");
    }
  }
}
