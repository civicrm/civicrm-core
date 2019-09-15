<?php

class CRM_Api4_Page_Api4Explorer extends CRM_Core_Page {

  public function run() {
    $vars = [
      'operators' => \CRM_Core_DAO::acceptedSQLOperators(),
      'basePath' => Civi::resources()->getUrl('civicrm'),
      'schema' => (array) \Civi\Api4\Entity::get()->setChain(['fields' => ['$name', 'getFields']])->execute(),
      'links' => (array) \Civi\Api4\Entity::getLinks()->execute(),
    ];
    Civi::resources()
      ->addVars('api4', $vars)
      ->addScriptFile('civicrm', 'js/load-bootstrap.js')
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
