<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

require_once 'CRM/Core/Page.php';

/**
 * Page for displaying list of auctions
 */
class CRM_Auction_Page_ManageItem extends CRM_Core_Page {

  /**
   * the id of the auction for this item
   *
   * @var int
   * @protected
   */
  public $_aid;

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_actionLinks = NULL;

  static $_links = NULL;

  protected $_pager = NULL;

  protected $_sortByCharacter;

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */ function &links() {
    if (!(self::$_actionLinks)) {
      // helper variable for nicer formatting
      $disableExtra = ts('Are you sure you want to disable this Item?');
      $deleteExtra  = ts('Are you sure you want to delete this Item?');
      $copyExtra    = ts('Are you sure you want to make a copy of this Item?');

      self::$_actionLinks = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/auction/item/add',
          'qs' => 'action=update&id=%%id%%&aid=%%aid%%&reset=1',
          'title' => ts('Edit Item'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Reject'),
          'url' => CRM_Utils_System::currentPath(),
          'qs' => 'action=disable&id=%%id%%&aid=%%aid%%',
          'extra' => 'onclick = "return confirm(\'' . $disableExtra . '\');"',
          'title' => ts('Disable Item'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Approve'),
          'url' => CRM_Utils_System::currentPath(),
          'qs' => 'action=enable&id=%%id%%&aid=%%aid%%',
          'title' => ts('Enable Item'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/auction/item/add',
          'qs' => 'action=delete&id=%%id%%&aid=%%aid%%&reset=1',
          'extra' => 'onclick = "return confirm(\'' . $deleteExtra . '\');"',
          'title' => ts('Delete Item'),
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
   * @access public
   *
   */
  function run() {
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

    $this->_aid = CRM_Utils_Request::retrieve('aid', 'Positive', $this, TRUE);

    // set breadcrumb to append to 2nd layer pages
    $breadCrumb = array(array('title' => ts('Manage Items'),
        'url' => CRM_Utils_System::url(CRM_Utils_System::currentPath(),
          'reset=1'
        ),
      ));

    // what action to take ?
    if ($action & CRM_Core_Action::DISABLE) {
      require_once 'CRM/Auction/BAO/Item.php';
      CRM_Auction_BAO_Item::setIsActive($id, 0);
    }
    elseif ($action & CRM_Core_Action::ENABLE) {
      require_once 'CRM/Auction/BAO/Item.php';
      CRM_Auction_BAO_Item::setIsActive($id, 1);
    }
    elseif ($action & CRM_Core_Action::DELETE) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'reset=1&action=browse'));
      $controller = new CRM_Core_Controller_Simple('CRM_Auction_Form_Auction_Delete',
        'Delete Auction',
        $action
      );
      $id = CRM_Utils_Request::retrieve('id', 'Positive',
        $this, FALSE, 0
      );
      $controller->set('id', $id);
      $controller->process();
      return $controller->run();
    }
    elseif ($action & CRM_Core_Action::COPY) {
      $this->copy();
    }

    // finally browse the auctions
    $this->browse();

    // parent run
    parent::run();
  }

  /**
   * Browse all auctions
   *
   *
   * @return void
   * @access public
   * @static
   */
  function browse() {
    $this->assign('newItemURL', CRM_Utils_System::url('civicrm/auction/item/add',
        'reset=1&action=add&aid=' . $this->_aid
      ));
    $this->assign('previewItemURL', CRM_Utils_System::url('civicrm/auction/item',
        'reset=1&aid=' . $this->_aid
      ));

    $this->_sortByCharacter = CRM_Utils_Request::retrieve('sortByCharacter',
      'String',
      $this
    );
    if ($this->_sortByCharacter == 1 ||
      !empty($_POST)
    ) {
      $this->_sortByCharacter = '';
      $this->set('sortByCharacter', '');
    }

    $this->_force = NULL;
    $this->_searchResult = NULL;

    $this->search();

    $config = CRM_Core_Config::singleton();

    $params = array();
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean',
      $this, FALSE
    );
    $this->_searchResult = CRM_Utils_Request::retrieve('searchResult', 'Boolean', $this);

    $whereClause = $this->whereClause($params, FALSE, $this->_force);
    $this->pagerAToZ($whereClause, $params);

    $params = array();
    $whereClause = $this->whereClause($params, TRUE, $this->_force);
    $this->pager($whereClause, $params);
    list($offset, $rowCount) = $this->_pager->getOffsetAndRowCount();

    //check for delete CRM-4418
    require_once 'CRM/Core/Permission.php';
    $allowToDelete = CRM_Core_Permission::check('delete in CiviAuction');

    $query = "
  SELECT i.*, c.display_name as donorName
    FROM civicrm_auction_item i, 
         civicrm_contact c
   WHERE $whereClause
     AND auction_id = {$this->_aid}
     AND i.donor_id = c.id
   LIMIT $offset, $rowCount";

    $dao = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Auction_DAO_Item');

    // get all custom groups sorted by weight
    $items = array();
    $auctionItemTypes = CRM_Core_OptionGroup::values('auction_item_type');
    while ($dao->fetch()) {
      $items[$dao->id] = array();
      CRM_Core_DAO::storeValues($dao, $items[$dao->id]);

      $items[$dao->id]['donorName'] = $dao->donorName;
      $items[$dao->id]['auction_item_type'] = CRM_Utils_Array::value($dao->auction_type_id, $auctionItemTypes);

      // form all action links
      $action = array_sum(array_keys($this->links()));

      if ($dao->is_active) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }
      //check for delete
      if (!$allowToDelete) {
        $action -= CRM_Core_Action::DELETE;
      }

      $items[$dao->id]['action'] = CRM_Core_Action::formLink(self::links(),
        $action,
        array('id' => $dao->id,
          'aid' => $this->_aid,
        )
      );
    }
    $this->assign('rows', $items);
  }

  /**
   * This function is to make a copy of a Auction, including
   * all the fields in the event wizard
   *
   * @return void
   * @access public
   */
  function copy() {
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE, 0, 'GET');

    require_once 'CRM/Auction/BAO/Auction.php';
    CRM_Auction_BAO_Auction::copy($id);

    return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/event/manage', 'reset=1'));
  }

  function search() {
    $form = new CRM_Core_Controller_Simple('CRM_Auction_Form_SearchAuction', ts('Search Auctions'), CRM_Core_Action::ADD);
    $form->setEmbedded(TRUE);
    $form->setParent($this);
    $form->process();
    $form->run();
  }

  function whereClause(&$params, $sortBy = TRUE, $force) {
    $values  = array();
    $clauses = array();
    $title   = $this->get('title');
    if ($title) {
      $clauses[] = "title LIKE %1";
      if (strpos($title, '%') !== FALSE) {
        $params[1] = array(trim($title), 'String', FALSE);
      }
      else {
        $params[1] = array(trim($title), 'String', TRUE);
      }
    }

    if ($sortBy &&
      $this->_sortByCharacter
    ) {
      $clauses[] = 'title LIKE %6';
      $params[6] = array($this->_sortByCharacter . '%', 'String');
    }

    // dont do a the below assignment when doing a
    // AtoZ pager clause
    if ($sortBy) {
      if (count($clauses) > 1) {
        $this->assign('isSearch', 1);
      }
      else {
        $this->assign('isSearch', 0);
      }
    }

    if (empty($clauses)) {
      return 1;
    }

    return implode(' AND ', $clauses);
  }

  function pager($whereClause, $whereParams) {
    require_once 'CRM/Utils/Pager.php';

    $params['status'] = ts('Item %%StatusMessage%%');
    $params['csvString'] = NULL;
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
    $params['rowCount'] = $this->get(CRM_Utils_Pager::PAGE_ROWCOUNT);
    if (!$params['rowCount']) {
      $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    }

    $query = "
SELECT count(id)
  FROM civicrm_auction_item
 WHERE $whereClause";

    $params['total'] = CRM_Core_DAO::singleValueQuery($query, $whereParams);

    $this->_pager = new CRM_Utils_Pager($params);
    $this->assign_by_ref('pager', $this->_pager);
  }

  function pagerAtoZ($whereClause, $whereParams) {
    require_once 'CRM/Utils/PagerAToZ.php';

    $query = "
   SELECT DISTINCT UPPER(LEFT(title, 1)) as sort_name
     FROM civicrm_auction_item
    WHERE $whereClause
 ORDER BY LEFT(title, 1)
";
    $dao = CRM_Core_DAO::executeQuery($query, $whereParams);

    $aToZBar = CRM_Utils_PagerAToZ::getAToZBar($dao, $this->_sortByCharacter, TRUE);
    $this->assign('aToZ', $aToZBar);
  }
}

