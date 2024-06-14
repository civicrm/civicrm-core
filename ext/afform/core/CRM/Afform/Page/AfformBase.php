<?php

use Civi\Api4\Navigation;
use CRM_Afform_ExtensionUtil as E;

class CRM_Afform_Page_AfformBase extends CRM_Core_Page {

  public function run() {
    // To avoid php complaints about the number of args passed to this function vs the base function
    [$pagePath, $pageArgs] = func_get_args();

    // The api will throw an exception if afform is not found (because of the index 0 param)
    $afform = civicrm_api4('Afform', 'get', [
      'where' => [['name', '=', $pageArgs['afform']]],
      'select' => ['title', 'module_name', 'directive_name', 'navigation', 'server_route', 'is_public'],
    ], 0);

    $this->assign('directive', $afform['directive_name']);

    Civi::service('angularjs.loader')
      ->addModules([$afform['module_name'], 'afformStandalone']);

    $isFrontEndPage = !empty($afform['is_public']);

    // If not being shown on the front-end website, calculate breadcrumbs
    if (!$isFrontEndPage && CRM_Core_Permission::check('access CiviCRM')) {
      // CiviCRM has already constructed a breadcrumb based on the server_route (see CRM_Core_Menu::buildBreadcrumb)
      // But if this afform is in the navigation menu, reset breadcrumb and build on that instead
      if (!empty($afform['navigation']['parent'])) {
        $navParent = Navigation::get(FALSE)
          ->addWhere('name', '=', $afform['navigation']['parent'])
          ->addWhere('domain_id', '=', 'current_domain')
          ->execute()->first();
        if (!empty($navParent['url'])) {
          CRM_Utils_System::resetBreadCrumb();
          CRM_Utils_System::appendBreadCrumb([['title' => E::ts('CiviCRM'), 'url' => Civi::url('current://civicrm')]]);
          CRM_Utils_System::appendBreadCrumb([['title' => $navParent['label'], 'url' => Civi::url('current://' . $navParent['url'])]]);
        }
      }
    }

    if (!empty($afform['title'])) {
      // Add current afform page to breadcrumb
      $title = strip_tags($afform['title']);
      if (!$isFrontEndPage) {
        CRM_Utils_System::appendBreadCrumb([
          [
            'title' => $title,
            'url' => CRM_Utils_System::url(implode('/', $pagePath)) . '#',
          ],
        ]);
      }
      // 'CiviCRM' be replaced with Afform title via AfformBase.tpl.
      // @see crmUi.directive(crmPageTitle)
      CRM_Utils_System::setTitle('CiviCRM');
    }
    else {
      // Afform has no title
      CRM_Utils_System::setTitle('');
    }

    parent::run();
  }

}
