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
 * The MixinScanner scans the list of actives extensions and their required mixins.
 */
class CRM_Extension_MixinScanner {

  /**
   * @var CRM_Extension_Mapper
   */
  protected $mapper;

  /**
   * @var CRM_Extension_Manager
   */
  protected $manager;

  /**
   * @var string[]|null
   *   A list of base-paths which are implicitly supported by 'include' directives.
   *   Sorted with the longest paths first.
   */
  protected $relativeBases;

  /**
   * CRM_Extension_ClassLoader constructor.
   * @param \CRM_Extension_Mapper|NULL $mapper
   * @param \CRM_Extension_Manager|NULL $manager
   * @param bool $relativize
   *   Whether to store paths in relative form.
   *   Enabling this may slow-down scanning a bit, and it has no benefit when for on-demand loaders.
   *   However, if the loader is cached, then it may make for smaller, more portable cache-file.
   */
  public function __construct(?\CRM_Extension_Mapper $mapper = NULL, \CRM_Extension_Manager $manager = NULL, $relativize = TRUE) {
    $this->mapper = $mapper ?: CRM_Extension_System::singleton()->getMapper();
    $this->manager = $manager ?: CRM_Extension_System::singleton()->getManager();
    if ($relativize) {
      $this->relativeBases = [Civi::paths()->getVariable('civicrm.root', 'path')];
      // Previous drafts used `relativeBases=explode(include_path)`. However, this produces unstable results
      // when flip through the phases of the lifecycle - because the include_path changes throughout the lifecycle.
      usort($this->relativeBases, function($a, $b) {
        return strlen($b) - strlen($a);
      });
    }
    else {
      $this->relativeBases = NULL;
    }
  }

  /**
   * @return \CRM_Extension_MixinLoader
   */
  public function createLoader() {
    $l = new CRM_Extension_MixinLoader();

    foreach ($this->getInstalledKeys() as $key) {
      try {
        $path = $this->mapper->keyToBasePath($key);
        $l->addMixInfo($this->createMixInfo($path . DIRECTORY_SEPARATOR . CRM_Extension_Info::FILENAME));
        $l->addFunctionFiles($this->findFunctionFiles("$path/mixin/*@*.mixin.php"));
        $l->addFunctionFiles($this->findFunctionFiles("$path/mixin/*@*/mixin.php"), TRUE);
      }
      catch (CRM_Extension_Exception_ParseException $e) {
        error_log(sprintf('MixinScanner: Failed to read extension (%s)', $key));
      }
    }

    $l->addFunctionFiles($this->findFunctionFiles(Civi::paths()->getPath('[civicrm.root]/mixin/*@*.mixin.php')));
    $l->addFunctionFiles($this->findFunctionFiles(Civi::paths()->getPath('[civicrm.root]/mixin/*@*/mixin.php')), TRUE);

    return $l->compile();
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
   * @param string $infoFile
   *   Path to the 'info.xml' file
   * @return \CRM_Extension_MixInfo
   * @throws \CRM_Extension_Exception_ParseException
   */
  private function createMixInfo(string $infoFile) {
    $info = CRM_Extension_Info::loadFromFile($infoFile);
    $instance = new CRM_Extension_MixInfo();
    $instance->longName = $info->key;
    $instance->shortName = $info->file;
    $instance->path = rtrim(dirname($infoFile), '/' . DIRECTORY_SEPARATOR);
    $instance->mixins = $info->mixins;
    return $instance;
  }

  /**
   * @param string $globPat
   * @return array
   *   Ex: ['mix/xml-menu-autoload@1.0.mixin.php']
   */
  private function findFunctionFiles($globPat) {
    $useRel = $this->relativeBases !== NULL;
    $result = [];
    $funcFiles = (array) glob($globPat);
    sort($funcFiles);
    foreach ($funcFiles as $shimFile) {
      $shimFileRel = $useRel ? $this->relativize($shimFile) : $shimFile;
      $result[] = $shimFileRel;
    }
    return $result;
  }

  /**
   * Convert the absolute $file to an expression that is supported by 'include'.
   *
   * @param string $file
   * @return string
   */
  private function relativize($file) {
    foreach ($this->relativeBases as $relativeBase) {
      if (CRM_Utils_File::isChildPath($relativeBase, $file)) {
        return ltrim(CRM_Utils_File::relativize($file, $relativeBase), '/' . DIRECTORY_SEPARATOR);
      }
    }
    return $file;
  }

}
