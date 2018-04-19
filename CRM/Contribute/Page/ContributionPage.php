<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Create a page for displaying Contribute Pages
 * Contribute Pages are pages that are used to display
 * contributions of different types. Pages consist
 * of many customizable sections which can be
 * accessed.
 *
 * This page provides a top level browse view
 * of all the contribution pages in the system.
 *
 */
class CRM_Contribute_Page_ContributionPage extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  private static $_actionLinks;
  private static $_contributionLinks;
  private static $_configureActionLinks;
  private static $_onlineContributionLinks;

  /**
   * @var CRM_Utils_Pager
   */
  protected $_pager = NULL;

  /**
   * @var string
   */
  protected $_sortByCharacter;

  /**
   * Get the action links for this page.
   *
   * @return array
   */
  public static function &actionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_actionLinks)) {
      // helper variable for nicer formatting
      $deleteExtra = ts('Are you sure you want to delete this Contribution page?');
      $copyExtra = ts('Are you sure you want to make a copy of this Contribution page?');

      self::$_actionLinks = array(
        CRM_Core_Action::COPY => array(
          'name' => ts('Make a Copy'),
          'url' => CRM_Utils_System::currentPath(),
          'qs' => 'action=copy&gid=%%id%%',
          'title' => ts('Make a Copy of CiviCRM Contribution Page'),
          'extra' => 'onclick = "return confirm(\'' . $copyExtra . '\');"',
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'title' => ts('Disable'),
          'ref' => 'crm-enable-disable',
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => CRM_Utils_System::currentPath(),
          'qs' => 'action=delete&reset=1&id=%%id%%',
          'title' => ts('Delete Custom Field'),
          'extra' => 'onclick = "return confirm(\'' . $deleteExtra . '\');"',
        ),
      );
    }
    return self::$_actionLinks;
  }

  /**
   * Get the configure action links for this page.
   *
   * @return array
   */
  public function &configureActionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_configureActionLinks)) {
      $urlString = 'civicrm/admin/contribute/';
      $urlParams = 'reset=1&action=update&id=%%id%%';

      self::$_configureActionLinks = array(
        CRM_Core_Action::ADD => array(
          'name' => ts('Title and Settings'),
          'title' => ts('Title and Settings'),
          'url' => $urlString . 'settings',
          'qs' => $urlParams,
          'uniqueName' => 'settings',
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Contribution Amounts'),
          'title' => ts('Contribution Amounts'),
          'url' => $urlString . 'amount',
          'qs' => $urlParams,
          'uniqueName' => 'amount',
        ),
        CRM_Core_Action::VIEW => array(
          'name' => ts('Membership Settings'),
          'title' => ts('Membership Settings'),
          'url' => $urlString . 'membership',
          'qs' => $urlParams,
          'uniqueName' => 'membership',
        ),
        CRM_Core_Action::EXPORT => array(
          'name' => ts('Thank-you and Receipting'),
          'title' => ts('Thank-you and Receipting'),
          'url' => $urlString . 'thankyou',
          'qs' => $urlParams,
          'uniqueName' => 'thankyou',
        ),
        CRM_Core_Action::BASIC => array(
          'name' => ts('Tell a Friend'),
          'title' => ts('Tell a Friend'),
          'url' => $urlString . 'friend',
          'qs' => $urlParams,
          'uniqueName' => 'friend',
        ),
        CRM_Core_Action::PROFILE => array(
          'name' => ts('Include Profiles'),
          'title' => ts('Include Profiles'),
          'url' => $urlString . 'custom',
          'qs' => $urlParams,
          'uniqueName' => 'custom',
        ),
        CRM_Core_Action::MAP => array(
          'name' => ts('Contribution Widget'),
          'title' => ts('Contribution Widget'),
          'url' => $urlString . 'widget',
          'qs' => $urlParams,
          'uniqueName' => 'widget',
        ),
        CRM_Core_Action::FOLLOWUP => array(
          'name' => ts('Premiums'),
          'title' => ts('Premiums'),
          'url' => $urlString . 'premium',
          'qs' => $urlParams,
          'uniqueName' => 'premium',
        ),
        CRM_Core_Action::ADVANCED => array(
          'name' => ts('Personal Campaign Pages'),
          'title' => ts('Personal Campaign Pages'),
          'url' => $urlString . 'pcp',
          'qs' => $urlParams,
          'uniqueName' => 'pcp',
        ),
      );
      $context = array(
        'urlString' => $urlString,
        'urlParams' => $urlParams,
      );
      CRM_Utils_Hook::tabset('civicrm/admin/contribute', self::$_configureActionLinks, $context);
    }

    return self::$_configureActionLinks;
  }

  /**
   * Get the online contribution links.
   *
   * @return array
   */
  public function &onlineContributionLinks() {
    if (!isset(self::$_onlineContributionLinks)) {
      $urlString = 'civicrm/contribute/transact';
      $urlParams = 'reset=1&id=%%id%%';
      self::$_onlineContributionLinks = array(
        CRM_Core_Action::RENEW => array(
          'name' => ts('Live Page'),
          'title' => ts('Live Page'),
          'url' => $urlString,
          'qs' => $urlParams,
          'fe' => TRUE,
          'uniqueName' => 'live_page',
        ),
        CRM_Core_Action::PREVIEW => array(
          'name' => ts('Test-drive'),
          'title' => ts('Test-drive'),
          'url' => $urlString,
          'qs' => $urlParams . '&action=preview',
          'uniqueName' => 'test_drive',
        ),
      );
    }

    return self::$_onlineContributionLinks;
  }

  /**
   * Get the contributions links.
   *
   * @return array
   */
  public function &contributionLinks() {
    if (!isset(self::$_contributionLinks)) {
      //get contribution dates.
      $dates = CRM_Contribute_BAO_Contribution::getContributionDates();
      $now = $dates['now'];
      $yearDate = $dates['yearDate'];
      $monthDate = $dates['monthDate'];
      $yearNow = $yearDate + 10000;

      $urlString = 'civicrm/contribute/search';
      $urlParams = 'reset=1&pid=%%id%%&force=1&test=0';

      self::$_contributionLinks = array(
        CRM_Core_Action::DETACH => array(
          'name' => ts('Current Month-To-Date'),
          'title' => ts('Current Month-To-Date'),
          'url' => $urlString,
          'qs' => "{$urlParams}&start={$monthDate}&end={$now}",
          'uniqueName' => 'current_month_to_date',
        ),
        CRM_Core_Action::REVERT => array(
          'name' => ts('Fiscal Year-To-Date'),
          'title' => ts('Fiscal Year-To-Date'),
          'url' => $urlString,
          'qs' => "{$urlParams}&start={$yearDate}&end={$yearNow}",
          'uniqueName' => 'fiscal_year_to_date',
        ),
        CRM_Core_Action::BROWSE => array(
          'name' => ts('Cumulative'),
          'title' => ts('Cumulative'),
          'url' => $urlString,
          'qs' => "{$urlParams}&start=&end=$now",
          'uniqueName' => 'cumulative',
        ),
      );
    }

    return self::$_contributionLinks;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   *
   * @return mixed
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

    // set breadcrumb to append to 2nd layer pages
    $breadCrumb = array(
      array(
        'title' => ts('Manage Contribution Pages'),
        'url' => CRM_Utils_System::url(CRM_Utils_System::currentPath(),
          'reset=1'
        ),
      ),
    );

    // what action to take ?
    if ($action & CRM_Core_Action::ADD) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(),
        'action=browse&reset=1'
      ));

      $controller = new CRM_Contribute_Controller_ContributionPage(NULL, $action);
      CRM_Utils_System::setTitle(ts('Manage Contribution Page'));
      CRM_Utils_System::appendBreadCrumb($breadCrumb);
      return $controller->run();
    }
    elseif ($action & CRM_Core_Action::UPDATE) {
      $config = CRM_Core_Config::singleton();

      // assign vars to templates
      $this->assign('id', $id);
      $this->assign('title', CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $id, 'title'));
      $this->assign('is_active', CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $id, 'is_active'));
      if (in_array('CiviMember', $config->enableComponents)) {
        $this->assign('CiviMember', TRUE);
      }
    }
    elseif ($action & CRM_Core_Action::COPY) {
      // @todo Unused local variable can be safely removed.
      // But are there any side effects of CRM_Core_Session::singleton() that we
      // need to preserve?
      $session = CRM_Core_Session::singleton();
      CRM_Core_Session::setStatus(ts('A copy of the contribution page has been created'), ts('Successfully Copied'), 'success');
      $this->copy();
    }
    elseif ($action & CRM_Core_Action::DELETE) {
      CRM_Utils_System::appendBreadCrumb($breadCrumb);

      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(),
        'reset=1&action=browse'
      ));

      $id = CRM_Utils_Request::retrieve('id', 'Positive',
        $this, FALSE, 0
      );
      $query = "
SELECT      ccp.title
FROM        civicrm_contribution_page ccp
JOIN        civicrm_pcp cp ON ccp.id = cp.page_id
WHERE       cp.page_id = {$id}
AND         cp.page_type = 'contribute'
";

      if ($pageTitle = CRM_Core_DAO::singleValueQuery($query)) {
        CRM_Core_Session::setStatus(ts('The \'%1\' cannot be deleted! You must Delete all Personal Campaign Page(s) related with this contribution page prior to deleting the page.', array(1 => $pageTitle)), ts('Deletion Error'), 'error');

        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/contribute', 'reset=1'));
      }

      $controller = new CRM_Core_Controller_Simple('CRM_Contribute_Form_ContributionPage_Delete',
        'Delete Contribution Page',
        CRM_Core_Action::DELETE
      );
      $controller->set('id', $id);
      $controller->process();
      return $controller->run();
    }
    else {
      // finally browse the contribution pages
      $this->browse();

      CRM_Utils_System::setTitle(ts('Manage Contribution Pages'));
    }

    return parent::run();
  }

  /**
   * Make a copy of a contribution page, including all the fields in the page.
   */
  public function copy() {
    $gid = CRM_Utils_Request::retrieve('gid', 'Positive',
      $this, TRUE, 0, 'GET'
    );

    CRM_Contribute_BAO_ContributionPage::copy($gid);

    CRM_Utils_System::redirect(CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'reset=1'));
  }

  /**
   * Browse all contribution pages.
   *
   * @param mixed $action
   *   Unused parameter.
   */
  public function browse($action = NULL) {
    Civi::resources()->addStyleFile('civicrm', 'css/searchForm.css', 1, 'html-header');

    $this->_sortByCharacter = CRM_Utils_Request::retrieve('sortByCharacter',
      'String',
      $this
    );
    // @todo Unused local variable can be safely removed.
    // But are there any side effects of CRM_Utils_Request::retrieve() that we
    // need to preserve?
    $createdId = CRM_Utils_Request::retrieve('cid', 'Positive',
      $this, FALSE, 0
    );

    if ($this->_sortByCharacter == 'all' ||
      !empty($_POST)
    ) {
      $this->_sortByCharacter = '';
      $this->set('sortByCharacter', '');
    }

    $this->search();

    $params = array();

    $whereClause = $this->whereClause($params, FALSE);
    $this->pagerAToZ($whereClause, $params);

    $params = array();
    $whereClause = $this->whereClause($params, TRUE);
    $this->pager($whereClause, $params);

    list($offset, $rowCount) = $this->_pager->getOffsetAndRowCount();

    //check for delete CRM-4418
    $allowToDelete = CRM_Core_Permission::check('delete in CiviContribute');

    $query = "
  SELECT  id
    FROM  civicrm_contribution_page
   WHERE  $whereClause
   ORDER BY is_active desc, title asc
   LIMIT  $offset, $rowCount";
    $contribPage = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Contribute_DAO_ContributionPage');
    $contribPageIds = array();
    while ($contribPage->fetch()) {
      $contribPageIds[$contribPage->id] = $contribPage->id;
    }
    //get all section info.
    $contriPageSectionInfo = CRM_Contribute_BAO_ContributionPage::getSectionInfo($contribPageIds);

    $query = "
SELECT *
FROM civicrm_contribution_page
WHERE $whereClause
ORDER BY is_active desc, title asc
   LIMIT $offset, $rowCount";

    $dao = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Contribute_DAO_ContributionPage');

    //get all campaigns.
    $allCampaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);

    //get configure actions links.
    $configureActionLinks = self::configureActionLinks();

    while ($dao->fetch()) {
      $contribution[$dao->id] = array();
      CRM_Core_DAO::storeValues($dao, $contribution[$dao->id]);

      // form all action links
      $action = array_sum(array_keys(self::actionLinks()));

      //add configure actions links.
      $action += array_sum(array_keys($configureActionLinks));

      //add online contribution links.
      $action += array_sum(array_keys(self::onlineContributionLinks()));

      //add contribution search links.
      $action += array_sum(array_keys(self::contributionLinks()));

      if ($dao->is_active) {
        $action -= (int) CRM_Core_Action::ENABLE;
      }
      else {
        $action -= (int) CRM_Core_Action::DISABLE;
      }

      //CRM-4418
      if (!$allowToDelete) {
        $action -= (int) CRM_Core_Action::DELETE;
      }

      //build the configure links.
      $sectionsInfo = CRM_Utils_Array::value($dao->id, $contriPageSectionInfo, array());
      $contribution[$dao->id]['configureActionLinks'] = CRM_Core_Action::formLink(self::formatConfigureLinks($sectionsInfo),
        $action,
        array('id' => $dao->id),
        ts('Configure'),
        TRUE,
        'contributionpage.configure.actions',
        'ContributionPage',
        $dao->id
      );

      //build the contributions links.
      $contribution[$dao->id]['contributionLinks'] = CRM_Core_Action::formLink(self::contributionLinks(),
        $action,
        array('id' => $dao->id),
        ts('Contributions'),
        TRUE,
        'contributionpage.contributions.search',
        'ContributionPage',
        $dao->id
      );

      //build the online contribution links.
      $contribution[$dao->id]['onlineContributionLinks'] = CRM_Core_Action::formLink(self::onlineContributionLinks(),
        $action,
        array('id' => $dao->id),
        ts('Links'),
        TRUE,
        'contributionpage.online.links',
        'ContributionPage',
        $dao->id
      );

      //build the normal action links.
      $contribution[$dao->id]['action'] = CRM_Core_Action::formLink(self::actionLinks(),
        $action,
        array('id' => $dao->id),
        ts('more'),
        TRUE,
        'contributionpage.action.links',
        'ContributionPage',
        $dao->id
      );

      //show campaigns on selector.
      $contribution[$dao->id]['campaign'] = CRM_Utils_Array::value($dao->campaign_id, $allCampaigns);
    }

    if (isset($contribution)) {
      $this->assign('rows', $contribution);
    }
  }

  public function search() {
    if (isset($this->_action) & (CRM_Core_Action::ADD |
        CRM_Core_Action::UPDATE |
        CRM_Core_Action::DELETE
      )
    ) {
      return;
    }

    $form = new CRM_Core_Controller_Simple('CRM_Contribute_Form_SearchContribution',
      ts('Search Contribution'),
      CRM_Core_Action::ADD
    );
    $form->setEmbedded(TRUE);
    $form->setParent($this);
    $form->process();
    $form->run();
  }

  /**
   * @param array $params
   * @param bool $sortBy
   *
   * @return int|string
   */
  public function whereClause(&$params, $sortBy = TRUE) {
    // @todo Unused local variable can be safely removed.
    $values = $clauses = array();
    $title = $this->get('title');
    $createdId = $this->get('cid');

    if ($createdId) {
      $clauses[] = "(created_id = {$createdId})";
    }

    if ($title) {
      $clauses[] = "title LIKE %1";
      if (strpos($title, '%') !== FALSE) {
        $params[1] = array(trim($title), 'String', FALSE);
      }
      else {
        $params[1] = array(trim($title), 'String', TRUE);
      }
    }

    $value = $this->get('financial_type_id');
    $val = array();
    if ($value) {
      if (is_array($value)) {
        foreach ($value as $k => $v) {
          if ($v) {
            $val[$k] = $k;
          }
        }
        $type = implode(',', $val);
      }
      // @todo Variable 'type' might not have been defined.
      $clauses[] = "financial_type_id IN ({$type})";
    }

    if ($sortBy && $this->_sortByCharacter !== NULL) {
      $clauses[] = "title LIKE '" . strtolower(CRM_Core_DAO::escapeWildCardString($this->_sortByCharacter)) . "%'";
    }

    $campaignIds = $this->getCampaignIds();
    if (count($campaignIds) >= 1) {
      $clauses[] = '( campaign_id IN ( ' . implode(' , ', $campaignIds) . ' ) )';
    }

    if (empty($clauses)) {
      // Let template know if user has run a search or not
      $this->assign('isSearch', 0);
      return 1;
    }
    else {
      $this->assign('isSearch', 1);
    }

    return implode(' AND ', $clauses);
  }

  /**
   * Gets the campaign ids from the session.
   *
   * @return int[]
   */
  public function getCampaignIds() {
    // The unfiltered value from the session cannot be trusted, it needs to be
    // processed to get a clean array of positive integers.
    $ids = array();
    foreach ((array) $this->get('campaign_id') as $id) {
      if ((string) (int) $id === (string) $id && $id > 0) {
        $ids[] = $id;
      }
    }
    return $ids;
  }

  /**
   * @param $whereClause
   * @param array $whereParams
   */
  public function pager($whereClause, $whereParams) {

    $params['status'] = ts('Contribution %%StatusMessage%%');
    $params['csvString'] = NULL;
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
    $params['rowCount'] = $this->get(CRM_Utils_Pager::PAGE_ROWCOUNT);
    if (!$params['rowCount']) {
      $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    }

    $query = "
SELECT count(id)
FROM civicrm_contribution_page
WHERE $whereClause";

    $params['total'] = CRM_Core_DAO::singleValueQuery($query, $whereParams);

    $this->_pager = new CRM_Utils_Pager($params);
    $this->assign_by_ref('pager', $this->_pager);
  }

  /**
   * @param $whereClause
   * @param array $whereParams
   */
  public function pagerAtoZ($whereClause, $whereParams) {

    $query = "
SELECT DISTINCT UPPER(LEFT(title, 1)) as sort_name
FROM civicrm_contribution_page
WHERE $whereClause
ORDER BY UPPER(LEFT(title, 1))
";
    $dao = CRM_Core_DAO::executeQuery($query, $whereParams);

    $aToZBar = CRM_Utils_PagerAToZ::getAToZBar($dao, $this->_sortByCharacter, TRUE);
    $this->assign('aToZ', $aToZBar);
  }

  /**
   * @param array $sectionsInfo
   *
   * @return array
   */
  public function formatConfigureLinks($sectionsInfo) {
    // build the formatted configure links.
    $formattedConfLinks = self::configureActionLinks();
    foreach ($formattedConfLinks as $act => & $link) {
      $sectionName = CRM_Utils_Array::value('uniqueName', $link);
      if (!$sectionName) {
        continue;
      }

      if (empty($sectionsInfo[$sectionName])) {
        $classes = array();
        if (isset($link['class'])) {
          $classes = $link['class'];
        }
        $link['class'] = array_merge($classes, array('disabled'));
      }
    }

    return $formattedConfLinks;
  }

}
