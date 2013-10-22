<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Page for displaying list of events
 */
class CRM_Event_Page_ManageEvent extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_actionLinks = NULL;

  static $_links = NULL;

  static $_tabLinks = NULL;

  protected $_pager = NULL;

  protected $_sortByCharacter;

  protected $_isTemplate = FALSE;

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_actionLinks)) {
      // helper variable for nicer formatting
      $copyExtra = ts('Are you sure you want to make a copy of this Event?');
      $deleteExtra = ts('Are you sure you want to delete this Event?');

      self::$_actionLinks = array(
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Event_BAO_Event' . '\',\'' . 'enable-disable' . '\' );"',
          'ref' => 'disable-action',
          'title' => ts('Disable Event'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Event_BAO_Event' . '\',\'' . 'disable-enable' . '\' );"',
          'ref' => 'enable-action',
          'title' => ts('Enable Event'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => CRM_Utils_System::currentPath(),
          'qs' => 'action=delete&id=%%id%%',
          'extra' => 'onclick = "return confirm(\'' . $deleteExtra . '\');"',
          'title' => ts('Delete Event'),
        ),
        CRM_Core_Action::COPY => array(
          'name' => ts('Copy'),
          'url' => CRM_Utils_System::currentPath(),
          'qs' => 'reset=1&action=copy&id=%%id%%',
          'extra' => 'onclick = "return confirm(\'' . $copyExtra . '\');"',
          'title' => ts('Copy Event'),
        ),
      );
    }
    return self::$_actionLinks;
  }

  /**
   * Get tab  Links for events
   *
   * @return array (reference) of tab links
   */
  static function &tabs($enableCart) {
    $cacheKey = $enableCart ? 1 : 0;
    if (!(self::$_tabLinks)) {
      self::$_tabLinks = array();
    }
    if (!isset(self::$_tabLinks[$cacheKey])) {
      self::$_tabLinks[$cacheKey] = array(
        'settings' => array(
          'title' => ts('Info and Settings'),
          'url' => 'civicrm/event/manage/settings',
          'field' => 'id'
        ),
        'location' => array(
          'title' => ts('Location'),
          'url' => 'civicrm/event/manage/location',
          'field' => 'loc_block_id',
        ),
        'fee' => array(
          'title' => ts('Fees'),
          'url' => 'civicrm/event/manage/fee',
          'field' => 'is_monetary',
        ),
        'registration' => array(
          'title' => ts('Online Registration'),
          'url' => 'civicrm/event/manage/registration',
          'field' => 'is_online_registration',
        ),
        'reminder' => array(
          'title' => ts('Schedule Reminders'),
          'url' => 'civicrm/event/manage/reminder',
          'field' => 'reminder',
        ),
        'conference' => array(
          'title' => ts('Conference Slots'),
          'url' => 'civicrm/event/manage/conference',
          'field' => 'slot_label_id',
        ),
        'friend' => array(
          'title' => ts('Tell a Friend'),
          'url' => 'civicrm/event/manage/friend',
          'field' => 'friend',
        ),
        'pcp' => array(
          'title' => ts('Personal Campaign Pages'),
          'url' => 'civicrm/event/manage/pcp',
          'field' => 'is_pcp_enabled',
        ),
      );
    }

    if (!$enableCart) {
      unset(self::$_tabLinks[$cacheKey]['conference']);
    }

    CRM_Utils_Hook::tabset('civicrm/event/manage', self::$_tabLinks[$cacheKey], array());
    return self::$_tabLinks[$cacheKey];
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
      $this, FALSE, 0, 'REQUEST'
    );

    // figure out whether we’re handling an event or an event template
    if ($id) {
      $this->_isTemplate = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $id, 'is_template');
    }
    elseif ($action & CRM_Core_Action::ADD) {
      $this->_isTemplate = CRM_Utils_Request::retrieve('is_template', 'Boolean', $this);
    }

    if (!$this->_isTemplate && $id) {
      $breadCrumb = array(array('title' => ts('Manage Events'),
          'url' => CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'reset=1'),
        ));
      CRM_Utils_System::appendBreadCrumb($breadCrumb);
    }

    // what action to take ?
    if ($action & CRM_Core_Action::DELETE) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'reset=1&action=browse'));
      $controller = new CRM_Core_Controller_Simple('CRM_Event_Form_ManageEvent_Delete',
        'Delete Event',
        $action
      );
      $controller->set('id', $id);
      $controller->process();
      return $controller->run();
    }
    elseif ($action & CRM_Core_Action::COPY) {
      $this->copy();
    }

    // finally browse the custom groups
    $this->browse();

    // parent run
    return parent::run();
  }

  /**
   * browse all events
   *
   * @return void
   */
  function browse() {
    $this->_sortByCharacter = CRM_Utils_Request::retrieve('sortByCharacter',
      'String',
      $this
    );
    $createdId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE, 0);
    if (strtolower($this->_sortByCharacter) == 'all' ||
      !empty($_POST)
    ) {
      $this->_sortByCharacter = '';
      $this->set('sortByCharacter', '');
    }

    $this->_force = $this->_searchResult = NULL;

    $this->search();

    $params = array();
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean',
      $this, FALSE
    );
    $this->_searchResult = CRM_Utils_Request::retrieve('searchResult', 'Boolean', $this);

    $whereClause = $this->whereClause($params, FALSE, $this->_force);
    $this->pagerAToZ($whereClause, $params);

    $params = array();
    $whereClause = $this->whereClause($params, TRUE, $this->_force);
    // because is_template != 1 would be to simple
    $whereClause .= ' AND (is_template = 0 OR is_template IS NULL)';

    $this->pager($whereClause, $params);

    list($offset, $rowCount) = $this->_pager->getOffsetAndRowCount();

    // get all custom groups sorted by weight
    $manageEvent = array();

    $query = "
  SELECT *
    FROM civicrm_event
   WHERE $whereClause
ORDER BY start_date desc
   LIMIT $offset, $rowCount";

    $dao = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Event_DAO_Event');
    $permissions = CRM_Event_BAO_Event::checkPermission();

    //get all campaigns.
    $allCampaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);

    // get the list of active event pcps
    $eventPCPS = array();

    $pcpDao = new CRM_PCP_DAO_PCPBlock;
    $pcpDao->entity_table = 'civicrm_event';
    $pcpDao->find();

    while ($pcpDao->fetch()) {
      $eventPCPS[$pcpDao->entity_id] = $pcpDao->entity_id;
    }
    // check if we're in shopping cart mode for events
    $enableCart = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::EVENT_PREFERENCES_NAME,
      'enable_cart'
    );
    $this->assign('eventCartEnabled', $enableCart);
    $mappingID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_ActionMapping', 'civicrm_event', 'id', 'entity_value');
    $eventType = CRM_Core_OptionGroup::values('event_type');
    while ($dao->fetch()) {
      if (in_array($dao->id, $permissions[CRM_Core_Permission::VIEW])) {
        $manageEvent[$dao->id] = array();
        CRM_Core_DAO::storeValues($dao, $manageEvent[$dao->id]);

        // form all action links
        $action = array_sum(array_keys($this->links()));

        if ($dao->is_active) {
          $action -= CRM_Core_Action::ENABLE;
        }
        else {
          $action -= CRM_Core_Action::DISABLE;
        }

        if (!in_array($dao->id, $permissions[CRM_Core_Permission::DELETE])) {
          $action -= CRM_Core_Action::DELETE;
        }
        if (!in_array($dao->id, $permissions[CRM_Core_Permission::EDIT])) {
          $action -= CRM_Core_Action::UPDATE;
        }

        $manageEvent[$dao->id]['action'] = CRM_Core_Action::formLink(self::links(),
          $action,
          array('id' => $dao->id),
          ts('more'),
          TRUE
        );

        $params = array(
          'entity_id' => $dao->id,
          'entity_table' => 'civicrm_event',
          'is_active' => 1,
        );

        $defaults['location'] = CRM_Core_BAO_Location::getValues($params, TRUE);

        $manageEvent[$dao->id]['friend'] = CRM_Friend_BAO_Friend::getValues($params);

        if (isset($defaults['location']['address'][1]['city'])) {
          $manageEvent[$dao->id]['city'] = $defaults['location']['address'][1]['city'];
        }
        if (isset($defaults['location']['address'][1]['state_province_id'])) {
          $manageEvent[$dao->id]['state_province'] = CRM_Core_PseudoConstant::stateProvince($defaults['location']['address'][1]['state_province_id']);
        }

        //show campaigns on selector.
        $manageEvent[$dao->id]['campaign'] = CRM_Utils_Array::value($dao->campaign_id, $allCampaigns);
        $manageEvent[$dao->id]['reminder'] = CRM_Core_BAO_ActionSchedule::isConfigured($dao->id, $mappingID);
        $manageEvent[$dao->id]['is_pcp_enabled'] = CRM_Utils_Array::value($dao->id, $eventPCPS);
        $manageEvent[$dao->id]['event_type'] = CRM_Utils_Array::value($manageEvent[$dao->id]['event_type_id'], $eventType);
        
        // allow hooks to set 'field' value which allows configuration pop-up to show a tab as enabled/disabled
        CRM_Utils_Hook::tabset('civicrm/event/manage/rows', $manageEvent, array('event_id' => $dao->id));
      }
    }

    $manageEvent['tab'] = self::tabs($enableCart);
    $this->assign('rows', $manageEvent);

    $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 1');
    $statusTypesPending = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 0');
    $findParticipants['statusCounted'] = implode(', ', array_values($statusTypes));
    $findParticipants['statusNotCounted'] = implode(', ', array_values($statusTypesPending));
    $this->assign('findParticipants', $findParticipants);
  }

  /**
   * This function is to make a copy of a Event, including
   * all the fields in the event wizard
   *
   * @return void
   * @access public
   */
  function copy() {
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE, 0, 'GET');

    $urlString = 'civicrm/event/manage';
    $copyEvent = CRM_Event_BAO_Event::copy($id);
    $urlParams = 'reset=1';
    // Redirect to Copied Event Configuration
    if ($copyEvent->id) {
      $urlString = 'civicrm/event/manage/settings';
      $urlParams .= '&action=update&id=' . $copyEvent->id;
    }

    return CRM_Utils_System::redirect(CRM_Utils_System::url($urlString, $urlParams));
  }

  function search() {
    if (isset($this->_action) &
      (CRM_Core_Action::ADD |
        CRM_Core_Action::UPDATE |
        CRM_Core_Action::DELETE
      )
    ) {
      return;
    }

    $form = new CRM_Core_Controller_Simple('CRM_Event_Form_SearchEvent', ts('Search Events'), CRM_Core_Action::ADD);
    $form->setEmbedded(TRUE);
    $form->setParent($this);
    $form->process();
    $form->run();
  }

  function whereClause(&$params, $sortBy = TRUE, $force) {
    $values    = array();
    $clauses   = array();
    $title     = $this->get('title');
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

    $value = $this->get('event_type_id');
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
      $clauses[] = "event_type_id IN ({$type})";
    }

    $eventsByDates = $this->get('eventsByDates');
    if ($this->_searchResult) {
      if ($eventsByDates) {

        $from = $this->get('start_date');
        if (!CRM_Utils_System::isNull($from)) {
          $clauses[] = '( start_date >= %3 OR start_date IS NULL )';
          $params[3] = array($from, 'String');
        }

        $to = $this->get('end_date');
        if (!CRM_Utils_System::isNull($to)) {
          $clauses[] = '( end_date <= %4 OR end_date IS NULL )';
          $params[4] = array($to, 'String');
        }
      }
      else {
        $curDate = date('YmdHis');
        $clauses[5] = "(end_date >= {$curDate} OR end_date IS NULL)";
      }
    }
    else {
      $curDate = date('YmdHis');
      $clauses[] = "(end_date >= {$curDate} OR end_date IS NULL)";
    }

    if ($sortBy &&
      $this->_sortByCharacter !== NULL
    ) {
      $clauses[] = "title LIKE '" . strtolower(CRM_Core_DAO::escapeWildCardString($this->_sortByCharacter)) . "%'";
    }

    $campaignIds = $this->get('campaign_id');
    if (!CRM_Utils_System::isNull($campaignIds)) {
      if (!is_array($campaignIds)) {
        $campaignIds = array($campaignIds);
      }
      $clauses[] = '( campaign_id IN ( ' . implode(' , ', array_values($campaignIds)) . ' ) )';
    }

    // don't do a the below assignment when doing a
    // AtoZ pager clause
    if ($sortBy) {
      if (count($clauses) > 1 || $eventsByDates) {
        $this->assign('isSearch', 1);
      }
      else {
        $this->assign('isSearch', 0);
      }
    }

    return !empty($clauses) ? implode(' AND ', $clauses) : '(1)';
  }

  function pager($whereClause, $whereParams) {

    $params['status'] = ts('Event %%StatusMessage%%');
    $params['csvString'] = NULL;
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
    $params['rowCount'] = $this->get(CRM_Utils_Pager::PAGE_ROWCOUNT);
    if (!$params['rowCount']) {
      $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    }

    $query = "
SELECT count(id)
  FROM civicrm_event
 WHERE $whereClause";

    $params['total'] = CRM_Core_DAO::singleValueQuery($query, $whereParams);

    $this->_pager = new CRM_Utils_Pager($params);
    $this->assign_by_ref('pager', $this->_pager);
  }

  function pagerAtoZ($whereClause, $whereParams) {

    $query = "
   SELECT DISTINCT UPPER(LEFT(title, 1)) as sort_name
     FROM civicrm_event
    WHERE $whereClause
 ORDER BY LEFT(title, 1)
";
    $dao = CRM_Core_DAO::executeQuery($query, $whereParams);

    $aToZBar = CRM_Utils_PagerAToZ::getAToZBar($dao, $this->_sortByCharacter, TRUE);
    $this->assign('aToZ', $aToZBar);
  }
}

