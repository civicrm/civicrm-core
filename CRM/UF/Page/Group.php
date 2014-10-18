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

/**
 * Create a page for displaying UF Groups.
 *
 * Heart of this class is the run method which checks
 * for action type and then displays the appropriate
 * page.
 *
 */
class CRM_UF_Page_Group extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   */
  private static $_actionLinks = NULL;

  /**
   * Get the action links for this page.
   *
   * @param
   *
   * @return array $_actionLinks
   *
   */
  function &actionLinks() {
    // check if variable _actionsLinks is populated
    if (!self::$_actionLinks) {
      // helper variable for nicer formatting
      $copyExtra = ts('Are you sure you want to make a copy of this Profile?');
      self::$_actionLinks = array(
        CRM_Core_Action::BROWSE => array(
          'name' => ts('Fields'),
          'url' => 'civicrm/admin/uf/group/field',
          'qs' => 'reset=1&action=browse&gid=%%id%%',
          'title' => ts('View and Edit Fields'),
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Settings'),
          'url' => 'civicrm/admin/uf/group/update',
          'qs' => 'action=update&id=%%id%%&context=group',
          'title' => ts('Edit CiviCRM Profile Group'),
        ),
        CRM_Core_Action::PREVIEW => array(
          'name' => ts('Preview'),
          'url' => 'civicrm/admin/uf/group',
          'qs' => 'action=preview&id=%%id%%&field=0&context=group',
          'title' => ts('Edit CiviCRM Profile Group'),
        ),
        CRM_Core_Action::ADD => array(
          'name' => ts('Use Profile-Create Mode'),
          'url' => 'civicrm/profile/create',
          'qs' => 'gid=%%id%%&reset=1',
          'title' => ts('Use Profile-Create Mode'),
          'fe' => true,
        ),
        CRM_Core_Action::BASIC => array(
          'name' => ts('Use Profile-Listings Mode'),
          'url' => 'civicrm/profile',
          'qs' => 'gid=%%id%%&reset=1',
          'title' => ts('Use Profile-Listings Mode'),
          'fe' => true,
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable CiviCRM Profile Group'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable CiviCRM Profile Group'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/uf/group',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete CiviCRM Profile Group'),
        ),
        CRM_Core_Action::PROFILE => array(
          'name' => ts('HTML Form Snippet'),
          'url' => 'civicrm/admin/uf/group',
          'qs' => 'action=profile&gid=%%id%%',
          'title' => ts('HTML Form Snippet for this Profile'),
        ),
        CRM_Core_Action::COPY => array(
          'name' => ts('Copy Profile'),
          'url' => 'civicrm/admin/uf/group',
          'qs' => 'action=copy&gid=%%id%%',
          'title' => ts('Make a Copy of CiviCRM Profile Group'),
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
   * @param
   *
   * @return void
   * @access public
   */
  function run() {
    // get the requested action
    $action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE,
      // default to 'browse'
      'browse'
    );

    // assign vars to templates
    $this->assign('action', $action);
    $id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE, 0
    );

    //set the context and then start w/ action.
    $this->setContext($id, $action);

    // what action to take ?
    if ($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::DELETE | CRM_Core_Action::DISABLE)) {
      $this->edit($id, $action);
    }
    else {
      // if action is enable or disable do the needful.
      if ($action & CRM_Core_Action::ENABLE) {
        CRM_Core_BAO_UFGroup::setIsActive($id, 1);

        // update cms integration with registration / my account
        CRM_Utils_System::updateCategories();
      }
      elseif ($action & CRM_Core_Action::PROFILE) {
        $this->profile();
        CRM_Utils_System::setTitle(ts('%1 - HTML Form Snippet', array(1 => $this->_title)));
      }
      elseif ($action & CRM_Core_Action::PREVIEW) {
        $this->preview($id, $action);
      }
      elseif ($action & CRM_Core_Action::COPY) {
        $this->copy();
      }
      // finally browse the uf groups
      $this->browse();
    }
    // parent run
    return parent::run();
  }

  /**
   * This function is to make a copy of a profile, including
   * all the fields in the profile
   *
   * @return void
   * @access public
   */
  function copy() {
    $gid = CRM_Utils_Request::retrieve('gid', 'Positive',
      $this, TRUE, 0, 'GET'
    );

    CRM_Core_BAO_UFGroup::copy($gid);
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/uf/group', 'reset=1'));
  }

  /**
   * This function is for profile mode (standalone html form ) for uf group
   *
   * @return void
   * @access public
   */
  function profile() {
    $config = CRM_Core_Config::singleton();

    // reassign resource base to be the full url, CRM-4660
    $config->resourceBase = $config->userFrameworkResourceURL;
    $config->useFrameworkRelativeBase = $config->userFrameworkBaseURL;

    $gid = CRM_Utils_Request::retrieve('gid', 'Positive',
      $this, FALSE, 0, 'GET'
    );
    $controller = new CRM_Core_Controller_Simple('CRM_Profile_Form_Edit', ts('Create'), CRM_Core_Action::ADD,
      FALSE, FALSE, TRUE
    );
    $controller->reset();
    $controller->process();
    $controller->set('gid', $gid);
    $controller->setEmbedded(TRUE);
    $controller->run();
    $template = CRM_Core_Smarty::singleton();
    $template->assign('gid', $gid);
    $template->assign('tplFile', 'CRM/Profile/Form/Edit.tpl');
    $profile = trim($template->fetch('CRM/Form/default.tpl'));

    // not sure how to circumvent our own navigation system to generate the right form url
    $urlReplaceWith = 'civicrm/profile/create&amp;gid=' . $gid . '&amp;reset=1';
    if ($config->userSystem->is_drupal && $config->cleanURL) {
      $urlReplaceWith = 'civicrm/profile/create?gid=' . $gid . '&amp;reset=1';
    }
    $profile = str_replace('civicrm/admin/uf/group', $urlReplaceWith, $profile);

    // FIXME: (CRM-3587) hack to make standalone profile work
    // in wordpress and joomla without administrator login
    if ($config->userFramework == 'Joomla') {
      $profile = str_replace('/administrator/', '/index.php', $profile);
    }
    elseif ($config->userFramework == 'WordPress') {
      $profile = str_replace('/wp-admin/admin.php', '/index.php', $profile);
    }

    // add header files
    CRM_Core_Resources::singleton()->addCoreResources('html-header');
    $profile = CRM_Core_Region::instance('html-header')->render('', FALSE) . $profile;

    $this->assign('profile', htmlentities($profile, ENT_NOQUOTES, 'UTF-8'));
    //get the title of uf group
    if ($gid) {
      $title = CRM_Core_BAO_UFGroup::getTitle($gid);
      $this->_title = $title;
    }
    else {
      $title = 'Profile Form';
    }

    $this->assign('title', $title);
    $this->assign('action', CRM_Core_Action::PROFILE);
    $this->assign('isForm', 0);
  }

  /**
   * edit uf group
   *
   * @param int $id uf group id
   * @param string $action the action to be invoked
   *
   * @return void
   * @access public
   */
  function edit($id, $action) {
    // create a simple controller for editing uf data
    $controller = new CRM_Core_Controller_Simple('CRM_UF_Form_Group', ts('CiviCRM Profile Group'), $action);
    $this->setContext($id, $action);
    $controller->set('id', $id);
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();
  }

  /**
   * Browse all uf data groups.
   *
   * @param
   *
   * @return void
   * @access public
   * @static
   */
  function browse($action = NULL) {
    $ufGroup = array();
    $allUFGroups = array();
    $allUFGroups = CRM_Core_BAO_UFGroup::getModuleUFGroup();
    if (empty($allUFGroups)) {
      return;
    }

    $ufGroups = CRM_Core_PseudoConstant::get('CRM_Core_DAO_UFField', 'uf_group_id');
    CRM_Utils_Hook::aclGroup(CRM_Core_Permission::ADMIN, NULL, 'civicrm_uf_group', $ufGroups, $allUFGroups);

    foreach ($allUFGroups as $id => $value) {
      $ufGroup[$id] = array();
      $ufGroup[$id]['id'] = $id;
      $ufGroup[$id]['title'] = $value['title'];
      $ufGroup[$id]['created_id'] = $value['created_id'];
      $ufGroup[$id]['created_by'] = CRM_Contact_BAO_Contact::displayName($value['created_id']);
      $ufGroup[$id]['description'] = $value['description'];
      $ufGroup[$id]['is_active'] = $value['is_active'];
      $ufGroup[$id]['group_type'] = $value['group_type'];
      $ufGroup[$id]['is_reserved'] = $value['is_reserved'];

      // form all action links
      $action = array_sum(array_keys($this->actionLinks()));

      // update enable/disable links depending on uf_group properties.
      if ($value['is_active']) {
        $action -= CRM_Core_Action::ENABLE;
      }
      else {
        $action -= CRM_Core_Action::DISABLE;
      }

      // drop certain actions if the profile is reserved
      if ($value['is_reserved']) {
        $action -= CRM_Core_Action::UPDATE;
        $action -= CRM_Core_Action::DISABLE;
        $action -= CRM_Core_Action::DELETE;
      }

      $groupTypes = self::extractGroupTypes($value['group_type']);
      $groupComponents = array('Contribution', 'Membership', 'Activity', 'Participant', 'Case');

      // drop Create, Edit and View mode links if profile group_type is Contribution, Membership, Activities or Participant
      $componentFound = array_intersect($groupComponents, array_keys($groupTypes));
      if (!empty($componentFound)) {
        $action -= CRM_Core_Action::ADD;
      }

      $groupTypesString = '';
      if (!empty($groupTypes)) {
        $groupTypesStrings = array();
        foreach ($groupTypes as $groupType => $typeValues) {
          if (is_array($typeValues)) {
            if ($groupType == 'Participant') {
              foreach ($typeValues as $subType => $subTypeValues) {
                $groupTypesStrings[] = $subType . '::' . implode(': ', $subTypeValues);
              }
            }
            else {
              $groupTypesStrings[] = $groupType . '::' . implode(': ', current($typeValues));
            }
          }
          else {
            $groupTypesStrings[] = $groupType;
          }
        }
        $groupTypesString = implode(', ', $groupTypesStrings);
      }
      $ufGroup[$id]['group_type'] = $groupTypesString;

      $ufGroup[$id]['action'] = CRM_Core_Action::formLink(self::actionLinks(), $action,
        array('id' => $id),
        ts('more'),
        FALSE,
        'ufGroup.row.actions',
        'UFGroup',
        $id
      );
      //get the "Used For" from uf_join
      $ufGroup[$id]['module'] = implode(', ', CRM_Core_BAO_UFGroup::getUFJoinRecord($id, TRUE));
    }

    $this->assign('rows', $ufGroup);
  }

  /**
   * this function is for preview mode for ufoup
   *
   * @param int $id uf group id
   *
   * @param $action
   *
   * @return void
   * @access public
   */
  function preview($id, $action) {
    $controller = new CRM_Core_Controller_Simple('CRM_UF_Form_Preview', ts('CiviCRM Profile Group Preview'), NULL);
    $controller->set('id', $id);
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();
  }

  /**
   * @param $id
   * @param $action
   */
  function setContext($id, $action) {
    $context = CRM_Utils_Request::retrieve('context', 'String', $this);

    //we need to differentiate context for update and preview profile.
    if (!$context && !($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::PREVIEW))) {
      $context = 'group';
    }

    if ($context == 'field') {
      $url = CRM_Utils_System::url('civicrm/admin/uf/group/field', "reset=1&action=browse&gid={$id}");
    }
    else {
      $url = CRM_Utils_System::url('civicrm/admin/uf/group', 'reset=1&action=browse');
    }

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext($url);
  }

  /**
   * @param $groupType
   *
   * @return array
   */
  static function extractGroupTypes($groupType) {
    $returnGroupTypes = array();
    if (!$groupType) {
      return $returnGroupTypes;
    }

    $groupTypeParts = explode(CRM_Core_DAO::VALUE_SEPARATOR, $groupType);
    foreach (explode(',', $groupTypeParts[0]) as $type) {
      $returnGroupTypes[$type] = $type;
    }

    if (!empty($groupTypeParts[1])) {
      foreach (explode(',', $groupTypeParts[1]) as $typeValue) {
        $groupTypeValues = $valueLabels = array();
        $valueParts = explode(':', $typeValue);
        $typeName = NULL;
        switch ($valueParts[0]) {
          case 'ContributionType':
            $typeName = 'Contribution';
            $valueLabels = CRM_Contribute_PseudoConstant::financialType();
            break;

          case 'ParticipantRole':
            $typeName = 'Participant';
            $valueLabels = CRM_Event_PseudoConstant::participantRole();
            break;

          case 'ParticipantEventName':
            $typeName = 'Participant';
            $valueLabels = CRM_Event_PseudoConstant::event();
            break;

          case 'ParticipantEventType':
            $typeName = 'Participant';
            $valueLabels = CRM_Event_PseudoConstant::eventType();
            break;

          case 'MembershipType':
            $typeName = 'Membership';
            $valueLabels = CRM_Member_PseudoConstant::membershipType();
            break;

          case 'ActivityType':
            $typeName = 'Activity';
            $valueLabels = CRM_Core_PseudoConstant::ActivityType(TRUE, TRUE, FALSE, 'label', TRUE);
            break;
          case 'CaseType':
            $typeName = 'Case';
            $valueLabels = CRM_Case_PseudoConstant::caseType();
            break;
        }

        foreach ($valueParts as $val) {
          if (CRM_Utils_Rule::integer($val)) {
            $groupTypeValues[$val] = CRM_Utils_Array::value($val, $valueLabels);
          }
        }

        if (!is_array($returnGroupTypes[$typeName])) {
          $returnGroupTypes[$typeName] = array();
        }
        $returnGroupTypes[$typeName][$valueParts[0]] = $groupTypeValues;
      }
    }
    return $returnGroupTypes;
  }
}

