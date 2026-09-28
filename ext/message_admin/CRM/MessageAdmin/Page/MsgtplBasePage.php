<?php
use CRM_MessageAdmin_ExtensionUtil as E;

class CRM_MessageAdmin_Page_MsgtplBasePage extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle(ts('Message Templates'));

    $breadCrumb = [
      'title' => E::ts('Message Templates'),
      'url' => CRM_Utils_System::url('civicrm/admin/messageTemplates', 'reset=1'),
    ];
    CRM_Utils_System::appendBreadCrumb([$breadCrumb]);

    /** @var \Civi\Angular\AngularLoader $loader */
    $loader = \Civi::service('angularjs.loader');
    $loader->addModules(['crmMsgadm']);
    $loader->useApp([
      'defaultRoute' => '/edit',
    ]);
    parent::run();
  }

}
