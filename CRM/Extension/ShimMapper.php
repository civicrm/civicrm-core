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
 * Class CRM_Extension_ShimMap
 *
 * The ShimmerMap scans the list of extensions and creates a list of
 * active shims.
 */
class CRM_Extension_ShimMapper {

  /**
   * @var CRM_Extension_Mapper
   */
  protected $mapper;

  /**
   * @var CRM_Extension_Manager
   */
  protected $manager;

  /**
   * CRM_Extension_ClassLoader constructor.
   * @param \CRM_Extension_Mapper $mapper
   * @param \CRM_Extension_Manager $manager
   */
  public function __construct(\CRM_Extension_Mapper $mapper, \CRM_Extension_Manager $manager) {
    $this->mapper = $mapper;
    $this->manager = $manager;
  }

  /**
   * @return array
   *   Ex: ['org.civicrm.flexmailer' => ['longName' => 'org.civicrm.flexmailer', 'shortName' => 'flexmailer', 'shimFiles' => ['foo' => 'shims/foo.shim.php']]]
   */
  public function build() {
    $shimMap = [];

    foreach ($this->getInstalledKeys() as $key) {
      $path = $this->mapper->keyToBasePath($key);
      $info = $this->mapper->keyToInfo($key);

      $shimFiles = $this->findShimFiles($path);
      if (!empty($shimFiles)) {
        $shimMap[$key] = [
          'longName' => $key,
          'shortName' => $info->file,
          'shimFiles' => $shimFiles,
          'bootCache' => [],
        ];
      }
    }
    return $shimMap;
  }

  /**
   * Convert a shimMap to PHP source-code.
   *
   * @param array $shimMap
   * @return string
   *   The serialized PHP source-code representation of the shim map.
   *   This should be loaded on future page-views.
   */
  public function dump($shimMap) {
    $src = sprintf('%s::loadMap(unserialize(%s));',
      CRM_Extension_ShimApi::CLASS,
      var_export(serialize($shimMap), 1));
    return $src;
  }

  /**
   * @return array
   */
  private function getInstalledKeys() {
    $keys = [];

    $statuses = $this->manager->getStatuses();
    ksort($statuses);
    foreach ($statuses as $key => $status) {
      if ($status === CRM_Extension_Manager::STATUS_INSTALLED) {
        $keys[] = $key;
      }
    }

    return $keys;
  }

  /**
   * @param string $path
   * @return array
   *   Ex: ['xml-menu-autoload' => 'shims/xml-menu-autoload.shim.php']
   */
  private function findShimFiles($path) {
    $result = [];
    $shimFiles = (array) glob("$path/shims/*.shim.php");
    sort($shimFiles);
    foreach ($shimFiles as $shimFile) {
      $shimName = preg_replace(';\.shim\.php$;', '', basename($shimFile));
      $shimFileRel = ltrim(CRM_Utils_File::relativize($shimFile, $path), '/' . DIRECTORY_SEPARATOR);
      $result[$shimName] = $shimFileRel;
    }
    return $result;
  }

}
