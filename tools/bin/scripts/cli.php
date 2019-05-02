<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright Tech To The People http:tttp.eu (c) 2008                 |
 +--------------------------------------------------------------------+
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 * A PHP shell script

 On drupal if you have a symlink to your civi module, don't forget to create a new file - settings_location.php
 Enter the following code (substitute the actual location of your <drupal root>/sites directory)
 <?php
 define( 'CIVICRM_CONFDIR', '/var/www/drupal.6/sites' );
 ?>

 */
$include_path = "../packages/:" . get_include_path();
set_include_path($include_path);

/**
 * Class civicrm_CLI
 */
class civicrm_CLI {

  /**
   * constructor
   */
  function __construct() {
    //	$include_path = "packages/" . get_include_path( );
    //	set_include_path( $include_path );
    require_once 'Console/Getopt.php';
    $shortOptions = "s:u:p:k:";
    $longOptions = array('site=', 'user', 'pass');

    $getopt = new Console_Getopt();
    $args = $getopt->readPHPArgv();
    array_shift($args);
    list($valid, $this->args) = $getopt->getopt2($args, $shortOptions, $longOptions);

    $vars = array(
      'user' => 'u',
      'pass' => 'p',
      'key' => 'k',
      'site' => 's',
    );

    foreach ($vars as $var => $short) {
      $$var = NULL;
      foreach ($valid as $v) {
        if ($v[0] == $short || $v[0] == "--$var") {
          $$var = $v[1];
          break;
        }
      }
      if (!$$var) {
        die("\nUsage: $ php5 " . $_SERVER['PHP_SELF'] . " -k key -u user -p password -s yoursite.org\n");
      }
    }
    $this->site = $site;
    $this->key = $key;
    $this->setEnv();
    $this->authenticate($user, $pass);
  }

  /**
   * @param $user
   * @param $pass
   */
  function authenticate($user, $pass) {
    session_start();
    require_once 'CRM/Core/Config.php';
    // Does calling this do anything here?
    CRM_Core_Config::singleton();

    // this does not return on failure
    // require_once 'CRM/Utils/System.php';
    //    CRM_Utils_System::authenticateScript( true );
    CRM_Utils_System::authenticateScript(TRUE, $user, $pass);
  }

  function setEnv() {
    // so the configuration works with php-cli
    $_SERVER['PHP_SELF'] = "/index.php";
    $_SERVER['HTTP_HOST'] = $this->site;
    $_REQUEST['key'] = $this->key;
    require_once ("./civicrm.config.php");
  }
}


//$cli=new civicrm_cli ();

