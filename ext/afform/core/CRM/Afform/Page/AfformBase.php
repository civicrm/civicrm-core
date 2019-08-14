<?php
use CRM_Afform_ExtensionUtil as E;

class CRM_Afform_Page_AfformBase extends CRM_Core_Page {

  public function run() {
    list ($pagePath, $pageArgs) = func_get_args();

    $module = _afform_angular_module_name($pageArgs['afform']);

    $loader = new \Civi\Angular\AngularLoader();
    $loader->setModules([$module, 'afformStandalone']);
    $loader->setPageName(implode('/', $pagePath));
    $loader->getRes()->addSetting([
      'afform' => [
        'open' => _afform_angular_module_name($pageArgs['afform'], 'dash'),
      ],
    ]);
    $loader->load();

    $afform = civicrm_api4('Afform', 'get', ['where' => [['name', '=', $pageArgs['afform']]], 'select' => ['title']]);

    if (!empty($afform[0]['title'])) {
      CRM_Utils_System::setTitle(strip_tags($afform[0]['title']));
    }

    parent::run();
  }

}
