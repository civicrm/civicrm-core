<?php
use CRM_Afform_ExtensionUtil as E;

class CRM_Afform_Page_AfformBase extends CRM_Core_Page {

  public function run() {
    // To avoid php complaints about the number of args passed to this function vs the base function
    [$pagePath, $pageArgs] = func_get_args();

    // The api will throw an exception if afform is not found (because of the index 0 param)
    $afform = civicrm_api4('Afform', 'get', [
      'where' => [['name', '=', $pageArgs['afform']]],
      'select' => ['title', 'module_name', 'directive_name', 'type', 'is_public'],
    ], 0);

    $this->assign('directive', $afform['directive_name']);

    Civi::service('angularjs.loader')
      ->addModules([$afform['module_name'], 'afformStandalone']);

    $isFrontEndPage = !empty($afform['is_public']);

    // If not being shown on the front-end website, append breadcrumb for CiviCRM users
    if (!$isFrontEndPage && CRM_Core_Permission::check('access CiviCRM')) {
      CRM_Utils_System::appendBreadCrumb([['title' => E::ts('CiviCRM'), 'url' => CRM_Utils_System::url('civicrm')]]);
      // If the user has "admin civicrm" & the admin extension is enabled
      if (CRM_Core_Permission::check('administer CiviCRM')) {
        if (($pagePath[1] ?? NULL) === 'admin') {
          CRM_Utils_System::appendBreadCrumb([['title' => E::ts('Admin'), 'url' => CRM_Utils_System::url('civicrm/admin')]]);
        }
        if ($afform['type'] !== 'system' &&
          \CRM_Extension_System::singleton()->getMapper()->isActiveModule('afform_admin')
        ) {
          CRM_Utils_System::appendBreadCrumb([['title' => E::ts('FormBuilder'), 'url' => CRM_Utils_System::url('civicrm/admin/afform')]]);
          CRM_Utils_System::appendBreadCrumb([['title' => E::ts('Edit Form'), 'url' => CRM_Utils_System::url('civicrm/admin/afform', NULL, FALSE, '/edit/' . $pageArgs['afform'])]]);
        }
      }
    }

    if (!empty($afform['title'])) {
      $title = strip_tags($afform['title']);
      if (!$isFrontEndPage) {
        CRM_Utils_System::appendBreadCrumb([
          [
            'title' => $title,
            'url' => CRM_Utils_System::url(implode('/', $pagePath)) . '#',
          ],
        ]);
      }
    }

    parent::run();
  }

}
