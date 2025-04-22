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
   * @var array
   *   Ex: ['civix' => ['1.0.0' => 'path/to/civix@1.0.0.mixin.php']]
   */
  protected $allFuncFiles = [];

  /**
   * @var array|null
   *   If we have not scanned for live funcs, then NULL.
   *   Otherwise, every live version-requirement is mapped to the corresponding file.
   *   Ex: ['civix@1' => 'path/to/civix@1.0.0.mixin.php']
   */
  protected $liveFuncFiles = NULL;

  /**
   * @var \CRM_Extension_MixInfo[]
   */
  protected $mixInfos = [];

  /**
   * CRM_Extension_ClassLoader constructor.
   * @param \CRM_Extension_Mapper|null $mapper
   * @param \CRM_Extension_Manager|null $manager
   * @param bool $relativize
   *   Whether to store paths in relative form.
   *   Enabling this may slow-down scanning a bit, and it has no benefit when for on-demand loaders.
   *   However, if the loader is cached, then it may make for smaller, more portable cache-file.
   */
  public function __construct(?\CRM_Extension_Mapper $mapper = NULL, ?\CRM_Extension_Manager $manager = NULL, $relativize = TRUE) {
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
   * @return array{0: funcFiles, 1: mixInfos}
   */
  public function build() {
    $this->scan();
    return $this->compile();
  }

  /**
   * Search through known extensions
   */
  protected function scan() {
    foreach ($this->getInstalledKeys() as $key) {
      try {
        $path = $this->mapper->keyToBasePath($key);
        $this->addMixInfo($this->createMixInfo($path . DIRECTORY_SEPARATOR . CRM_Extension_Info::FILENAME));
        $this->addFunctionFiles($this->findFunctionFiles("$path/mixin/*@*.mixin.php"));
        $this->addFunctionFiles($this->findFunctionFiles("$path/mixin/*@*/mixin.php"), TRUE);
      }
      catch (CRM_Extension_Exception_ParseException $e) {
        error_log(sprintf('MixinScanner: Failed to read extension (%s)', $key));
      }
    }

    $this->addFunctionFiles($this->findFunctionFiles(Civi::paths()->getPath('[civicrm.root]/mixin/*@*.mixin.php')));
    $this->addFunctionFiles($this->findFunctionFiles(Civi::paths()->getPath('[civicrm.root]/mixin/*@*/mixin.php')), TRUE);
  }

  /**
   * Optimize the metadata, removing information that is not needed at runtime.
   *
   * Steps:
   *
   * - Remove any unnecessary $mixInfos (ie they have no mixins).
   * - Given the available versions and expectations, pick the best $liveFuncFiles.
   * - Drop $allFuncFiles.
   */
  protected function compile() {
    $this->liveFuncFiles = [];
    $allFuncs = $this->allFuncFiles ?? [];

    $sortByVer = function ($a, $b) {
      return version_compare($a, $b /* ignore third arg */);
    };
    foreach (array_keys($allFuncs) as $name) {
      uksort($allFuncs[$name], $sortByVer);
    }

    $this->mixInfos = array_filter($this->mixInfos, function(CRM_Extension_MixInfo $mixInfo) {
      return !empty($mixInfo->mixins);
    });

    foreach ($this->mixInfos as $ext) {
      /** @var \CRM_Extension_MixInfo $ext */
      foreach ($ext->mixins as $verExpr) {
        list ($name, $expectVer) = explode('@', $verExpr);
        $matchFile = NULL;
        // NOTE: allFuncs[$name] is sorted by increasing version number. Choose highest satisfactory match.
        foreach ($allFuncs[$name] ?? [] as $availVer => $availFile) {
          if (static::satisfies($expectVer, $availVer)) {
            $matchFile = $availFile;
          }
        }
        if ($matchFile) {
          $this->liveFuncFiles[$verExpr] = $matchFile;
        }
        else {
          error_log(sprintf('MixinLoader: Failed to locate match for "%s"', $verExpr));
        }
      }
    }

    $this->allFuncFiles = NULL;

    return [$this->liveFuncFiles, $this->mixInfos];
  }

  /**
   * @param CRM_Extension_MixInfo $mix
   * @return static
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function addMixInfo(CRM_Extension_MixInfo $mix) {
    $this->mixInfos[$mix->longName] = $mix;
    return $this;
  }

  /**
   * @param array|string $files
   *   Ex: 'path/to/some/file@1.0.0.mixin.php'
   * @param bool $deepRead
   *   If TRUE, then the file will be read to find metadata.
   * @return $this
   */
  public function addFunctionFiles($files, $deepRead = FALSE) {
    $files = (array) $files;
    foreach ($files as $file) {
      if (preg_match(';^([^@]+)@([^@]+)\.mixin\.php$;', basename($file), $m)) {
        $this->allFuncFiles[$m[1]][$m[2]] = $file;
        continue;
      }

      if ($deepRead) {
        $header = $this->loadFunctionFileHeader($file);
        if (isset($header['mixinName'], $header['mixinVersion'])) {
          $this->allFuncFiles[$header['mixinName']][$header['mixinVersion']] = $file;
          continue;
        }
        else {
          error_log(sprintf('MixinLoader: Invalid mixin header for "%s". @mixinName and @mixinVersion required.', $file));
          continue;
        }
      }

      error_log(sprintf('MixinLoader: File \"%s\" cannot be parsed.', $file));
    }
    return $this;
  }

  private function loadFunctionFileHeader($file) {
    $php = file_get_contents($file, TRUE);
    foreach (token_get_all($php) as $token) {
      if (is_array($token) && in_array($token[0], [T_DOC_COMMENT, T_COMMENT, T_FUNC_C, T_METHOD_C, T_TRAIT_C, T_CLASS_C])) {
        return \Civi\Api4\Utils\ReflectionUtils::parseDocBlock($token[1]);
      }
    }
    return [];
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

  /**
   * @param string $expectVer
   * @param string $actualVer
   * @return bool
   */
  private static function satisfies($expectVer, $actualVer) {
    [$expectMajor] = explode('.', $expectVer);
    [$actualMajor] = explode('.', $actualVer);
    return ($expectMajor == $actualMajor) && version_compare($actualVer, $expectVer, '>=');
  }

}
