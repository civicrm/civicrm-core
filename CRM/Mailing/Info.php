<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
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
    return array(
      'name' => 'CiviMail',
      'translatedName' => ts('CiviMail'),
      'title' => 'CiviCRM Mailing Engine',
      'search' => 1,
      'showActivitiesInCore' => 1,
    );
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
      return array();
    }

    $result = array();
    $result['crmMailing'] = array(
      'ext' => 'civicrm',
      'js' => array(
        'ang/crmMailing.js',
        'ang/crmMailing/*.js',
      ),
      'css' => array('ang/crmMailing.css'),
      'partials' => array('ang/crmMailing'),
    );
    $result['crmMailingAB'] = array(
      'ext' => 'civicrm',
      'js' => array(
        'ang/crmMailingAB.js',
        'ang/crmMailingAB/*.js',
        'ang/crmMailingAB/*/*.js',
      ),
      'css' => array('ang/crmMailingAB.css'),
      'partials' => array('ang/crmMailingAB'),
    );
    $result['crmD3'] = array(
      'ext' => 'civicrm',
      'js' => array(
        'ang/crmD3.js',
        'bower_components/d3/d3.min.js',
      ),
    );

    $config = CRM_Core_Config::singleton();
    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');

    // Get past mailings.
    // CRM-16155 - Limit to a reasonable number.
    $civiMails = civicrm_api3('Mailing', 'get', array(
      'is_completed' => 1,
      'mailing_type' => array('IN' => array('standalone', 'winner')),
      'domain_id' => CRM_Core_Config::domainID(),
      'return' => array('id', 'name', 'scheduled_date'),
      'sequential' => 1,
      'options' => array(
        'limit' => 500,
        'sort' => 'is_archived asc, scheduled_date desc',
      ),
    ));
    // Generic params.
    $params = array(
      'options' => array('limit' => 0),
      'sequential' => 1,
    );

    $groupNames = civicrm_api3('Group', 'get', $params + array(
      'is_active' => 1,
      'check_permissions' => TRUE,
      'return' => array('title', 'visibility', 'group_type', 'is_hidden'),
    ));
    $headerfooterList = civicrm_api3('MailingComponent', 'get', $params + array(
      'is_active' => 1,
      'return' => array('name', 'component_type', 'is_default', 'body_html', 'body_text'),
    ));

    $emailAdd = civicrm_api3('Email', 'get', array(
      'sequential' => 1,
      'return' => "email",
      'contact_id' => $contactID,
    ));

    $mesTemplate = civicrm_api3('MessageTemplate', 'get', $params + array(
      'sequential' => 1,
      'is_active' => 1,
      'return' => array("id", "msg_title"),
      'workflow_id' => array('IS NULL' => ""),
    ));
    $mailTokens = civicrm_api3('Mailing', 'gettokens', array(
      'entity' => array('contact', 'mailing'),
      'sequential' => 1,
    ));
    $fromAddress = civicrm_api3('OptionValue', 'get', $params + array(
      'option_group_id' => "from_email_address",
      'domain_id' => CRM_Core_Config::domainID(),
    ));
    CRM_Core_Resources::singleton()
      ->addSetting(array(
        'crmMailing' => array(
          'civiMails' => $civiMails['values'],
          'campaignEnabled' => in_array('CiviCampaign', $config->enableComponents),
          'groupNames' => $groupNames['values'],
          'headerfooterList' => $headerfooterList['values'],
          'mesTemplate' => $mesTemplate['values'],
          'emailAdd' => $emailAdd['values'],
          'mailTokens' => $mailTokens['values'],
          'contactid' => $contactID,
          'requiredTokens' => CRM_Utils_Token::getRequiredTokens(),
          'enableReplyTo' => (int) Civi::settings()->get('replyTo'),
          'disableMandatoryTokensCheck' => (int) Civi::settings()->get('disable_mandatory_tokens_check'),
          'fromAddress' => $fromAddress['values'],
          'defaultTestEmail' => civicrm_api3('Contact', 'getvalue', array(
              'id' => 'user_contact_id',
              'return' => 'email',
            )),
          'visibility' => CRM_Utils_Array::makeNonAssociative(CRM_Core_SelectValues::groupVisibility()),
          'workflowEnabled' => CRM_Mailing_Info::workflowEnabled(),
        ),
      ))
      ->addPermissions(array(
        'view all contacts',
        'access CiviMail',
        'create mailings',
        'schedule mailings',
        'approve mailings',
        'delete in CiviMail',
        'edit message templates',
      ));

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
    $permissions = array(
      'access CiviMail' => array(
        ts('access CiviMail'),
      ),
      'access CiviMail subscribe/unsubscribe pages' => array(
        ts('access CiviMail subscribe/unsubscribe pages'),
        ts('Subscribe/unsubscribe from mailing list group'),
      ),
      'delete in CiviMail' => array(
        ts('delete in CiviMail'),
        ts('Delete Mailing'),
      ),
      'view public CiviMail content' => array(
        ts('view public CiviMail content'),
      ),
    );

    if (self::workflowEnabled() || $getAllUnconditionally) {
      $permissions[] = array(
        'create mailings' => array(
          ts('create mailings'),
        ),
      );
      $permissions[] = array(
        'schedule mailings' => array(
          ts('schedule mailings'),
        ),
      );
      $permissions[] = array(
        'approve mailings' => array(
          ts('approve mailings'),
        ),
      );
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
    return array(
      'title' => ts('Mailings'),
      'id' => 'mailing',
      'url' => 'mailing',
      'weight' => 45,
    );
  }

  /**
   * @inheritDoc
   * @return array
   */
  public function registerAdvancedSearchPane() {
    return array(
      'title' => ts('Mailings'),
      'weight' => 20,
    );
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
