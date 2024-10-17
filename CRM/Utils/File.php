<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * class to provide simple static functions for file objects
 */
class CRM_Utils_File {

  /**
   * Used to remove md5 hash that was injected into uploaded file names.
   */
  const HASH_REMOVAL_PATTERN = '/_[a-f0-9]{32}\./';

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
   * @throws \CRM_Core_Exception
   */
  public static function cleanDir(string $target, bool $rmdir = TRUE, bool $verbose = TRUE) {
    static $exceptions = ['.', '..'];
    if (!$target || $target === '/') {
      throw new CRM_Core_Exception('Overly broad deletion');
    }

    $target = rtrim($target, '/' . DIRECTORY_SEPARATOR);

    if (!file_exists($target) && !is_link($target)) {
      return;
    }

    if (!is_dir($target)) {
      CRM_Core_Session::setStatus(ts('cleanDir() can only remove directories. %1 is not a directory.', [1 => $target]), ts('Warning'), 'error');
      return;
    }

    if (is_link($target) /* it's a directory based on a symlink... no need to recurse... */) {
      if ($rmdir) {
        static::try_unlink($target, 'symlink');
      }
      return;
    }

    if ($dh = @opendir($target)) {
      while (FALSE !== ($sibling = readdir($dh))) {
        if (!in_array($sibling, $exceptions)) {
          $object = $target . DIRECTORY_SEPARATOR . $sibling;
          if (is_link($object)) {
            // Strangely, symlinks to directories under Windows need special treatment
            if (PHP_OS_FAMILY === "Windows" && is_dir($object)) {
              if (!rmdir($object)) {
                CRM_Core_Session::setStatus(ts('Unable to remove directory symlink %1', [1 => $object]), ts('Warning'), 'error');
              }
            }
            else {
              CRM_Utils_File::try_unlink($object, "symlink");
            }
          }
          elseif (is_dir($object)) {
            CRM_Utils_File::cleanDir($object, TRUE, $verbose);
          }
          elseif (is_file($object)) {
            CRM_Utils_File::try_unlink($object, "file");
          }
          else {
            CRM_Utils_File::try_unlink($object, "other filesystem object");
          }
        }
      }
      closedir($dh);

      if ($rmdir) {
        if (rmdir($target)) {
          if ($verbose) {
            CRM_Core_Session::setStatus(ts('Removed directory %1', [1 => $target]), '', 'success');
          }
          return TRUE;
        }
        else {
          CRM_Core_Session::setStatus(ts('Unable to remove directory %1', [1 => $target]), ts('Warning'), 'error');
        }
      }
    }
  }

  /**
   * Helper function to avoid repetition in cleanDir: execute unlink and produce a warning on failure.
   */
  private static function try_unlink($object, $description) {
    if (!unlink($object)) {
      CRM_Core_Session::setStatus(ts('Unable to remove %1 %2', [1 => $description, 2 => $object]), ts('Warning'), 'error');
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
    return !($written === FALSE or !$closed);
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
    if (!in_array(substr($path, -1, 1), ['/', '\\'])) {
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
   * @param string|null $dsn
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
      throw new CRM_Core_Exception('Could not find the SQL file.');
    }

    self::runSqlQuery($dsn, file_get_contents($fileName), $prefix, $dieOnErrors);
  }

  /**
   * Runs an SQL query.
   *
   * @param string|null $dsn
   * @param string $queryString
   * @param string $prefix
   * @param bool $dieOnErrors
   *
   * @throws \CRM_Core_Exception
   */
  public static function runSqlQuery($dsn, $queryString, $prefix = NULL, $dieOnErrors = TRUE) {
    $string = $prefix . $queryString;

    if ($dsn === NULL) {
      $db = CRM_Core_DAO::getConnection();
    }
    else {
      require_once 'DB.php';
      $dsn = CRM_Utils_SQL::autoSwitchDSN($dsn);
      try {
        $options = CRM_Utils_SQL::isSSLDSN($dsn) ? ['ssl' => TRUE] : [];
        $db = DB::connect($dsn, $options);
      }
      catch (Exception $e) {
        throw new CRM_Core_Exception("Cannot open $dsn: " . $e->getMessage());
      }
    }

    $db->query('SET NAMES utf8mb4');
    $transactionId = CRM_Utils_Type::escape(CRM_Utils_Request::id(), 'String');
    $db->query('SET @uniqueID = ' . "'$transactionId'");

    // get rid of comments starting with # and --

    $string = self::stripComments($string);

    $queries = preg_split('/;\s*$/m', $string);
    foreach ($queries as $query) {
      $query = trim($query);
      if (!empty($query)) {
        CRM_Core_Error::debug_query($query);
        try {
          $res = &$db->query($query);
        }
        catch (Exception $e) {
          if ($dieOnErrors) {
            throw new CRM_Core_Exception("Cannot execute $query: " . $e->getMessage());
          }
          else {
            echo "Cannot execute $query: " . $e->getMessage() . "<p>";
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
    return preg_replace("/^(#|--).*\R*/m", "", ($string ?? ''));
  }

  /**
   * @param string $ext
   *
   * @return bool
   */
  public static function isExtensionSafe($ext) {
    if (!isset(Civi::$statics[__CLASS__]['file_extensions'])) {
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
      Civi::$statics[__CLASS__]['file_extensions'] = $extensions;
    }
    $restricted = CRM_Utils_Constant::value('CIVICRM_RESTRICTED_UPLOADS', '/(php|php\d|phtml|phar|pl|py|cgi|asp|js|sh|exe|pcgi\d)/i');
    // support lower and uppercase file extensions
    return (bool) isset(Civi::$statics[__CLASS__]['file_extensions'][strtolower($ext)]) && !preg_match($restricted, strtolower($ext));
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
    $full_filepath = stream_resolve_include_path($name);
    if ($full_filepath === FALSE) {
      return FALSE;
    }
    return is_readable($full_filepath);
  }

  /**
   * Remove 32 bit md5 hash prepended to the file suffix.
   *
   * Note: if the filename was munged with an `.unknown` suffix, this removes
   * the md5 but doesn't undo the munging or remove the `.unknown` suffix.
   *
   * @param $name
   *
   * @return mixed
   */
  public static function cleanFileName($name) {
    // replace the last 33 character before the '.' with null
    $name = preg_replace(self::HASH_REMOVAL_PATTERN, '.', $name);
    return $name;
  }

  /**
   * Make a valid file name.
   *
   * @param string $name
   * @param bool $unicode
   *
   * @return string
   */
  public static function makeFileName($name, bool $unicode = FALSE) {
    $uniqID = md5(uniqid(rand(), TRUE));
    $info = pathinfo($name);
    $basename = substr($info['basename'],
      0, -(strlen($info['extension'] ?? '') + (($info['extension'] ?? '') == '' ? 0 : 1))
    );
    if (!self::isExtensionSafe($info['extension'] ?? '')) {
      if ($unicode) {
        return self::makeFilenameWithUnicode("{$basename}_" . ($info['extension'] ?? '') . "_{$uniqID}", '_', 240) . ".unknown";
      }
      // munge extension so it cannot have an embbeded dot in it
      // The maximum length of a filename for most filesystems is 255 chars.
      // We'll truncate at 240 to give some room for the extension.
      return CRM_Utils_String::munge("{$basename}_" . ($info['extension'] ?? '') . "_{$uniqID}", '_', 240) . ".unknown";
    }
    else {
      if ($unicode) {
        return self::makeFilenameWithUnicode("{$basename}_{$uniqID}", '_', 240) . "." . ($info['extension'] ?? '');
      }
      return CRM_Utils_String::munge("{$basename}_{$uniqID}", '_', 240) . "." . ($info['extension'] ?? '');
    }
  }

  /**
   * CRM_Utils_String::munge() doesn't handle unicode and needs to be able
   * to generate valid database tablenames so will sometimes generate a
   * random string. Here what we want is a human-sensible filename that might
   * contain unicode.
   * Note that this does filter out emojis and such, but keeps characters that
   * are considered alphanumeric in non-english languages.
   *
   * @param string $input
   * @param string $replacementString Character or string to replace invalid characters with. Can be the empty string.
   * @param int $cutoffLength Length to truncate the result after replacements.
   * @return string
   */
  public static function makeFilenameWithUnicode(string $input, string $replacementString = '_', int $cutoffLength = 63): string {
    $filename = preg_replace('/\W/u', $replacementString, $input);
    if ($cutoffLength) {
      return mb_substr($filename, 0, $cutoffLength);
    }
    return $filename;
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
    $files = [];
    if ($dh = opendir($path)) {
      while (FALSE !== ($elem = readdir($dh))) {
        if (substr($elem, -(strlen($ext) + 1)) == '.' . $ext) {
          $files[] = $path . $elem;
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
# OpenLiteSpeed 1.4.38+
  <IfModule !authz_core_module>
    RewriteRule .* - [F,L]
  </IfModule>

# Apache 2.2
  <IfModule !authz_core_module>
    Order allow,deny
    Deny from all
  </IfModule>

# Apache 2.4+
  <IfModule authz_core_module>
    Require all denied
  </IfModule>
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
   * (Deprecated) Create the file-path from which all other internal paths are
   * computed. This implementation determines it as `dirname(CIVICRM_TEMPLATE_COMPILEDIR)`.
   *
   * This approach is problematic - e.g. it prevents one from authentically
   * splitting the CIVICRM_TEMPLATE_COMPILEDIR away from other dirs. The implementation
   * is preserved for backwards compatibility (and should only be called by
   * CMS-adapters and by Civi\Core\Paths).
   *
   * Do not use it for new path construction logic. Instead, use Civi::paths().
   *
   * @deprecated
   * @see \Civi::paths()
   * @see \Civi\Core\Paths
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
   * @param string $basePath
   *   The base path when evaluating relative paths. Should include trailing slash.
   *
   * @return string
   */
  public static function absoluteDirectory($directory, $basePath) {
    // check if directory is already absolute, if so return immediately
    // Note: Windows PHP accepts any mix of "/" or "\", so "C:\htdocs" or "C:/htdocs" would be a valid absolute path
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && preg_match(';^[a-zA-Z]:[/\\\\];', $directory)) {
      return $directory;
    }

    // check if directory is already absolute, if so return immediately
    if (substr($directory, 0, 1) == DIRECTORY_SEPARATOR) {
      return $directory;
    }

    if ($basePath === NULL) {
      // Previous versions interpreted `NULL` to mean "default to `self::baseFilePath()`".
      // However, no code in the known `universe` relies on this interpretation, and
      // the `baseFilePath()` function is problematic/deprecated.
      throw new \RuntimeException("absoluteDirectory() requires specifying a basePath");
    }

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
   * @param int|null $maxDepth
   *   Maximum depth of subdirs to check.
   *   For no limit, use NULL.
   *
   * @return array(string)
   */
  public static function findFiles($dir, $pattern, $relative = FALSE, ?int $maxDepth = NULL) {
    if (!is_dir($dir) || !is_readable($dir)) {
      return [];
    }
    // Which dirs should we exclude from our searches?
    // If not defined, we default to excluding any dirname that begins
    // with a . which is the old behaviour and therefore excludes .git/
    $excludeDirsPattern = defined('CIVICRM_EXCLUDE_DIRS_PATTERN')
      ? constant('CIVICRM_EXCLUDE_DIRS_PATTERN')
      : '@' . preg_quote(DIRECTORY_SEPARATOR) . '\.@';

    $dir = rtrim($dir, '/' . DIRECTORY_SEPARATOR);
    $baseDepth = static::findPathDepth($dir);
    $todos = [$dir];
    $result = [];
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
      // Find subdirs to recurse into.
      $depth = static::findPathDepth($subdir) - $baseDepth + 1;
      if ($maxDepth === NULL || $depth <= $maxDepth) {
        $subdirs = glob("$subdir/*", GLOB_ONLYDIR);
        if (!empty($excludeDirsPattern)) {
          $subdirs = preg_grep($excludeDirsPattern, $subdirs, PREG_GREP_INVERT);
        }
        $todos = array_merge($todos, $subdirs);
      }
    }
    return $result;
  }

  /**
   * Determine the absolute depth of a path expression.
   *
   * @param string $path
   *   Ex: '/var/www/foo'
   * @return int
   *   Ex: 3
   */
  private static function findPathDepth(string $path): int {
    // Both PHP-Unix and PHP-Windows support '/'s. Additionally, PHP-Windows also supports '\'s.
    // They are roughly equivalent. (The differences are described by a secret book hidden in the tower of Mordor.)
    $depth = substr_count($path, '/');
    if (DIRECTORY_SEPARATOR !== '/') {
      $depth += substr_count($path, DIRECTORY_SEPARATOR);
    }
    return $depth;
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
      if ($parent === FALSE || $child === FALSE) {
        return FALSE;
      }
    }

    // windows fix
    $parent = str_replace(DIRECTORY_SEPARATOR, '/', $parent);
    $child = str_replace(DIRECTORY_SEPARATOR, '/', $child);

    $parentParts = explode('/', rtrim($parent, '/'));
    $childParts = explode('/', rtrim($child, '/'));
    while (($parentPart = array_shift($parentParts)) !== NULL) {
      $childPart = array_shift($childParts);
      if ($parentPart != $childPart) {
        return FALSE;
      }
    }
    if (empty($childParts)) {
      // same directory
      return FALSE;
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
      CRM_Core_Session::setStatus(ts('Failed to clean temp dir: %1', [1 => $fromDir]), '', 'alert');
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
  public static function formatFile(&$param, $fileName, $extraParams = []) {
    if (empty($param[$fileName])) {
      return;
    }

    $fileParams = [
      'uri' => $param[$fileName]['name'],
      'type' => $param[$fileName]['type'],
      'location' => $param[$fileName]['name'],
      'upload_date' => date('YmdHis'),
    ] + $extraParams;

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
        [$imageWidth, $imageHeight] = getimagesize($path);
        [$imageThumbWidth, $imageThumbHeight] = CRM_Contact_BAO_Contact::getThumbSize($imageWidth, $imageHeight);
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
    $fileExtension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    //According to (https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Complete_list_of_MIME_types),
    // there are some extensions that would need translating.:
    $translateMimeTypes = [
      'tif' => 'tiff',
      'jpg' => 'jpeg',
      'svg' => 'svg+xml',
    ];
    $mimeType = 'image/' . CRM_Utils_Array::value(
      $fileExtension,
      $translateMimeTypes,
      $fileExtension
    );

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
      if (strpos(($mimeType ?? ''), $text) === 0) {
        return $icon;
      }
    }
    return $iconClasses['*'];
  }

  /**
   * Is the filename a safe and valid filename passed in from URL
   *
   * @param string $fileName
   * @return bool
   */
  public static function isValidFileName($fileName = NULL) {
    if ($fileName) {
      $check = ($fileName === basename($fileName));
      if ($check) {
        if (substr($fileName, 0, 1) == '/' || substr($fileName, 0, 1) == '.' || substr($fileName, 0, 1) == DIRECTORY_SEPARATOR) {
          $check = FALSE;
        }
      }
      return $check;
    }
    return FALSE;
  }

  /**
   * Get the extensions that this MimeTpe is for
   * @param string $mimeType the mime-type we want extensions for
   * @return array
   */
  public static function getAcceptableExtensionsForMimeType($mimeType = []) {
    $mimeRepostory = new \MimeTyper\Repository\ExtendedRepository();
    return $mimeRepostory->findExtensions($mimeType);
  }

  /**
   * Get the extension of a file based on its path
   * @param string $path path of the file to query
   * @return string
   */
  public static function getExtensionFromPath($path) {
    return pathinfo($path, PATHINFO_EXTENSION);
  }

  /**
   * Wrapper for is_dir() to avoid flooding logs when open_basedir is used.
   *
   * Don't use this function as a swap-in replacement for is_dir() for all
   * situations as this might silence errors that you want to know about
   * and would help troubleshoot problems. It should only be used when
   * doing something like iterating over a set of folders where you know some
   * of them might not legitimately exist or might be outside open_basedir
   * because you're trying to find the right one. If you expect the path you're
   * checking to be inside open_basedir, then you should use the regular
   * is_dir(). (e.g. it might not exist but might be something
   * like a cache folder in templates_c, which can't be outside open_basedir,
   * so there you would use regular is_dir).
   *
   * **** Security alert ****
   * If you change this function so that it would be possible to return
   * TRUE without checking the real value of is_dir() then it opens up a
   * possible security issue.
   * It should either return FALSE, or the value returned from is_dir().
   *
   * @param string|null $dir
   * @return bool|null
   *   In php8 the return value from is_dir() is always bool but in php7 it can be null.
   */
  public static function isDir(?string $dir) {
    if ($dir === NULL) {
      return FALSE;
    }
    set_error_handler(function($errno, $errstr) {
      // If this is open_basedir-related, convert it to an exception so we
      // can catch it.
      if (strpos($errstr, 'open_basedir restriction in effect') !== FALSE) {
        throw new \ErrorException($errstr, $errno);
      }
      // Continue with normal error handling so other errors still happen.
      return FALSE;
    });
    try {
      $is_dir = is_dir($dir);
    }
    catch (\ErrorException $e) {
      $is_dir = FALSE;
    }
    finally {
      restore_error_handler();
    }
    return $is_dir;
  }

  /**
   * Get the maximum file size permitted for upload.
   *
   * This function contains logic to check the server setting if none
   * is configured. It is unclear if this is still relevant but perhaps it is no
   * harm-no-foul.
   *
   * @return int
   *   Size in mega-bytes.
   */
  public static function getMaxFileSize(): int {
    $maxFileSizeMegaBytes = \Civi::settings()->get('maxFileSize');
    //Fetch maxFileSizeMegaBytes from php_ini when $config->maxFileSize is set to "no limit".
    if (empty($maxFileSizeMegaBytes)) {
      $maxFileSizeMegaBytes = round((CRM_Utils_Number::formatUnitSize(ini_get('upload_max_filesize')) / (1024 * 1024)), 2);
    }
    return $maxFileSizeMegaBytes;
  }

}
