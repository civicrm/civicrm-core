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
 * This implements the profile page for all contacts. It uses a selector
 * object to do the actual dispay. The fields displayd are controlled by
 * the admin
 */
class CRM_Profile_Page_Listings extends CRM_Core_Page {

  /**
   * All the fields that are listings related
   *
   * @var array
   */
  protected $_fields;

  /**
   * The custom fields for this domain
   *
   * @var array
   */
  protected $_customFields;

  /**
   * The input params from the request
   *
   * @var array
   */
  protected $_params;

  /**
   * The group id that we are editing
   *
   * @var int
   */
  protected $_gid;

  /**
   * State whether to display search form or not
   *
   * @var int
   */
  protected $_search;

  /**
   * Should we display a map
   *
   * @var int
   */
  protected $_map;

  /**
   * Store profile ids if multiple profile ids are passed using comma separated.
   * Currently lets implement this functionality only for dialog mode
   */
  protected $_profileIds = array();

  /**
   * Extracts the parameters from the request and constructs information for
   * the selector object to do a query
   *
   * @return void
   */
  public function preProcess() {

    $this->_search = TRUE;

    $search = CRM_Utils_Request::retrieve('search', 'Boolean', $this, FALSE, 0, 'GET');
    if (isset($search) && $search == 0) {
      $this->_search = FALSE;
    }

    $this->_gid = $this->get('gid');
    $this->_profileIds = $this->get('profileIds');

    $gids = explode(',', CRM_Utils_Request::retrieve('gid', 'String', CRM_Core_DAO::$_nullObject, FALSE, 0, 'GET'));

    if ((count($gids) > 1) && !$this->_profileIds && empty($this->_profileIds)) {
      if (!empty($gids)) {
        foreach ($gids as $pfId) {
          $this->_profileIds[] = CRM_Utils_Type::escape($pfId, 'Positive');
        }
      }

      // check if we are rendering mixed profiles
      if (CRM_Core_BAO_UFGroup::checkForMixProfiles($this->_profileIds)) {
        CRM_Core_Error::fatal(ts('You cannot combine profiles of multiple types.'));
      }

      $this->_gid = $this->_profileIds[0];
      $this->set('profileIds', $this->_profileIds);
      $this->set('gid', $this->_gid);
    }

    if (!$this->_gid) {
      $this->_gid = CRM_Utils_Request::retrieve('gid', 'Positive', $this, FALSE, 0, 'GET');
    }

    if (empty($this->_profileIds)) {
      $gids = $this->_gid;
    }
    else {
      $gids = $this->_profileIds;
    }

    $this->_fields = CRM_Core_BAO_UFGroup::getListingFields(CRM_Core_Action::UPDATE,
      CRM_Core_BAO_UFGroup::PUBLIC_VISIBILITY | CRM_Core_BAO_UFGroup::LISTINGS_VISIBILITY,
      FALSE, $gids, FALSE, 'Profile',
      CRM_Core_Permission::SEARCH
    );

    $this->_customFields = CRM_Core_BAO_CustomField::getFieldsForImport(NULL, FALSE, FALSE, FALSE, TRUE, TRUE);
    $this->_params = array();

    $resetArray = array(
      'group',
      'tag',
      'preferred_communication_method',
      'do_not_phone',
      'do_not_email',
      'do_not_mail',
      'do_not_sms',
      'do_not_trade',
      'gender',
    );

    foreach ($this->_fields as $name => $field) {
      if ((substr($name, 0, 6) == 'custom') && !empty($field['is_search_range'])) {
        $from = CRM_Utils_Request::retrieve($name . '_from', 'String', $this);
        $to = CRM_Utils_Request::retrieve($name . '_to', 'String', $this);
        $value = array();
        if ($from && $to) {
          $value['from'] = $from;
          $value['to'] = $to;
        }
        elseif ($from) {
          $value['from'] = $from;
        }
        elseif ($to) {
          $value['to'] = $to;
        }
      }
      elseif ((substr($name, 0, 7) == 'custom_') &&
        (CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
            substr($name, 7), 'html_type'
          ) == 'TextArea')
      ) {
        $value = trim(CRM_Utils_Request::retrieve($name, 'String',
          $this, FALSE, NULL, 'REQUEST'
        ));
        if (!empty($value) &&
          !((substr($value, 0, 1) == '%') &&
            (substr($value, -1, 1) == '%')
          )
        ) {
          $value = '%' . $value . '%';
        }
      }
      elseif (CRM_Utils_Array::value('html_type', $field) == 'Multi-Select State/Province'
        || CRM_Utils_Array::value('html_type', $field) == 'Multi-Select Country'
      ) {
        $value = CRM_Utils_Request::retrieve($name, 'String', $this, FALSE, NULL, 'REQUEST');
        if (!is_array($value)) {
          $value = explode(CRM_Core_DAO::VALUE_SEPARATOR, substr($value, 1, -1));
        }
      }
      elseif ($name == 'contact_sub_type') {
        $v = CRM_Utils_Request::retrieve($name, 'String', $this, FALSE, NULL, 'REQUEST');
        if ($v && !is_array($v)) {
          $v = explode(CRM_Core_DAO::VALUE_SEPARATOR, trim($v, CRM_Core_DAO::VALUE_SEPARATOR));
        }
        if (!empty($v)) {
          foreach ($v as $item) {
            $value[$item] = 1;
          }
        }
      }
      else {
        $value = CRM_Utils_Request::retrieve($name, 'String',
          $this, FALSE, NULL, 'REQUEST'
        );
      }

      if (($name == 'group' || $name == 'tag') && !empty($value) && !is_array($value)) {
        $v = explode(',', $value);
        $value = array();
        foreach ($v as $item) {
          $value[$item] = 1;
        }
      }

      $customField = CRM_Utils_Array::value($name, $this->_customFields);

      if (!empty($_POST) && empty($_POST[$name])) {
        if ($customField) {
          // reset checkbox/radio because a form does not send null checkbox values
          if (in_array($customField['html_type'],
            array('Multi-Select', 'CheckBox', 'Multi-Select State/Province', 'Multi-Select Country', 'Radio', 'Select')
          )) {
            // only reset on a POST submission if we dont see any value
            $value = NULL;
            $this->set($name, $value);
          }
        }
        elseif (in_array($name, $resetArray)) {
          $value = NULL;
          $this->set($name, $value);
        }
      }

      if (isset($value) && $value != NULL) {
        if (!is_array($value)) {
          $value = trim($value);
        }
        $operator = CRM_Utils_Request::retrieve($name . '_operator', 'String', $this);
        if ($operator) {
          $this->_params[$name . '_operator'] = $operator;
        }
        $this->_params[$name] = $this->_fields[$name]['value'] = $value;
      }
    }

    // set the prox params
    // need to ensure proximity searching is enabled
    $proximityVars = array(
      'street_address',
      'city',
      'postal_code',
      'state_province_id',
      'country_id',
      'distance',
      'distance_unit',
    );
    foreach ($proximityVars as $var) {
      $value = CRM_Utils_Request::retrieve("prox_{$var}",
        'String',
        $this, FALSE, NULL, 'REQUEST'
      );
      if ($value) {
        $this->_params["prox_{$var}"] = $value;
      }
    }

    // set the params in session
    $session = CRM_Core_Session::singleton();
    $session->set('profileParams', $this->_params);
  }

  /**
   * Run this page (figure out the action needed and perform it).
   *
   * @return void
   */
  public function run() {
    $this->preProcess();

    $this->assign('recentlyViewed', FALSE);
    // override later (if possible):
    $this->assign('ufGroupName', 'unknown');

    if ($this->_gid) {
      $ufgroupDAO = new CRM_Core_DAO_UFGroup();
      $ufgroupDAO->id = $this->_gid;
      if (!$ufgroupDAO->find(TRUE)) {
        CRM_Core_Error::fatal();
      }
    }

    if ($this->_gid) {
      // set the title of the page
      if ($ufgroupDAO->title) {
        CRM_Utils_System::setTitle($ufgroupDAO->title);
      }
      if ($ufgroupDAO->name) {
        $this->assign('ufGroupName', $ufgroupDAO->name);
      }
    }

    $this->assign('isReset', TRUE);

    $formController = new CRM_Core_Controller_Simple('CRM_Profile_Form_Search',
      ts('Search Profile'),
      CRM_Core_Action::ADD
    );
    $formController->setEmbedded(TRUE);
    $formController->set('gid', $this->_gid);
    $formController->process();

    $searchError = FALSE;
    // check if there is a POST
    if (!empty($_POST)) {
      if ($formController->validate() !== TRUE) {
        $searchError = TRUE;
      }
    }

    // also get the search tpl name
    $this->assign('searchTPL', $formController->getHookedTemplateFileName());

    $this->assign('search', $this->_search);

    // search if search returned a form error?
    if ((empty($_GET['reset']) || !empty($_GET['force'])) &&
      !$searchError
    ) {
      $this->assign('isReset', FALSE);

      $gidString = $this->_gid;
      if (empty($this->_profileIds)) {
        $gids = $this->_gid;
      }
      else {
        $gids = $this->_profileIds;
        $gidString = implode(',', $this->_profileIds);
      }

      $map = 0;
      $linkToUF = 0;
      $editLink = FALSE;
      if ($this->_gid) {
        $map = $ufgroupDAO->is_map;
        $linkToUF = $ufgroupDAO->is_uf_link;
        $editLink = $ufgroupDAO->is_edit_link;
      }

      if ($map) {
        $this->assign('mapURL',
          CRM_Utils_System::url('civicrm/profile/map',
            "map=1&gid={$gidString}&reset=1"
          )
        );
      }
      if (!empty($this->_params['group'])) {
        foreach ($this->_params['group'] as $key => $val) {
          if (!$val) {
            unset($this->_params['group'][$key]);
          }
        }
      }

      // the selector will override this if the user does have
      // edit permissions as determined by the mask, CRM-4341
      // do not allow edit for anon users in joomla frontend, CRM-4668
      $config = CRM_Core_Config::singleton();
      if (!CRM_Core_Permission::check('access CiviCRM') ||
        $config->userFrameworkFrontend == 1
      ) {
        $editLink = FALSE;
      }

      $selector = new CRM_Profile_Selector_Listings($this->_params, $this->_customFields, $gids,
        $map, $editLink, $linkToUF
      );

      $controller = new CRM_Core_Selector_Controller($selector,
        $this->get(CRM_Utils_Pager::PAGE_ID),
        $this->get(CRM_Utils_Sort::SORT_ID),
        CRM_Core_Action::VIEW,
        $this,
        CRM_Core_Selector_Controller::TEMPLATE
      );
      $controller->setEmbedded(TRUE);
      $controller->run();
    }

    //CRM-6862 -run form cotroller after
    //selector, since it erase $_POST
    $formController->run();

    return parent::run();
  }

  /**
   * Get the list of contacts for a profile.
   *
   * @param int $gid
   *
   * @return array
   */
  public static function getProfileContact($gid) {
    $session = CRM_Core_Session::singleton();
    $params = $session->get('profileParams');

    $details = array();
    $ufGroupParam = array('id' => $gid);
    CRM_Core_BAO_UFGroup::retrieve($ufGroupParam, $details);

    // make sure this group can be mapped
    if (!$details['is_map']) {
      CRM_Core_Error::statusBounce(ts('This profile does not have the map feature turned on.'));
    }

    $groupId = CRM_Utils_Array::value('limit_listings_group_id', $details);

    // add group id to params if a uf group belong to a any group
    if ($groupId) {
      if (!empty($params['group'])) {
        $params['group'][$groupId] = 1;
      }
      else {
        $params['group'] = array($groupId => 1);
      }
    }

    $fields = CRM_Core_BAO_UFGroup::getListingFields(
      CRM_Core_Action::VIEW,
      CRM_Core_BAO_UFGroup::PUBLIC_VISIBILITY | CRM_Core_BAO_UFGroup::LISTINGS_VISIBILITY,
      FALSE,
      $gid
    );

    $returnProperties = CRM_Contact_BAO_Contact::makeHierReturnProperties($fields);
    $returnProperties['contact_type'] = 1;
    $returnProperties['sort_name'] = 1;

    $queryParams = CRM_Contact_BAO_Query::convertFormValues($params, 1);
    $query = new CRM_Contact_BAO_Query($queryParams, $returnProperties, $fields);

    $additionalWhereClause = 'contact_a.is_deleted = 0';

    $ids = $query->searchQuery(0, 0, NULL,
      FALSE, FALSE, FALSE,
      TRUE, FALSE,
      $additionalWhereClause
    );

    $contactIds = explode(',', $ids);

    return $contactIds;
  }

  /**
   * @param string $suffix
   *
   * @return null|string
   */
  public function checkTemplateFileExists($suffix = '') {
    if ($this->_gid) {
      $templateFile = "CRM/Profile/Page/{$this->_gid}/Listings.{$suffix}tpl";
      $template = CRM_Core_Page::getTemplate();
      if ($template->template_exists($templateFile)) {
        return $templateFile;
      }

      // lets see if we have customized by name
      $ufGroupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $this->_gid, 'name');
      if ($ufGroupName) {
        $templateFile = "CRM/Profile/Page/{$ufGroupName}/Listings.{$suffix}tpl";
        if ($template->template_exists($templateFile)) {
          return $templateFile;
        }
      }
    }
    return NULL;
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  /**
   * @return string
   */
  public function getTemplateFileName() {
    $fileName = $this->checkTemplateFileExists();
    return $fileName ? $fileName : parent::getTemplateFileName();
  }

  /**
   * Default extra tpl file basically just replaces .tpl with .extra.tpl
   * i.e. we dont override
   *
   * @return string
   */
  /**
   * @return string
   */
  public function overrideExtraTemplateFileName() {
    $fileName = $this->checkTemplateFileExists('extra.');
    return $fileName ? $fileName : parent::overrideExtraTemplateFileName();
  }

}
