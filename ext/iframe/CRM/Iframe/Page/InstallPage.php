<?php
use CRM_Iframe_ExtensionUtil as E;

class CRM_Iframe_Page_InstallPage extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle(E::ts('IFRAME Installation'));

    $iframe = Civi::service('iframe');
    $scriptMgr = Civi::service('iframe.script');
    $this->assign('filePath', $scriptMgr->getPath());
    $this->assign('fileUrl', (string) \Civi::url('[civicrm.iframe]'));
    $this->assign('newCode', $scriptMgr->render($iframe->getTemplate()));
    $this->assign('oldCode', $scriptMgr->getCurrent());
    $this->assign('isCurrent', $scriptMgr->render($iframe->getTemplate()) === $scriptMgr->getCurrent());

    parent::run();
  }

}
