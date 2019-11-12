<?php
use CRM_Afform_ExtensionUtil as E;

class CRM_Afform_Page_AfformBase extends CRM_Core_Page {

  public function run() {
    list ($pagePath, $pageArgs) = func_get_args();

    $module = _afform_angular_module_name($pageArgs['afform']);
    $this->set('afModule', $module);

    $loader = new \Civi\Angular\AngularLoader();
    $loader->setModules([$module, 'afformStandalone']);
    $loader->setPageName(implode('/', $pagePath));
    $loader->getRes()->addSetting([
      'afform' => [
        'open' => _afform_angular_module_name($pageArgs['afform'], 'dash'),
      ],
    ]);
    $loader->load();

    $afform = civicrm_api4('Afform', 'get', ['checkPermissions' => FALSE, 'where' => [['name', '=', $module]], 'select' => ['title']]);

    if (!empty($afform[0]['title'])) {
      $title = strip_tags($afform[0]['title']);
      CRM_Utils_System::setTitle($title);
      CRM_Utils_System::appendBreadCrumb([['title' => $title, 'url' => CRM_Utils_System::url(implode('/', $pagePath), NULL, FALSE, '/')]]);
    }

    parent::run();
  }

}
