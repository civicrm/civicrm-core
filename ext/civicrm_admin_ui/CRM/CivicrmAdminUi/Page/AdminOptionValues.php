<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

class CRM_CivicrmAdminUi_Page_AdminOptionValues extends CRM_Core_Page {

  public function run() {
    $groupName = $this->urlPath[3] ?? NULL;
    $filters = [];

    // Browse all groups
    if (!$groupName) {
      $display = $this->getSearchDisplay('Administer_Option_Groups', 'Administer_Option_Groups_Display');
      CRM_Utils_System::setTitle(ts('Option Groups'));
    }
    // Browse options in specified group
    else {
      $group = \Civi\Api4\OptionGroup::get(FALSE)
        ->addWhere('name', '=', $groupName)
        ->execute()->single();

      CRM_Utils_System::setTitle(ts('%1 Options', [1 => $group['title']]));

      $display = $this->getSearchDisplay('Administer_Option_Values', 'Administer_Option_Values_Display');

      $optionValueFields = \Civi\Api4\OptionGroup::getFields(FALSE)
        ->addWhere('name', '=', 'option_value_fields')
        ->setLoadOptions(TRUE)
        ->execute()->single()['options'];

      // Adjust table columns according to the fields used by this option group
      foreach ($optionValueFields as $fieldName) {
        if (!in_array($fieldName, $group['option_value_fields'], TRUE)) {
          foreach ($display['settings']['columns'] as $idx => $column) {
            if ($fieldName === ($column['key'] ?? NULL)) {
              unset($display['settings']['columns'][$idx]);
            }
          }
        }
      }

      if (!empty($group['is_locked'])) {
        unset($display['settings']['addButton']);
      }

      $display['settings']['description'] = $group['description'] ?: ts('The existing option choices for %1 group are listed below. You can add, edit or delete them from this screen.', [1 => $group['title']]);
      $filters = ['option_group_id' => $group['id']];
    }

    $this->outputSearchDisplay($display, $filters);

    parent::run();
  }

  /**
   * @param string $searchName
   * @param string $displayName
   * @return array
   */
  private function getSearchDisplay(string $searchName, string $displayName): array {
    return \Civi\Api4\SearchDisplay::get(FALSE)
      ->addWhere('name', '=', $displayName)
      ->addWhere('saved_search_id.name', '=', $searchName)
      ->addSelect('name', 'settings', 'saved_search_id.name', 'saved_search_id.api_entity')
      ->execute()->single();
  }

  /**
   * Outputs smarty variables and js to render search display
   *
   * @param array $display
   * @param array $filters
   */
  private function outputSearchDisplay(array $display, array $filters): void {
    $this->assign('apiEntity', $display['saved_search_id.api_entity']);
    $this->assign('filters', htmlspecialchars(\CRM_Utils_JS::encode($filters), ENT_COMPAT));
    $this->assign('searchName', $display['saved_search_id.name']);
    $this->assign('displayName', $display['name']);
    $this->assign('searchSettings', htmlspecialchars(\CRM_Utils_JS::encode($display['settings']), ENT_COMPAT));

    Civi::service('angularjs.loader')
      ->addModules('crmSearchDisplayTable');
  }

}
