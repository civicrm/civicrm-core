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
 * Page for displaying list of personal campaign pages
 */
class CRM_PCP_Page_PCP extends CRM_Core_Page_Basic {

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
    return 'CRM_PCP_BAO_PCP';
  }

  /**
   * This holds the value taken from the url for the A-Z pager.
   *
   * It seems like the A-Z pager is a bit broken & could be removed
   * from this page.
   *
   * @var string|null
   */
  protected $_sortByCharacter;

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      // helper variable for nicer formatting
      $deleteExtra = ts('Are you sure you want to delete this Campaign Page ?');

      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/pcp/info',
          'qs' => 'action=update&reset=1&id=%%id%%&context=dashboard',
          'title' => ts('Edit Personal Campaign Page'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
        CRM_Core_Action::RENEW => [
          'name' => ts('Approve'),
          'url' => 'civicrm/admin/pcp',
          'qs' => 'action=renew&id=%%id%%',
          'title' => ts('Approve Personal Campaign Page'),
          'weight' => 30,
        ],
        CRM_Core_Action::REVERT => [
          'name' => ts('Reject'),
          'url' => 'civicrm/admin/pcp',
          'qs' => 'action=revert&id=%%id%%',
          'title' => ts('Reject Personal Campaign Page'),
          'weight' => 30,
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/pcp',
          'qs' => 'action=delete&id=%%id%%',
          'extra' => 'onclick = "return confirm(\'' . $deleteExtra . '\');"',
          'title' => ts('Delete Personal Campaign Page'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'url' => 'civicrm/admin/pcp',
          'qs' => 'action=enable&id=%%id%%',
          'title' => ts('Enable'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ENABLE),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'url' => 'civicrm/admin/pcp',
          'qs' => 'action=disable&id=%%id%%',
          'title' => ts('Disable'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DISABLE),
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
   * @return mixed|null
   * @throws \CRM_Core_Exception
   */
  public function run() {
    $id = $this->getIdAndAction();

    if ($this->_action & CRM_Core_Action::REVERT) {
      CRM_PCP_BAO_PCP::setIsActive($id, 0);
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'reset=1'));
    }
    elseif ($this->_action & CRM_Core_Action::RENEW) {
      CRM_PCP_BAO_PCP::setIsActive($id, 1);
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'reset=1'));
    }
    elseif ($this->_action & CRM_Core_Action::DELETE) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'reset=1&action=browse'));
      $controller = new CRM_Core_Controller_Simple('CRM_PCP_Form_PCP',
        'Personal Campaign Page',
        CRM_Core_Action::DELETE
      );
      //$this->setContext( $id, $action );
      $controller->set('id', $id);
      $controller->process();
      return $controller->run();
    }

    // finally browse
    $this->browse();

    // parent run
    CRM_Core_Page::run();
  }

  /**
   * Browse all custom data groups.
   *
   * @param int $action
   *
   * @throws \CRM_Core_Exception
   */
  public function browse($action = NULL) {
    CRM_Core_Resources::singleton()->addStyleFile('civicrm', 'css/searchForm.css', 1, 'html-header');

    $this->_sortByCharacter = CRM_Utils_Request::retrieve('sortByCharacter',
      'String',
      $this
    );
    if ($this->_sortByCharacter == 1 ||
      !empty($_POST)
    ) {
      $this->_sortByCharacter = '';
    }

    $status = CRM_PCP_BAO_PCP::buildOptions('status_id', 'create');

    $pcpSummary = $params = [];
    $whereClause = NULL;

    if (!empty($_POST) || !empty($_GET['page_type'])) {
      if (!empty($_POST['status_id'])) {
        $whereClause = ' AND cp.status_id = %1';
        $params['1'] = [$_POST['status_id'], 'Integer'];
      }

      if (!empty($_POST['page_type'])) {
        $whereClause .= ' AND cp.page_type = %2';
        $params['2'] = [$_POST['page_type'], 'String'];
      }
      elseif (!empty($_GET['page_type'])) {
        $whereClause .= ' AND cp.page_type = %2';
        $params['2'] = [$_GET['page_type'], 'String'];
      }

      if (!empty($_POST['page_id'])) {
        $whereClause .= ' AND cp.page_id = %4 AND cp.page_type = "contribute"';
        $params['4'] = [$_POST['page_id'], 'Integer'];
      }

      if (!empty($_POST['event_id'])) {
        $whereClause .= ' AND cp.page_id = %5 AND cp.page_type = "event"';
        $params['5'] = [$_POST['event_id'], 'Integer'];
      }

      if ($whereClause) {
        $this->set('whereClause', $whereClause);
        $this->set('params', $params);
      }
      else {
        $this->set('whereClause', NULL);
        $this->set('params', NULL);
      }
    }

    $approvedId = CRM_Core_PseudoConstant::getKey('CRM_PCP_BAO_PCP', 'status_id', 'Approved');

    //check for delete CRM-4418
    $allowToDelete = CRM_Core_Permission::check('delete in CiviContribute');

    // get all contribution pages
    $query = "SELECT id, title, start_date, end_date FROM civicrm_contribution_page WHERE (1)";
    $cpages = CRM_Core_DAO::executeQuery($query);
    while ($cpages->fetch()) {
      $pages['contribute'][$cpages->id]['id'] = $cpages->id;
      $pages['contribute'][$cpages->id]['title'] = $cpages->title;
      $pages['contribute'][$cpages->id]['start_date'] = $cpages->start_date;
      $pages['contribute'][$cpages->id]['end_date'] = $cpages->end_date;
    }

    // get all event pages. pcp campaign start and end dates for event related pcp's use the online registration start and end dates,
    // although if target is contribution page this might not be correct. fixme? dgg
    $query = "SELECT id, title, start_date, end_date, registration_start_date, registration_end_date
                  FROM civicrm_event
                  WHERE is_template = 0";
    $epages = CRM_Core_DAO::executeQuery($query);
    while ($epages->fetch()) {
      $pages['event'][$epages->id]['id'] = $epages->id;
      $pages['event'][$epages->id]['title'] = $epages->title;
      $pages['event'][$epages->id]['start_date'] = $epages->registration_start_date;
      $pages['event'][$epages->id]['end_date'] = $epages->registration_end_date;
    }

    $params = $this->get('params') ?: [];

    $title = '1';
    if ($this->_sortByCharacter !== NULL) {
      $clauses[] = "cp.title LIKE '" . strtolower(CRM_Core_DAO::escapeWildCardString($this->_sortByCharacter)) . "%'";
    }

    $query = "
        SELECT cp.id, cp.contact_id , cp.status_id, cp.title, cp.is_active, cp.page_type, cp.page_id
        FROM civicrm_pcp cp
        WHERE $title" . $this->get('whereClause') . " ORDER BY cp.status_id";

    $pcp = CRM_Core_DAO::executeQuery($query, $params);
    while ($pcp->fetch()) {
      $action = array_sum(array_keys($this->links()));
      $contact = CRM_Contact_BAO_Contact::getDisplayAndImage($pcp->contact_id);

      $class = '';

      if ($pcp->status_id != $approvedId || $pcp->is_active != 1) {
        $class = 'disabled';
      }

      switch ($pcp->status_id) {
        case 2:
          $action -= CRM_Core_Action::RENEW;
          break;

        case 3:
          $action -= CRM_Core_Action::REVERT;
          break;
      }

      switch ($pcp->is_active) {
        case 1:
          $action -= CRM_Core_Action::ENABLE;
          break;

        case 0:
          $action -= CRM_Core_Action::DISABLE;
          break;
      }

      if (!$allowToDelete) {
        $action -= CRM_Core_Action::DELETE;
      }

      $page_type = $pcp->page_type;
      $page_id = (int) $pcp->page_id;
      if ($pages[$page_type][$page_id]['title'] == '' || $pages[$page_type][$page_id]['title'] == NULL) {
        $title = '(no title found for ' . $page_type . ' id ' . $page_id . ')';
      }
      else {
        $title = $pages[$page_type][$page_id]['title'];
      }

      if ($pcp->page_type == 'contribute') {
        $pageUrl = CRM_Utils_System::url('civicrm/' . $page_type . '/transact', 'reset=1&id=' . $pcp->page_id);
      }
      else {
        $pageUrl = CRM_Utils_System::url('civicrm/' . $page_type . '/register', 'reset=1&id=' . $pcp->page_id);
      }

      $pcpSummary[$pcp->id] = [
        'id' => $pcp->id,
        'start_date' => $pages[$page_type][$page_id]['start_date'],
        'end_date' => $pages[$page_type][$page_id]['end_date'],
        'supporter' => $contact['0'],
        'supporter_id' => $pcp->contact_id,
        'status_id' => $status[$pcp->status_id],
        'page_id' => $page_id,
        'page_title' => $title,
        'page_url' => $pageUrl,
        'page_type' => $page_type,
        'action' => CRM_Core_Action::formLink($this->links(), $action,
          ['id' => $pcp->id], ts('more'), FALSE, 'contributionpage.pcp.list', 'PCP', $pcp->id
        ),
        'title' => $pcp->title,
        'class' => $class,
      ];
    }

    $this->search();
    $this->pagerAToZ($this->get('whereClause'), $params);

    $this->assign('rows', $pcpSummary);

    // Let template know if user has run a search or not
    if ($this->get('whereClause')) {
      $this->assign('isSearch', 1);
    }
    else {
      $this->assign('isSearch', 0);
    }
  }

  public function search() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $form = new CRM_Core_Controller_Simple('CRM_PCP_Form_PCP', ts('Search Campaign Pages'), CRM_Core_Action::ADD);
    $form->setEmbedded(TRUE);
    $form->setParent($this);
    $form->process();
    $form->run();
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_PCP_Form_PCP';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return ts('Personal Campaign Page');
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
    return 'civicrm/admin/pcp';
  }

  /**
   * @TODO this function changed, debug this at runtime
   * @param $whereClause
   * @param array $whereParams
   */
  public function pagerAtoZ($whereClause, $whereParams) {
    $where = '';
    if ($whereClause) {
      if (strpos($whereClause, ' AND') == 0) {
        $whereClause = substr($whereClause, 4);
      }
      $where = 'WHERE ' . $whereClause;
    }

    $query = "
 SELECT UPPER(LEFT(cp.title, 1)) as sort_name
 FROM civicrm_pcp cp
   " . $where . "
 ORDER BY LEFT(cp.title, 1);
        ";

    $dao = CRM_Core_DAO::executeQuery($query, $whereParams);

    $aToZBar = CRM_Utils_PagerAToZ::getAToZBar($dao, $this->_sortByCharacter, TRUE);
    $this->assign('aToZ', $aToZBar);
  }

}
