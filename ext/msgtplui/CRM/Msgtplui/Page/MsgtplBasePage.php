<?php
use CRM_Msgtplui_ExtensionUtil as E;

class CRM_Msgtplui_Page_MsgtplBasePage extends CRM_Core_Page {

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

    /** @var \Civi\Angular\AngularLoader $loader */
    CRM_Utils_System::setTitle(ts('Message Templates'));
    $loader = \Civi::service('angularjs.loader');
    $loader->addModules(['msgtplui']);
    $loader->useApp(array(
      'defaultRoute' => '/user',
    ));
    parent::run();
  }

}
