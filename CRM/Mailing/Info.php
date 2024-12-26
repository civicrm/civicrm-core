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
 * This class introduces component to the system and provides all the
 * information about it. It needs to extend CRM_Core_Component_Info
 * abstract class.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Mailing_Info extends CRM_Core_Component_Info {

  /**
   * @var string
   * @inheritDoc
   */
  protected $keyword = 'mailing';

  /**
   * @return array
   */
  public static function createAngularSettings():array {
    $reportIds = [];
    $reportTypes = ['detail', 'opened', 'bounce', 'clicks'];
    foreach ($reportTypes as $report) {
      $rptResult = civicrm_api3('ReportInstance', 'get', [
        'sequential' => 1,
        'report_id' => 'mailing/' . $report,
      ]);
      if (!empty($rptResult['values'])) {
        $reportIds[$report] = $rptResult['values'][0]['id'];
      }
    }

    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');

    // Generic params.
    $params = [
      'options' => ['limit' => 0],
      'sequential' => 1,
    ];
    $headerfooterList = civicrm_api3('MailingComponent', 'get', $params + [
      'is_active' => 1,
      'return' => [
        'name',
        'component_type',
        'is_default',
        'body_html',
        'body_text',
      ],
    ]);

    $emailAdd = civicrm_api3('Email', 'get', [
      'sequential' => 1,
      'return' => 'email',
      'contact_id' => $contactID,
    ]);

    $mesTemplate = civicrm_api3('MessageTemplate', 'get', $params + [
      'sequential' => 1,
      'is_active' => 1,
      'return' => ['id', 'msg_title'],
      'workflow_name' => ['IS NULL' => ''],
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
    $requiredTokens = Civi\Core\Resolver::singleton()->call('call://civi_flexmailer_required_tokens/getRequiredTokens', []);

    $crmMailingSettings = [
      'templateTypes' => CRM_Mailing_BAO_Mailing::getTemplateTypes(),
      'civiMails' => [],
      'campaignEnabled' => CRM_Core_Component::isEnabled('CiviCampaign'),
      'groupNames' => [],
      'headerfooterList' => $headerfooterList['values'],
      'mesTemplate' => $mesTemplate['values'],
      'emailAdd' => $emailAdd['values'],
      'mailTokens' => $mailTokens['values'],
      'contactid' => $contactID,
      'requiredTokens' => $requiredTokens,
      'enableReplyTo' => (int) Civi::settings()->get('replyTo'),
      'disableMandatoryTokensCheck' => (int) Civi::settings()
        ->get('disable_mandatory_tokens_check'),
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
      'autoRecipientRebuild' => Civi::settings()->get('auto_recipient_rebuild'),
    ];
    return $crmMailingSettings;
  }

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
   * @return bool
   */
  public static function workflowEnabled() {
    $config = CRM_Core_Config::singleton();
    return $config->userSystem->mailingWorkflowIsEnabled();
  }

  /**
   * @inheritDoc
   */
  public function getPermissions(): array {
    $permissions = [
      'access CiviMail' => [
        'label' => ts('access CiviMail'),
      ],
      'access CiviMail subscribe/unsubscribe pages' => [
        'label' => ts('access CiviMail subscribe/unsubscribe pages'),
        'description' => ts('Subscribe/unsubscribe from mailing list group'),
      ],
      'delete in CiviMail' => [
        'label' => ts('delete in CiviMail'),
        'description' => ts('Delete Mailing'),
      ],
      'view public CiviMail content' => [
        'label' => ts('view public CiviMail content'),
      ],
    ];
    // Workflow permissions
    $permissions['create mailings'] = [
      'label' => ts('create mailings'),
      'disabled' => !self::workflowEnabled(),
    ];
    $permissions['schedule mailings'] = [
      'label' => ts('schedule mailings'),
      'disabled' => !self::workflowEnabled(),
    ];
    $permissions['approve mailings'] = [
      'label' => ts('approve mailings'),
      'disabled' => !self::workflowEnabled(),
    ];
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
