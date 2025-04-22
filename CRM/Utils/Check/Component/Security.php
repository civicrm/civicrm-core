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
class CRM_Utils_Check_Component_Security extends CRM_Utils_Check_Component {

  /**
   * CMS have a different pattern to their default file path and URL.
   *
   * @todo Use Civi::paths instead?
   * @return string
   */
  public function getFilePathMarker() {
    $config = CRM_Core_Config::singleton();
    switch ($config->userFramework) {
      case 'Joomla':
        return '/media/';

      case 'WordPress':
        return '/uploads/';

      default:
        return '/files/';
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
   * @return CRM_Utils_Check_Message[]
   * @see CRM-14091
   */
  public function checkLogFileIsNotAccessible() {
    $messages = [];

    $config = CRM_Core_Config::singleton();

    CRM_Core_Error::createDebugLogger();
    $log_filename = str_replace('\\', '/', CRM_Core_Error::generateLogFileName(''));

    $filePathMarker = $this->getFilePathMarker();

    // Hazard a guess at the URL of the logfile, based on common
    // CiviCRM layouts.
    if ($upload_url = explode($filePathMarker, $config->imageUploadURL)) {
      $url[] = $upload_url[0];
      if ($log_path = explode($filePathMarker, $log_filename)) {
        // CRM-17149: check if debug log path includes $filePathMarker
        if (count($log_path) > 1) {
          $url[] = $log_path[1];
          $log_url = implode($filePathMarker, $url);
          if ($this->fileExists($log_url)) {
            $docs_url = $this->createDocUrl('the-log-file-should-not-be-accessible');
            $msg = ts('The <a %1>CiviCRM debug log</a> should not be downloadable.', [1 => "href='$log_url'"])
              . '<br />'
              . '<a href="' . $docs_url . '">' . ts('Read more about this warning') . '</a>';
            $messages[] = new CRM_Utils_Check_Message(
              __FUNCTION__,
              $msg,
              ts('Security Warning'),
              \Psr\Log\LogLevel::WARNING,
              'fa-lock'
            );
          }
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
   * @return CRM_Utils_Check_Message[]
   * @see CRM-14091
   *
   * @todo Test with WordPress, Joomla.
   */
  public function checkUploadsAreNotAccessible() {
    if ($this->isLimitedDevelopmentServer()) {
      return [];
    }

    $messages = [];

    $config = CRM_Core_Config::singleton();
    $privateDirs = [
      $config->uploadDir,
      $config->customFileUploadDir,
    ];

    foreach ($privateDirs as $privateDir) {
      $heuristicUrl = $this->guessUrl($privateDir);
      if ($this->isDirAccessible($privateDir, $heuristicUrl)) {
        $messages[] = new CRM_Utils_Check_Message(
          __FUNCTION__,
          ts('Files in the data directory (<a href="%3">%2</a>) should not be downloadable.'
            . '<br />'
            . '<a href="%1">Read more about this warning</a>',
            [
              1 => $this->createDocUrl('uploads-should-not-be-accessible'),
              2 => $privateDir,
              3 => $heuristicUrl,
            ]),
            ts('Private Files Readable'),
            \Psr\Log\LogLevel::WARNING,
            'fa-lock'
        );
      }
    }

    return $messages;
  }

  /**
   * Some security checks require sending a real HTTP request. This breaks the single-threading
   * model historically used by the PHP built-in webserver (for local development). There is some
   * experimental support for multi-threading in PHP 7.4+. Anecdotally, this is still insufficient
   * on PHP 7.4 -- but it works well enough on PHP 8.1.
   *
   * @return CRM_Utils_Check_Message[]
   */
  public function checkHttpAuditable() {
    $messages = [];
    if ($this->isLimitedDevelopmentServer()) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        // No ts since end users should never see this
        'The built-in php HTTP server has no configuration options to secure folders, and so there is no point testing if they are secure. This problem only affects local development and E2E testing.',
        'Incomplete Security Checks',
        \Psr\Log\LogLevel::WARNING,
        'fa-lock'
      );
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
   * @return CRM_Utils_Check_Message[]
   * @see CRM-14091
   *
   * @todo Test with WordPress, Joomla.
   */
  public function checkDirectoriesAreNotBrowseable() {
    if ($this->isLimitedDevelopmentServer()) {
      return [];
    }

    $messages = [];
    $config = CRM_Core_Config::singleton();
    $publicDirs = [
      $config->imageUploadDir => $config->imageUploadURL,
    ];

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
        $docs_url = $this->createDocUrl('directories-should-not-be-browsable');
        $messages[] = new CRM_Utils_Check_Message(
          __FUNCTION__,
          ts($msg, [1 => $publicDir, 2 => $publicDir, 3 => $docs_url]),
          ts('Browseable Directories'),
          \Psr\Log\LogLevel::ERROR,
          'fa-lock'
        );
      }
    }

    return $messages;
  }

  /**
   * Check that the site is configured with a signing-key.
   *
   * The current infrastructure for signatures was introduced circa 5.36. Specifically,
   * most sites should now define `CIVICRM_SIGN_KEYS`. However, this could be missing for
   * sites which either (a) upgraded from an earlier release or (b) used an unpatched installer.
   *
   * @return CRM_Utils_Check_Message[]
   */
  public function checkSigningKey(): array {
    $messages = [];

    try {
      $found = !empty(Civi::service('crypto.registry')->findKey('SIGN'));
      // Subtle point: We really want to know if there are any `SIGN`ing keys. The most
      // typical way to define `SIGN`ing keys is to configure `CIVICRM_SIGN_KEYS`.
    }
    catch (\Civi\Crypto\Exception\CryptoException $e) {
      $found = FALSE;
    }
    if (!$found) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('The system requires a cryptographic signing key. Please configure <a %1>CIVICRM_SIGN_KEYS</a>. ',
          [1 => 'href="https://docs.civicrm.org/sysadmin/en/latest/setup/secret-keys/" target="_blank"']
        ),
        ts('Signing Key Required'),
        \Psr\Log\LogLevel::ERROR,
        'fa-lock'
      );
    }

    return $messages;
  }

  /**
   * Check that some files are not present.
   *
   * These files have generally been deleted but Civi source tree but could be
   * left online if one does a faulty upgrade.
   *
   * @return CRM_Utils_Check_Message[]
   */
  public function checkFilesAreNotPresent() {
    $packages_path = rtrim(\Civi::paths()->getPath('[civicrm.packages]/'), '/' . DIRECTORY_SEPARATOR);
    $vendor_path = rtrim(\Civi::paths()->getPath('[civicrm.vendor]/'), '/' . DIRECTORY_SEPARATOR);

    $messages = [];
    $files = [
      [
        // CRM-16005, upgraded from Civi <= 4.5.6
        "{$packages_path}/dompdf/dompdf.php",
        \Psr\Log\LogLevel::CRITICAL,
      ],
      [
        // CRM-16005, Civi >= 4.5.7
        "{$packages_path}/vendor/dompdf/dompdf/dompdf.php",
        \Psr\Log\LogLevel::CRITICAL,
      ],
      [
        // CRM-16005, Civi >= 4.6.0
        "{$vendor_path}/dompdf/dompdf/dompdf.php",
        \Psr\Log\LogLevel::CRITICAL,
      ],
      [
        // CIVI-SA-2013-001
        "{$packages_path}/OpenFlashChart/php-ofc-library/ofc_upload_image.php",
        \Psr\Log\LogLevel::CRITICAL,
      ],
    ];
    foreach ($files as $file) {
      if (file_exists($file[0])) {
        $messages[] = new CRM_Utils_Check_Message(
          __FUNCTION__,
          ts('File \'%1\' presents a security risk and should be deleted.', [1 => $file[0]]),
          ts('Unsafe Files'),
          $file[1],
          'fa-lock'
        );
      }
    }
    return $messages;
  }

  /**
   * Discourage use of remote profile forms.
   * @return CRM_Utils_Check_Message[]
   */
  public function checkRemoteProfile() {
    $messages = [];

    if (Civi::settings()->get('remote_profile_submissions')) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('Warning: External profile support (aka "HTML Snippet" support) is enabled in <a href="%1">system settings</a>. This setting may be prone to abuse. If you must retain it, consider HTTP throttling or other protections.',
          [1 => CRM_Utils_System::url('civicrm/admin/setting/misc', 'reset=1')]
        ),
        ts('Remote Profiles Enabled'),
        \Psr\Log\LogLevel::WARNING,
        'fa-lock'
      );
    }

    return $messages;
  }

  /**
   * Check to see if anonymous user has excessive permissions.
   * @return CRM_Utils_Check_Message[]
   */
  public function checkAnonPermissions() {
    $messages = [];
    $permissions = [];
    // These specific permissions were referenced in a security submission.
    // This functionality is generally useful -- may be good to expand to a longer list.
    $checkPerms = ['access CiviContribute', 'edit contributions'];
    foreach ($checkPerms as $checkPerm) {
      if (CRM_Core_Config::singleton()->userPermissionClass->check($checkPerm, 0)) {
        $permissions[] = $checkPerm;
      }
    }
    if (!empty($permissions)) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('The system configuration grants anonymous users an <em>unusually broad</em> list of permissions. This could compromise security. Please reassess whether these permissions are required: %1', [
          1 => '<ul><li><tt>' . implode('</tt></li><li><tt>', $permissions) . '</tt></li></ul>',
        ]),
        ts('Unusual Permissions for Anonymous Users'),
        \Psr\Log\LogLevel::WARNING,
        'fa-lock'
      );
    }
    return $messages;
  }

  public function isLimitedDevelopmentServer(): bool {
    return PHP_SAPI === 'cli-server';
  }

  /**
   * Determine whether $url is a public, browsable listing for $dir
   *
   * @param string $dir
   *   Local dir path.
   * @param string $url
   *   Public URL.
   * @return bool
   */
  public function isBrowsable($dir, $url) {
    if (empty($dir) || empty($url) || !is_dir($dir)) {
      return FALSE;
    }

    $result = FALSE;

    // this could be a new system with no uploads (yet) -- so we'll make a file
    $file = CRM_Utils_File::createFakeFile($dir);

    if ($file === FALSE) {
      // Couldn't write the file
      return FALSE;
    }

    // Since this can be confusing as to how this works:
    // $url corresponds to $dir not $file, but we're not checking if we can
    // retrieve $file, we're checking if retrieving $url gives us a LISTING of
    // the files in $dir. So $content is that listing, and then the stristr
    // is checking if $file, which is the bare filename (e.g. "delete-this-123")
    // is contained in that listing (which would be undesirable).
    $content = '';
    try {
      $response = (new \GuzzleHttp\Client())->request('GET', $url, [
        'timeout' => \Civi::settings()->get('http_timeout'),
      ]);
      $content = $response->getBody()->getContents();
    }
    catch (\GuzzleHttp\Exception\GuzzleException $e) {
    }
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
   * @param string $dir
   *   Local dir path.
   * @param string $url
   *   Public URL.
   * @return bool
   */
  public function isDirAccessible($dir, $url) {
    $url = rtrim($url, '/');
    if (empty($dir) || empty($url) || !is_dir($dir)) {
      return FALSE;
    }

    $result = FALSE;
    $file = CRM_Utils_File::createFakeFile($dir, 'delete me');

    if ($file === FALSE) {
      // Couldn't write the file
      return FALSE;
    }

    // @todo why call fileExists before doing almost the same thing. It's slightly different than reading the file's content, but is it necessary?
    if ($this->fileExists("$url/$file")) {
      $content = '';
      try {
        $response = (new \GuzzleHttp\Client())->request('GET', "$url/$file", [
          'timeout' => \Civi::settings()->get('http_timeout'),
        ]);
        $content = $response->getBody()->getContents();
      }
      catch (\GuzzleHttp\Exception\GuzzleException $e) {
      }
      if (preg_match('/delete me/', $content)) {
        $result = TRUE;
      }
    }

    unlink("$dir/$file");

    return $result;
  }

  /**
   * @param string $topic
   *
   * @return string
   */
  public function createDocUrl($topic) {
    return CRM_Utils_System::docURL2('sysadmin/setup/security#' . $topic, TRUE);
  }

  /**
   * Make a guess about the URL that corresponds to $targetDir.
   *
   * @param string $targetDir
   *   Local path to a directory.
   * @return string
   *   a guessed URL for $realDir
   */
  public function guessUrl($targetDir) {
    $filePathMarker = $this->getFilePathMarker();
    $config = CRM_Core_Config::singleton();

    list($heuristicBaseUrl) = explode($filePathMarker, $config->imageUploadURL);
    list(, $heuristicSuffix) = array_pad(explode($filePathMarker, str_replace('\\', '/', $targetDir)), 2, '');
    return $heuristicBaseUrl . $filePathMarker . $heuristicSuffix;
  }

}
