<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
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
      self::$_actionLinks = array(
        CRM_Core_Action::BROWSE => array(
          'name' => ts('View and Edit Price Fields'),
          'url' => 'civicrm/admin/price/field',
          'qs' => 'reset=1&action=browse&sid=%%sid%%',
          'title' => ts('View and Edit Price Fields'),
        ),
        CRM_Core_Action::PREVIEW => array(
          'name' => ts('Preview'),
          'url' => 'civicrm/admin/price',
          'qs' => 'action=preview&reset=1&sid=%%sid%%',
          'title' => ts('Preview Price Set'),
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Settings'),
          'url' => 'civicrm/admin/price',
          'qs' => 'action=update&reset=1&sid=%%sid%%',
          'title' => ts('Edit Price Set'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Price Set'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Price Set'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/price',
          'qs' => 'action=delete&reset=1&sid=%%sid%%',
          'title' => ts('Delete Price Set'),
          'extra' => 'onclick = "return confirm(\'' . $deleteExtra . '\');"',
        ),
        CRM_Core_Action::COPY => array(
          'name' => ts('Copy Price Set'),
          'url' => CRM_Utils_System::currentPath(),
          'qs' => 'action=copy&sid=%%sid%%',
          'title' => ts('Make a Copy of Price Set'),
          'extra' => 'onclick = "return confirm(\'' . $copyExtra . '\');"',
        ),
      );
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
      $session = CRM_Core_Session::singleton();
      CRM_Core_Session::setStatus(ts('A copy of the price set has been created'), ts('Saved'), 'success');
      $this->copy();
    }
    else {

      // if action is delete do the needful.
      if ($action & (CRM_Core_Action::DELETE)) {
        $usedBy = CRM_Price_BAO_PriceSet::getUsedBy($sid);

        if (empty($usedBy)) {
          // prompt to delete
          $session = CRM_Core_Session::singleton();
          $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/price', 'action=browse'));
          $controller = new CRM_Core_Controller_Simple('CRM_Price_Form_DeleteSet', 'Delete Price Set', NULL);
          // $id = CRM_Utils_Request::retrieve('sid', 'Positive', $this, false, 0);
          $controller->set('sid', $sid);
          $controller->setEmbedded(TRUE);
          $controller->process();
          $controller->run();
        }
        else {
          // add breadcrumb
          $url = CRM_Utils_System::url('civicrm/admin/price', 'reset=1');
          CRM_Utils_System::appendBreadCrumb(ts('Price Sets'), $url);
          $this->assign('usedPriceSetTitle', CRM_Price_BAO_PriceSet::getTitle($sid));
          $this->assign('usedBy', $usedBy);

          $comps = array(
            'Event' => 'civicrm_event',
            'Contribution' => 'civicrm_contribution_page',
            'EventTemplate' => 'civicrm_event_template',
          );
          $priceSetContexts = array();
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
    $context = CRM_Utils_Request::retrieve('context', 'String', $this);
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
    $priceSet = array();
    $comps = array(
      'CiviEvent' => ts('Event'),
      'CiviContribute' => ts('Contribution'),
      'CiviMember' => ts('Membership'),
    );

    $dao = new CRM_Price_DAO_PriceSet();
    if (CRM_Price_BAO_PriceSet::eventPriceSetDomainID()) {
      $dao->domain_id = CRM_Core_Config::domainID();
    }
    $dao->is_quick_config = 0;
    $dao->find();
    while ($dao->fetch()) {
      $priceSet[$dao->id] = array();
      CRM_Core_DAO::storeValues($dao, $priceSet[$dao->id]);

      $compIds = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        CRM_Utils_Array::value('extends', $priceSet[$dao->id])
      );
      $extends = array();
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
        $actionLinks[CRM_Core_Action::BROWSE]['name'] = 'View Price Fields';
      }
      $priceSet[$dao->id]['action'] = CRM_Core_Action::formLink($actionLinks, $action,
        array('sid' => $dao->id),
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
