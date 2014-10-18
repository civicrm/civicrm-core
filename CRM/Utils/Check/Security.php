<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
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
   * Run some sanity checks.
   *
   * @return array<CRM_Utils_Check_Message>
   */
  public function checkAll() {
    $messages = array_merge(
      $this->checkLogFileIsNotAccessible(),
      $this->checkUploadsAreNotAccessible(),
      $this->checkDirectoriesAreNotBrowseable()
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
    $log_filename = str_replace('\\', '/', $log->_filename);

    $filePathMarker = $this->getFilePathMarker();

    // Hazard a guess at the URL of the logfile, based on common
    // CiviCRM layouts.
    if ($upload_url = explode($filePathMarker, $config->imageUploadURL)) {
      $url[] = $upload_url[0];
      if ($log_path = explode($filePathMarker, $log_filename)) {
        $url[] = $log_path[1];
        $log_url = implode($filePathMarker, $url);
        $headers = @get_headers($log_url);
        if (stripos($headers[0], '200')) {
          $docs_url = $this->createDocUrl('checkLogFileIsNotAccessible');
          $msg = 'The <a href="%1">CiviCRM debug log</a> should not be downloadable.'
            . '<br />' .
            '<a href="%2">Read more about this warning</a>';
          $messages[] = new CRM_Utils_Check_Message(
            'checkLogFileIsNotAccessible',
            ts($msg, array(1 => $log_url, 2 => $docs_url)),
            ts('Security Warning')
          );
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
    $privateDirs = array(
      $config->uploadDir,
      $config->customFileUploadDir,
    );

    foreach ($privateDirs as $privateDir) {
      $heuristicUrl = $this->guessUrl($privateDir);
      if ($this->isDirAccessible($privateDir, $heuristicUrl)) {
        $messages[] = new CRM_Utils_Check_Message(
          'checkUploadsAreNotAccessible',
          ts('Files in the data directory (<a href="%3">%2</a>) should not be downloadable.'
              . '<br />'
              . '<a href="%1">Read more about this warning</a>',
            array(
              1 => $this->createDocUrl('checkUploadsAreNotAccessible'),
              2 => $privateDir,
              3 => $heuristicUrl,
            )),
          ts('Security Warning')
        );
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
        $messages[] = new CRM_Utils_Check_Message(
          'checkDirectoriesAreNotBrowseable',
          ts($msg, array(1 => $publicDir, 2 => $publicDir, 3 => $docs_url)),
          ts('Security Warning')
        );
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

  /**
   * Determine whether $url is a public version of $dir in which files
   * are remotely accessible.
   *
   * @param string $dir local dir path
   * @param string $url public URL
   * @return bool
   */
  public function isDirAccessible($dir, $url) {
    $dir = rtrim($dir, '/');
    $url = rtrim($url, '/');
    if (empty($dir) || empty($url) || !is_dir($dir)) {
      return FALSE;
    }

    $result = FALSE;
    $file = 'delete-this-' . CRM_Utils_String::createRandom(10, CRM_Utils_String::ALPHANUMERIC);

    // this could be a new system with no uploads (yet) -- so we'll make a file
    file_put_contents("$dir/$file", "delete me");

    $headers = @get_headers("$url/$file");
    if (stripos($headers[0], '200')) {
      $content = @file_get_contents("$url/$file");
      if (preg_match('/delete me/', $content)) {
        $result = TRUE;
      }
    }

    unlink("$dir/$file");

    return $result;
  }

  /**
   * @param $topic
   *
   * @return string
   */
  public function createDocUrl($topic) {
    return CRM_Utils_System::getWikiBaseURL() . $topic;
  }

  /**
   * Make a guess about the URL that corresponds to $targetDir.
   *
   * @param string $targetDir local path to a directory
   * @return string a guessed URL for $realDir
   */
  public function guessUrl($targetDir) {
    $filePathMarker = $this->getFilePathMarker();
    $config = CRM_Core_Config::singleton();

    list ($heuristicBaseUrl, $ignore) = explode($filePathMarker, $config->imageUploadURL);
    list ($ignore, $heuristicSuffix) = explode($filePathMarker, str_replace('\\', '/', $targetDir));
    return $heuristicBaseUrl . $filePathMarker . $heuristicSuffix;
  }
}
