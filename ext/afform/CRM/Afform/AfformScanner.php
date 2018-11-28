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

  const METADATA_FILE = 'meta.json';

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
        $this->appendFilePaths($paths, dirname($mapper->keyToPath($module->name)) . DIRECTORY_SEPARATOR . 'afform', 20);
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
   * @param string $subFile
   *   Ex: 'meta.json'
   * @return string|NULL
   *   Ex: '/var/www/sites/default/files/civicrm/afform/view-individual'
   */
  public function findFilePath($formName, $subFile) {
    $paths = $this->findFilePaths();

    if (isset($paths[$formName])) {
      foreach ($paths[$formName] as $path) {
        if (file_exists($path . DIRECTORY_SEPARATOR . $subFile)) {
          return $path . DIRECTORY_SEPARATOR . $subFile;
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
   *   Ex: 'meta.json'
   * @return string|NULL
   *   Ex: '/var/www/sites/default/files/civicrm/afform/view-individual'
   */
  public function createSiteLocalPath($formName, $file) {
    return $this->getSiteLocalPath() . DIRECTORY_SEPARATOR . $formName . DIRECTORY_SEPARATOR . $file;
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
   *   NOTE: This is only data available in meta.json. It does *NOT* include layout.
   *   Ex: [
   *     'name' => 'view-individual',
   *     'title' => 'View an individual contact',
   *     'server_route' => 'civicrm/view-individual',
   *     'requires' => ['afform'],
   *   ]
   */
  public function getMeta($name) {
    // FIXME error checking
    $metaFile = $this->findFilePath($name, self::METADATA_FILE);
    if (!$metaFile) {
      return NULL;
    }

    $defaults = [
      'name' => $name,
      'requires' => explode(',', self::DEFAULT_REQUIRES),
      'title' => '',
      'description' => '',
      'is_public' => false,
    ];

    return array_merge($defaults, json_decode(file_get_contents($metaFile), 1));
  }

  /**
   * Get the effective metadata for all forms.
   *
   * @return array
   *   A list of all forms, keyed by form name.
   *   NOTE: This is only data available in meta.json. It does *NOT* include layout.
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
   *   Ex: ['foo' => [0 => '/var/www/org.example.foobar/afform//foo']]
   * @param string $parent
   *   Ex: '/var/www/org.example.foobar/afform/'
   * @param int $priority
   *   Lower priority files override higher priority files.
   */
  private function appendFilePaths(&$formPaths, $parent, $priority) {
    $parent = CRM_Utils_File::addTrailingSlash($parent);
    if (is_dir($parent) && $handle = opendir($parent)) {
      while (FALSE !== ($entry = readdir($handle))) {
        if ($entry{0} !== '.' && is_dir($parent . $entry)) {
          $formPaths[$entry][$priority] = $parent . $entry;
          ksort($formPaths[$entry]);
        }
      }
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
    return Civi::paths()->getPath('[civicrm.files]/afform');
  }

}
