<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

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
  static $_links = NULL;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Contribute_BAO_Product';
  }

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/contribute/managePremiums',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Premium'),
        ],
        CRM_Core_Action::PREVIEW => [
          'name' => ts('Preview'),
          'url' => 'civicrm/admin/contribute/managePremiums',
          'qs' => 'action=preview&id=%%id%%',
          'title' => ts('Preview Premium'),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Premium'),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Premium'),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/contribute/managePremiums',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Premium'),
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
   */
  public function run() {
    $id = $this->getIdAndAction();

    // what action to take ?
    if (!($this->_action & CRM_Core_Action::BROWSE)) {
      $this->edit($this->_action, $id, TRUE);
    }
    // finally browse the custom groups
    $this->browse();

    // parent run
    return CRM_Core_Page::run();
  }

  /**
   * Browse all custom data groups.
   */
  public function browse() {
    // get all custom groups sorted by weight
    $premiums = [];
    $dao = new CRM_Contribute_DAO_Product();
    $dao->orderBy('name');
    $dao->find();

    while ($dao->fetch()) {
      $premiums[$dao->id] = [];
      CRM_Core_DAO::storeValues($dao, $premiums[$dao->id]);
      // form all action links
      $action = array_sum(array_keys($this->links()));

      if ($dao->is_active) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }

      $premiums[$dao->id]['action'] = CRM_Core_Action::formLink(self::links(),
        $action,
        ['id' => $dao->id],
        ts('more'),
        FALSE,
        'premium.manage.row',
        'Premium',
        $dao->id
      );
      // Financial Type
      if (!empty($dao->financial_type_id)) {
        $premiums[$dao->id]['financial_type'] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Product', 'financial_type_id', $dao->financial_type_id);
      }
    }
    $this->assign('rows', $premiums);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Contribute_Form_ManagePremiums';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
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
  public function userContext($mode = NULL) {
    return 'civicrm/admin/contribute/managePremiums';
  }

}
