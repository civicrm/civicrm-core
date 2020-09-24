<?php
use CRM_Afform_ExtensionUtil as E;

class CRM_Afform_Page_AfformBase extends CRM_Core_Page {

  public function run() {
    list ($pagePath, $pageArgs) = func_get_args();

    $afform = civicrm_api4('Afform', 'get', [
      'checkPermissions' => FALSE,
      'where' => [['name', '=', $pageArgs['afform']]],
      'select' => ['title', 'module_name', 'directive_name'],
    ], 0);

    $this->set('afModule', $afform['module_name']);

    $loader = new \Civi\Angular\AngularLoader();
    $loader->setModules([$afform['module_name'], 'afformStandalone']);
    $loader->setPageName(implode('/', $pagePath));
    $loader->getRes()->addSetting([
      'afform' => [
        'open' => $afform['directive_name'],
      ],
    ])
      // TODO: Allow afforms to declare their own theming requirements
      ->addBundle('bootstrap3');
    $loader->load();

    if (!empty($afform['title'])) {
      $title = strip_tags($afform['title']);
      CRM_Utils_System::setTitle($title);
      CRM_Utils_System::appendBreadCrumb([['title' => $title, 'url' => CRM_Utils_System::url(implode('/', $pagePath), NULL, FALSE, '/')]]);
    }

    parent::run();
  }

}
