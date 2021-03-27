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

use Civi\Api4\Service\Schema\Joinable\Joinable;
use Civi\Api4\Utils\CoreUtil;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Api4_Page_Api4Explorer extends CRM_Core_Page {

  public function run() {
    $apiDoc = new ReflectionFunction('civicrm_api4');
    $groupOptions = civicrm_api4('Group', 'getFields', ['loadOptions' => TRUE, 'select' => ['options', 'name'], 'where' => [['name', 'IN', ['visibility', 'group_type']]]]);
    // Don't show n-to-many joins in Explorer
    $entityLinks = (array) civicrm_api4('Entity', 'getLinks', [], ['entity' => 'links']);
    foreach ($entityLinks as $entity => $links) {
      $entityLinks[$entity] = array_filter($links, function($link) {
        return $link['joinType'] != Joinable::JOIN_TYPE_ONE_TO_MANY;
      });
    }
    $vars = [
      'operators' => CoreUtil::getOperators(),
      'basePath' => Civi::resources()->getUrl('civicrm'),
      'schema' => (array) \Civi\Api4\Entity::get()->setChain(['fields' => ['$name', 'getFields']])->execute(),
      'links' => $entityLinks,
      'docs' => \Civi\Api4\Utils\ReflectionUtils::parseDocBlock($apiDoc->getDocComment()),
      'functions' => self::getSqlFunctions(),
      'groupOptions' => array_column((array) $groupOptions, 'options', 'name'),
    ];
    Civi::resources()
      ->addBundle('bootstrap3')
      ->addVars('api4', $vars)
      ->addPermissions(['access debug output', 'edit groups', 'administer reserved groups'])
      ->addScriptFile('civicrm', 'bower_components/js-yaml/dist/js-yaml.min.js')
      ->addScriptFile('civicrm', 'bower_components/marked/marked.min.js')
      ->addScriptFile('civicrm', 'bower_components/google-code-prettify/bin/prettify.min.js')
      ->addStyleFile('civicrm', 'bower_components/google-code-prettify/bin/prettify.min.css');

    Civi::service('angularjs.loader')
      ->addModules('api4Explorer')
      ->useApp(['defaultRoute' => '/explorer']);

    parent::run();
  }

  /**
   * Gets info about all available sql functions
   * @return array
   */
  public static function getSqlFunctions() {
    $fns = [];
    foreach (glob(Civi::paths()->getPath('[civicrm.root]/Civi/Api4/Query/SqlFunction*.php')) as $file) {
      $matches = [];
      if (preg_match('/(SqlFunction[A-Z_]+)\.php$/', $file, $matches)) {
        $className = '\Civi\Api4\Query\\' . $matches[1];
        if (is_subclass_of($className, '\Civi\Api4\Query\SqlFunction')) {
          $fns[] = [
            'name' => $className::getName(),
            'title' => $className::getTitle(),
            'params' => $className::getParams(),
            'category' => $className::getCategory(),
          ];
        }
      }
    }
    return $fns;
  }

}
