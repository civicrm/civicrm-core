<?php

/**
 * Class CRM_Afform_AfformScanner
 *
 * The AfformScanner searches the extensions and `civicrm.files` for subfolders
 * named `afform`. Each item in there is interpreted as a form instance.
 *
 * To reduce file-scanning, we keep a cache of file paths.
 */
class CRM_Afform_AfformScanner {

  const METADATA_FILE = 'aff.json';

  const LAYOUT_FILE = 'aff.html';

  const FILE_REGEXP = '/\.aff\.(json|html)$/';

  const DEFAULT_REQUIRES = 'afformCore';

  /**
   * @var CRM_Utils_Cache_Interface
   */
  protected $cache;

  /**
   * CRM_Afform_AfformScanner constructor.
   */
  public function __construct() {
    // TODO Manage this is a service, and inject the cache service.
    $this->cache = new CRM_Utils_Cache_SqlGroup([
      'group' => md5('afform_' . CRM_Core_Config_Runtime::getId() . $this->getSiteLocalPath()),
      'prefetch' => FALSE,
    ]);
    // $this->cache = new CRM_Utils_Cache_Arraycache([]);
  }

  /**
   * Get a list of all forms and their file paths.
   *
   * @return array
   *   Ex: ['view-individual' => ['/var/www/foo/afform/view-individual']]
   */
  public function findFilePaths() {
    if (!CRM_Core_Config::singleton()->debug) {
      // FIXME: Use a separate setting. Maybe use the asset-builder cache setting?
      $paths = $this->cache->get('allPaths');
      if ($paths !== NULL) {
        return $paths;
      }
    }

    $paths = array();

    $mapper = CRM_Extension_System::singleton()->getMapper();
    foreach ($mapper->getModules() as $module) {
      /** @var $module CRM_Core_Module */
      if ($module->is_active) {
        $this->appendFilePaths($paths, dirname($mapper->keyToPath($module->name)) . DIRECTORY_SEPARATOR . 'ang', 20);
      }
    }

    $this->appendFilePaths($paths, $this->getSiteLocalPath(), 10);

    $this->cache->set('allPaths', $paths);
    return $paths;
  }

  /**
   * Get the full path to the given file.
   *
   * @param string $formName
   *   Ex: 'view-individual'
   * @param string $suffix
   *   Ex: 'aff.json'
   * @return string|NULL
   *   Ex: '/var/www/sites/default/files/civicrm/afform/view-individual.aff.json'
   */
  public function findFilePath($formName, $suffix) {
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
   * Determine the path where we can write our own customized/overriden
   * version of a file.
   *
   * @param string $formName
   *   Ex: 'view-individual'
   * @param string $file
   *   Ex: 'aff.json'
   * @return string|NULL
   *   Ex: '/var/www/sites/default/files/civicrm/afform/view-individual.aff.json'
   */
  public function createSiteLocalPath($formName, $file) {
    return $this->getSiteLocalPath() . DIRECTORY_SEPARATOR . $formName . '.' . $file;
  }

  public function clear() {
    $this->cache->flush();
  }

  /**
   * Get the effective metadata for a form.
   *
   * @param string $name
   *   Ex: 'view-individual'
   * @return array
   *   An array with some mix of the following keys: name, title, description, client_route, server_route, requires.
   *   NOTE: This is only data available in *.aff.json. It does *NOT* include layout.
   *   Ex: [
   *     'name' => 'view-individual',
   *     'title' => 'View an individual contact',
   *     'server_route' => 'civicrm/view-individual',
   *     'requires' => ['afform'],
   *   ]
   */
  public function getMeta($name) {
    // FIXME error checking

    $defaults = [
      'name' => $name,
      'requires' => explode(',', self::DEFAULT_REQUIRES),
      'title' => '',
      'description' => '',
      'is_public' => FALSE,
    ];

    $metaFile = $this->findFilePath($name, self::METADATA_FILE);
    if ($metaFile !== NULL) {
      return array_merge($defaults, json_decode(file_get_contents($metaFile), 1));
    }
    elseif ($this->findFilePath($name, self::LAYOUT_FILE)) {
      return $defaults;
    }
    else {
      return NULL;
    }
  }

  /**
   * Get the effective metadata for all forms.
   *
   * @return array
   *   A list of all forms, keyed by form name.
   *   NOTE: This is only data available in *.aff.json. It does *NOT* include layout.
   *   Ex: ['view-individual' => ['title' => 'View an individual contact', ...]]
   */
  public function getMetas() {
    $result = array();
    foreach (array_keys($this->findFilePaths()) as $name) {
      $result[$name] = $this->getMeta($name);
    }
    return $result;
  }

  /**
   * @param array $formPaths
   *   List of all form paths.
   *   Ex: ['foo' => [0 => '/var/www/org.example.foobar/ang']]
   * @param string $parent
   *   Ex: '/var/www/org.example.foobar/afform/'
   * @param int $priority
   *   Lower priority files override higher priority files.
   */
  private function appendFilePaths(&$formPaths, $parent, $priority) {
    $files = preg_grep(self::FILE_REGEXP, (array) glob("$parent/*"));

    foreach ($files as $file) {
      $fileBase = preg_replace(self::FILE_REGEXP, '', $file);
      $name = _afform_angular_module_name(basename($fileBase));
      $formPaths[$name][$priority] = $fileBase;
      ksort($formPaths[$name]);
    }
  }

  /**
   * Get the path where site-local form customizations are stored.
   *
   * @return mixed|string
   *   Ex: '/var/www/sites/default/files/civicrm/afform'.
   */
  private function getSiteLocalPath() {
    // TODO Allow a setting override.
    // return Civi::paths()->getPath(Civi::settings()->get('afformPath'));
    return Civi::paths()->getPath('[civicrm.files]/ang');
  }

  /**
   * @return string
   */
  private function getMarkerRegexp() {
    static $v;
    if ($v === NULL) {
      $v = '/\.(' . preg_quote(self::LAYOUT_FILE, '/') . '|' . preg_quote(self::METADATA_FILE, '/') . ')$/';
    }
    return $v;
  }

}
