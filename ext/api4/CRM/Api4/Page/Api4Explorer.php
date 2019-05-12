<?php

class CRM_Api4_Page_Api4Explorer extends CRM_Core_Page {

  public function run() {
    $vars = [
      'operators' => \CRM_Core_DAO::acceptedSQLOperators(),
      'basePath' => Civi::resources()->getUrl('org.civicrm.api4'),
    ];
    Civi::resources()
      ->addVars('api4', $vars)
      ->addScriptFile('org.civicrm.api4', 'js/load-bootstrap.js');

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
