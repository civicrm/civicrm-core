<?php
use CRM_MessageAdmin_ExtensionUtil as E;

class CRM_MessageAdmin_Page_MsgtplBasePage extends CRM_Core_Page {

  public function run() {
    // Did we get an old school URL? Translate to preferred format.
    $child = CRM_Utils_Request::retrieve('selectedChild', 'String');
    switch ($child) {
      case 'user':
      case 'workflow':
        $url = CRM_Utils_System::url('civicrm/admin/messageTemplates/', NULL, TRUE, '/' . $child, FALSE);
        CRM_Utils_System::redirect($url);
        break;
    }

    CRM_Utils_System::setTitle(ts('Message Templates'));

    $breadCrumb = [
      'title' => E::ts('Message Templates'),
      'url' => CRM_Utils_System::url('civicrm/admin/messageTemplates', NULL, FALSE, '/user'),
    ];
    CRM_Utils_System::appendBreadCrumb([$breadCrumb]);

    /** @var \Civi\Angular\AngularLoader $loader */
    $loader = \Civi::service('angularjs.loader');
    $loader->addModules(['crmMsgadm']);
    $loader->useApp([
      'defaultRoute' => '/user',
    ]);
    parent::run();
  }

}
