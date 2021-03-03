<?php
use CRM_Afform_ExtensionUtil as E;

class CRM_Afform_Page_AfformBase extends CRM_Core_Page {

  public function run() {
    // To avoid php complaints about the number of args passed to this function vs the base function
    [$pagePath, $pageArgs] = func_get_args();

    // The api will throw an exception if afform is not found (because of the index 0 param)
    $afform = civicrm_api4('Afform', 'get', [
      'checkPermissions' => FALSE,
      'where' => [['name', '=', $pageArgs['afform']]],
      'select' => ['title', 'module_name', 'directive_name'],
    ], 0);

    $this->assign('directive', $afform['directive_name']);

    (new \Civi\Angular\AngularLoader())
      ->setModules([$afform['module_name'], 'afformStandalone'])
      ->load();

    if (!empty($afform['title'])) {
      $title = strip_tags($afform['title']);
      CRM_Utils_System::setTitle($title);
      CRM_Utils_System::appendBreadCrumb([['title' => $title, 'url' => CRM_Utils_System::url(implode('/', $pagePath), NULL, FALSE, '!/')]]);
    }

    parent::run();
  }

}
