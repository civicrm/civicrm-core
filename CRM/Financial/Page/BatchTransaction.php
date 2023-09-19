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
 * Page for displaying list of financial batches
 */
class CRM_Financial_Page_BatchTransaction extends CRM_Core_Page_Basic {
  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;
  public static $_entityID;

  public static $_columnHeader = NULL;
  public static $_returnvalues = NULL;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Batch_BAO_Batch';
  }

  /**
   * Get action Links.
   *
   * @todo:
   * While this function only references static self::$_links, we can't make
   * the function static because we need to match CRM_Core_Page_Basic. Possibly
   * the intent was caching, but there's nothing very time-consuming in here
   * that needs it so do we even need $_links? The variable is public - a quick
   * look doesn't seem like it's used on its own, but it's hard to fully check
   * that.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        'view' => [
          'name' => ts('View'),
          'url' => 'civicrm/contact/view/contribution',
          'qs' => 'reset=1&id=%%contid%%&cid=%%cid%%&action=view&context=contribution&selectedChild=contribute',
          'title' => ts('View Contribution'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::VIEW),
        ],
        'remove' => [
          'name' => ts('Remove'),
          'title' => ts('Remove Transaction'),
          'extra' => 'onclick = "removeFromBatch(%%id%%);"',
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
    // default to 'browse'
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');

    // assign vars to templates
    $this->assign('action', $action);

    self::$_entityID = CRM_Utils_Request::retrieve('bid', 'Positive');
    $statusID = NULL;
    if (isset(self::$_entityID)) {
      $statusID = CRM_Core_DAO::getFieldValue('CRM_Batch_BAO_Batch', self::$_entityID, 'status_id');
    }
    $breadCrumb
      = [
        [
          'title' => ts('Accounting Batches'),
          'url' => CRM_Utils_System::url('civicrm/financial/financialbatches',
            "reset=1&batchStatus=$statusID"),
        ],
      ];

    CRM_Utils_System::appendBreadCrumb($breadCrumb);
    $this->edit($action, self::$_entityID);
    return parent::run();
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Financial_Form_BatchTransaction';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'Batch';
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
    return 'civicrm/batchtransaction';
  }

}
