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
 * The MixinLoader tracks a list of extensions and mixins.
 */
class CRM_Extension_MixinLoader {

  /**
   * @var \CRM_Extension_MixInfo[]
   */
  protected $mixInfos = [];

  /**
   * @var array|null
   *   If we have not scanned for live funcs, then NULL.
   *   Otherwise, every live version-requirement is mapped to the corresponding file.
   *   Ex: ['civix@1' => 'path/to/civix@1.0.0.mixin.php']
   */
  protected $liveFuncFiles = NULL;

  /**
   * @var array
   *   Ex: ['civix' => ['1.0.0' => 'path/to/civix@1.0.0.mixin.php']]
   */
  protected $allFuncFiles = [];

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
   * Optimize the metadata, removing information that is not needed at runtime.
   *
   * Steps:
   *
   * - Remove any unnecessary $mixInfos (ie they have no mixins).
   * - Given the available versions and expectations, pick the best $liveFuncFiles.
   * - Drop $allFuncFiles.
   */
  public function compile() {
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

    return $this;
  }

  /**
   * Load all extensions and call their respective function-files.
   *
   * @return static
   * @throws \CRM_Core_Exception
   */
  public function run(CRM_Extension_BootCache $bootCache) {
    if ($this->liveFuncFiles === NULL) {
      throw new CRM_Core_Exception("Premature initialization. MixinLoader has not identified live functions.");
    }

    // == WIP ==
    //
    //Do mixins run strictly once (during boot)? Or could they run twice? Or incrementally? Some edge-cases:
    // - Mixins should make changes via dispatcher() and container(). If there's a Civi::reset(), then these things go away. We'll need to
    //   re-register. (Example scenario: unit-testing)
    // - Mixins register for every active module. If a new module is enabled, then we haven't had a chance to run on the new extension.
    // - Mixins register for every active module. If an old module is disabled, then there may be old listeners/services lingering.
    if (!isset(\Civi::$statics[__CLASS__]['done'])) {
      \Civi::$statics[__CLASS__]['done'] = [];
    }
    $done = &\Civi::$statics[__CLASS__]['done'];

    // Read each live func-file once, even if there's some kind of Civi::reset(). This avoids hard-crash where the func-file registers a PHP class/function/interface.
    // Granted, PHP symbols require care to avoid conflicts between `mymixin@1.0` and `mymixin@2.0` -- but you can deal with that. For minor-versions, you're
    // safe because we deduplicate.
    static $funcsByFile = [];
    foreach ($this->liveFuncFiles as $verExpr => $file) {
      if (!isset($funcsByFile[$file])) {
        $func = include_once $file;
        if (is_callable($func)) {
          $funcsByFile[$file] = $func;
        }
        else {
          error_log(sprintf('MixinLoader: Received invalid callback from \"%s\"', $file));
        }
      }
    }

    foreach ($this->mixInfos as $ext) {
      /** @var \CRM_Extension_MixInfo $ext */
      foreach ($ext->mixins as $verExpr) {
        $doneId = $ext->longName . '::' . $verExpr;
        if (isset($done[$doneId])) {
          continue;
        }
        if (isset($funcsByFile[$this->liveFuncFiles[$verExpr]])) {
          call_user_func($funcsByFile[$this->liveFuncFiles[$verExpr]], $ext, $bootCache);
          $done[$doneId] = 1;
        }
        else {
          error_log(sprintf('MixinLoader: Failed to load "%s" for extension "%s"', $verExpr, $ext->longName));
        }
      }
    }

    return $this;
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
