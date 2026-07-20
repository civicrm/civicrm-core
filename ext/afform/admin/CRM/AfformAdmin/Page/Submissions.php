<?php
declare(strict_types = 1);

use Civi\Api4\SearchDisplay;
use CRM_AfformAdmin_ExtensionUtil as E;

class CRM_AfformAdmin_Page_Submissions extends CRM_Core_Page {

  public function run() {
    $afformName = CRM_Utils_Request::retrieve('name', 'String', $this, TRUE, NULL, 'GET');

    $afform = \Civi\Api4\Afform::get(TRUE)
      ->addWhere('name', '=', $afformName)
      ->addSelect('title', 'server_route')
      ->execute()->first();

    CRM_Utils_System::setTitle(E::ts('Submissions for %1', [1 => $afform['title']]));

    CRM_Utils_System::resetBreadCrumb();
    CRM_Utils_System::appendBreadCrumb([
      ['title' => E::ts('CiviCRM'), 'url' => Civi::url('current://civicrm', 'h')],
      ['title' => E::ts('FormBuilder'), 'url' => Civi::url('current://civicrm/admin/afform', 'h')],
    ]);

    if (!empty($afform['server_route'])) {
      CRM_Utils_System::appendBreadCrumb([
        ['title' => $afform['title'], 'url' => Civi::url('current://' . $afform['server_route'], 'h')],
      ]);
    }

    // SearchDisplay metadata will be filled by AfformSubmissionDefaultDisplaySubscriber
    $display = SearchDisplay::getDefault(FALSE)
      ->setType('table')
      ->setSavedSearch('AfAdmin_Submission_List')
      ->setContext(['filters' => ['afformName' => $afformName]])
      ->execute()->first();

    // 4. Construct the search display markup
    $markup = sprintf('<crm-search-display-table search="\'AfAdmin_Submission_List\'" display="null" api-entity="AfformSubmissionData" settings="%s" filters="%s"></crm-search-display-table>',
      htmlspecialchars(\CRM_Utils_JS::encode($display['settings']), ENT_COMPAT),
      htmlspecialchars(\CRM_Utils_JS::encode(['afformName' => $afformName]), ENT_COMPAT),
    );

    $this->assign('submissionTableMarkup', $markup);
    Civi::service('angularjs.loader')->addModules(['crmSearchDisplay']);

    parent::run();
  }

}
