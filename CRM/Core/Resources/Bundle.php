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
 * Class CRM_Core_Resources_Bundle
 *
 * A bundle is a collection of web resources with the following details:
 * - Only scripts, styles, and settings are allowed. Free-form markup is not.
 * - Resources *may* have a 'region'. Hopefully, this is not necessary for most bundles.
 * - If no 'region' is given, then CRM_Core_Resources will pick a default at activation time.
 */
class CRM_Core_Resources_Bundle implements CRM_Core_Resources_CollectionInterface {

  use CRM_Core_Resources_CollectionTrait;

  /**
   * Symbolic name for this bundle.
   *
   * @var string|null
   */
  public $name;

  /**
   * @param string|null $name
   * @param string[]|null $types
   *   List of resource-types to permit in this bundle. NULL for a default list.
   *   Ex: ['styleFile', 'styleUrl']
   *   The following aliases are allowed: '*all*', '*default*', '*script*', '*style*'
   */
  public function __construct($name = NULL, $types = NULL) {
    $this->name = $name;

    $typeAliases = [
      '*all*' => ['script', 'scriptFile', 'scriptUrl', 'settings', 'style', 'styleFile', 'styleUrl', 'markup', 'template', 'callback'],
      '*default*' => ['script', 'scriptFile', 'scriptUrl', 'settings', 'style', 'styleFile', 'styleUrl'],
      '*style*' => ['style', 'styleFile', 'styleUrl'],
      '*script*' => ['script', 'scriptFile', 'scriptUrl'],
    ];
    $mapType = function ($t) use ($typeAliases) {
      return $typeAliases[$t] ?? [$t];
    };
    $types = $types ?: ['*default*'];
    $this->types = array_unique(array_merge(...array_map($mapType, (array) $types)));
  }

  /**
   * Fill in default values for the 'region' property.
   *
   * @return static
   */
  public function fillDefaults() {
    $this->filter(function ($s) {
      if (!isset($s['region'])) {
        if ($s['type'] === 'settings') {
          $s['region'] = NULL;
        }
        elseif (preg_match(';^(markup|template|callback);', $s['type'])) {
          $s['region'] = 'page-header';
        }
        else {
          $s['region'] = CRM_Core_Resources_Common::REGION;
        }
      }
      return $s;
    });
    return $this;
  }

}
