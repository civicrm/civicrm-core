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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */
class CRM_Api4_Page_Api4Explorer extends CRM_Core_Page {

  public function run() {
    $apiDoc = new ReflectionFunction('civicrm_api4');
    $groupOptions = civicrm_api4('Group', 'getFields', ['loadOptions' => TRUE, 'select' => ['options', 'name'], 'where' => [['name', 'IN', ['visibility', 'group_type']]]]);
    $vars = [
      'operators' => \CRM_Core_DAO::acceptedSQLOperators(),
      'basePath' => Civi::resources()->getUrl('civicrm'),
      'schema' => (array) \Civi\Api4\Entity::get()->setChain(['fields' => ['$name', 'getFields']])->execute(),
      'links' => (array) \Civi\Api4\Entity::getLinks()->execute(),
      'docs' => \Civi\Api4\Utils\ReflectionUtils::parseDocBlock($apiDoc->getDocComment()),
      'groupOptions' => array_column((array) $groupOptions, 'options', 'name'),
    ];
    Civi::resources()
      ->addVars('api4', $vars)
      ->addPermissions(['access debug output', 'edit groups', 'administer reserved groups'])
      ->addScriptFile('civicrm', 'js/load-bootstrap.js')
      ->addScriptFile('civicrm', 'bower_components/js-yaml/dist/js-yaml.min.js')
      ->addScriptFile('civicrm', 'bower_components/marked/marked.min.js')
      ->addScriptFile('civicrm', 'bower_components/google-code-prettify/bin/prettify.min.js')
      ->addStyleFile('civicrm', 'bower_components/google-code-prettify/bin/prettify.min.css');

    $loader = new Civi\Angular\AngularLoader();
    $loader->setModules(['api4Explorer']);
    $loader->setPageName('civicrm/api4');
    $loader->useApp([
      'defaultRoute' => '/explorer',
    ]);
    $loader->load();
    parent::run();
  }

}
