<?php
use CRM_Oembed_ExtensionUtil as E;

class CRM_Oembed_Page_InstallPage extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle(E::ts('oEmbed Installation'));

    $oembed = Civi::service('oembed');
    $scriptMgr = Civi::service('oembed.script');
    $this->assign('filePath', $scriptMgr->getPath());
    $this->assign('fileUrl', (string) \Civi::url('[civicrm.oembed]'));
    $this->assign('newCode', $scriptMgr->render($oembed->getTemplate()));
    $this->assign('oldCode', $scriptMgr->getCurrent());
    $this->assign('isCurrent', $scriptMgr->render($oembed->getTemplate()) === $scriptMgr->getCurrent());

    parent::run();
  }

}
