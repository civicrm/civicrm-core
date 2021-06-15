<?php
use CRM_Msgtplui_ExtensionUtil as E;

class CRM_Msgtplui_Page_MsgtplBasePage extends CRM_Core_Page {

  public function run() {
    /** @var \Civi\Angular\AngularLoader $loader */
    CRM_Utils_System::setTitle(ts('Message Templates'));
    $loader = \Civi::service('angularjs.loader');
    $loader->setModules(['msgtplui']);
    $loader->useApp(array(
      'defaultRoute' => '/user',
    ));
    parent::run();
  }

}
