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
 * The MixinLoader gets a list of extensions and mixins - then loads them.
 */
class CRM_Extension_MixinLoader {

  /**
   * List extension-mixins that have been loaded already.
   *
   * @var array
   */
  protected $done = [];

  public function run($force = FALSE) {
    $system = CRM_Extension_System::singleton();
    $cache = $system->getCache();

    $cachedScan = $force ? NULL : $cache->get('mixinScan');
    $cachedBootData = $force ? NULL : $cache->get('mixinBoot');

    [$funcFiles, $mixInfos] = $cachedScan ?: (new CRM_Extension_MixinScanner($system->getMapper(), $system->getManager(), TRUE))->build();
    $bootData = $cachedBootData ?: new CRM_Extension_BootCache();

    $this->loadMixins($bootData, $funcFiles, $mixInfos);

    if ($cachedScan === NULL) {
      $cache->set('mixinScan', [$funcFiles, $mixInfos], 24 * 60 * 60);
    }
    if ($cachedBootData === NULL) {
      $bootData->lock();
      $cache->set('mixinBoot', $bootData, 24 * 60 * 60);
    }
  }

  /**
   * Load all extensions and call their respective function-files.
   *
   * @throws \CRM_Core_Exception
   */
  protected function loadMixins(CRM_Extension_BootCache $bootCache, array $liveFuncFiles, array $mixInfos): void {
    // Read each live func-file once, even if there's some kind of Civi::reset(). This avoids hard-crash where the func-file registers a PHP class/function/interface.
    // Granted, PHP symbols require care to avoid conflicts between `mymixin@1.0` and `mymixin@2.0` -- but you can deal with that. For minor-versions, you're
    // safe because we deduplicate.
    static $funcsByFile = [];
    foreach ($liveFuncFiles as $verExpr => $file) {
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

    /** @var \CRM_Extension_MixInfo $ext */
    foreach ($mixInfos as $ext) {
      foreach ($ext->mixins as $verExpr) {
        $doneId = $ext->longName . '::' . $verExpr;
        if (isset($this->done[$doneId])) {
          continue;
        }
        if (isset($funcsByFile[$liveFuncFiles[$verExpr]])) {
          call_user_func($funcsByFile[$liveFuncFiles[$verExpr]], $ext, $bootCache);
          $this->done[$doneId] = 1;
        }
        else {
          error_log(sprintf('MixinLoader: Failed to load "%s" for extension "%s"', $verExpr, $ext->longName));
        }
      }
    }
  }

}
