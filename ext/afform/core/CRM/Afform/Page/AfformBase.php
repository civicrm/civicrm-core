<?php
use CRM_Afform_ExtensionUtil as E;

class CRM_Afform_Page_AfformBase extends CRM_Core_Page {

  public function run() {
    // To avoid php complaints about the number of args passed to this function vs the base function
    [$pagePath, $pageArgs] = func_get_args();

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
    }

    // If the user has "access civicrm" append home breadcrumb
    if (CRM_Core_Permission::check('access CiviCRM')) {
      CRM_Utils_System::appendBreadCrumb([['title' => ts('CiviCRM'), 'url' => CRM_Utils_System::url('civicrm')]]);
      // If the user has "admin civicrm" & the admin extension is enabled
      if (CRM_Core_Permission::check('administer CiviCRM') && CRM_Utils_Array::findAll(
          \CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles(),
          ['fullName' => 'org.civicrm.afform_admin']
        )) {
        CRM_Utils_System::appendBreadCrumb([['title' => E::ts('Form Builder'), 'url' => CRM_Utils_System::url('civicrm/admin/afform')]]);
        CRM_Utils_System::appendBreadCrumb([['title' => E::ts('Edit Form'), 'url' => CRM_Utils_System::url('civicrm/admin/afform', NULL, FALSE, '/edit/' . $pageArgs['afform'])]]);
      }
    }

    parent::run();
  }

}
