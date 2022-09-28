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

namespace Civi\Afform;

/**
 * Class AngularDependencyMapper
 * @package Civi\Afform
 */
class AngularDependencyMapper {

  /**
   * Scan the list of Angular modules and inject automatic-requirements.
   *
   * TLDR: if an afform uses element "<other-el/>", and if another module defines
   * `$angularModules['otherMod']['exports']['el'][0] === 'other-el'`, then
   * the 'otherMod' is automatically required.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::angularModules()
   */
  public static function autoReq($e) {
    /** @var \CRM_Afform_AfformScanner $scanner */
    $scanner = \Civi::service('afform_scanner');
    $moduleEnvId = md5(\CRM_Core_Config_Runtime::getId() . implode(',', array_keys($e->angularModules)));
    $depCache = \CRM_Utils_Cache::create([
      'name' => 'afdep_' . substr($moduleEnvId, 0, 32 - 6),
      'type' => ['*memory*', 'SqlGroup', 'ArrayCache'],
      'withArray' => 'fast',
      'prefetch' => TRUE,
    ]);
    $depCacheTtl = 2 * 60 * 60;

    $revMap = self::reverseDeps($e->angularModules);

    $formNames = array_keys($scanner->findFilePaths());
    foreach ($formNames as $formName) {
      $angModule = _afform_angular_module_name($formName, 'camel');
      $cacheLine = $depCache->get($formName, NULL);

      $jFile = $scanner->findFilePath($formName, 'aff.json');
      $hFile = $scanner->findFilePath($formName, 'aff.html');

      if (!$hFile) {
        \Civi::log()->warning("Missing html file for Afform: '$jFile'");
        continue;
      }
      $jStat = stat($jFile);
      $hStat = stat($hFile);

      if ($cacheLine === NULL) {
        $needsUpdate = TRUE;
      }
      elseif ($jStat !== FALSE && $jStat['size'] !== $cacheLine['js']) {
        $needsUpdate = TRUE;
      }
      elseif ($jStat !== FALSE && $jStat['mtime'] > $cacheLine['jm']) {
        $needsUpdate = TRUE;
      }
      elseif ($hStat !== FALSE && $hStat['size'] !== $cacheLine['hs']) {
        $needsUpdate = TRUE;
      }
      elseif ($hStat !== FALSE && $hStat['mtime'] > $cacheLine['hm']) {
        $needsUpdate = TRUE;
      }
      else {
        $needsUpdate = FALSE;
      }

      if ($needsUpdate) {
        $cacheLine = [
          'js' => $jStat['size'] ?? NULL,
          'jm' => $jStat['mtime'] ?? NULL,
          'hs' => $hStat['size'] ?? NULL,
          'hm' => $hStat['mtime'] ?? NULL,
          'r' => array_values(array_unique(array_merge(
            [\CRM_Afform_AfformScanner::DEFAULT_REQUIRES],
            $e->angularModules[$angModule]['requires'] ?? [],
            self::reverseDepsFind(file_get_contents($hFile), $revMap)
          ))),
        ];
        $depCache->set($formName, $cacheLine, $depCacheTtl);
      }

      $e->angularModules[$angModule]['requires'] = $cacheLine['r'];
    }
  }

  /**
   * @param $angularModules
   * @return array
   *   'attr': array(string $attrName => string $angModuleName)
   *   'el': array(string $elementName => string $angModuleName)
   */
  private static function reverseDeps($angularModules):array {
    $revMap = ['attr' => [], 'el' => []];
    foreach (array_keys($angularModules) as $module) {
      if (!isset($angularModules[$module]['exports'])) {
        continue;
      }
      foreach ($angularModules[$module]['exports'] as $symbolName => $symbolTypes) {
        if (strpos($symbolTypes, 'A') !== FALSE) {
          $revMap['attr'][$symbolName] = $module;
        }
        if (strpos($symbolTypes, 'E') !== FALSE) {
          $revMap['el'][$symbolName] = $module;
        }
      }
    }
    return $revMap;
  }

  /**
   * @param string $html
   * @param array $revMap
   *   The reverse-dependencies map from reverseDeps().
   * @return array
   */
  private static function reverseDepsFind($html, $revMap):array {
    $symbols = \Civi\Afform\Symbols::scan($html);
    $elems = array_intersect_key($revMap['el'], $symbols->elements);
    $attrs = array_intersect_key($revMap['attr'], $symbols->attributes);
    return array_values(array_unique(array_merge($elems, $attrs)));
  }

}
