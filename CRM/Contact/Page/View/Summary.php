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
 * Main page for viewing contact.
 */
class CRM_Contact_Page_View_Summary extends CRM_Contact_Page_View {

  /**
   * Heart of the viewing process.
   *
   * The runner gets all the meta data for the contact and calls the appropriate type of page to view.
   */
  public function preProcess() {
    parent::preProcess();

    // actions buttom contextMenu
    $menuItems = CRM_Contact_BAO_Contact::contextMenu($this->_contactId);

    $this->assign('actionsMenuList', $menuItems);

    //retrieve inline custom data
    $entityType = $this->get('contactType');
    if ($entitySubType = $this->get('contactSubtype')) {
      $entitySubType = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        trim($entitySubType, CRM_Core_DAO::VALUE_SEPARATOR)
      );
    }
    $groupTree = CRM_Core_BAO_CustomGroup::getTree($entityType,
      NULL,
      $this->_contactId,
      NULL,
      $entitySubType
    );

    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, NULL, NULL, $this->_contactId);

    // also create the form element for the activity links box
    $controller = new CRM_Core_Controller_Simple(
      'CRM_Activity_Form_ActivityLinks',
      ts('Activity Links'),
      NULL,
      FALSE,
      FALSE,
      TRUE
    );
    $controller->setEmbedded(TRUE);
    $controller->run();
  }

  /**
   * Heart of the viewing process.
   *
   * The runner gets all the meta data for the contact and calls the appropriate type of page to view.
   */
  public function run() {
    $this->preProcess();

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $this->edit();
    }
    else {
      $this->view();
    }

    return parent::run();
  }

  /**
   * Edit name and address of a contact.
   */
  public function edit() {
    // set the userContext stack
    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $this->_contactId);
    $session->pushUserContext($url);

    $controller = new CRM_Core_Controller_Simple('CRM_Contact_Form_Contact', ts('Contact Page'), CRM_Core_Action::UPDATE);
    $controller->setEmbedded(TRUE);
    $controller->process();
    return $controller->run();
  }

  /**
   * View summary details of a contact.
   */
  public function view() {
    // Add js for tabs, in-place editing, and jstree for tags
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'templates/CRM/Contact/Page/View/Summary.js', 2, 'html-header')
      ->addStyleFile('civicrm', 'css/contactSummary.css', 2, 'html-header')
      ->addScriptFile('civicrm', 'templates/CRM/common/TabHeader.js', 1, 'html-header')
      ->addSetting(array(
        'summaryPrint' => array('mode' => $this->_print),
        'tabSettings' => array('active' => CRM_Utils_Request::retrieve('selectedChild', 'Alphanumeric', $this, FALSE, 'summary')),
      ));
    $this->assign('summaryPrint', $this->_print);
    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $this->_contactId);
    $session->pushUserContext($url);
    $this->assignFieldMetadataToTemplate('Contact');

    $params = [];
    $defaults = [];

    $params['id'] = $params['contact_id'] = $this->_contactId;
    $params['noRelationships'] = $params['noNotes'] = $params['noGroups'] = TRUE;
    $contact = CRM_Contact_BAO_Contact::retrieve($params, $defaults, TRUE);
    // Let summary page know if outbound mail is disabled so email links can be built conditionally
    $mailingBackend = Civi::settings()->get('mailing_backend');
    $this->assign('mailingOutboundOption', $mailingBackend['outBound_option']);

    $communicationType = array(
      'phone' => array(
        'type' => 'phoneType',
        'id' => 'phone_type',
        'daoName' => 'CRM_Core_DAO_Phone',
        'fieldName' => 'phone_type_id',
      ),
      'im' => array(
        'type' => 'IMProvider',
        'id' => 'provider',
        'daoName' => 'CRM_Core_DAO_IM',
        'fieldName' => 'provider_id',
      ),
      'website' => array(
        'type' => 'websiteType',
        'id' => 'website_type',
        'daoName' => 'CRM_Core_DAO_Website',
        'fieldName' => 'website_type_id',
      ),
      'address' => array('skip' => TRUE, 'customData' => 1),
      'email' => array('skip' => TRUE),
      'openid' => array('skip' => TRUE),
    );

    foreach ($communicationType as $key => $value) {
      if (!empty($defaults[$key])) {
        foreach ($defaults[$key] as & $val) {
          CRM_Utils_Array::lookupValue($val, 'location_type', CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id', array('labelColumn' => 'display_name')), FALSE);
          if (empty($value['skip'])) {
            $daoName = $value['daoName'];
            $pseudoConst = $daoName::buildOptions($value['fieldName'], 'get');
            CRM_Utils_Array::lookupValue($val, $value['id'], $pseudoConst, FALSE);
          }
        }
        if (isset($value['customData'])) {
          foreach ($defaults[$key] as $blockId => $blockVal) {
            $idValue = $blockVal['id'];
            if ($key == 'address') {
              if (!empty($blockVal['master_id'])) {
                $idValue = $blockVal['master_id'];
              }
            }
            $groupTree = CRM_Core_BAO_CustomGroup::getTree(ucfirst($key), NULL, $idValue);
            // we setting the prefix to dnc_ below so that we don't overwrite smarty's grouptree var.
            $defaults[$key][$blockId]['custom'] = CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, "dnc_");
          }
          // reset template variable since that won't be of any use, and could be misleading
          $this->assign("dnc_viewCustomData", NULL);
        }
      }
    }

    if (!empty($defaults['gender_id'])) {
      $defaults['gender_display'] = CRM_Core_PseudoConstant::getLabel('CRM_Contact_DAO_Contact', 'gender_id', $defaults['gender_id']);
    }

    $communicationStyle = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'communication_style_id');
    if (!empty($communicationStyle)) {
      if (!empty($defaults['communication_style_id'])) {
        $defaults['communication_style_display'] = $communicationStyle[$defaults['communication_style_id']];
      }
      else {
        // Make sure the field is displayed as long as it is active, even if it is unset for this contact.
        $defaults['communication_style_display'] = '';
      }
    }

    // to make contact type label available in the template -
    $contactType = array_key_exists('contact_sub_type', $defaults) ? $defaults['contact_sub_type'] : $defaults['contact_type'];
    $defaults['contact_type_label'] = CRM_Contact_BAO_ContactType::contactTypePairs(TRUE, $contactType, ', ');

    // get contact tags
    $contactTags = CRM_Core_BAO_EntityTag::getContactTags($this->_contactId);

    if (!empty($contactTags)) {
      $defaults['contactTag'] = $contactTags;
      $defaults['allTags'] = CRM_Core_BAO_Tag::getTagsUsedFor('civicrm_contact', FALSE);
    }

    $defaults['privacy_values'] = CRM_Core_SelectValues::privacy();

    //Show blocks only if they are visible in edit form
    $this->_editOptions = CRM_Core_BAO_Setting::valueOptions(
      CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'contact_edit_options'
    );

    foreach ($this->_editOptions as $blockName => $value) {
      $varName = '_show' . $blockName;
      $this->$varName = $value;
      $this->assign(substr($varName, 1), $this->$varName);
    }

    // get contact name of shared contact names
    $sharedAddresses = [];
    $shareAddressContactNames = CRM_Contact_BAO_Contact_Utils::getAddressShareContactNames($defaults['address']);
    foreach ($defaults['address'] as $key => $addressValue) {
      if (!empty($addressValue['master_id']) &&
        !$shareAddressContactNames[$addressValue['master_id']]['is_deleted']
      ) {
        $sharedAddresses[$key]['shared_address_display'] = array(
          'address' => $addressValue['display'],
          'name' => $shareAddressContactNames[$addressValue['master_id']]['name'],
        );
      }
    }
    $this->assign('sharedAddresses', $sharedAddresses);

    //get the current employer name
    if (CRM_Utils_Array::value('contact_type', $defaults) == 'Individual') {
      if ($contact->employer_id && $contact->organization_name) {
        $defaults['current_employer'] = $contact->organization_name;
        $defaults['current_employer_id'] = $contact->employer_id;
      }
    }

    $defaults['external_identifier'] = $contact->external_identifier;
    $this->assign($defaults);

    // FIXME: when we sort out TZ isssues with DATETIME/TIMESTAMP, we can skip next query
    // also assign the last modifed details
    $lastModified = CRM_Core_BAO_Log::lastModified($this->_contactId, 'civicrm_contact');
    $this->assign_by_ref('lastModified', $lastModified);

    $this->_viewOptions = CRM_Core_BAO_Setting::valueOptions(
      CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'contact_view_options',
      TRUE
    );

    $changeLog = $this->_viewOptions['log'];
    $this->assign_by_ref('changeLog', $changeLog);

    $this->assign('allTabs', $this->getTabs());

    // hook for contact summary
    // ignored but needed to prevent warnings
    $contentPlacement = CRM_Utils_Hook::SUMMARY_BELOW;
    CRM_Utils_Hook::summary($this->_contactId, $content, $contentPlacement);
    if ($content) {
      $this->assign_by_ref('hookContent', $content);
      $this->assign('hookContentPlacement', $contentPlacement);
    }
  }

  /**
   * @return string
   */
  public function getTemplateFileName() {
    if ($this->_contactId) {
      $contactSubtypes = $this->get('contactSubtype') ? explode(CRM_Core_DAO::VALUE_SEPARATOR, $this->get('contactSubtype')) : [];

      // there could be multiple subtypes. We check templates for each of the subtype, and return the first one found.
      foreach ($contactSubtypes as $csType) {
        if ($csType) {
          $templateFile = "CRM/Contact/Page/View/SubType/{$csType}.tpl";
          $template = CRM_Core_Page::getTemplate();
          if ($template->template_exists($templateFile)) {
            return $templateFile;
          }
        }
      }
    }
    return parent::getTemplateFileName();
  }

  /**
   * @return array
   */
  public static function basicTabs() {
    return [
      [
        'id' => 'summary',
        'url' => '#contact-summary',
        'title' => ts('Summary'),
        'weight' => 0,
        'icon' => 'crm-i fa-address-card-o',
      ],
      [
        'id' => 'activity',
        'title' => ts('Activities'),
        'class' => 'livePage',
        'weight' => 70,
        'icon' => 'crm-i fa-tasks',
      ],
      [
        'id' => 'rel',
        'title' => ts('Relationships'),
        'class' => 'livePage',
        'weight' => 80,
        'icon' => 'crm-i fa-handshake-o',
      ],
      [
        'id' => 'group',
        'title' => ts('Groups'),
        'class' => 'ajaxForm',
        'weight' => 90,
        'icon' => 'crm-i fa-users',
      ],
      [
        'id' => 'note',
        'title' => ts('Notes'),
        'class' => 'livePage',
        'weight' => 100,
        'icon' => 'crm-i fa-sticky-note-o',
      ],
      [
        'id' => 'tag',
        'title' => ts('Tags'),
        'weight' => 110,
        'icon' => 'crm-i fa-tags',
      ],
      [
        'id' => 'log',
        'title' => ts('Change Log'),
        'weight' => 120,
        'icon' => 'crm-i fa-history',
      ],
    ];
  }

  /**
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getTabs() {
    $allTabs = [];
    $weight = 10;

    foreach (CRM_Core_Component::getEnabledComponents() as $name => $component) {
      if (!empty($this->_viewOptions[$name]) &&
        CRM_Core_Permission::access($component->name)
      ) {
        $elem = $component->registerTab();

        // FIXME: not very elegant, probably needs better approach
        // allow explicit id, if not defined, use keyword instead
        if (array_key_exists('id', $elem)) {
          $i = $elem['id'];
        }
        else {
          $i = $component->getKeyword();
        }
        $u = $elem['url'];

        //appending isTest to url for test soft credit CRM-3891.
        //FIXME: hack ajax url.
        $q = "reset=1&force=1&cid={$this->_contactId}";
        if (CRM_Utils_Request::retrieve('isTest', 'Positive', $this)) {
          $q .= "&isTest=1";
        }
        $allTabs[] = [
          'id' => $i,
          'url' => CRM_Utils_System::url("civicrm/contact/view/$u", $q),
          'title' => $elem['title'],
          'weight' => $elem['weight'],
          'count' => CRM_Contact_BAO_Contact::getCountComponent($u, $this->_contactId),
          'class' => 'livePage',
          'icon' => $component->getIcon(),
        ];
      }
    }

    // show the tabs only if user has generic access to CiviCRM
    $accessCiviCRM = CRM_Core_Permission::check('access CiviCRM');
    foreach (self::basicTabs() as $tab) {
      if ($tab['id'] == 'summary') {
        $allTabs[] = $tab;
      }
      elseif ($accessCiviCRM && !empty($this->_viewOptions[$tab['id']])) {
        $allTabs[] = $tab + [
          'url' => CRM_Utils_System::url("civicrm/contact/view/{$tab['id']}", "reset=1&cid={$this->_contactId}"),
          'count' => CRM_Contact_BAO_Contact::getCountComponent($tab['id'], $this->_contactId),
        ];
        $weight = $tab['weight'] + 10;
      }
    }

    // now add all the custom tabs
    $entityType = $this->get('contactType');
    $activeGroups = CRM_Core_BAO_CustomGroup::getActiveGroups(
      $entityType,
      'civicrm/contact/view/cd',
      $this->_contactId
    );

    foreach ($activeGroups as $group) {
      $id = "custom_{$group['id']}";
      $allTabs[] = [
        'id' => $id,
        'url' => CRM_Utils_System::url($group['path'], $group['query'] . "&selectedChild=$id"),
        'title' => $group['title'],
        'weight' => $weight,
        'count' => CRM_Contact_BAO_Contact::getCountComponent($id, $this->_contactId, $group['table_name']),
        'hideCount' => !$group['is_multiple'],
        'class' => 'livePage',
        'icon' => 'crm-i ' . ($group['icon'] ?: 'fa-gear'),
      ];
      $weight += 10;
    }

    // Allow other modules to add or remove tabs
    $context = ['contact_id' => $this->_contactId];
    CRM_Utils_Hook::tabset('civicrm/contact/view', $allTabs, $context);

    // now sort the tabs based on weight
    usort($allTabs, ['CRM_Utils_Sort', 'cmpFunc']);
    return $allTabs;
  }

}
