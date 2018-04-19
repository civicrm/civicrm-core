<?php

/**
 * A class to prepare the menu array for an extension to add additional items.
 *
 * Usage:
 *     function yourextension_civicrm_navigationMenu(&$menu) {
 *       $adder = new CRM_Utils_NavAdd($menu);
 *       $attributes = array(
 *         'label' => ts('One thing'),
 *         'name' => 'One thing',
 *         'url' => 'civicrm/onething',
 *         'permission' => 'access CiviCRM, administer CiviCRM',
 *         'operator' => 'AND',
 *         'separator' => 1,
 *         'active' => 1,
 *       );
 *       $adder->addItem($attributes, array('Administer'));
 *       $attributes = array(
 *         'label' => ts('Other thing'),
 *         'name' => 'Other thing',
 *         'url' => 'civicrm/otherthing',
 *         'permission' => 'access CiviCRM',
 *         'separator' => 1,
 *         'active' => 1,
 *       );
 *       $adder->addItem($attributes, array('Contributions', 'Pledges'));
 *       $menu = $adder->getMenu();
 *     }
 */
class CRM_Utils_NavAdd {
  /**
   * The menu array to fill.
   *
   * @param array $menu
   */
  protected $menu;

  /**
   * The current maximum ID of a menu item.
   *
   * @param int $maxId
   */
  protected $maxId = 0;

  /**
   * Prepare the adder.
   *
   * @param array $menu
   *   The current menu array from hook_civicrm_navigationMenu.
   */
  public function __construct($menu) {
    $this->menu = $menu;
    $this->scanMaxNavId($menu);
  }

  /**
   * Add an item to the menu
   *
   * @param array $attributes
   *   The attributes of the item to add, an array with the following keys:
   *   - `label`,
   *   - `name`,
   *   - `url`,
   *   - `permission`,
   *   - `operator`,
   *   - `separator`,
   *   - `active`
   * @param array $idealTree
   *   A list of preferred ancestors for the item in the menu.
   */
  public function addItem($attributes, $idealTree) {
    // Walk down the menu to see if we can find them where we expect them.
    $walkMenu = $this->menu;
    $branches = array();
    reset($idealTree);
    foreach ($idealTree as $limb) {
      foreach ($walkMenu as $id => $item) {
        if ($item['attributes']['name'] == $limb) {
          $walkMenu = CRM_Utils_Array::value('child', $item, array());
          $branches[] = $id;
          $branches[] = 'child';
          continue 2;
        }
      }
      // If the expected parent isn't at this level of the menu, we'll just drop
      // it here.
      break;
    }

    if (!empty($id)) {
      $attributes['parentID'] = $id;
    }

    // Need to put together exactly where the item should be added;
    $treeMenu = &$this->menu;
    foreach ($branches as $branch) {
      $treeMenu = &$treeMenu[$branch];
    }

    // Our ID is the next one after the current maximum.
    $this->maxId++;
    $attributes['navID'] = $this->maxId;
    $treeMenu[$this->maxId] = array('attributes' => $attributes);
  }

  /**
   * Getter for the menu that has been updated.
   *
   * @return array
   */
  public function getMenu() {
    return $this->menu;
  }

  /**
   * Scans recursively for the highest ID in the navigation.
   *
   * Better than searching the database because other extensions may have added
   * items in the meantime.
   *
   * @param array $menu
   *   The menu to scan.
   */
  protected function scanMaxNavId($menu) {
    foreach ($menu as $id => $item) {
      $this->maxId = max($id, $this->maxId);
      if (!empty($item['child'])) {
        $this->scanMaxNavId($item['child']);
      }
    }
  }

}
