<?php
use CRM_AfformGui_ExtensionUtil as E;

class CRM_AfformGui_Page_Gui extends CRM_Core_Page {

  public function run() {
    $loader = new \Civi\Angular\AngularLoader();
    $loader->setModules(['afformGui']);
    $loader->setPageName('civicrm/admin/afform');
    $loader->useApp([
      'defaultRoute' => '/list',
    ]);
    $loader->load();
    CRM_Utils_System::setTitle('CiviCRM');
    parent::run();
  }

}
