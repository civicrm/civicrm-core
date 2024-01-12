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

/**
 * Page for displaying list of Premiums.
 */
class CRM_Contribute_Page_Premium extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Contribute_BAO_Premium';
  }

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      // helper variable for nicer formatting
      $deleteExtra = ts('Are you sure you want to remove this product form this page?');

      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/contribute/addProductToPage',
          'qs' => 'action=update&id=%%id%%&pid=%%pid%%&reset=1',
          'title' => ts('Edit Premium'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
        CRM_Core_Action::PREVIEW => [
          'name' => ts('Preview'),
          'url' => 'civicrm/admin/contribute/addProductToPage',
          'qs' => 'action=preview&id=%%id%%&pid=%%pid%%',
          'title' => ts('Preview Premium'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::PREVIEW),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Remove'),
          'url' => 'civicrm/admin/contribute/addProductToPage',
          'qs' => 'action=delete&id=%%id%%&pid=%%pid%%',
          'extra' => 'onclick = "if (confirm(\'' . $deleteExtra . '\') ) this.href+=\'&amp;confirmed=1\'; else return false;"',
          'title' => ts('Disable Premium'),
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
   */
  public function run() {
    // get the requested action
    $action = CRM_Utils_Request::retrieve('action', 'String',
      // default to 'browse'
      $this, FALSE, 'browse'
    );

    // assign vars to templates
    $this->assign('action', $action);
    $id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE, 0
    );
    $this->assign('id', $id);

    $this->edit($action, $id, FALSE, FALSE);

    // this is special case where we need to call browse to list premium
    if ($action == CRM_Core_Action::UPDATE) {
      $this->browse();
    }

    // parent run
    return parent::run();
  }

  /**
   * Browse function.
   */
  public function browse() {
    // get all custom groups sorted by weight
    $premiums = [];
    $pageID = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE, 0
    );
    $premiumDao = new CRM_Contribute_DAO_Premium();
    $premiumDao->entity_table = 'civicrm_contribution_page';
    $premiumDao->entity_id = $pageID;
    $premiumDao->find(TRUE);
    $premiumID = $premiumDao->id;
    $this->assign('products', FALSE);
    $this->assign('id', $pageID);
    if (!$premiumID) {
      return;
    }

    $premiumsProductDao = new CRM_Contribute_DAO_PremiumsProduct();
    $premiumsProductDao->premiums_id = $premiumID;
    $premiumsProductDao->orderBy('weight');
    $premiumsProductDao->find();

    while ($premiumsProductDao->fetch()) {
      $productDAO = new CRM_Contribute_DAO_Product();
      $productDAO->id = $premiumsProductDao->product_id;
      $productDAO->is_active = 1;

      if ($productDAO->find(TRUE)) {
        $premiums[$productDAO->id] = [];
        $premiums[$productDAO->id]['weight'] = $premiumsProductDao->weight;
        CRM_Core_DAO::storeValues($productDAO, $premiums[$productDAO->id]);

        $action = array_sum(array_keys($this->links()));

        $premiums[$premiumsProductDao->product_id]['action'] = CRM_Core_Action::formLink($this->links(), $action,
          ['id' => $pageID, 'pid' => $premiumsProductDao->id],
          ts('more'),
          FALSE,
          'premium.contributionpage.row',
          'Premium',
          $premiumsProductDao->id
        );
        // Financial Type
        if (!empty($premiumsProductDao->financial_type_id)) {
          $premiums[$productDAO->id]['financial_type'] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Product', 'financial_type_id', $premiumsProductDao->financial_type_id);
        }
      }
    }

    if (count(CRM_Contribute_PseudoConstant::products($pageID)) == 0) {
      $this->assign('products', FALSE);
    }
    else {
      $this->assign('products', TRUE);
    }

    // Add order changing widget to selector
    $returnURL = CRM_Utils_System::url('civicrm/admin/contribute/premium', "reset=1&action=update&id={$pageID}");
    $filter = "premiums_id = {$premiumID}";
    CRM_Utils_Weight::addOrder($premiums, 'CRM_Contribute_DAO_PremiumsProduct',
      'id', $returnURL, $filter
    );
    $this->assign('rows', $premiums);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Contribute_Form_ContributionPage_Premium';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'Configure Premiums';
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
    return CRM_Utils_System::currentPath();
  }

}
