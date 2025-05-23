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
 * Create a page for displaying Price Sets.
 *
 * Heart of this class is the run method which checks
 * for action type and then displays the appropriate
 * page.
 *
 */
class CRM_Price_Page_Set extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  private static $_actionLinks;

  /**
   * Get the action links for this page.
   *
   * @return array
   *   array of action links that we need to display for the browse screen
   */
  public function &actionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_actionLinks)) {
      // helper variable for nicer formatting
      $deleteExtra = ts('Are you sure you want to delete this price set?');
      $copyExtra = ts('Are you sure you want to make a copy of this price set?');
      self::$_actionLinks = [
        CRM_Core_Action::BROWSE => [
          'name' => ts('View and Edit Price Fields'),
          'url' => 'civicrm/admin/price/field',
          'qs' => 'reset=1&action=browse&sid=%%sid%%',
          'title' => ts('View and Edit Price Fields'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::VIEW),
        ],
        CRM_Core_Action::PREVIEW => [
          'name' => ts('Preview'),
          'url' => 'civicrm/admin/price/edit',
          'qs' => 'action=preview&reset=1&sid=%%sid%%',
          'title' => ts('Preview Price Set'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::PREVIEW),
        ],
        CRM_Core_Action::UPDATE => [
          'name' => ts('Settings'),
          'url' => 'civicrm/admin/price/edit',
          'qs' => 'action=update&reset=1&sid=%%sid%%',
          'title' => ts('Edit Price Set'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Price Set'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DISABLE),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Price Set'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ENABLE),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/price/edit',
          'qs' => 'action=delete&reset=1&sid=%%sid%%',
          'title' => ts('Delete Price Set'),
          'extra' => 'onclick = "return confirm(\'' . $deleteExtra . '\');"',
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ],
        CRM_Core_Action::COPY => [
          'name' => ts('Copy Price Set'),
          'url' => CRM_Utils_System::currentPath(),
          'qs' => 'action=copy&sid=%%sid%%&qfKey=%%key%%',
          'title' => ts('Make a Copy of Price Set'),
          'extra' => 'onclick = "return confirm(\'' . $copyExtra . '\');"',
          'weight' => 120,
        ],
      ];
    }
    return self::$_actionLinks;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   *
   * @return void
   */
  public function run() {
    // get the requested action
    $action = CRM_Utils_Request::retrieve('action', 'String',
      // default to 'browse'
      $this, FALSE, 'browse'
    );

    // assign vars to templates
    $this->assign('usedBy', FALSE);
    $this->assign('action', $action);
    $sid = CRM_Utils_Request::retrieve('sid', 'Positive',
      $this, FALSE, 0
    );

    if ($sid) {
      CRM_Price_BAO_PriceSet::checkPermission($sid);
    }
    // what action to take ?
    if ($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD)) {
      $this->edit($sid, $action);
    }
    elseif ($action & CRM_Core_Action::PREVIEW) {
      $this->preview($sid);
    }
    elseif ($action & CRM_Core_Action::COPY) {
      $key = $_POST['qfKey'] ?? $_GET['qfKey'] ?? $_REQUEST['qfKey'] ?? NULL;
      $k = CRM_Core_Key::validate($key, CRM_Utils_System::getClassName($this));
      if (!$k) {
        $this->invalidKey();
      }
      CRM_Core_Session::setStatus(ts('A copy of the price set has been created'), ts('Saved'), 'success');
      $this->copy();
    }
    else {

      // if action is delete do the needful.
      if ($action & (CRM_Core_Action::DELETE)) {
        $usedBy = CRM_Price_BAO_PriceSet::getUsedBy($sid);

        if (empty($usedBy)) {
          // prompt to delete
          CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/admin/price', 'action=browse'));
          $controller = new CRM_Core_Controller_Simple('CRM_Price_Form_DeleteSet', 'Delete Price Set', NULL);
          $controller->set('sid', $sid);
          $controller->setEmbedded(TRUE);
          $controller->process();
          $controller->run();
        }
        else {
          // add breadcrumb
          $url = CRM_Utils_System::url('civicrm/admin/price', 'reset=1');
          CRM_Utils_System::appendBreadCrumb([
            [
              'title' => ts('Price Sets'),
              'url' => $url,
            ],
          ]);
          $this->assign('usedPriceSetTitle', CRM_Price_BAO_PriceSet::getTitle($sid));
          $this->assign('usedBy', $usedBy);

          $comps = [
            'Event' => 'civicrm_event',
            'Contribution' => 'civicrm_contribution_page',
            'EventTemplate' => 'civicrm_event_template',
          ];
          $priceSetContexts = [];
          foreach ($comps as $name => $table) {
            if (array_key_exists($table, $usedBy)) {
              $priceSetContexts[] = $name;
            }
          }
          $this->assign('contexts', $priceSetContexts);
        }
      }

      // finally browse the price sets
      $this->browse();
    }
    // parent run
    return parent::run();
  }

  /**
   * Edit price set.
   *
   * @param int $sid
   *   Price set id.
   * @param string $action
   *   The action to be invoked.
   *
   * @return void
   */
  public function edit($sid, $action) {
    // create a simple controller for editing price sets
    $controller = new CRM_Core_Controller_Simple('CRM_Price_Form_Set', ts('Price Set'), $action);

    // set the userContext stack
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/price', 'action=browse'));
    $controller->set('sid', $sid);
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();
  }

  /**
   * Preview price set.
   *
   * @param int $sid
   *   Price set id.
   *
   * @return void
   */
  public function preview($sid) {
    $controller = new CRM_Core_Controller_Simple('CRM_Price_Form_Preview', ts('Preview Price Set'), NULL);
    $session = CRM_Core_Session::singleton();
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
    if ($context == 'field') {
      $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/price/field', "action=browse&sid={$sid}"));
    }
    else {
      $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/price', 'action=browse'));
    }
    $controller->set('groupId', $sid);
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();
  }

  /**
   * Browse all price sets.
   *
   * @param string $action
   *   The action to be invoked.
   *
   * @return void
   */
  public function browse($action = NULL) {
    // get all price sets
    $priceSet = [];
    $comps = [
      'CiviEvent' => ts('Event'),
      'CiviContribute' => ts('Contribution'),
      'CiviMember' => ts('Membership'),
    ];

    $dao = new CRM_Price_DAO_PriceSet();
    if (CRM_Price_BAO_PriceSet::eventPriceSetDomainID()) {
      $dao->domain_id = CRM_Core_Config::domainID();
    }
    $dao->is_quick_config = 0;
    $dao->find();
    while ($dao->fetch()) {
      $priceSet[$dao->id] = [];
      CRM_Core_DAO::storeValues($dao, $priceSet[$dao->id]);

      $compIds = explode(CRM_Core_DAO::VALUE_SEPARATOR, $priceSet[$dao->id]['extends'] ?? '');
      $extends = [];
      //CRM-10225
      foreach ($compIds as $compId) {
        if (!empty($comps[CRM_Core_Component::getComponentName($compId)])) {
          $extends[] = $comps[CRM_Core_Component::getComponentName($compId)];
        }
      }
      $priceSet[$dao->id]['extends'] = implode(', ', $extends);

      // form all action links
      $action = array_sum(array_keys($this->actionLinks()));

      // update enable/disable links depending on price_set properties.
      if ($dao->is_reserved) {
        $action -= CRM_Core_Action::UPDATE + CRM_Core_Action::DISABLE + CRM_Core_Action::ENABLE + CRM_Core_Action::DELETE + CRM_Core_Action::COPY;
      }
      else {
        if ($dao->is_active) {
          $action -= CRM_Core_Action::ENABLE;
        }
        else {
          $action -= CRM_Core_Action::DISABLE;
        }
      }
      $actionLinks = self::actionLinks();
      //CRM-10117
      if ($dao->is_reserved) {
        $actionLinks[CRM_Core_Action::BROWSE]['name'] = ts('View Price Fields');
      }
      $priceSet[$dao->id]['action'] = CRM_Core_Action::formLink($actionLinks, $action,
        ['sid' => $dao->id, 'key' => CRM_Core_Key::get(CRM_Utils_System::getClassName($this))],
        ts('more'),
        FALSE,
        'priceSet.row.actions',
        'PriceSet',
        $dao->id
      );
    }
    $this->assign('rows', $priceSet);
  }

  /**
   * make a copy of a price set, including
   * all the fields in the page
   *
   * @return void
   */
  public function copy() {
    $id = CRM_Utils_Request::retrieve('sid', 'Positive',
      $this, TRUE, 0, 'GET'
    );

    CRM_Price_BAO_PriceSet::copy($id);

    CRM_Utils_System::redirect(CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'reset=1'));
  }

}
