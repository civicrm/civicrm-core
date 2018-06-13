<?php
use CRM_Afform_ExtensionUtil as E;

class CRM_Afform_Page_AfformBase extends CRM_Core_Page {

  public function run() {
    //    echo '<pre>';print_r(func_get_args());exit();
    list ($pagePath, $pageArgs) = func_get_args();

    $module = _afform_angular_module_name($pageArgs['afform']);

    $loader = new \Civi\Angular\AngularLoader();
    $loader->setModules([$module, 'afformStandalone']);
    $loader->setPageName(implode('/', $pagePath));
    $loader->useApp();
    $loader->getRes()->addSetting([
      'afform' => [
        'open' => _afform_angular_module_name($pageArgs['afform'], 'dash'),
      ],
    ]);
    $loader->load();

    parent::run();
  }

}
