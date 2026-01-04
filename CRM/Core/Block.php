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
 * Defines a simple implementation of a drupal block.
 *
 * Blocks definitions and html are in a smarty template file.
 */
class CRM_Core_Block {

  /**
   * The following blocks are supported.
   *
   * @var int
   */
  const
    CREATE_NEW = 1,
    RECENTLY_VIEWED = 2,
    DASHBOARD = 3,
    ADD = 4,
    LANGSWITCH = 5,
    EVENT = 6,
    FULLTEXT_SEARCH = 7;

  /**
   * Template file names for the above blocks.
   * @var array
   */
  public static $_properties = NULL;

  /**
   * Class constructor.
   */
  public function __construct() {
  }

  /**
   * Initialises the $_properties array
   */
  public static function initProperties() {
    if (!defined('BLOCK_CACHE_GLOBAL')) {
      define('BLOCK_CACHE_GLOBAL', 0x0008);
    }

    if (!defined('BLOCK_CACHE_PER_PAGE')) {
      define('BLOCK_CACHE_PER_PAGE', 0x0004);
    }

    if (!defined('BLOCK_NO_CACHE')) {
      define('BLOCK_NO_CACHE', -1);
    }

    if (!(self::$_properties)) {
      $config = CRM_Core_Config::singleton();
      self::$_properties = [
        // set status item to 0 to disable block by default (at install)
        self::CREATE_NEW => [
          'template' => 'CreateNew.tpl',
          'info' => ts('CiviCRM Create New Record'),
          'subject' => '',
          'active' => TRUE,
          'cache' => BLOCK_CACHE_GLOBAL,
          'visibility' => 1,
          'weight' => -100,
          'status' => 1,
          'pages' => "civicrm\ncivicrm/*",
          'region' => $config->userSystem->getDefaultBlockLocation(),
        ],
        self::RECENTLY_VIEWED => [
          'template' => 'RecentlyViewed.tpl',
          'info' => ts('CiviCRM Recent Items'),
          'subject' => ts('Recent Items'),
          'active' => TRUE,
          'cache' => BLOCK_NO_CACHE,
          'visibility' => 1,
          'weight' => -99,
          'status' => 1,
          'pages' => "civicrm\ncivicrm/*",
          'region' => $config->userSystem->getDefaultBlockLocation(),
        ],
        self::DASHBOARD => [
          'template' => 'Dashboard.tpl',
          'info' => ts('CiviCRM Contact Dashboard'),
          'subject' => '',
          'active' => TRUE,
          'cache' => BLOCK_NO_CACHE,
          'visibility' => 1,
          'weight' => -98,
          'status' => 1,
          'pages' => "civicrm\ncivicrm/*",
          'region' => $config->userSystem->getDefaultBlockLocation(),
        ],
        self::ADD => [
          'template' => 'Add.tpl',
          'info' => ts('CiviCRM Quick Add'),
          'subject' => ts('New Individual'),
          'active' => TRUE,
          'cache' => BLOCK_NO_CACHE,
          'visibility' => 1,
          'weight' => -97,
          'status' => 1,
          'pages' => "civicrm\ncivicrm/*",
          'region' => $config->userSystem->getDefaultBlockLocation(),
        ],
        self::LANGSWITCH => [
          'template' => 'LangSwitch.tpl',
          'info' => ts('CiviCRM Language Switcher'),
          'subject' => '',
          'templateValues' => [],
          'active' => TRUE,
          'cache' => BLOCK_NO_CACHE,
          'visibility' => 1,
          'weight' => -96,
          'status' => 1,
          'pages' => "civicrm\ncivicrm/*",
          'region' => $config->userSystem->getDefaultBlockLocation(),
        ],
        self::EVENT => [
          'template' => 'Event.tpl',
          'info' => ts('CiviCRM Upcoming Events'),
          'subject' => ts('Upcoming Events'),
          'templateValues' => [],
          'active' => TRUE,
          'cache' => BLOCK_NO_CACHE,
          'visibility' => 1,
          'weight' => -95,
          'status' => 0,
          'pages' => "civicrm\ncivicrm/*",
          'region' => $config->userSystem->getDefaultBlockLocation(),
        ],
        self::FULLTEXT_SEARCH => [
          'template' => 'FullTextSearch.tpl',
          'info' => ts('CiviCRM Full-text Search'),
          'subject' => ts('Full-text Search'),
          'active' => TRUE,
          'cache' => BLOCK_NO_CACHE,
          'visibility' => 1,
          'weight' => -94,
          'status' => 0,
          'pages' => "civicrm\ncivicrm/*",
          'region' => $config->userSystem->getDefaultBlockLocation(),
        ],
      ];
      // This block requires the legacycustomsearches extension
      if (!self::checkExtensionEnabled('legacycustomsearches')) {
        unset(self::$_properties[self::FULLTEXT_SEARCH]);
      }

      ksort(self::$_properties);
    }
  }

  /**
   * Returns the desired property from the $_properties array
   *
   * @param int $id
   *   One of the class constants (ADD, SEARCH, etc.).
   * @param string $property
   *   The desired property.
   *
   * @return string
   *   the value of the desired property
   */
  public static function getProperty($id, $property) {
    if (!(self::$_properties)) {
      self::initProperties();
    }
    return self::$_properties[$id][$property] ?? NULL;
  }

  /**
   * Sets the desired property in the $_properties array
   *
   * @param int $id
   *   One of the class constants (ADD, SEARCH, etc.).
   * @param string $property
   *   The desired property.
   * @param mixed $value
   *   The value of the desired property.
   */
  public static function setProperty($id, $property, $value) {
    if (!(self::$_properties)) {
      self::initProperties();
    }
    self::$_properties[$id][$property] = $value;
  }

  /**
   * Returns the whole $_properties array.
   *
   * @return array
   *   the $_properties array
   */
  public static function properties() {
    if (!(self::$_properties)) {
      self::initProperties();
    }
    return self::$_properties;
  }

  /**
   * Creates the info block for drupal.
   *
   * @return array
   */
  public static function getInfo() {

    $block = [];
    foreach (self::properties() as $id => $value) {
      if ($value['active']) {
        if (in_array($id, [self::ADD, self::CREATE_NEW])) {
          $hasAccess = TRUE;
          if (!CRM_Core_Permission::check('add contacts') &&
            !CRM_Core_Permission::check('edit groups')
          ) {
            $hasAccess = FALSE;
          }
          //validate across edit/view - CRM-5666
          if ($hasAccess && ($id == self::ADD)) {
            $hasAccess = CRM_Core_Permission::giveMeAllACLs();
          }
          if (!$hasAccess) {
            continue;
          }
        }

        if ($id == self::EVENT &&
          (!CRM_Core_Permission::access('CiviEvent', FALSE) ||
            !CRM_Core_Permission::check('view event info')
          )
        ) {
          continue;
        }

        $block[$id] = [
          'info' => $value['info'],
          'cache' => $value['cache'],
          'region' => $value['region'],
          'visibility' => $value['visibility'],
          'pages' => $value['pages'],
          'status' => $value['status'],
          'weight' => $value['weight'],
        ];
      }
    }

    return $block;
  }

  /**
   * Set the post action values for the block.
   *
   * php is lame and u cannot call functions from static initializers
   * hence this hack
   *
   * @param int $id
   */
  private static function setTemplateValues($id) {
    switch ($id) {
      case self::CREATE_NEW:
        self::setTemplateShortcutValues();
        break;

      case self::DASHBOARD:
        self::setTemplateDashboardValues();
        break;

      case self::ADD:
        $defaultLocation = CRM_Core_BAO_LocationType::getDefault();
        $defaultPrimaryLocationId = $defaultLocation->id;
        $values = [
          'postURL' => CRM_Utils_System::url('civicrm/contact/add', 'reset=1&ct=Individual'),
          'primaryLocationType' => $defaultPrimaryLocationId,
        ];

        foreach (CRM_Contact_BAO_Contact::$_greetingTypes as $greeting) {
          $values[$greeting . '_id'] = CRM_Contact_BAO_Contact_Utils::defaultGreeting('Individual', $greeting);
        }

        self::setProperty(self::ADD,
          'templateValues',
          $values
        );
        break;

      case self::LANGSWITCH:
        // gives the currentPath without trailing empty lcMessages to be completed
        $values = ['queryString' => CRM_Utils_System::getLinksUrl('lcMessages', TRUE, FALSE, FALSE)];
        self::setProperty(self::LANGSWITCH, 'templateValues', $values);
        break;

      case self::FULLTEXT_SEARCH:
        $urlArray = [
          'fullTextSearchID' => CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue',
            'CRM_Contact_Form_Search_Custom_FullText', 'value', 'name'
          ),
        ];
        self::setProperty(self::FULLTEXT_SEARCH, 'templateValues', $urlArray);
        break;

      case self::RECENTLY_VIEWED:
        $recent = CRM_Utils_Recent::get();
        self::setProperty(self::RECENTLY_VIEWED, 'templateValues', ['recentlyViewed' => $recent]);
        break;

      case self::EVENT:
        self::setTemplateEventValues();
        break;
    }
  }

  /**
   * Create the list of options to create New objects for the application and format is as a block.
   */
  private static function setTemplateShortcutValues() {
    static $shortCuts = [];

    if (!($shortCuts)) {
      if (CRM_Core_Permission::check('add contacts')) {
        if (CRM_Core_Permission::giveMeAllACLs()) {
          $shortCuts = CRM_Contact_BAO_ContactType::getCreateNewList();
        }
      }

      // new activity (select target contact)
      $shortCuts = array_merge($shortCuts, [
        [
          'path' => 'civicrm/activity',
          'query' => 'action=add&reset=1&context=standalone',
          'ref' => 'new-activity',
          'title' => ts('Activity'),
        ],
      ]);

      $components = CRM_Core_Component::getEnabledComponents();

      if ($components) {
        // check if we can process credit card contribs
        $newCredit = CRM_Core_Config::isEnabledBackOfficeCreditCardPayments();

        foreach ($components as $obj) {
          $obj->creatNewShortcut($shortCuts, $newCredit);
        }
      }

      // new email (select recipients)
      $shortCuts = array_merge($shortCuts, [
        [
          'path' => 'civicrm/activity/email/add',
          'query' => 'atype=3&action=add&reset=1&context=standalone',
          'ref' => 'new-email',
          'title' => ts('Email'),
        ],
      ]);

      if (CRM_Core_Permission::check('edit groups')) {
        $shortCuts = array_merge($shortCuts, [
          [
            'path' => 'civicrm/group/add',
            'query' => 'reset=1',
            'ref' => 'new-group',
            'title' => ts('Group'),
          ],
        ]);
      }

      if (CRM_Core_Permission::check('manage tags')) {
        $shortCuts = array_merge($shortCuts, [
          [
            'path' => 'civicrm/tag',
            'query' => 'reset=1&action=add',
            'ref' => 'new-tag',
            'title' => ts('Tag'),
          ],
        ]);
      }

      if (empty($shortCuts)) {
        return NULL;
      }
    }

    $values = [];

    foreach ($shortCuts as $key => $short) {
      $values[$key] = self::setShortCutValues($short);
    }

    // Hook that enables extensions to add user-defined links
    CRM_Utils_Hook::links('create.new.shortcuts',
      NULL,
      NULL,
      $values
    );

    foreach ($values as $key => $val) {
      $values[$key]['name'] = $val['name'] ?? $val['title'];
      $values[$key] += ['shortCuts' => []];
    }

    self::setProperty(self::CREATE_NEW, 'templateValues', ['shortCuts' => $values]);
  }

  /**
   * @param array $short
   *
   * @return array
   */
  private static function setShortcutValues($short) {
    $value = [];
    if (isset($short['url'])) {
      $value['url'] = $short['url'];
    }
    elseif (isset($short['path'])) {
      $value['url'] = CRM_Utils_System::url($short['path'], $short['query'], FALSE);
    }
    $value['title'] = $short['title'];
    $value['ref'] = $short['ref'] ?? '';
    $value['shortCuts'] = [];
    if (!empty($short['shortCuts'])) {
      foreach ($short['shortCuts'] as $shortCut) {
        $value['shortCuts'][] = self::setShortcutValues($shortCut);
      }
    }
    return $value;
  }

  /**
   * Create the list of dashboard links.
   */
  private static function setTemplateDashboardValues() {
    static $dashboardLinks = [];
    if (CRM_Core_Permission::check('access Contact Dashboard')) {
      $dashboardLinks = [
        [
          'path' => 'civicrm/user',
          'query' => 'reset=1',
          'title' => ts('My Contact Dashboard'),
        ],
      ];
    }

    $values = [];
    foreach ($dashboardLinks as $dash) {
      $value = [];
      if (isset($dash['url'])) {
        $value['url'] = $dash['url'];
      }
      else {
        $value['url'] = CRM_Utils_System::url($dash['path'], $dash['query'], FALSE);
      }
      $value['title'] = $dash['title'];
      $value['key'] = $dash['key'] ?? NULL;
      $values[] = $value;
    }
    self::setProperty(self::DASHBOARD, 'templateValues', ['dashboardLinks' => $values]);
  }

  /**
   * Create the event blocks for upcoming events.
   */
  private static function setTemplateEventValues() {
    $config = CRM_Core_Config::singleton();

    $info = CRM_Event_BAO_Event::getCompleteInfo(date("Ymd"));

    if ($info) {
      $session = CRM_Core_Session::singleton();
      // check if registration link should be displayed
      foreach ($info as $id => $event) {
        //@todo FIXME  - validRegistraionRequest takes eventID not contactID as a param
        // this is called via an obscure patch from Joomla event block rendering (only)
        $info[$id]['onlineRegistration'] = CRM_Event_BAO_Event::validRegistrationRequest($event,
          $session->get('userID')
        );
      }

      self::setProperty(self::EVENT, 'templateValues', ['eventBlock' => $info]);
    }
  }

  /**
   * Given an id creates a subject/content array
   *
   * @param int $id
   *   Id of the block.
   *
   * @return array
   */
  public static function getContent($id) {
    // return if upgrade mode
    $config = CRM_Core_Config::singleton();
    if ($config->isUpgradeMode()) {
      return NULL;
    }
    CRM_Core_Smarty::singleton()->ensureVariablesAreAssigned(['langSwitch', 'breadcrumb', 'pageTitle']);

    if (!self::getProperty($id, 'active')) {
      return NULL;
    }

    if ($id == self::EVENT &&
      CRM_Core_Permission::check('view event info')
    ) {
      // is CiviEvent enabled?
      if (!CRM_Core_Permission::access('CiviEvent', FALSE)) {
        return NULL;
      }
      // do nothing
    }
    // require 'access CiviCRM' permissons, except for the language switch block
    elseif (!CRM_Core_Permission::check('access CiviCRM') && $id != self::LANGSWITCH) {
      return NULL;
    }
    elseif ($id == self::ADD) {
      $hasAccess = TRUE;
      if (!CRM_Core_Permission::check('add contacts') &&
        !CRM_Core_Permission::check('edit groups')
      ) {
        $hasAccess = FALSE;
      }
      //validate across edit/view - CRM-5666
      if ($hasAccess) {
        $hasAccess = CRM_Core_Permission::giveMeAllACLs();
      }
      if (!$hasAccess) {
        return NULL;
      }
    }
    // This block requires the legacycustomsearches extension
    if ($id == self::FULLTEXT_SEARCH && !self::checkExtensionEnabled('legacycustomsearches')) {
      return NULL;
    }

    self::setTemplateValues($id);

    // Suppress Recent Items block if it's empty - CRM-5188
    if ($id == self::RECENTLY_VIEWED) {
      $recent = self::getProperty($id, 'templateValues');
      if (CRM_Utils_Array::crmIsEmptyArray($recent)) {
        return NULL;
      }
    }

    // Suppress Language switcher if language is inherited from CMS - CRM-9971
    $config = CRM_Core_Config::singleton();
    if ($id == self::LANGSWITCH && $config->inheritLocale) {
      return NULL;
    }

    $block = [];
    $block['name'] = 'block-civicrm';
    $block['id'] = $block['name'] . '_' . $id;
    $block['subject'] = self::fetch($id, 'Subject.tpl',
      ['subject' => self::getProperty($id, 'subject')]
    );
    $block['content'] = self::fetch($id, self::getProperty($id, 'template'),
      self::getProperty($id, 'templateValues')
    );

    return $block;
  }

  /**
   * Given an id and a template, fetch the contents
   *
   * @param int $id
   *   Id of the block.
   * @param string $fileName
   *   Name of the template file.
   * @param array $properties
   *   Template variables.
   *
   * @return array
   */
  public static function fetch($id, $fileName, $properties) {
    $template = CRM_Core_Smarty::singleton();

    if ($properties) {
      $template->assign($properties);
    }

    return $template->fetch('CRM/Block/' . $fileName);
  }

  private static function checkExtensionEnabled(string $key): bool {
    $extension = \Civi\Api4\Extension::get(FALSE)
      ->addWhere('key', '=', $key)
      ->addWhere('status', '=', 'installed')
      ->selectRowCount()
      ->execute();
    return (bool) $extension->count();
  }

}
