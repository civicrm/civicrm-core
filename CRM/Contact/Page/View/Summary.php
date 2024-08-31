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
  use CRM_Custom_Page_CustomDataTrait;

  /**
   * Contents of contact_view_options setting.
   *
   * @var array
   * @internal
   */
  public $_viewOptions;

  /**
   * Provide support for extensions that are using the _show{Block} properties
   * (e.g. `_showCustomData`, `_showAddress`, `_showPhone` etc)
   *
   * These properties were dynamically defined,
   * based on the available `contact_edit_options`,
   * and so have been deprecated for PHP 8.2 support.
   *
   * Extension authors can read contact_edit_options directly.
   * The show{Block} values are also still assigned to the template layer.
   *
   * @param string $name
   * @return bool|null
   */
  public function __get($name) {
    if (str_starts_with($name, '_show')) {
      $blockName = substr($name, strlen('_show'));
      $editOptions = CRM_Core_BAO_Setting::valueOptions(
        CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_edit_options'
      );

      CRM_Core_Error::deprecatedWarning('_show{Block} properties are deprecated in CRM_Contact_Page_View_Summary. Read contact_edit_options directly instead.');
      return $editOptions[$blockName] ?? FALSE;
    }
    return NULL;
  }

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
    // Custom groups with VIEW permission
    $visibleGroups = CRM_Core_BAO_CustomGroup::getTree($entityType,
      NULL,
      $this->_contactId,
      NULL,
      $entitySubType,
      NULL,
      TRUE,
      NULL,
      FALSE,
      CRM_Core_Permission::VIEW
    );

    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $visibleGroups, FALSE, NULL, NULL, NULL, $this->_contactId, CRM_Core_Permission::EDIT);

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
   *
   * @throws \CRM_Core_Exception
   */
  public function view() {
    // Add js for tabs, in-place editing, and jstree for tags
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'templates/CRM/Contact/Page/View/Summary.js', 2, 'html-header')
      ->addStyleFile('civicrm', 'css/contactSummary.css', 2, 'html-header')
      ->addScriptFile('civicrm', 'templates/CRM/common/TabHeader.js', 1, 'html-header')
      ->addSetting([
        'summaryPrint' => ['mode' => $this->_print],
        'tabSettings' => ['active' => CRM_Utils_Request::retrieve('selectedChild', 'Alphanumeric', $this, FALSE, 'summary')],
      ]);
    $this->assign('summaryPrint', $this->_print);
    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $this->_contactId);
    $session->pushUserContext($url);
    $this->assignFieldMetadataToTemplate('Contact');

    $defaults = [
      // Set empty default values for these - they will be overwritten when the contact is
      // loaded in CRM_Contact_BAO_Contact::retrieve if there are real values
      // but since we are not using apiV4 they will be left unset if empty.
      // However, the wind up assigned as smarty variables so we ensure they are set to prevent e-notices
      // used by ContactInfo.tpl
      'job_title' => '',
      'current_employer_id' => '',
      'nick_name' => '',
      'legal_name' => '',
      'source' => '',
      'sic_code' => '',
      'external_identifier' => '',
      // for CommunicationPreferences.tpl
      'postal_greeting_custom' => '',
      'email_greeting_custom' => '',
      'addressee_custom' => '',
      'communication_style_display' => '',
      'email_greeting_display' => '',
      'postal_greeting_display' => '',
      // for Demographics.tpl
      'age' => ['y' => '', 'm' => ''],
      'birth_date' => '',
    ];

    CRM_Contact_BAO_Contact::getValues(['id' => $this->_contactId], $defaults);
    $defaults['im'] = $this->getLocationValues($this->_contactId, 'IM');
    $emails = $this->getLocationValues($this->_contactId, 'Email');
    foreach ($emails as $blockId => $email) {
      $emails[$blockId]['custom'] = $this->addBlockCustomData('Email', $email['id']);
    }
    $this->assign('email', $emails);
    $defaults['openid'] = $this->getLocationValues($this->_contactId, 'OpenID');
    $defaults['phone'] = $this->getLocationValues($this->_contactId, 'Phone');
    $defaults['website'] = $this->getLocationValues($this->_contactId, 'Website');
    // Copy employer fields to the current_employer keys.
    if (($defaults['contact_type'] === 'Individual') && !empty($defaults['employer_id']) && !empty($defaults['organization_name'])) {
      $defaults['current_employer'] = $defaults['organization_name'];
      $defaults['current_employer_id'] = $defaults['employer_id'];
    }

    // Let summary page know if outbound mail is disabled so email links can be built conditionally
    $mailingBackend = Civi::settings()->get('mailing_backend');
    $this->assign('mailingOutboundOption', $mailingBackend['outBound_option']);

    // This microformat magic is still required...
    $addresses = (array) CRM_Core_BAO_Address::getValues(['contact_id' => $this->_contactId], TRUE);
    foreach ($addresses as $blockId => &$blockVal) {
      // Does this do anything?
      CRM_Utils_Array::lookupValue($blockVal, 'location_type', CRM_Core_BAO_Address::buildOptions('location_type_id'), FALSE);

      $idValue = $blockVal['id'];
      if (!empty($blockVal['master_id'])) {
        $idValue = $blockVal['master_id'];
      }
      $addresses[$blockId]['custom'] = $this->addBlockCustomData('Address', $idValue);
    }
    $this->assign('address', $addresses);

    $defaults['gender_display'] = CRM_Core_PseudoConstant::getLabel('CRM_Contact_DAO_Contact', 'gender_id', $defaults['gender_id'] ?? NULL);

    $communicationStyle = CRM_Contact_DAO_Contact::buildOptions('communication_style_id');
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
    $defaults['contactTag'] = CRM_Core_BAO_EntityTag::getContactTags($this->_contactId);
    if (!empty($defaults['contactTag'])) {
      $defaults['allTags'] = CRM_Core_BAO_Tag::getTagsUsedFor('civicrm_contact', FALSE);
    }

    $defaults['privacy_values'] = CRM_Core_SelectValues::privacy();

    //Show blocks only if they are visible in edit form
    $editOptions = CRM_Core_BAO_Setting::valueOptions(
      CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'contact_edit_options'
    );

    foreach ($editOptions as $blockName => $value) {
      $varName = 'show' . $blockName;
      $this->assign($varName, $value);
    }

    // get contact name of shared contact names
    $sharedAddresses = [];
    $shareAddressContactNames = CRM_Contact_BAO_Contact_Utils::getAddressShareContactNames($addresses);
    foreach ($addresses as $key => $addressValue) {
      if (!empty($addressValue['master_id']) &&
        !$shareAddressContactNames[$addressValue['master_id']]['is_deleted']
      ) {
        $sharedAddresses[$key]['shared_address_display'] = [
          'address' => $addressValue['display'],
          'name' => $shareAddressContactNames[$addressValue['master_id']]['name'],
        ];
      }
    }
    $this->assign('sharedAddresses', $sharedAddresses);
    // @todo - stop assigning defaults - assign variables individually
    // rather than adding to defaults for transparency - this is some old
    // copy & paste.
    $this->assign($defaults);

    // FIXME: when we sort out TZ isssues with DATETIME/TIMESTAMP, we can skip next query
    // also assign the last modifed details
    $lastModified = CRM_Core_BAO_Log::lastModified($this->_contactId, 'civicrm_contact');
    $this->assign('lastModified', $lastModified);

    $this->_viewOptions = CRM_Core_BAO_Setting::valueOptions(
      CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'contact_view_options',
      TRUE
    );

    $changeLog = $this->_viewOptions['log'];
    $this->assign('changeLog', $changeLog);

    $this->assign('allTabs', $this->getTabs($defaults));

    // hook for contact summary
    // ignored but needed to prevent warnings
    $contentPlacement = CRM_Utils_Hook::SUMMARY_BELOW;
    CRM_Utils_Hook::summary($this->_contactId, $content, $contentPlacement);
    if ($content) {
      $this->assign('hookContent', $content);
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
        'template' => 'CRM/Contact/Page/View/Summary-tab.tpl',
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
        'id' => 'group',
        'title' => ts('Groups'),
        'class' => 'ajaxForm',
        'weight' => 90,
        'icon' => 'crm-i fa-users',
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
  public function getTabs(array $contact) {
    $allTabs = [];
    $getCountParams = [];
    $weight = 10;

    foreach (CRM_Core_Component::getEnabledComponents() as $name => $component) {
      if (!empty($this->_viewOptions[$name]) &&
        CRM_Core_Permission::access($component->name)
      ) {
        $elem = $component->registerTab();

        // FIXME: not very elegant, probably needs better approach
        // allow explicit id, if not defined, use keyword instead
        $i = $elem['id'] ?? $component->getKeyword();
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
          'count' => NULL,
          'class' => 'livePage',
          'icon' => $component->getIcon(),
        ];
        $getCountParams[$i] = [$u, $this->_contactId];
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
          'count' => NULL,
        ];
        $getCountParams[$tab['id']] = [$tab['id'], $this->_contactId];
        $weight = $tab['weight'] + 10;
      }
    }

    // now add all the custom tabs
    $filters = [
      'is_active' => TRUE,
      'extends' => $this->get('contactType'),
      'style' => ['Tab', 'Tab with table'],
    ];
    $activeGroups = CRM_Core_BAO_CustomGroup::getAll($filters, CRM_Core_Permission::VIEW);

    foreach ($activeGroups as $group) {
      $id = "custom_{$group['id']}";
      $allTabs[] = [
        'id' => $id,
        'url' => CRM_Utils_System::url('civicrm/contact/view/cd', "reset=1&gid={$group['id']}&cid={$this->_contactId}&selectedChild=$id"),
        'title' => $group['title'],
        'weight' => $weight,
        'count' => NULL,
        'hideCount' => !$group['is_multiple'],
        'class' => 'livePage',
        'icon' => 'crm-i ' . ($group['icon'] ?: 'fa-gear'),
      ];
      $getCountParams[$id] = [$id, $this->_contactId, $group['table_name']];
      $weight += 10;
    }

    // Allow other modules to add or remove tabs
    $context = [
      'contact_id' => $contact['id'],
      'contact_type' => $contact['contact_type'],
      'contact_sub_type' => CRM_Utils_Array::explodePadded($contact['contact_sub_type'] ?? NULL),
    ];
    CRM_Utils_Hook::tabset('civicrm/contact/view', $allTabs, $context);

    // Remove any tabs that don't apply to this contact type
    foreach (array_keys($allTabs) as $key) {
      $tabContactType = (array) ($allTabs[$key]['contact_type'] ?? []);
      if ($tabContactType && !in_array($contact['contact_type'], $tabContactType, TRUE)) {
        unset($allTabs[$key]);
      }
    }

    $expectedKeys = ['count', 'class', 'template', 'hideCount', 'icon'];

    foreach ($allTabs as &$tab) {
      // Ensure tab has all expected keys
      $tab += array_fill_keys($expectedKeys, NULL);
      // Get tab counts last to avoid wasting time; if a tab was removed by hook, the count isn't needed.
      if (!isset($tab['count']) && isset($getCountParams[$tab['id']])) {
        $tab['count'] = call_user_func_array([
          'CRM_Contact_BAO_Contact',
          'getCountComponent',
        ], $getCountParams[$tab['id']]);
      }
    }

    // now sort the tabs based on weight
    usort($allTabs, ['CRM_Utils_Sort', 'cmpFunc']);
    return $allTabs;
  }

  /**
   * Get the values for the location entity for this contact.
   *
   * The form layer requires that we put the label values into keys too.
   * Unfortunately smarty can't handle {$location_type_id:label} - ie
   * the colon - so we need to map the value over in the php layer.
   *
   * @param int $contact_id
   * @param string $entity
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getLocationValues(int $contact_id, string $entity): array {
    $fieldMap = [
      'location_type_id' => 'location_type',
      'provider_id' => 'provider',
      'phone_type_id' => 'phone_type',
      'website_type_id' => 'website_type',
    ];
    $optionFields = array_keys((array) civicrm_api4($entity, 'getFields', [
      'where' => [['options', 'IS NOT EMPTY'], ['name', 'IN', array_keys($fieldMap)]],
    ], 'name'));
    $select = ['*', 'custom.*'];
    foreach ($optionFields as $optionField) {
      $select[] = $optionField . ':label';
    }
    $locationEntities = (array) civicrm_api4($entity, 'get', [
      'select' => $select,
      'where' => [['contact_id', '=', $contact_id]],
      'orderBy' => $entity === 'Website' ? [] : ['is_primary' => 'DESC'],
    ], 'id');

    foreach ($locationEntities as $index => $locationEntity) {
      foreach ($optionFields as $optionField) {
        $locationEntities[$index][$fieldMap[$optionField]] = $locationEntity[$optionField . ':label'];
      }
    }
    return $locationEntities;
  }

  /**
   * @param string $entityType
   *
   * @param int $entityID
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function addBlockCustomData(string $entityType, int $entityID): array {
    return $this->getCustomDataFieldsForEntityDisplay($entityType, $entityID);
  }

}
