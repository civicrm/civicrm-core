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

namespace Civi\Mailing;

use Civi\Afform\AfformMetadataInjector;
use Civi\Afform\FormDataModel;

/**
 * Angular helper functions for CiviMail.
 */
class Angular {

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

    $contactID = \CRM_Core_Session::getLoggedInContactID();

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
      'domain_id' => \CRM_Core_Config::domainID(),
    ]);
    $enabledLanguages = \CRM_Core_I18n::languages(TRUE);
    $isMultiLingual = (count($enabledLanguages) > 1);
    $requiredTokens = \Civi\Core\Resolver::singleton()->call('call://civi_flexmailer_required_tokens/getRequiredTokens', []);
    $default_email = \Civi\Api4\Email::get(TRUE)
      ->addWhere('contact_id', '=', 'user_contact_id')
      ->addWhere('is_primary', '=', TRUE)
      ->setLimit(25)
      ->execute()
      ->first()['email'] ?? '';
    $crmMailingSettings = [
      'templateTypes' => \CRM_Mailing_BAO_Mailing::getTemplateTypes(),
      'civiMails' => [],
      'campaignEnabled' => \CRM_Core_Component::isEnabled('CiviCampaign'),
      'groupNames' => [],
      'headerfooterList' => $headerfooterList['values'],
      'mesTemplate' => $mesTemplate['values'],
      'emailAdd' => $emailAdd['values'],
      'mailTokens' => $mailTokens['values'],
      'contactid' => $contactID,
      'requiredTokens' => $requiredTokens,
      'enableReplyTo' => (int) \Civi::settings()->get('replyTo'),
      'disableMandatoryTokensCheck' => (int) \Civi::settings()
        ->get('disable_mandatory_tokens_check'),
      'fromAddress' => $fromAddress['values'],
      'defaultTestEmail' => $default_email,
      'visibility' => \CRM_Utils_Array::makeNonAssociative(\CRM_Core_SelectValues::groupVisibility()),
      'workflowEnabled' => \CRM_Mailing_Info::workflowEnabled(),
      'reportIds' => $reportIds,
      'enabledLanguages' => $enabledLanguages,
      'isMultiLingual' => $isMultiLingual,
      'autoRecipientRebuild' => \Civi::settings()->get('auto_recipient_rebuild'),
      'customGroups' => array_map(function($group) {
        return [
          'name' => $group['name'],
          'title' => $group['title'],
          'template' => '~/crmMailing/customGroup_' . $group['name'] . '.html',
        ];
      }, array_values(self::getMailingGroups())),
    ];
    return $crmMailingSettings;
  }

  /**
   * Generate HTML partials for Mailing custom field groups.
   *
   * @param string $moduleName
   * @param array $module
   * @return array
   */
  public static function createAngularPartials(string $moduleName, array $module): array {
    $partials = [];
    foreach (self::getMailingGroups() as $group) {
      $doc = \phpQuery::newDocument('<div af-fieldset field-data="mailing"></div>');
      $fieldset = \pq($doc)->find('div');
      foreach ($group['fields'] ?? [] as $field) {
        $fieldName = $group['name'] . '.' . $field['name'];
        $fieldInfo = FormDataModel::getField('Mailing', $fieldName, 'create');
        if ($fieldInfo) {
          $afField = \pq('<af-field></af-field>');
          $afField->attr('name', $fieldName);
          $fieldInfo['label'] = $field['label'];
          AfformMetadataInjector::setFieldMetadata($afField->get(0), $fieldInfo);
          $fieldset->append($afField);
        }
      }
      $partials["~/{$moduleName}/customGroup_{$group['name']}.html"] = $doc->html();
    }
    return $partials;
  }

  private static function getMailingGroups(): array {
    return \CRM_Core_BAO_CustomGroup::getAll(['extends' => 'Mailing', 'is_active' => TRUE]);
  }

}
