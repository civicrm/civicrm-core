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
  public function getFilePathMarker() {
    $config = CRM_Core_Config::singleton();
    switch ($config->userFramework) {
      case 'Joomla':
        return '/media/';
      default:
        return '/files/';
    }
  }

  /**
   * Execute "checkAll"
   */
  public function showPeriodicAlerts() {
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $session = CRM_Core_Session::singleton();
      if ($session->timer('check_' . __CLASS__, self::CHECK_TIMER)) {

        // Best attempt at re-securing folders
        $config = CRM_Core_Config::singleton();
        $config->cleanup(0, FALSE);

        foreach ($this->checkAll() as $message) {
          CRM_Core_Session::setStatus($message, ts('Security Warning'));
        }
      }
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
   * @return array of messages
   * @see Drupal's hook_requirements() -
   * https://api.drupal.org/api/drupal/modules%21system%21system.api.php/function/hook_requirements
   */
  public function checkAll() {
    $messages = array_merge(
      CRM_Utils_Check_Security::singleton()->checkLogFileIsNotAccessible(),
      CRM_Utils_Check_Security::singleton()->checkUploadsAreNotAccessible(),
      CRM_Utils_Check_Security::singleton()->checkDirectoriesAreNotBrowseable()
    );
    return $messages;
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
   * @return array of messages
   * @see CRM-14091
   */
  public function checkLogFileIsNotAccessible() {
    $messages = array();

    $config = CRM_Core_Config::singleton();

    $log = CRM_Core_Error::createDebugLogger();
    $log_filename = $log->_filename;

    $filePathMarker = $this->getFilePathMarker();

    // Hazard a guess at the URL of the logfile, based on common
    // CiviCRM layouts.
    if ($upload_url = explode($filePathMarker, $config->imageUploadURL)) {
      $url[] = $upload_url[0];
      if ($log_path = explode($filePathMarker, $log_filename)) {
        $url[] = $log_path[1];
        $log_url = implode($filePathMarker, $url);
        $docs_url = $this->createDocUrl('checkLogFileIsNotAccessible');
        if ($log = @file_get_contents($log_url)) {
          $msg = 'The <a href="%1">CiviCRM debug log</a> should not be downloadable.'
            . '<br />' .
            '<a href="%2">Read more about this warning</a>';
          $messages[] = ts($msg, array(1 => $log_url, 2 => $docs_url));
        }
      }
    }

    return $messages;
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
   * @return array of messages
   * @see CRM-14091
   *
   * @TODO: Test with WordPress, Joomla.
   */
  public function checkUploadsAreNotAccessible() {
    $messages = array();

    $config = CRM_Core_Config::singleton();
    $filePathMarker = $this->getFilePathMarker();

    if ($upload_url = explode($filePathMarker, $config->imageUploadURL)) {
      if ($files = glob($config->uploadDir . '/*')) {
        for ($i = 0; $i < 3; $i++) {
          $f = array_rand($files);
          if ($file_path = explode($filePathMarker, $files[$f])) {
            $url = implode($filePathMarker, array($upload_url[0], $file_path[1]));
            if ($file = @file_get_contents($url)) {
              $msg = 'Files in the upload directory should not be downloadable.'
                . '<br />' .
                '<a href="%1">Read more about this warning</a>';
              $docs_url = $this->createDocUrl('checkUploadsAreNotAccessible');
              $messages[] = ts($msg, array(1 => $docs_url));
            }
          }
        }
      }
    }

    return $messages;
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
   * @return array of messages
   * @see CRM-14091
   *
   * @TODO: Test with WordPress, Joomla.
   */
  public function checkDirectoriesAreNotBrowseable() {
    $messages = array();
    $config = CRM_Core_Config::singleton();
    $publicDirs = array(
      $config->imageUploadDir => $config->imageUploadURL,
    );

    // Setup index.html files to prevent browsing
    foreach ($publicDirs as $publicDir => $publicUrl) {
      CRM_Utils_File::restrictBrowsing($publicDir);
    }

    // Test that $publicDir is not browsable
    foreach ($publicDirs as $publicDir => $publicUrl) {
      if ($this->isBrowsable($publicDir, $publicUrl)) {
        $msg = 'Directory <a href="%1">%2</a> should not be browseable via the web.'
          . '<br />' .
          '<a href="%3">Read more about this warning</a>';
        $docs_url = $this->createDocUrl('checkDirectoriesAreNotBrowseable');
        $messages[] = ts($msg, array(1 => $publicDir, 2 => $publicDir, 3 => $docs_url));
      }
    }

    return $messages;
  }

  /**
   * Determine whether $url is a public, browsable listing for $dir
   *
   * @param string $dir local dir path
   * @param string $url public URL
   * @return bool
   */
  public function isBrowsable($dir, $url) {
    if (empty($dir) || empty($url) || !is_dir($dir)) {
      return FALSE;
    }

    $result = FALSE;
    $file = 'delete-this-' . CRM_Utils_String::createRandom(10, CRM_Utils_String::ALPHANUMERIC);

    // this could be a new system with no uploads (yet) -- so we'll make a file
    file_put_contents("$dir/$file", "delete me");
    $content = @file_get_contents("$url");
    if (stristr($content, $file)) {
      $result = TRUE;
    }
    unlink("$dir/$file");

    return $result;
  }

  public function createDocUrl($topic) {
    return CRM_Utils_System::getWikiBaseURL() . $topic;
  }
}
