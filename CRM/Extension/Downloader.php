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
 * This class handles downloads of remotely-provided extensions
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Extension_Downloader {

  /**
   * @var CRM_Extension_Manager
   */
  private $manager;

  /**
   * @var string
   */
  private $containerDir;

  /**
   * @var string
   * Local path to a temporary data directory
   */
  public $tmpDir;

  /**
   * @var GuzzleHttp\Client
   */
  protected $guzzleClient;

  /**
   * @var CRM_Extension_Container_Basic
   * The place where downloaded extensions are ultimately stored
   */
  public $container;

  /**
   * @param CRM_Extension_Manager $manager
   * @param string $containerDir
   *   The place to store downloaded & extracted extensions.
   * @param string $tmpDir
   */
  public function __construct(CRM_Extension_Manager $manager, $containerDir, $tmpDir) {
    $this->manager = $manager;
    $this->containerDir = $containerDir;
    $this->tmpDir = $tmpDir;
  }

  /**
   * @return \GuzzleHttp\Client
   */
  public function getGuzzleClient(): \GuzzleHttp\Client {
    return $this->guzzleClient ?? new \GuzzleHttp\Client();
  }

  /**
   * @param \GuzzleHttp\Client $guzzleClient
   */
  public function setGuzzleClient(\GuzzleHttp\Client $guzzleClient) {
    $this->guzzleClient = $guzzleClient;
  }

  /**
   * Determine whether downloading is supported.
   *
   * @param \CRM_Extension_Info $extensionInfo Optional info for (updated) extension
   *
   * @return array
   *   list of error messages; empty if OK
   */
  public function checkRequirements($extensionInfo = NULL) {
    $errors = [];

    if (!$this->containerDir || !is_dir($this->containerDir) || !is_writable($this->containerDir)) {
      $civicrmDestination = urlencode(CRM_Utils_System::url('civicrm/admin/extensions', 'reset=1'));
      $url = CRM_Utils_System::url('civicrm/admin/setting/path', "reset=1&civicrmDestination={$civicrmDestination}");
      $errors[] = [
        'title' => ts('Directory Unwritable'),
        'message' => ts("Your extensions directory is not set or is not writable. Click <a href='%1'>here</a> to set the extensions directory.",
          [
            1 => $url,
          ]
        ),
      ];
    }

    if (!class_exists('ZipArchive')) {
      $errors[] = [
        'title' => ts('ZIP Support Required'),
        'message' => ts('You will not be able to install extensions at this time because your installation of PHP does not support ZIP archives. Please ask your system administrator to install the standard PHP-ZIP extension.'),
      ];
    }

    if ($extensionInfo) {
      $requiredExtensions = CRM_Extension_System::singleton()->getManager()->findInstallRequirements([$extensionInfo->key], $extensionInfo);
      foreach ($requiredExtensions as $extension) {
        if (CRM_Extension_System::singleton()->getManager()->getStatus($extension) !== CRM_Extension_Manager::STATUS_INSTALLED && $extension !== $extensionInfo->key) {
          $requiredExtensionInfo = CRM_Extension_System::singleton()->getBrowser()->getExtension($extension);
          $requiredExtensionInfoName = empty($requiredExtensionInfo->name) ? $extension : $requiredExtensionInfo->name;
          $errors[] = [
            'title' => ts('Missing Requirement: %1', [1 => $extension]),
            'message' => ts('You will not be able to install/upgrade %1 until you have installed the %2 extension.', [1 => $extensionInfo->name, 2 => $requiredExtensionInfoName]),
          ];
        }
      }
    }

    return $errors;
  }

  /**
   * Install or upgrade an extension from a remote URL.
   *
   * @param string $key
   *   The name of the extension being installed.
   * @param string $downloadUrl
   *   URL of a .zip file.
   * @return bool
   *   TRUE for success
   * @throws CRM_Extension_Exception
   */
  public function download($key, $downloadUrl) {
    $filename = $this->tmpDir . DIRECTORY_SEPARATOR . $key . '.zip';

    if (!$downloadUrl) {
      throw new CRM_Extension_Exception(ts('Cannot install this extension - downloadUrl is not set!'));
    }

    if (!$this->fetch($downloadUrl, $filename)) {
      return FALSE;
    }

    $extractedZipPath = $this->extractFiles($key, $filename);
    if (!$extractedZipPath) {
      return FALSE;
    }

    if (!$this->validateFiles($key, $extractedZipPath)) {
      return FALSE;
    }

    $this->manager->replace($extractedZipPath);

    return TRUE;
  }

  /**
   * Download the remote zipfile.
   *
   * @param string $remoteFile
   *   URL of a .zip file.
   * @param string $localFile
   *   Path at which to store the .zip file.
   * @return bool
   *   Whether the download was successful.
   */
  public function fetch($remoteFile, $localFile) {
    $client = $this->getGuzzleClient();
    $response = $client->request('GET', $remoteFile, ['sink' => $localFile, 'timeout' => \Civi::settings()->get('http_timeout')]);
    if ($response->getStatusCode() === 200) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Extract an extension from a zip file.
   *
   * @param string $key
   *   The name of the extension being installed; this usually matches the basedir in the .zip.
   * @param string $zipFile
   *   The local path to a .zip file.
   * @return string|FALSE
   *   zip file path
   */
  public function extractFiles($key, $zipFile) {
    $config = CRM_Core_Config::singleton();

    $zip = new ZipArchive();
    $res = $zip->open($zipFile);
    if ($res === TRUE) {
      $zipSubDir = CRM_Utils_Zip::guessBasedir($zip, $key);
      if ($zipSubDir === FALSE) {
        \Civi::log()->error('Unable to extract the extension: bad directory structure');
        CRM_Core_Session::setStatus(ts('Unable to extract the extension: bad directory structure'), '', 'error');
        return FALSE;
      }
      $extractedZipPath = $this->tmpDir . DIRECTORY_SEPARATOR . $zipSubDir;
      if (is_dir($extractedZipPath)) {
        if (!CRM_Utils_File::cleanDir($extractedZipPath, TRUE, FALSE)) {
          \Civi::log()->error('Unable to extract the extension {extension}: {path} cannot be cleared', [
            'extension' => $key,
            'path' => $extractedZipPath,
          ]);
          CRM_Core_Session::setStatus(ts('Unable to extract the extension: %1 cannot be cleared', [1 => $extractedZipPath]), ts('Installation Error'), 'error');
          return FALSE;
        }
      }
      if (!$zip->extractTo($this->tmpDir)) {
        \Civi::log()->error('Unable to extract the extension to {path}.', ['path' => $this->tmpDir]);
        CRM_Core_Session::setStatus(ts('Unable to extract the extension to %1.', [1 => $this->tmpDir]), ts('Installation Error'), 'error');
        return FALSE;
      }
      $zip->close();
    }
    else {
      \Civi::log()->error('Unable to extract the extension');
      CRM_Core_Session::setStatus(ts('Unable to extract the extension'), '', 'error');
      return FALSE;
    }

    return $extractedZipPath;
  }

  /**
   * Validate that $extractedZipPath contains valid for extension $key
   *
   * @param $key
   * @param $extractedZipPath
   *
   * @return bool
   * @throws CRM_Core_Exception
   */
  public function validateFiles($key, $extractedZipPath) {
    $filename = $extractedZipPath . DIRECTORY_SEPARATOR . CRM_Extension_Info::FILENAME;
    if (!is_readable($filename)) {
      \Civi::log()->error('Failed reading data from {filename} during installation', ['filename' => $filename]);
      CRM_Core_Session::setStatus(ts('Failed reading data from %1 during installation', [1 => $filename]), ts('Installation Error'), 'error');
      return FALSE;
    }

    try {
      $newInfo = CRM_Extension_Info::loadFromFile($filename);
    }
    catch (Exception $e) {
      \Civi::log()->error('Failed reading data from {filename} during installation', ['filename' => $filename]);
      CRM_Core_Session::setStatus(ts('Failed reading data from %1 during installation', [1 => $filename]), ts('Installation Error'), 'error');
      return FALSE;
    }

    if ($newInfo->key != $key) {
      throw new CRM_Core_Exception(ts('Cannot install - there are differences between extdir XML file and archive XML file!'));
    }

    return TRUE;
  }

}
