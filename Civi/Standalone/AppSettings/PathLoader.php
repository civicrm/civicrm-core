<?php

namespace Civi\Standalone\AppSettings;

use Civi\Standalone\AppSettings;

class PathLoader {
  protected array $paths = [];
  protected array $urls = [];

  public function __construct(array $pathStructure) {
    foreach ($pathStructure as $key => $config) {
      $this->paths[$key] = $config['default'] ?? NULL;

      if ($config['hasUrl']) {
        // setting the keys to NULL tell us these are valid
        // no explicit default - derived from paths
        $this->urls[$key] = NULL;
      }
    }
    // better default for web_root from server vars
    if ($_SERVER['DOCUMENT_ROOT'] ?? null) {
      // if available, this is a better default
      $this->paths['web_root'] = $_SERVER['DOCUMENT_ROOT'];
    }
  }

  public function getPath(string $key): ?string {
    return $this->paths[$key] ?? NULL;
  }

  public function getUrl(string $key): ?string {
    return $this->urls[$key] ?? NULL;
  }

  public function getPathAndUrlSettings(): array {
    $settings = [];

    foreach ($this->paths as $key => $value) {
      $settings['CIVICRM_PATH_' . strtoupper($key)] = $value;
    }

    foreach ($this->urls as $key => $value) {
      $settings['CIVICRM_URL_' . strtoupper($key)] = $value;
    }

    return $settings;
  }

  public function setPathsAndUrlsFromSettings(array $settings) {
    foreach ($settings as $settingName => $value) {
      if (strpos($settingName, 'CIVICRM_PATH_') === 0) {
        $key = strtolower(substr($settingName, 13));
        $this->paths[$key] = $value;
      }
      elseif (strpos($settingName, 'CIVICRM_URL_') === 0) {
        $key = strtolower(substr($settingName, 12));
        $this->urls[$key] = $value;
      }
      // else ignore, not a path or url setting
    }
  }

  public function resolvePathsAndUrls() {
    if (!($this->urls['web_root'] ?? NULL)) {
      // if no url has been provided, we will try to determine from the environment
      $this->urls['web_root'] = self::deriveWebRootUrl();
    }
    $this->resolvePaths();
    $this->resolveUrls();
  }

  protected function resolvePaths() {
    // collect our known values for future replacements
    $resolvedPaths = [];

    foreach ($this->paths as $key => $value) {
      $resolvedPaths[$key] = $this->resolvePath($key, $value, $resolvedPaths);
    }

    $this->paths = $resolvedPaths;
  }

  protected function resolvePath($key, $value, $resolvedPaths): ?string {
    switch ($key) {
      case 'packages':
        $value = $value ?: $this->findCivicrmPackagesDirectory($resolvedPaths['core']);
        break;

      case 'smarty_autoload':
        $value = $value ?: $this->smartyAutoloadDefault();
        break;

    }
    if ($value) {
      $value = self::replaceTokens($value, $resolvedPaths);
      $value = self::cleanPath($value);
    }

    return $value;
  }

  protected function smartyAutoloadDefault(): ?string {
    $baseUrl = $this->urls['web_root'] ?? NULL;
    if (strpos($baseUrl, 'localhost') !== FALSE || strpos($baseUrl, 'demo.civicrm.org') !== FALSE) {
      return '[packages]/smarty5/Smarty.php';
    }
    return NULL;
  }

  protected function resolveUrls() {
    $webRootPath = $this->paths['web_root'];
    $webRootUrl = $this->urls['web_root'];
    if (!$webRootUrl) {
      // if no url has been provided, we will try to determine from the environment
      $webRootUrl = self::deriveWebRootUrl();
      $this->urls['web_root'] = $webRootUrl;
    }
    foreach ($this->urls as $key => $value) {
      if (!$value) {
        $path = $this->paths[$key];
        // this may come back blank if we cant derive
        // maybe we should warn?
        $this->urls[$key] = self::deriveRelativeUrl($webRootUrl, $webRootPath, $path);
      }
      // $value = self::cleanUrl($value);
    }
  }

  /**
   * replace tokens in a string (not path specific?)
   *
   * specifically tokens like "[key]" are replaced with the value of $knownValues["key"]
   *
   * @return string
   */
  public static function replaceTokens(string $valueWithTokens, array $knownValues): string {
    // replace any parts which are already set tokens
    foreach ($knownValues as $key => $value) {
      $token = '[' . $key . ']';
      $valueWithTokens = str_replace($token, $value, $valueWithTokens);
    }

    // check for outstanding tokens
    // if (preg_match('/\[.+\]/', $valueWithTokens)) {
    //   // throw new \CRM_Core_Exception("Unreplaced tokens in setting value: " . $valueWithTokens);
    // }

    return $valueWithTokens;
  }

  /**
   * ensures correct directory separators and no trailing slash
   * @return string
   */
  public static function cleanPath(string $path): string {
    $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
    return rtrim($path, DIRECTORY_SEPARATOR);
  }

  public static function deriveWebRootUrl(): string {
    return (self::deriveScheme() . '://' . self::deriveHost());
  }

  protected static function deriveHost(): string {
    return AppSettings::get('CIVICRM_SITE_HOST') ?: $_SERVER['HTTP_HOST'] ?? 'default';
  }

  /**
   * @todo this might fail behind a proxy?
   */
  protected static function deriveScheme(): string {
    $schemeSetting = AppSettings::get('CIVICRM_SITE_SCHEME');

    if ($schemeSetting) {
      return $schemeSetting;
    }

    if ((!empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') ||
      (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ||
      (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443')) {
      return 'https';
    }

    return 'http';
  }

  public static function deriveRelativeUrl(string $webRootUrl, string $webRootPath, string $targetPath): ?string {
    if (strpos($targetPath, $webRootPath) !== 0) {
      // oh dear, we have a unset URL we cant derive
      // should this be an error?
      return NULL;
    }
    $relativePath = substr($targetPath, strlen($webRootPath));
    return $webRootUrl . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
  }

  protected function getCombinedPathAndUrl(string $key): array {
    return array_filter([
      'path' => $this->getPath($key),
      'url' => $this->urls[$key] ?? NULL,
    ]);
  }

  public function getUserFrameworkResourceUrl(): string {
    return (\Composer\InstalledVersions::isInstalled('civicrm/civicrm-asset-plugin'))
      ? $this->getUrl('public') . '/assets/civicrm/core' : $this->getUrl('core');
  }

  protected static function findCivicrmPackagesDirectory(string $corePath): ?string {
    // if not set explicitly, search in likely places
    $candidates = [
      [$corePath, 'packages'],
      [$corePath, 'civicrm-packages'],
      [$corePath, '..', 'civicrm-packages'],
    ];

    foreach ($candidates as $candidate) {
      $path = implode(DIRECTORY_SEPARATOR, $candidate);
      if (is_dir($path)) {
        return $path;
      }
    }
    // cant find it - not good but wont fail
    return NULL;
  }

  public function getCorePaths(): array {
    $keyMap = [
      'cms.root' => 'web_root',
      'civicrm.private' => 'private',
      'civicrm.compile' => 'compile',
      'civicrm.files' => 'public_uploads',
      'civicrm.l10n' => 'translations',
      'civicrm.tmp' => 'tmp',
      'civicrm.custom' => 'private_uploads',
      'civicrm.log' => 'log',
      'civicrm.root' => 'core',
      'civicrm.vendor' => 'vendor',
      'civicrm.packages' => 'packages',
      'civicrm.bower' => 'bower',
    ];

    $mapped = [];

    foreach ($keyMap as $theirKey => $ourKey) {
      $mapped[$theirKey] = $this->getCombinedPathAndUrl($ourKey);
    }

    return $mapped;
  }

  public function getDomainLevelSettings(): array {
    return array_filter([
      'extensionsDir' => $this->getPath('extensions'),
      'extensionsURL' => $this->getUrl('extensions'),
      'imageUploadDir' => $this->getPath('public_uploads'),
      'imageUploadURL' => $this->getUrl('public_uploads'),
      'uploadDir' => $this->getPath('tmp'),
      'customFileUploadDir' => $this->getPath('private_uploads'),
      'userFrameworkResourceURL' => $this->getUserFrameworkResourceUrl(),
    ]);
  }

}
