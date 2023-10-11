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
 * Class used to calculate Afform Angular dependencies.
 * @package Civi\Afform
 */
class AngularDependencyMapper {

  /**
   * @var array{attr: array, el: array}
   */
  private $revMap;

  public function __construct(array $angularModules) {
    $this->revMap = $this->getRevMap($angularModules);
  }

  /**
   * Adds angular dependencies based on the html contents of an afform.
   *
   * TLDR: if an afform uses element "<other-el/>", and if another module defines
   * `$angularModules['otherMod']['exports']['el'][0] === 'other-el'`, then
   * the 'otherMod' is automatically required.
   *
   * @param array $afform
   * @see CRM_Utils_Hook::angularModules()
   */
  public function autoReq(array $afform) {
    $afform['requires'][] = \CRM_Afform_AfformScanner::DEFAULT_REQUIRES;
    $dependencies = empty($afform['layout']) ? [] : $this->reverseDepsFind($afform['layout']);
    return array_values(array_unique(array_merge($afform['requires'], $dependencies)));
  }

  /**
   * @param array $angularModules
   * @return array{attr: array, el: array}
   *   'attr': [string $attrName => string $angModuleName]
   *   'el': [string $elementName => string $angModuleName]
   */
  private function getRevMap(array $angularModules): array {
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
   * @return array
   */
  private function reverseDepsFind(string $html): array {
    $symbols = \Civi\Afform\Symbols::scan($html);
    $elems = array_intersect_key($this->revMap['el'], $symbols->elements);
    $attrs = array_intersect_key($this->revMap['attr'], $symbols->attributes);
    return array_merge($elems, $attrs);
  }

}
