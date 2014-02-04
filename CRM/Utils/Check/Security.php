<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id: $
 *
 */
class CRM_Utils_Check_Security {

  CONST
    // How often to run checks and notify admins about issues.
    CHECK_TIMER = 86400;

  /**
   * We only need one instance of this object, so we use the
   * singleton pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * Provide static instance of CRM_Utils_Check_Security.
   *
   * @return CRM_Utils_Check_Security
   */
  static function &singleton() {
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_Utils_Check_Security();
    }
    return self::$_singleton;
  }

  /**
   * CMS have a different pattern to their default file path and URL.
   *
   * @TODO This function might be better shared in CRM_Utils_Check
   * class, but that class doesn't yet exist.
   */
  static function getFilePathMarker() {
    $config = CRM_Core_Config::singleton();
    switch ($config->userFramework) {
      case 'Joomla':
        return '/media/';
      default:
        return '/files/';
    }
  }

  /**
   * Run some sanity checks.
   *
   * This could become a hook so that CiviCRM can run both built-in
   * configuration & sanity checks, and modules/extensions can add
   * their own checks.
   *
   * We might even expose the results of these checks on the Wordpress
   * plugin status page or the Drupal admin/reports/status path.
   *
   * @see Drupal's hook_requirements() -
   * https://api.drupal.org/api/drupal/modules%21system%21system.api.php/function/hook_requirements
   */
  public function allChecks() {
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $session = CRM_Core_Session::singleton();
      if ($session->timer('check_' . __CLASS__, self::CHECK_TIMER)) {
        CRM_Utils_Check_Security::singleton()->CheckLogFileIsNotAccessible();
        CRM_Utils_Check_Security::singleton()->CheckUploadsAreNotAccessible();
        CRM_Utils_Check_Security::singleton()->CheckDirectoriesAreNotBrowseable();
      }
    }
  }

  /**
   * Check if our logfile is directly accessible.
   *
   * Per CiviCRM default the logfile sits in a folder which is
   * web-accessible, and is protected by a default .htaccess
   * configuration. If server config causes the .htaccess not to
   * function as intended, there may be information disclosure.
   *
   * The debug log may be jam-packed with sensitive data, we don't
   * want that.
   *
   * Being able to be retrieved directly doesn't mean the logfile
   * is browseable or visible to search engines; it means it can be
   * requested directly.
   *
   * @see CRM-14091
   */
  public function CheckLogFileIsNotAccessible() {
    $config = CRM_Core_Config::singleton();

    $log = CRM_Core_Error::createDebugLogger();
    $log_filename = $log->_filename;

    $config = CRM_Core_Config::singleton();
    $filePathMarker = CRM_Utils_Check_Security::getFilePathMarker();

    // Hazard a guess at the URL of the logfile, based on common
    // CiviCRM layouts.
    if ($upload_url = explode($filePathMarker, $config->imageUploadURL)) {
      $url[] = $upload_url[0];
      if ($log_path = explode($filePathMarker, $log_filename)) {
        $url[] = $log_path[1];
        $log_url = implode($filePathMarker, $url);
        $docs_url = 'http://wiki.civicrm.org/confluence/display/CRMDOC/Security/LogNotAccessible';
        if ($log = @file_get_contents($log_url)) {
          $msg = 'The <a href="%1">CiviCRM debug log</a> should not be downloadable.'
            . '<br />' .
            '<a href="%2">Read more about this warning</a>';
          $msg = ts($msg, array(1 => $log_url, 2 => $docs_url));
          CRM_Core_Session::setStatus($msg, ts('Security Warning'));
        }
      }
    }
  }

  /**
   * Check if our uploads directory has accessible files.
   *
   * We'll test a handful of files randomly. Hazard a guess at the URL
   * of the uploads dir, based on common CiviCRM layouts. Try and
   * request the files, and if any are successfully retrieved, warn.
   *
   * Being retrievable doesn't mean the files are browseable or visible
   * to search engines; it only means they can be requested directly.
   *
   * @see CRM-14091
   *
   * @TODO: Test with WordPress, Joomla.
   */
  public function CheckUploadsAreNotAccessible() {
    $config = CRM_Core_Config::singleton();
    $filePathMarker = CRM_Utils_Check_Security::getFilePathMarker();

    if ($upload_url = explode($filePathMarker, $config->imageUploadURL)) {
      if ($files = glob($config->uploadDir . '/*')) {
        for ($i=0; $i<3; $i++) {
          $f = array_rand($files);
          if ($file_path = explode($filePathMarker, $files[$f])) {
            $url = implode($filePathMarker, array($upload_url[0], $file_path[1]));
            if ($file = @file_get_contents($url)) {
              $msg = 'Files in the upload directory should not be downloadable.'
                . '<br />' .
                '<a href="%2">Read more about this warning</a>';
              $docs_url = 'http://wiki.civicrm.org/confluence/display/CRMDOC/Security/UploadDirNotAccessible';
              $msg = ts($msg, array(1 => $docs_url));
              CRM_Core_Session::setStatus($msg, ts('Security Warning'));
            }
          }
        }
      }
    }
  }

  /**
   * Check if our uploads or ConfigAndLog directories have browseable
   * listings.
   *
   * Retrieve a listing of files from the local filesystem, and the
   * corresponding path via HTTP. Then check and see if the local
   * files are represented in the HTTP result; if so then warn. This
   * MAY trigger false positives (if you have files named 'a', 'e'
   * we'll probably match that).
   *
   * @see CRM-14091
   *
   * @TODO: Test with WordPress, Joomla.
   */
  public function CheckDirectoriesAreNotBrowseable() {
    $config = CRM_Core_Config::singleton();
    $log = CRM_Core_Error::createDebugLogger();
    $log_name = $log->_filename;
    $filePathMarker = CRM_Utils_Check_Security::getFilePathMarker();

    $paths = array(
      $config->uploadDir,
      dirname($log_name),
    );
    if ($upload_url = explode($filePathMarker, $config->imageUploadURL)) {
      if ($files = glob($config->uploadDir . '/*')) {
        foreach ($paths as $path) {
          if ($dir_path = explode($filePathMarker, $path)) {
            $url = implode($filePathMarker, array($upload_url[0], $dir_path[1]));
            if ($files = glob($path . '/*')) {
              if ($listing = @file_get_contents($url)) {
                foreach ($files as $file) {
                  if (stristr($listing, $file)) {
                    $msg = 'Directory <a href="%1">%2</a> may be browseable via the web.'
                      . '<br />' .
                      '<a href="%3">Read more about this warning</a>';
                    $docs_url = 'http://wiki.civicrm.org/confluence/display/CRMDOC/Security/UploadDirNotAccessible';
                    $msg = ts($msg, array(1 => $log_url, 2 => $path, 3 => $docs_url));
                    CRM_Core_Session::setStatus($msg, ts('Security Warning'));
                  }
                }
              }
            }
          }
        }
      }
    }
  }

}
