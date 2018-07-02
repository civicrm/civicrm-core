<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * class to provide simple static functions for file objects
 */
class CRM_Utils_File {

  /**
   * Given a file name, determine if the file contents make it an ascii file
   *
   * @param string $name
   *   Name of file.
   *
   * @return bool
   *   true if file is ascii
   */
  public static function isAscii($name) {
    $fd = fopen($name, "r");
    if (!$fd) {
      return FALSE;
    }

    $ascii = TRUE;
    while (!feof($fd)) {
      $line = fgets($fd, 8192);
      if (!CRM_Utils_String::isAscii($line)) {
        $ascii = FALSE;
        break;
      }
    }

    fclose($fd);
    return $ascii;
  }

  /**
   * Given a file name, determine if the file contents make it an html file
   *
   * @param string $name
   *   Name of file.
   *
   * @return bool
   *   true if file is html
   */
  public static function isHtml($name) {
    $fd = fopen($name, "r");
    if (!$fd) {
      return FALSE;
    }

    $html = FALSE;
    $lineCount = 0;
    while (!feof($fd) & $lineCount <= 5) {
      $lineCount++;
      $line = fgets($fd, 8192);
      if (!CRM_Utils_String::isHtml($line)) {
        $html = TRUE;
        break;
      }
    }

    fclose($fd);
    return $html;
  }

  /**
   * Create a directory given a path name, creates parent directories
   * if needed
   *
   * @param string $path
   *   The path name.
   * @param bool $abort
   *   Should we abort or just return an invalid code.
   * @return bool|NULL
   *   NULL: Folder already exists or was not specified.
   *   TRUE: Creation succeeded.
   *   FALSE: Creation failed.
   */
  public static function createDir($path, $abort = TRUE) {
    if (is_dir($path) || empty($path)) {
      return NULL;
    }

    CRM_Utils_File::createDir(dirname($path), $abort);
    if (@mkdir($path, 0777) == FALSE) {
      if ($abort) {
        $docLink = CRM_Utils_System::docURL2('Moving an Existing Installation to a New Server or Location', NULL, NULL, NULL, NULL, "wiki");
        echo "Error: Could not create directory: $path.<p>If you have moved an existing CiviCRM installation from one location or server to another there are several steps you will need to follow. They are detailed on this CiviCRM wiki page - {$docLink}. A fix for the specific problem that caused this error message to be displayed is to set the value of the config_backend column in the civicrm_domain table to NULL. However we strongly recommend that you review and follow all the steps in that document.</p>";

        CRM_Utils_System::civiExit();
      }
      else {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Delete a directory given a path name, delete children directories
   * and files if needed
   *
   * @param string $target
   *   The path name.
   * @param bool $rmdir
   * @param bool $verbose
   *
   * @throws Exception
   */
  public static function cleanDir($target, $rmdir = TRUE, $verbose = TRUE) {
    static $exceptions = array('.', '..');
    if ($target == '' || $target == '/' || !$target) {
      throw new Exception("Overly broad deletion");
    }

    if ($dh = @opendir($target)) {
      while (FALSE !== ($sibling = readdir($dh))) {
        if (!in_array($sibling, $exceptions)) {
          $object = $target . DIRECTORY_SEPARATOR . $sibling;

          if (is_dir($object)) {
            CRM_Utils_File::cleanDir($object, $rmdir, $verbose);
          }
          elseif (is_file($object)) {
            if (!unlink($object)) {
              CRM_Core_Session::setStatus(ts('Unable to remove file %1', array(1 => $object)), ts('Warning'), 'error');
            }
          }
        }
      }
      closedir($dh);

      if ($rmdir) {
        if (rmdir($target)) {
          if ($verbose) {
            CRM_Core_Session::setStatus(ts('Removed directory %1', array(1 => $target)), '', 'success');
          }
          return TRUE;
        }
        else {
          CRM_Core_Session::setStatus(ts('Unable to remove directory %1', array(1 => $target)), ts('Warning'), 'error');
        }
      }
    }
  }

  /**
   * Concatenate several files.
   *
   * @param array $files
   *   List of file names.
   * @param string $delim
   *   An optional delimiter to put between files.
   * @return string
   */
  public static function concat($files, $delim = '') {
    $buf = '';
    $first = TRUE;
    foreach ($files as $file) {
      if (!$first) {
        $buf .= $delim;
      }
      $buf .= file_get_contents($file);
      $first = FALSE;
    }
    return $buf;
  }

  /**
   * @param string $source
   * @param string $destination
   */
  public static function copyDir($source, $destination) {
    if ($dh = opendir($source)) {
      @mkdir($destination);
      while (FALSE !== ($file = readdir($dh))) {
        if (($file != '.') && ($file != '..')) {
          if (is_dir($source . DIRECTORY_SEPARATOR . $file)) {
            CRM_Utils_File::copyDir($source . DIRECTORY_SEPARATOR . $file, $destination . DIRECTORY_SEPARATOR . $file);
          }
          else {
            copy($source . DIRECTORY_SEPARATOR . $file, $destination . DIRECTORY_SEPARATOR . $file);
          }
        }
      }
      closedir($dh);
    }
  }

  /**
   * Given a file name, recode it (in place!) to UTF-8
   *
   * @param string $name
   *   Name of file.
   *
   * @return bool
   *   whether the file was recoded properly
   */
  public static function toUtf8($name) {
    static $config = NULL;
    static $legacyEncoding = NULL;
    if ($config == NULL) {
      $config = CRM_Core_Config::singleton();
      $legacyEncoding = $config->legacyEncoding;
    }

    if (!function_exists('iconv')) {

      return FALSE;

    }

    $contents = file_get_contents($name);
    if ($contents === FALSE) {
      return FALSE;
    }

    $contents = iconv($legacyEncoding, 'UTF-8', $contents);
    if ($contents === FALSE) {
      return FALSE;
    }

    $file = fopen($name, 'w');
    if ($file === FALSE) {
      return FALSE;
    }

    $written = fwrite($file, $contents);
    $closed = fclose($file);
    if ($written === FALSE or !$closed) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Appends a slash to the end of a string if it doesn't already end with one
   *
   * @param string $path
   * @param string $slash
   *
   * @return string
   */
  public static function addTrailingSlash($path, $slash = NULL) {
    if (!$slash) {
      // FIXME: Defaulting to backslash on windows systems can produce
      // unexpected results, esp for URL strings which should always use forward-slashes.
      // I think this fn should default to forward-slash instead.
      $slash = DIRECTORY_SEPARATOR;
    }
    if (!in_array(substr($path, -1, 1), array('/', '\\'))) {
      $path .= $slash;
    }
    return $path;
  }

  /**
   * Save a fake file somewhere
   *
   * @param string $dir
   *   The directory where the file should be saved.
   * @param string $contents
   *   Optional: the contents of the file.
   * @param string $fileName
   *
   * @return string
   *   The filename saved, or FALSE on failure.
   */
  public static function createFakeFile($dir, $contents = 'delete me', $fileName = NULL) {
    $dir = self::addTrailingSlash($dir);
    if (!$fileName) {
      $fileName = 'delete-this-' . CRM_Utils_String::createRandom(10, CRM_Utils_String::ALPHANUMERIC);
    }
    $success = @file_put_contents($dir . $fileName, $contents);

    return ($success === FALSE) ? FALSE : $fileName;
  }

  /**
   * @param string|NULL $dsn
   *   Use NULL to load the default/active connection from CRM_Core_DAO.
   *   Otherwise, give a full DSN string.
   * @param string $fileName
   * @param string $prefix
   * @param bool $dieOnErrors
   */
  public static function sourceSQLFile($dsn, $fileName, $prefix = NULL, $dieOnErrors = TRUE) {
    if (FALSE === file_get_contents($fileName)) {
      // Our file cannot be found.
      // Using 'die' here breaks this on extension upgrade.
      throw new CRM_Exception('Could not find the SQL file.');
    }

    self::runSqlQuery($dsn, file_get_contents($fileName), $prefix, $dieOnErrors);
  }

  /**
   *
   * @param string|NULL $dsn
   * @param string $queryString
   * @param string $prefix
   * @param bool $dieOnErrors
   */
  public static function runSqlQuery($dsn, $queryString, $prefix = NULL, $dieOnErrors = TRUE) {
    $string = $prefix . $queryString;

    if ($dsn === NULL) {
      $db = CRM_Core_DAO::getConnection();
    }
    else {
      require_once 'DB.php';
      $db = DB::connect($dsn);
    }

    if (PEAR::isError($db)) {
      die("Cannot open $dsn: " . $db->getMessage());
    }
    if (CRM_Utils_Constant::value('CIVICRM_MYSQL_STRICT', CRM_Utils_System::isDevelopment())) {
      $db->query('SET SESSION sql_mode = STRICT_TRANS_TABLES');
    }
    $db->query('SET NAMES utf8');
    $transactionId = CRM_Utils_Type::escape(CRM_Utils_Request::id(), 'String');
    $db->query('SET @uniqueID = ' . "'$transactionId'");

    // get rid of comments starting with # and --

    $string = self::stripComments($string);

    $queries = preg_split('/;\s*$/m', $string);
    foreach ($queries as $query) {
      $query = trim($query);
      if (!empty($query)) {
        CRM_Core_Error::debug_query($query);
        $res = &$db->query($query);
        if (PEAR::isError($res)) {
          if ($dieOnErrors) {
            die("Cannot execute $query: " . $res->getMessage());
          }
          else {
            echo "Cannot execute $query: " . $res->getMessage() . "<p>";
          }
        }
      }
    }
  }

  /**
   *
   * Strips comment from a possibly multiline SQL string
   *
   * @param string $string
   *
   * @return string
   *   stripped string
   */
  public static function stripComments($string) {
    return preg_replace("/^(#|--).*\R*/m", "", $string);
  }

  /**
   * @param $ext
   *
   * @return bool
   */
  public static function isExtensionSafe($ext) {
    static $extensions = NULL;
    if (!$extensions) {
      $extensions = CRM_Core_OptionGroup::values('safe_file_extension', TRUE);

      // make extensions to lowercase
      $extensions = array_change_key_case($extensions, CASE_LOWER);
      // allow html/htm extension ONLY if the user is admin
      // and/or has access CiviMail
      if (!(CRM_Core_Permission::check('access CiviMail') ||
        CRM_Core_Permission::check('administer CiviCRM') ||
        (CRM_Mailing_Info::workflowEnabled() &&
          CRM_Core_Permission::check('create mailings')
        )
      )
      ) {
        unset($extensions['html']);
        unset($extensions['htm']);
      }
    }
    // support lower and uppercase file extensions
    return isset($extensions[strtolower($ext)]) ? TRUE : FALSE;
  }

  /**
   * Determine whether a given file is listed in the PHP include path.
   *
   * @param string $name
   *   Name of file.
   *
   * @return bool
   *   whether the file can be include()d or require()d
   */
  public static function isIncludable($name) {
    $x = @fopen($name, 'r', TRUE);
    if ($x) {
      fclose($x);
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Remove the 32 bit md5 we add to the fileName also remove the unknown tag if we added it.
   *
   * @param $name
   *
   * @return mixed
   */
  public static function cleanFileName($name) {
    // replace the last 33 character before the '.' with null
    $name = preg_replace('/(_[\w]{32})\./', '.', $name);
    return $name;
  }

  /**
   * Make a valid file name.
   *
   * @param string $name
   *
   * @return string
   */
  public static function makeFileName($name) {
    $uniqID = md5(uniqid(rand(), TRUE));
    $info = pathinfo($name);
    $basename = substr($info['basename'],
      0, -(strlen(CRM_Utils_Array::value('extension', $info)) + (CRM_Utils_Array::value('extension', $info) == '' ? 0 : 1))
    );
    if (!self::isExtensionSafe(CRM_Utils_Array::value('extension', $info))) {
      // munge extension so it cannot have an embbeded dot in it
      // The maximum length of a filename for most filesystems is 255 chars.
      // We'll truncate at 240 to give some room for the extension.
      return CRM_Utils_String::munge("{$basename}_" . CRM_Utils_Array::value('extension', $info) . "_{$uniqID}", '_', 240) . ".unknown";
    }
    else {
      return CRM_Utils_String::munge("{$basename}_{$uniqID}", '_', 240) . "." . CRM_Utils_Array::value('extension', $info);
    }
  }

  /**
   * Copies a file
   *
   * @param $filePath
   * @return mixed
   */
  public static function duplicate($filePath) {
    $oldName = pathinfo($filePath, PATHINFO_FILENAME);
    $uniqID = md5(uniqid(rand(), TRUE));
    $newName = preg_replace('/(_[\w]{32})$/', '', $oldName) . '_' . $uniqID;
    $newPath = str_replace($oldName, $newName, $filePath);
    copy($filePath, $newPath);
    return $newPath;
  }

  /**
   * Get files for the extension.
   *
   * @param string $path
   * @param string $ext
   *
   * @return array
   */
  public static function getFilesByExtension($path, $ext) {
    $path = self::addTrailingSlash($path);
    $files = array();
    if ($dh = opendir($path)) {
      while (FALSE !== ($elem = readdir($dh))) {
        if (substr($elem, -(strlen($ext) + 1)) == '.' . $ext) {
          $files[] .= $path . $elem;
        }
      }
      closedir($dh);
    }
    return $files;
  }

  /**
   * Restrict access to a given directory (by planting there a restrictive .htaccess file)
   *
   * @param string $dir
   *   The directory to be secured.
   * @param bool $overwrite
   */
  public static function restrictAccess($dir, $overwrite = FALSE) {
    // note: empty value for $dir can play havoc, since that might result in putting '.htaccess' to root dir
    // of site, causing site to stop functioning.
    // FIXME: we should do more checks here -
    if (!empty($dir) && is_dir($dir)) {
      $htaccess = <<<HTACCESS
<Files "*">
  Order allow,deny
  Deny from all
</Files>

HTACCESS;
      $file = $dir . '.htaccess';
      if ($overwrite || !file_exists($file)) {
        if (file_put_contents($file, $htaccess) === FALSE) {
          CRM_Core_Error::movedSiteError($file);
        }
      }
    }
  }

  /**
   * Restrict remote users from browsing the given directory.
   *
   * @param $publicDir
   */
  public static function restrictBrowsing($publicDir) {
    if (!is_dir($publicDir) || !is_writable($publicDir)) {
      return;
    }

    // base dir
    $nobrowse = realpath($publicDir) . '/index.html';
    if (!file_exists($nobrowse)) {
      @file_put_contents($nobrowse, '');
    }

    // child dirs
    $dir = new RecursiveDirectoryIterator($publicDir);
    foreach ($dir as $name => $object) {
      if (is_dir($name) && $name != '..') {
        $nobrowse = realpath($name) . '/index.html';
        if (!file_exists($nobrowse)) {
          @file_put_contents($nobrowse, '');
        }
      }
    }
  }

  /**
   * Create the base file path from which all our internal directories are
   * offset. This is derived from the template compile directory set
   */
  public static function baseFilePath() {
    static $_path = NULL;
    if (!$_path) {
      // Note: Don't rely on $config; that creates a dependency loop.
      if (!defined('CIVICRM_TEMPLATE_COMPILEDIR')) {
        throw new RuntimeException("Undefined constant: CIVICRM_TEMPLATE_COMPILEDIR");
      }
      $templateCompileDir = CIVICRM_TEMPLATE_COMPILEDIR;

      $path = dirname($templateCompileDir);

      //this fix is to avoid creation of upload dirs inside templates_c directory
      $checkPath = explode(DIRECTORY_SEPARATOR, $path);

      $cnt = count($checkPath) - 1;
      if ($checkPath[$cnt] == 'templates_c') {
        unset($checkPath[$cnt]);
        $path = implode(DIRECTORY_SEPARATOR, $checkPath);
      }

      $_path = CRM_Utils_File::addTrailingSlash($path);
    }
    return $_path;
  }

  /**
   * Determine if a path is absolute.
   *
   * @param string $path
   *
   * @return bool
   *   TRUE if absolute. FALSE if relative.
   */
  public static function isAbsolute($path) {
    if (substr($path, 0, 1) === DIRECTORY_SEPARATOR) {
      return TRUE;
    }
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      if (preg_match('!^[a-zA-Z]:[/\\\\]!', $path)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @param $directory
   *
   * @return string
   */
  public static function relativeDirectory($directory) {
    // Do nothing on windows
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      return $directory;
    }

    // check if directory is relative, if so return immediately
    if (!self::isAbsolute($directory)) {
      return $directory;
    }

    // make everything relative from the baseFilePath
    $basePath = self::baseFilePath();
    // check if basePath is a substr of $directory, if so
    // return rest of string
    if (substr($directory, 0, strlen($basePath)) == $basePath) {
      return substr($directory, strlen($basePath));
    }

    // return the original value
    return $directory;
  }

  /**
   * @param $directory
   * @param string|NULL $basePath
   *   The base path when evaluating relative paths. Should include trailing slash.
   *
   * @return string
   */
  public static function absoluteDirectory($directory, $basePath = NULL) {
    // check if directory is already absolute, if so return immediately
    // Note: Windows PHP accepts any mix of "/" or "\", so "C:\htdocs" or "C:/htdocs" would be a valid absolute path
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && preg_match(';^[a-zA-Z]:[/\\\\];', $directory)) {
      return $directory;
    }

    // check if directory is already absolute, if so return immediately
    if (substr($directory, 0, 1) == DIRECTORY_SEPARATOR) {
      return $directory;
    }

    // make everything absolute from the baseFilePath
    $basePath = ($basePath === NULL) ? self::baseFilePath() : $basePath;

    // ensure that $basePath has a trailing slash
    $basePath = self::addTrailingSlash($basePath);
    return $basePath . $directory;
  }

  /**
   * Make a file path relative to some base dir.
   *
   * @param $directory
   * @param $basePath
   *
   * @return string
   */
  public static function relativize($directory, $basePath) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      $directory = strtr($directory, '\\', '/');
      $basePath = strtr($basePath, '\\', '/');
    }
    if (substr($directory, 0, strlen($basePath)) == $basePath) {
      return substr($directory, strlen($basePath));
    }
    else {
      return $directory;
    }
  }

  /**
   * Create a path to a temporary file which can endure for multiple requests.
   *
   * @todo Automatic file cleanup using, eg, TTL policy
   *
   * @param string $prefix
   *
   * @return string, path to an openable/writable file
   * @see tempnam
   */
  public static function tempnam($prefix = 'tmp-') {
    // $config = CRM_Core_Config::singleton();
    // $nonce = md5(uniqid() . $config->dsn . $config->userFrameworkResourceURL);
    // $fileName = "{$config->configAndLogDir}" . $prefix . $nonce . $suffix;
    $fileName = tempnam(sys_get_temp_dir(), $prefix);
    return $fileName;
  }

  /**
   * Create a path to a temporary directory which can endure for multiple requests.
   *
   * @todo Automatic file cleanup using, eg, TTL policy
   *
   * @param string $prefix
   *
   * @return string, path to an openable/writable directory; ends with '/'
   * @see tempnam
   */
  public static function tempdir($prefix = 'tmp-') {
    $fileName = self::tempnam($prefix);
    unlink($fileName);
    mkdir($fileName, 0700);
    return $fileName . '/';
  }

  /**
   * Search directory tree for files which match a glob pattern.
   *
   * Note: Dot-directories (like "..", ".git", or ".svn") will be ignored.
   *
   * @param string $dir
   *   base dir.
   * @param string $pattern
   *   glob pattern, eg "*.txt".
   * @param bool $relative
   *   TRUE if paths should be made relative to $dir
   * @return array(string)
   */
  public static function findFiles($dir, $pattern, $relative = FALSE) {
    if (!is_dir($dir)) {
      return array();
    }
    $dir = rtrim($dir, '/');
    $todos = array($dir);
    $result = array();
    while (!empty($todos)) {
      $subdir = array_shift($todos);
      $matches = glob("$subdir/$pattern");
      if (is_array($matches)) {
        foreach ($matches as $match) {
          if (!is_dir($match)) {
            $result[] = $relative ? CRM_Utils_File::relativize($match, "$dir/") : $match;
          }
        }
      }
      if ($dh = opendir($subdir)) {
        while (FALSE !== ($entry = readdir($dh))) {
          $path = $subdir . DIRECTORY_SEPARATOR . $entry;
          if ($entry{0} == '.') {
            // ignore
          }
          elseif (is_dir($path)) {
            $todos[] = $path;
          }
        }
        closedir($dh);
      }
    }
    return $result;
  }

  /**
   * Determine if $child is a sub-directory of $parent
   *
   * @param string $parent
   * @param string $child
   * @param bool $checkRealPath
   *
   * @return bool
   */
  public static function isChildPath($parent, $child, $checkRealPath = TRUE) {
    if ($checkRealPath) {
      $parent = realpath($parent);
      $child = realpath($child);
    }
    $parentParts = explode('/', rtrim($parent, '/'));
    $childParts = explode('/', rtrim($child, '/'));
    while (($parentPart = array_shift($parentParts)) !== NULL) {
      $childPart = array_shift($childParts);
      if ($parentPart != $childPart) {
        return FALSE;
      }
    }
    if (empty($childParts)) {
      return FALSE; // same directory
    }
    else {
      return TRUE;
    }
  }

  /**
   * Move $fromDir to $toDir, replacing/deleting any
   * pre-existing content.
   *
   * @param string $fromDir
   *   The directory which should be moved.
   * @param string $toDir
   *   The new location of the directory.
   * @param bool $verbose
   *
   * @return bool
   *   TRUE on success
   */
  public static function replaceDir($fromDir, $toDir, $verbose = FALSE) {
    if (is_dir($toDir)) {
      if (!self::cleanDir($toDir, TRUE, $verbose)) {
        return FALSE;
      }
    }

    // return rename($fromDir, $toDir); CRM-11987, https://bugs.php.net/bug.php?id=54097

    CRM_Utils_File::copyDir($fromDir, $toDir);
    if (!CRM_Utils_File::cleanDir($fromDir, TRUE, FALSE)) {
      CRM_Core_Session::setStatus(ts('Failed to clean temp dir: %1', array(1 => $fromDir)), '', 'alert');
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Format file.
   *
   * @param array $param
   * @param string $fileName
   * @param array $extraParams
   */
  public static function formatFile(&$param, $fileName, $extraParams = array()) {
    if (empty($param[$fileName])) {
      return;
    }

    $fileParams = array(
      'uri' => $param[$fileName]['name'],
      'type' => $param[$fileName]['type'],
      'location' => $param[$fileName]['name'],
      'upload_date' => date('YmdHis'),
    ) + $extraParams;

    $param[$fileName] = $fileParams;
  }

  /**
   * Return formatted file URL, like for image file return image url with image icon
   *
   * @param string $path
   *   Absoulte file path
   * @param string $fileType
   * @param string $url
   *   File preview link e.g. https://example.com/civicrm/file?reset=1&filename=image.png&mime-type=image/png
   *
   * @return string $url
   */
  public static function getFileURL($path, $fileType, $url = NULL) {
    if (empty($path) || empty($fileType)) {
      return '';
    }
    elseif (empty($url)) {
      $fileName = basename($path);
      $url = CRM_Utils_System::url('civicrm/file', "reset=1&filename={$fileName}&mime-type={$fileType}");
    }
    switch ($fileType) {
      case 'image/jpeg':
      case 'image/pjpeg':
      case 'image/gif':
      case 'image/x-png':
      case 'image/png':
      case 'image/jpg':
        list($imageWidth, $imageHeight) = getimagesize($path);
        list($imageThumbWidth, $imageThumbHeight) = CRM_Contact_BAO_Contact::getThumbSize($imageWidth, $imageHeight);
        $url = "<a href=\"$url\" class='crm-image-popup'>
          <img src=\"$url\" width=$imageThumbWidth height=$imageThumbHeight/>
          </a>";
        break;

      default:
        $url = sprintf('<a href="%s">%s</a>', $url, self::cleanFileName(basename($path)));
        break;
    }

    return $url;
  }

  /**
   * Return formatted image icon
   *
   * @param string $imageURL
   *   Contact's image url
   *
   * @return string $url
   */
  public static function getImageURL($imageURL) {
    // retrieve image name from $imageURL
    $imageURL = CRM_Utils_String::unstupifyUrl($imageURL);
    parse_str(parse_url($imageURL, PHP_URL_QUERY), $query);

    $url = NULL;
    if (!empty($query['photo'])) {
      $path = CRM_Core_Config::singleton()->customFileUploadDir . $query['photo'];
    }
    else {
      $path = $url = $imageURL;
    }
    $mimeType = 'image/' . strtolower(pathinfo($path, PATHINFO_EXTENSION));

    return self::getFileURL($path, $mimeType, $url);
  }

  /**
   * Resize an image.
   *
   * @param string $sourceFile
   *   Filesystem path to existing image on server
   * @param int $targetWidth
   *   New width desired, in pixels
   * @param int $targetHeight
   *   New height desired, in pixels
   * @param string $suffix = ""
   *   If supplied, the image will be renamed to include this suffix. For
   *   example if the original file name is "foo.png" and $suffix = "_bar",
   *   then the final file name will be "foo_bar.png".
   * @param bool $preserveAspect = TRUE
   *   When TRUE $width and $height will be used as a bounding box, outside of
   *   which the resized image will not extend.
   *   When FALSE, the image will be resized exactly to $width and $height, even
   *   if it means stretching it.
   *
   * @return string
   *   Path to image
   * @throws \CRM_Core_Exception
   *   Under the following conditions
   *   - When GD is not available.
   *   - When the source file is not an image.
   */
  public static function resizeImage($sourceFile, $targetWidth, $targetHeight, $suffix = "", $preserveAspect = TRUE) {

    // Check if GD is installed
    $gdSupport = CRM_Utils_System::getModuleSetting('gd', 'GD Support');
    if (!$gdSupport) {
      throw new CRM_Core_Exception(ts('Unable to resize image because the GD image library is not currently compiled in your PHP installation.'));
    }

    $sourceMime = mime_content_type($sourceFile);
    if ($sourceMime == 'image/gif') {
      $sourceData = imagecreatefromgif($sourceFile);
    }
    elseif ($sourceMime == 'image/png') {
      $sourceData = imagecreatefrompng($sourceFile);
    }
    elseif ($sourceMime == 'image/jpeg') {
      $sourceData = imagecreatefromjpeg($sourceFile);
    }
    else {
      throw new CRM_Core_Exception(ts('Unable to resize image because the file supplied was not an image.'));
    }

    // get image about original image
    $sourceInfo = getimagesize($sourceFile);
    $sourceWidth = $sourceInfo[0];
    $sourceHeight = $sourceInfo[1];

    // Adjust target width/height if preserving aspect ratio
    if ($preserveAspect) {
      $sourceAspect = $sourceWidth / $sourceHeight;
      $targetAspect = $targetWidth / $targetHeight;
      if ($sourceAspect > $targetAspect) {
        $targetHeight = $targetWidth / $sourceAspect;
      }
      if ($sourceAspect < $targetAspect) {
        $targetWidth = $targetHeight * $sourceAspect;
      }
    }

    // figure out the new filename
    $pathParts = pathinfo($sourceFile);
    $targetFile = $pathParts['dirname'] . DIRECTORY_SEPARATOR
      . $pathParts['filename'] . $suffix . "." . $pathParts['extension'];

    $targetData = imagecreatetruecolor($targetWidth, $targetHeight);

    // resize
    imagecopyresized($targetData, $sourceData,
      0, 0, 0, 0,
      $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

    // save the resized image
    $fp = fopen($targetFile, 'w+');
    ob_start();
    imagejpeg($targetData);
    $image_buffer = ob_get_contents();
    ob_end_clean();
    imagedestroy($targetData);
    fwrite($fp, $image_buffer);
    rewind($fp);
    fclose($fp);

    // return the URL to link to
    $config = CRM_Core_Config::singleton();
    return $config->imageUploadURL . basename($targetFile);
  }

  /**
   * Get file icon class for specific MIME Type
   *
   * @param string $mimeType
   * @return string
   */
  public static function getIconFromMimeType($mimeType) {
    if (!isset(Civi::$statics[__CLASS__]['mimeIcons'])) {
      Civi::$statics[__CLASS__]['mimeIcons'] = json_decode(file_get_contents(__DIR__ . '/File/mimeIcons.json'), TRUE);
    }
    $iconClasses = Civi::$statics[__CLASS__]['mimeIcons'];
    foreach ($iconClasses as $text => $icon) {
      if (strpos($mimeType, $text) === 0) {
        return $icon;
      }
    }
    return $iconClasses['*'];
  }

}
