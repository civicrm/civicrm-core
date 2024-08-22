<?php

/**
 * Class CRM_Afform_AfformScanner
 *
 * The AfformScanner searches the `ang` directory of extensions and `civicrm.files` for files
 * named `*.aff.*`. Each item is interpreted as a form instance.
 *
 * To reduce file-scanning, we keep a cache of file paths.
 */
class CRM_Afform_AfformScanner {

  const METADATA_JSON = 'aff.json';

  const METADATA_PHP = 'aff.php';

  const LAYOUT_FILE = 'aff.html';

  const FILE_REGEXP = '/\.aff\.(json|html|php)$/';

  const DEFAULT_REQUIRES = 'afCore';

  /**
   * @var CRM_Utils_Cache_Interface
   */
  protected $cache;

  /**
   * CRM_Afform_AfformScanner constructor.
   */
  public function __construct() {
    $this->cache = Civi::cache('long');
  }

  /**
   * Get a list of all forms and their file paths.
   *
   * @return array
   *   Ex: ['afformViewIndividual' => ['/var/www/foo/ang/afformViewIndividual']]
   */
  public function findFilePaths(): array {
    if ($this->isUseCachedPaths()) {
      $formPaths = $this->cache->get('afformAllPaths');
      if ($formPaths !== NULL) {
        return $formPaths;
      }
    }

    // List of folders to search
    $basePaths = [];
    // List of specific forms that we found
    $formPaths = [];

    $mapper = CRM_Extension_System::singleton()->getMapper();
    foreach ($mapper->getModules() as $module) {
      try {
        if ($module->is_active) {
          $basePaths[] = [
            'weight' => 0,
            'path' => dirname($mapper->keyToPath($module->name)) . DIRECTORY_SEPARATOR . 'ang',
            'module' => $module->name,
          ];
        }
      }
      catch (CRM_Extension_Exception_MissingException $e) {
        // If the extension is missing skip & continue.
      }
    }

    // Scan core ang/afform directory
    $basePaths[] = [
      'weight' => 100,
      'path' => Civi::paths()->getPath('[civicrm.root]/ang/afform'),
      'module' => 'civicrm',
    ];
    // Scan uploads/files directory
    $basePaths[] = [
      'weight' => 200,
      'path' => $this->getSiteLocalPath(),
      'module' => '',
    ];

    $event = \Civi\Core\Event\GenericHookEvent::create(['paths' => &$basePaths]);
    \Civi::dispatcher()->dispatch('civi.afform.searchPaths', $event);

    usort($basePaths, fn($a, $b) =>
      $a['weight'] === $b['weight']
        ? $b['module'] <=> $a['module']
        : $b['weight'] <=> $a['weight']
    );
    foreach ($basePaths as $basePath) {
      $this->appendFilePaths($formPaths, $basePath['path'], $basePath['module']);
    }

    if ($this->isUseCachedPaths()) {
      $this->cache->set('afformAllPaths', $formPaths);
    }
    return $formPaths;
  }

  /**
   * Is the cache to be used.
   *
   * Skipping the cache helps developers moving files around & messes with developers
   * debugging performance. It's a cruel world.
   *
   * FIXME: Use a separate setting. Maybe use the asset-builder cache setting?
   *
   * @return bool
   */
  private function isUseCachedPaths(): bool {
    return !CRM_Core_Config::singleton()->debug;
  }

  /**
   * Get the absolute path to the given file.
   *
   * @param string $formName
   *   Ex: 'afformViewIndividual'
   * @param string $suffix
   *   Ex: 'aff.json'
   * @return string|NULL
   *   Ex: '/var/www/sites/default/files/civicrm/ang/afform/afformViewIndividual.aff.json'
   */
  public function findFilePath(string $formName, string $suffix): ?string {
    $paths = $this->findFilePaths();

    if (isset($paths[$formName])) {
      foreach ($paths[$formName] as $path) {
        if (file_exists($path . '.' . $suffix)) {
          return $path . '.' . $suffix;
        }
      }
    }

    return NULL;
  }

  /**
   * Determine the path where we can write our own customized/overridden
   * version of a file.
   *
   * @param string $formName
   *   Ex: 'afformViewIndividual'
   * @param string $fileType
   *   Ex: 'aff.json'
   * @return string
   *   Ex: '/var/www/sites/default/files/civicrm/afform/afformViewIndividual.aff.json'
   */
  public function createSiteLocalPath(string $formName, string $fileType): string {
    return $this->getSiteLocalPath() . DIRECTORY_SEPARATOR . $formName . '.' . $fileType;
  }

  public function clear(): void {
    $this->cache->delete('afformAllPaths');
  }

  /**
   * Get metadata and optionally the layout for a file-based Afform.
   *
   * @param string $name
   *   Ex: 'afformViewIndividual'
   * @param bool $getLayout
   *   Whether to fetch 'layout' from the related html file.
   * @return array|null
   *   An array with some mix of the keys supported by getFields
   * @see \Civi\Api4\Afform::getFields
   */
  public function getMeta(string $name, bool $getLayout = FALSE): ?array {
    $defn = [];
    $mtime = NULL;

    $jsonFile = $this->findFilePath($name, self::METADATA_JSON);
    $htmlFile = $this->findFilePath($name, self::LAYOUT_FILE);

    // Meta file can be either php or json format.
    // Json takes priority because local overrides are always saved in that format.
    if ($jsonFile !== NULL) {
      $defn = json_decode(file_get_contents($jsonFile), 1);
      $mtime = filemtime($jsonFile);
    }
    // Extensions may provide afform definitions in php files
    else {
      $phpFile = $this->findFilePath($name, self::METADATA_PHP);
      if ($phpFile !== NULL) {
        $defn = include $phpFile;
        $mtime = filemtime($phpFile);
      }
    }
    if ($htmlFile !== NULL) {
      $mtime = max($mtime, filemtime($htmlFile));
      if ($getLayout) {
        // If the defn file included a layout, the html file overrides
        $defn['layout'] = file_get_contents($htmlFile);
      }
    }
    // All 3 files don't exist!
    elseif (!$defn) {
      return NULL;
    }
    $defn['name'] = $name;
    $defn['modified_date'] = date('Y-m-d H:i:s', $mtime);
    return $defn;
  }

  /**
   * Adds base_module, has_local & has_base to an afform metadata record
   *
   * @param array $record
   */
  public function addComputedFields(array &$record) {
    $name = $record['name'];
    // Ex: $allPaths['viewIndividual']['org.civicrm.foo'] == '/var/www/foo/ang/afformViewIndividual'].
    $allPaths = $this->findFilePaths()[$name] ?? [];
    // Empty string key refers to the site local path
    $record['has_local'] = isset($allPaths['']);
    if (!isset($record['has_base'])) {
      $record['base_module'] = \CRM_Utils_Array::first(array_filter(array_keys($allPaths)));
      $record['has_base'] = !empty($record['base_module']);
    }
  }

  /**
   * @deprecated unused function
   */
  public function getLayout($formName) {
    CRM_Core_Error::deprecatedFunctionWarning('APIv4');
    $filePath = $this->findFilePath($formName, self::LAYOUT_FILE);
    return $filePath === NULL ? NULL : file_get_contents($filePath);
  }

  /**
   * Get the effective metadata for all file-based forms.
   *
   * @return array
   *   A list of all forms, keyed by form name.
   *   NOTE: This is only data available in *.aff.(json|php) files. It does *NOT* include layout.
   *   Ex: ['afformViewIndividual' => ['title' => 'View an individual contact', ...]]
   */
  public function getMetas(): array {
    $result = [];
    foreach (array_keys($this->findFilePaths()) as $name) {
      $result[$name] = $this->getMeta($name);
    }
    return $result;
  }

  /**
   * @param array[] $formPaths
   *   List of all form paths.
   *   Ex: ['foo' => [0 => '/var/www/org.example.foobar/ang']]
   * @param string $parent
   *   Ex: '/var/www/org.example.foobar/afform/'
   * @param string $module
   *   Name of module or '' empty string for local files.
   */
  private function appendFilePaths(array &$formPaths, string $parent, string $module) {
    $files = preg_grep(self::FILE_REGEXP, (array) glob("$parent/*"));

    foreach ($files as $file) {
      $fileBase = preg_replace(self::FILE_REGEXP, '', $file);
      $name = basename($fileBase);
      $formPaths[$name][$module] = $fileBase;
    }
  }

  /**
   * Get the path where site-local form customizations are stored.
   *
   * @return string
   *   Ex: '/var/www/sites/default/files/civicrm/afform'.
   */
  public function getSiteLocalPath(): string {
    // TODO Allow a setting override.
    // return Civi::paths()->getPath(Civi::settings()->get('afformPath'));
    return Civi::paths()->getPath('[civicrm.files]/ang');
  }

}
