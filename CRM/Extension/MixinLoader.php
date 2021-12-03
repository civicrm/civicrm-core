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
   * Load all extensions and call their respective function-files.
   *
   * @throws \CRM_Core_Exception
   */
  public function run(CRM_Extension_BootCache $bootCache, array $liveFuncFiles, array $mixInfos): void {
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

    foreach ($mixInfos as $ext) {
      /** @var \CRM_Extension_MixInfo $ext */
      foreach ($ext->mixins as $verExpr) {
        $doneId = $ext->longName . '::' . $verExpr;
        if (isset($done[$doneId])) {
          continue;
        }
        if (isset($funcsByFile[$liveFuncFiles[$verExpr]])) {
          call_user_func($funcsByFile[$liveFuncFiles[$verExpr]], $ext, $bootCache);
          $done[$doneId] = 1;
        }
        else {
          error_log(sprintf('MixinLoader: Failed to load "%s" for extension "%s"', $verExpr, $ext->longName));
        }
      }
    }
  }

}
