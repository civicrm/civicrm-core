<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Api4\Product;

/**
 * Page for displaying list of Premiums.
 */
class CRM_Contribute_Page_ManagePremiums extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName(): string {
    return 'CRM_Contribute_BAO_Product';
  }

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links(): array {
    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/contribute/managePremiums/edit',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Premium'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
        CRM_Core_Action::PREVIEW => [
          'name' => ts('Preview'),
          'url' => 'civicrm/admin/contribute/managePremiums/edit',
          'qs' => 'action=preview&id=%%id%%',
          'title' => ts('Preview Premium'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::PREVIEW),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Premium'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DISABLE),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Premium'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ENABLE),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/contribute/managePremiums/edit',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Premium'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ],
      ];
    }
    return self::$_links;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   *
   * @throws \CRM_Core_Exception
   */
  public function run(): void {
    $id = $this->getIdAndAction();

    // what action to take ?
    if (!($this->_action & CRM_Core_Action::BROWSE)) {
      $this->edit($this->_action, $id, TRUE);
    }
    // finally browse the custom groups
    $this->browse();

    // parent run
    CRM_Core_Page::run();
  }

  /**
   * Browse all custom data groups.
   *
   * @throws \CRM_Core_Exception
   */
  public function browse(): void {
    // We could probably use checkPermissions here but historically didn't
    // so have set it to FALSE to be safe while converting to api use.
    $premiums = Product::get(FALSE)->addOrderBy('name')
      ->addSelect('*', 'financial_type_id:name')
      ->execute();

    foreach ($premiums as $index => $premium) {
      $action = array_sum(array_keys($this->links()));

      if ($premium['is_active']) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }

      $premiums[$index]['action'] = CRM_Core_Action::formLink($this->links(),
        $action,
        ['id' => $premium['id']],
        ts('more'),
        FALSE,
        'premium.manage.row',
        'Premium',
        $premium['id']
      );
      $premiums[$index]['financial_type'] = $premium['financial_type_id:name'];
      $premiums[$index]['class'] = '';
    }
    $this->assign('rows', $premiums);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm(): string {
    return 'CRM_Contribute_Form_ManagePremiums';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName(): string {
    return 'Manage Premiums';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context.
   */
  public function userContext($mode = NULL): string {
    return 'civicrm/admin/contribute/managePremiums';
  }

}
