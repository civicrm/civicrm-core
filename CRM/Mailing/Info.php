<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * This class introduces component to the system and provides all the
 * information about it. It needs to extend CRM_Core_Component_Info
 * abstract class.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Mailing_Info extends CRM_Core_Component_Info {

  /**
   * @inheritDoc
   */
  protected $keyword = 'mailing';


  /**
   * @inheritDoc
   * @return array
   */
  public function getInfo() {
    return [
      'name' => 'CiviMail',
      'translatedName' => ts('CiviMail'),
      'title' => ts('CiviCRM Mailing Engine'),
      'search' => 1,
      'showActivitiesInCore' => 1,
    ];
  }

  /**
   * Get AngularJS modules and their dependencies.
   *
   * @return array
   *   list of modules; same format as CRM_Utils_Hook::angularModules(&$angularModules)
   * @see CRM_Utils_Hook::angularModules
   */
  public function getAngularModules() {
    // load angular files only if valid permissions are granted to the user
    if (!CRM_Core_Permission::check('access CiviMail')
      && !CRM_Core_Permission::check('create mailings')
      && !CRM_Core_Permission::check('schedule mailings')
      && !CRM_Core_Permission::check('approve mailings')
    ) {
      return [];
    }
    global $civicrm_root;

    $reportIds = [];
    $reportTypes = ['detail', 'opened', 'bounce', 'clicks'];
    foreach ($reportTypes as $report) {
      $result = civicrm_api3('ReportInstance', 'get', [
        'sequential' => 1,
        'report_id' => 'mailing/' . $report]);
      if (!empty($result['values'])) {
        $reportIds[$report] = $result['values'][0]['id'];
      }
    }
    $result = [];
    $result['crmMailing'] = include "$civicrm_root/ang/crmMailing.ang.php";
    $result['crmMailingAB'] = include "$civicrm_root/ang/crmMailingAB.ang.php";
    $result['crmD3'] = include "$civicrm_root/ang/crmD3.ang.php";

    $config = CRM_Core_Config::singleton();
    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');

    // Generic params.
    $params = [
      'options' => ['limit' => 0],
      'sequential' => 1,
    ];
    $groupNames = civicrm_api3('Group', 'get', $params + [
      'is_active' => 1,
      'check_permissions' => TRUE,
      'return' => ['title', 'visibility', 'group_type', 'is_hidden'],
    ]);
    $headerfooterList = civicrm_api3('MailingComponent', 'get', $params + [
      'is_active' => 1,
      'return' => ['name', 'component_type', 'is_default', 'body_html', 'body_text'],
    ]);

    $emailAdd = civicrm_api3('Email', 'get', [
      'sequential' => 1,
      'return' => "email",
      'contact_id' => $contactID,
    ]);

    $mesTemplate = civicrm_api3('MessageTemplate', 'get', $params + [
      'sequential' => 1,
      'is_active' => 1,
      'return' => ["id", "msg_title"],
      'workflow_id' => ['IS NULL' => ""],
    ]);
    $mailTokens = civicrm_api3('Mailing', 'gettokens', [
      'entity' => ['contact', 'mailing'],
      'sequential' => 1,
    ]);
    $fromAddress = civicrm_api3('OptionValue', 'get', $params + [
      'option_group_id' => "from_email_address",
      'domain_id' => CRM_Core_Config::domainID(),
    ]);
    $enabledLanguages = CRM_Core_I18n::languages(TRUE);
    $isMultiLingual = (count($enabledLanguages) > 1);
    // FlexMailer is a refactoring of CiviMail which provides new hooks/APIs/docs. If the sysadmin has opted to enable it, then use that instead of CiviMail.
    $requiredTokens = defined('CIVICRM_FLEXMAILER_HACK_REQUIRED_TOKENS') ? Civi\Core\Resolver::singleton()->call(CIVICRM_FLEXMAILER_HACK_REQUIRED_TOKENS, []) : CRM_Utils_Token::getRequiredTokens();
    CRM_Core_Resources::singleton()
      ->addSetting([
        'crmMailing' => [
          'templateTypes' => CRM_Mailing_BAO_Mailing::getTemplateTypes(),
          'civiMails' => [],
          'campaignEnabled' => in_array('CiviCampaign', $config->enableComponents),
          'groupNames' => [],
          // @todo this is not used in core. Remove once Mosaico no longer depends on it.
          'testGroupNames' => $groupNames['values'],
          'headerfooterList' => $headerfooterList['values'],
          'mesTemplate' => $mesTemplate['values'],
          'emailAdd' => $emailAdd['values'],
          'mailTokens' => $mailTokens['values'],
          'contactid' => $contactID,
          'requiredTokens' => $requiredTokens,
          'enableReplyTo' => (int) Civi::settings()->get('replyTo'),
          'disableMandatoryTokensCheck' => (int) Civi::settings()->get('disable_mandatory_tokens_check'),
          'fromAddress' => $fromAddress['values'],
          'defaultTestEmail' => civicrm_api3('Contact', 'getvalue', [
              'id' => 'user_contact_id',
              'return' => 'email',
            ]),
          'visibility' => CRM_Utils_Array::makeNonAssociative(CRM_Core_SelectValues::groupVisibility()),
          'workflowEnabled' => CRM_Mailing_Info::workflowEnabled(),
          'reportIds' => $reportIds,
          'enabledLanguages' => $enabledLanguages,
          'isMultiLingual' => $isMultiLingual,
        ],
      ])
      ->addPermissions([
        'view all contacts',
        'edit all contacts',
        'access CiviMail',
        'create mailings',
        'schedule mailings',
        'approve mailings',
        'delete in CiviMail',
        'edit message templates',
      ]);

    return $result;
  }

  /**
   * @return bool
   */
  public static function workflowEnabled() {
    $config = CRM_Core_Config::singleton();

    // early exit, since not true for most
    if (!$config->userSystem->is_drupal ||
      !function_exists('module_exists')
    ) {
      return FALSE;
    }

    if (!module_exists('rules')) {
      return FALSE;
    }

    $enableWorkflow = Civi::settings()->get('civimail_workflow');

    return ($enableWorkflow &&
      $config->userSystem->is_drupal
    ) ? TRUE : FALSE;
  }

  /**
   * @inheritDoc
   * @param bool $getAllUnconditionally
   * @param bool $descriptions
   *   Whether to return permission descriptions
   *
   * @return array
   */
  public function getPermissions($getAllUnconditionally = FALSE, $descriptions = FALSE) {
    $permissions = [
      'access CiviMail' => [
        ts('access CiviMail'),
      ],
      'access CiviMail subscribe/unsubscribe pages' => [
        ts('access CiviMail subscribe/unsubscribe pages'),
        ts('Subscribe/unsubscribe from mailing list group'),
      ],
      'delete in CiviMail' => [
        ts('delete in CiviMail'),
        ts('Delete Mailing'),
      ],
      'view public CiviMail content' => [
        ts('view public CiviMail content'),
      ],
    ];

    if (self::workflowEnabled() || $getAllUnconditionally) {
      $permissions['create mailings'] = [
        ts('create mailings'),
      ];
      $permissions['schedule mailings'] = [
        ts('schedule mailings'),
      ];
      $permissions['approve mailings'] = [
        ts('approve mailings'),
      ];
    }

    if (!$descriptions) {
      foreach ($permissions as $name => $attr) {
        $permissions[$name] = array_shift($attr);
      }
    }

    return $permissions;
  }


  /**
   * @inheritDoc
   * @return null
   */
  public function getUserDashboardElement() {
    // no dashboard element for this component
    return NULL;
  }

  /**
   * @return null
   */
  public function getUserDashboardObject() {
    // no dashboard element for this component
    return NULL;
  }

  /**
   * @inheritDoc
   * @return array
   */
  public function registerTab() {
    return [
      'title' => ts('Mailings'),
      'id' => 'mailing',
      'url' => 'mailing',
      'weight' => 45,
    ];
  }

  /**
   * @inheritDoc
   * @return string
   */
  public function getIcon() {
    return 'crm-i fa-envelope-o';
  }

  /**
   * @inheritDoc
   * @return array
   */
  public function registerAdvancedSearchPane() {
    return [
      'title' => ts('Mailings'),
      'weight' => 20,
    ];
  }

  /**
   * @inheritDoc
   * @return null
   */
  public function getActivityTypes() {
    return NULL;
  }

  /**
   * add shortcut to Create New.
   * @param $shortCuts
   */
  public function creatNewShortcut(&$shortCuts) {
  }

}
