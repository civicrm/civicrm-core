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
class CRM_PCP_BAO_PCP extends CRM_PCP_DAO_PCP implements \Civi\Core\HookInterface {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_pcpLinks = NULL;

  /**
   * @deprecated
   * @return CRM_PCP_DAO_PCP
   */
  public static function create($params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::writeRecord($params);
  }

  /**
   * Callback for hook_civicrm_pre().
   *
   * @param \Civi\Core\Event\PreEvent $event
   *
   * @throws \CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event): void {
    if ($event->action === 'create') {
      // For some reason `status_id` is allowed to be empty
      // FIXME: Why?
      $event->params['status_id'] ??= 0;
      // Supply default for `currency`
      $event->params['currency'] ??= Civi::settings()->get('defaultCurrency');
    }
  }

  /**
   * Get the Display  name of a contact for a PCP.
   *
   * @param int $id
   *   Id for the PCP.
   *
   * @return null|string
   *   Display name of the contact if found
   */
  public static function displayName($id) {
    $id = CRM_Utils_Type::escape($id, 'Integer');

    $query = "
SELECT civicrm_contact.display_name
FROM   civicrm_pcp, civicrm_contact
WHERE  civicrm_pcp.contact_id = civicrm_contact.id
  AND  civicrm_pcp.id = {$id}
";
    return CRM_Core_DAO::singleValueQuery($query);
  }

  /**
   * Return PCP  Block info for dashboard.
   *
   * @param int $contactID
   *
   * @return array
   *   array of Pcp if found
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function getPcpDashboardInfo(int $contactID): array {
    $query = '
SELECT pcp.*, block.is_tellfriend_enabled,
COALESCE(cp.end_date, event.end_date) as end_date
FROM civicrm_pcp pcp
LEFT JOIN civicrm_pcp_block block ON block.id = pcp.pcp_block_id
LEFT OUTER JOIN civicrm_contribution_page cp ON (cp.id = pcp.page_id AND pcp.page_type = "contribute")
LEFT OUTER JOIN civicrm_event event ON (event.id = pcp.page_id AND pcp.page_type = "event")
WHERE pcp.is_active = 1
  AND pcp.contact_id = %1
ORDER BY page_type, page_id';

    $pcpInfoDao = CRM_Core_DAO::executeQuery($query, [1 => [$contactID, 'Integer']]);

    $approved = CRM_Core_PseudoConstant::getKey('CRM_PCP_BAO_PCP', 'status_id', 'Approved');
    $contactPCPPages = [];
    $pcpInfo = [];
    $links = [];
    while ($pcpInfoDao->fetch()) {
      $links = self::pcpLinks($pcpInfoDao->id);
      $hide = array_sum(array_keys($links['all']));
      $mask = $hide;
      if ($links) {
        $replace = [
          'pcpId' => $pcpInfoDao->id,
          'pcpBlock' => $pcpInfoDao->pcp_block_id,
          'pageComponent' => $pcpInfoDao->page_type,
        ];
      }

      $pcpLink = $links['all'];
      $class = '';

      if ($pcpInfoDao->status_id != $approved || $pcpInfoDao->is_active != 1) {
        $class = 'disabled';
        if (!function_exists('tellafriend_civicrm_config') || !$pcpInfoDao->is_tellfriend_enabled) {
          $mask -= CRM_Core_Action::DETACH;
        }
      }

      if ($pcpInfoDao->is_active == 1) {
        $mask -= CRM_Core_Action::ENABLE;
      }
      else {
        $mask -= CRM_Core_Action::DISABLE;
      }
      $action = CRM_Core_Action::formLink($pcpLink, $mask, $replace, ts('more'),
        FALSE, 'pcp.dashboard.active', 'PCP', $pcpInfoDao->id);

      $pageTitle = self::getPcpTitle($pcpInfoDao->page_type, (int) $pcpInfoDao->page_id);

      $pcpInfo[] = [
        'pageTitle' => $pageTitle,
        'pcpId' => $pcpInfoDao->id,
        'pcpTitle' => $pcpInfoDao->title,
        'pcpStatus' => CRM_Core_PseudoConstant::getLabel('CRM_PCP_BAO_PCP', 'status_id', $pcpInfoDao->status_id),
        'end_date' => $pcpInfoDao->end_date,
        'action' => $action,
        'class' => $class,
      ];
      $contactPCPPages[$pcpInfoDao->page_type][] = $pcpInfoDao->page_id;
    }

    $excludePageClause = $clause = NULL;
    if (!empty($contactPCPPages)) {
      foreach ($contactPCPPages as $component => $entityIds) {
        $excludePageClause[] = "
( target_entity_type = '{$component}'
AND target_entity_id NOT IN ( " . implode(',', $entityIds) . ") )";
      }

      $clause = ' AND ' . implode(' OR ', $excludePageClause);
    }

    $query = "
SELECT block.*,
COALESCE(cp.end_date, event.end_date) as end_date
FROM civicrm_pcp_block block
LEFT OUTER JOIN civicrm_contribution_page cp ON (cp.id = block.target_entity_id AND block.target_entity_type = 'contribute')
LEFT OUTER JOIN civicrm_event event ON (event.id = block.target_entity_id AND block.target_entity_type = 'event')
WHERE block.is_active = 1
{$clause}
  AND (cp.is_active = 1 OR event.is_active = 1)
  AND (
    (block.target_entity_type = 'contribute' AND (cp.end_date >= DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s') OR cp.end_date IS NULL))
    OR
    (block.target_entity_type = 'event' AND (event.end_date >= DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s') OR event.end_date IS NULL)))
GROUP BY block.id, block.target_entity_id
ORDER BY target_entity_type, target_entity_id
";
    $pcpBlockDao = CRM_Core_DAO::executeQuery($query);
    $pcpBlock = [];
    $mask = 0;

    while ($pcpBlockDao->fetch()) {
      if ($links) {
        $replace = [
          'pageId' => $pcpBlockDao->target_entity_id,
          'pageComponent' => $pcpBlockDao->target_entity_type,
        ];
      }
      $pcpLink = $links['add'] ?? NULL;
      $action = CRM_Core_Action::formLink($pcpLink, $mask, $replace ?? [], ts('more'),
        FALSE, 'pcp.dashboard.other', "{$pcpBlockDao->target_entity_type}_PCP", $pcpBlockDao->target_entity_id);
      $pageTitle = self::getPcpTitle($pcpBlockDao->target_entity_type, (int) $pcpBlockDao->target_entity_id);
      if ($pageTitle) {
        $pcpBlock[] = [
          'pageId' => $pcpBlockDao->target_entity_id,
          'pageComponent' => $pcpBlockDao->target_entity_type,
          'pageTitle' => $pageTitle,
          'end_date' => $pcpBlockDao->end_date,
          'action' => $action,
        ];
      }
    }

    return [$pcpBlock, $pcpInfo];
  }

  /**
   * Show the total amount for Personal Campaign Page on thermometer.
   *
   * @param array $pcpId
   *   Contains the pcp ID.
   *
   * @return float
   *   Total amount
   */
  public static function thermoMeter($pcpId) {
    $completedStatusId = CRM_Core_PseudoConstant::getKey(
      'CRM_Contribute_BAO_Contribution',
      'contribution_status_id',
      'Completed'
    );
    $query = "
SELECT SUM(cc.total_amount) as total
FROM civicrm_pcp pcp
LEFT JOIN civicrm_contribution_soft cs ON ( pcp.id = cs.pcp_id )
LEFT JOIN civicrm_contribution cc ON ( cs.contribution_id = cc.id)
WHERE pcp.id = %1 AND cc.contribution_status_id = %2 AND cc.is_test = 0";

    $params = [
      1 => [$pcpId, 'Integer'],
      2 => [$completedStatusId, 'Integer'],
    ];
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * Show the amount, nickname on honor roll.
   *
   * @param array $pcpId
   *   Contains the pcp ID.
   *
   * @return array
   */
  public static function honorRoll($pcpId) {
    $completedStatusId = CRM_Core_PseudoConstant::getKey(
      'CRM_Contribute_BAO_Contribution',
      'contribution_status_id',
      'Completed'
    );
    $query = "
            SELECT cc.id, cs.pcp_roll_nickname, cs.pcp_personal_note,
                   cc.total_amount, cc.currency
            FROM civicrm_contribution cc
                 LEFT JOIN civicrm_contribution_soft cs ON cc.id = cs.contribution_id
            WHERE cs.pcp_id = %1
                  AND cs.pcp_display_in_roll = 1
                  AND contribution_status_id = %2
                  AND is_test = 0";
    $params = [
      1 => [$pcpId, 'Integer'],
      2 => [$completedStatusId, 'Integer'],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $honor = [];
    while ($dao->fetch()) {
      $honor[$dao->id]['nickname'] = ucwords($dao->pcp_roll_nickname);
      $honor[$dao->id]['total_amount'] = CRM_Utils_Money::format($dao->total_amount, $dao->currency);
      $honor[$dao->id]['personal_note'] = $dao->pcp_personal_note;
    }
    return $honor;
  }

  /**
   * Get action links.
   *
   * @param int $pcpId
   *   Contains the pcp ID. Defaults to NULL for backwards compatibility.
   *
   * @return array
   *   (reference) of action links
   */
  public static function &pcpLinks($pcpId = NULL) {
    if (!$pcpId) {
      CRM_Core_Error::deprecatedWarning('pcpId should be provided to render links');
    }
    if (!(self::$_pcpLinks)) {
      $deleteExtra = ts('Are you sure you want to delete this Personal Campaign Page?') . '\n' . ts('This action cannot be undone.');

      self::$_pcpLinks['add'] = [
        CRM_Core_Action::ADD => [
          'name' => ts('Create a Personal Campaign Page'),
          'class' => 'no-popup',
          'url' => 'civicrm/contribute/campaign',
          'qs' => 'action=add&reset=1&pageId=%%pageId%%&component=%%pageComponent%%',
          'title' => ts('Configure'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ADD),
        ],
      ];

      self::$_pcpLinks['all'] = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit Your Page'),
          'url' => 'civicrm/pcp/info',
          'qs' => 'action=update&reset=1&id=%%pcpId%%&component=%%pageComponent%%',
          'title' => ts('Configure'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
        CRM_Core_Action::DETACH => [
          'name' => ts('Tell Friends'),
          'url' => 'civicrm/friend',
          'qs' => 'eid=%%pcpId%%&blockId=%%pcpBlock%%&reset=1&pcomponent=pcp&component=%%pageComponent%%',
          'title' => ts('Tell Friends'),
          'weight' => -15,
        ],
        CRM_Core_Action::VIEW => [
          'name' => ts('URL for this Page'),
          'url' => 'civicrm/pcp/info',
          'qs' => 'reset=1&id=%%pcpId%%&component=%%pageComponent%%',
          'title' => ts('URL for this Page'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::VIEW),
        ],
        CRM_Core_Action::BROWSE => [
          'name' => ts('Update Contact Information'),
          'url' => 'civicrm/pcp/info',
          'qs' => 'action=browse&reset=1&id=%%pcpId%%&component=%%pageComponent%%',
          'title' => ts('Update Contact Information'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::BROWSE),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'url' => 'civicrm/pcp',
          'qs' => 'action=enable&reset=1&id=%%pcpId%%&component=%%pageComponent%%',
          'title' => ts('Enable'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ENABLE),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'url' => 'civicrm/pcp',
          'qs' => 'action=disable&reset=1&id=%%pcpId%%&component=%%pageComponent%%',
          'title' => ts('Disable'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DISABLE),
        ],
      ];

      // pcp.user.actions emits a malformed set of $links. But it is locked-in via unit-test, so we'll grandfather
      // the bad one and fire new variants that are well-formed.
      CRM_Utils_Hook::links('pcp.user.actions', 'Pcp', $pcpId, self::$_pcpLinks);
      CRM_Utils_Hook::links('pcp.user.actions.add', 'Pcp', $pcpId, self::$_pcpLinks['add']);
      CRM_Utils_Hook::links('pcp.user.actions.all', 'Pcp', $pcpId, self::$_pcpLinks['all']);
    }
    return self::$_pcpLinks;
  }

  /**
   * @deprecated
   * @param int $id
   */
  public static function deleteById($id) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    return self::deleteRecord(['id' => $id]);
  }

  /**
   * Build the form object.
   *
   * @param CRM_Core_Form $form
   *   Form object.
   */
  public static function buildPCPForm($form) {
    $form->addElement('checkbox', 'pcp_active', ts('Enable Personal Campaign Pages?'), NULL, ['onclick' => "return showHideByValue('pcp_active',true,'pcpFields','block','radio',false);"]);

    $form->addElement('checkbox', 'is_approval_needed', ts('Approval required'));

    $profile = [];
    $isUserRequired = NULL;
    $config = CRM_Core_Config::singleton();
    if ($config->userFramework != 'Standalone') {
      $isUserRequired = 2;
    }
    CRM_Core_DAO::commonRetrieveAll('CRM_Core_DAO_UFGroup', 'is_cms_user', $isUserRequired, $profiles, [
      'title',
      'is_active',
    ]);
    if (!empty($profiles)) {
      foreach ($profiles as $key => $value) {
        if ($value['is_active']) {
          $profile[$key] = $value['title'];
        }
      }
      $form->assign('profile', $profile);
    }

    $form->add('select', 'supporter_profile_id', ts('Supporter Profile'), ['' => ts('- select -')] + $profile, FALSE, ['class' => 'crm-select2']);

    //CRM-15821 - To add new option for PCP "Owner" notification
    $ownerNotifications = CRM_Core_OptionGroup::values('pcp_owner_notify');
    $form->addRadio('owner_notify_id', ts('Owner Email Notification'), $ownerNotifications, NULL, '<br/>', TRUE);

    $form->addElement('checkbox', 'is_tellfriend_enabled', ts("Allow 'Tell a friend' functionality"), NULL, ['onclick' => "return showHideByValue('is_tellfriend_enabled',true,'tflimit','table-row','radio',false);"]);

    $form->add('number',
      'tellfriend_limit',
      ts("'Tell a friend' maximum recipients limit"),
      CRM_Core_DAO::getAttribute('CRM_PCP_DAO_PCPBlock', 'tellfriend_limit')
    );
    $form->addRule('tellfriend_limit', ts('Please enter a valid limit.'), 'integer');

    $form->add('text',
      'link_text',
      ts("'Create Personal Campaign Page' link text"),
      CRM_Core_DAO::getAttribute('CRM_PCP_DAO_PCPBlock', 'link_text')
    );

    $form->add('text', 'notify_email', ts('Notify Email'), CRM_Core_DAO::getAttribute('CRM_PCP_DAO_PCPBlock', 'notify_email'));
  }

  /**
   * This function builds the supporter text for the pcp
   *
   * @param int $pcpID
   *   the personal campaign page ID
   * @param int $contributionPageID
   * @param string $component
   *   one of 'contribute' or 'event'
   *
   * @return string
   */
  public static function getPcpSupporterText($pcpID, $contributionPageID, $component) {
    $pcp_supporter_text = '';
    $text = CRM_PCP_BAO_PCP::getPcpBlockStatus($contributionPageID, $component);
    $pcpSupporter = CRM_PCP_BAO_PCP::displayName($pcpID);
    switch ($component) {
      case 'event':
        $pcp_supporter_text = ts('This event registration is being made thanks to the efforts of <strong>%1</strong>, who supports our campaign. ', [1 => $pcpSupporter]);
        if (!empty($text)) {
          $pcp_supporter_text .= ts('You can support it as well - once you complete the registration, you will be able to create your own Personal Campaign Page!');
        }
        break;

      case 'contribute':
        $pcp_supporter_text = ts('This contribution is being made thanks to the efforts of <strong>%1</strong>, who supports our campaign. ', [1 => $pcpSupporter]);
        if (!empty($text)) {
          $pcp_supporter_text .= ts('You can support it as well - once you complete the donation, you will be able to create your own Personal Campaign Page!');
        }
        break;
    }
    return $pcp_supporter_text;
  }

  /**
   * Add PCP form elements to a form.
   *
   * @param int $pcpId
   * @param CRM_Core_Form $page
   * @param array $elements
   *
   * @throws \CRM_Core_Exception
   */
  public static function buildPcp($pcpId, &$page, &$elements = NULL) {
    $prms = ['id' => $pcpId];
    CRM_Core_DAO::commonRetrieve('CRM_PCP_DAO_PCP', $prms, $pcpInfo);

    if (CRM_PCP_BAO_PCP::displayName($pcpId)) {
      $pcp_supporter_text = self::getPcpSupporterText($pcpId, $pcpInfo['page_id'], $pcpInfo['page_type']);
      $page->assign('pcpSupporterText', $pcp_supporter_text);
    }
    $page->assign('pcp', TRUE);

    // build honor roll fields for registration form if supporter has honor roll enabled for their PCP
    if ($pcpInfo['is_honor_roll']) {
      $page->assign('is_honor_roll', TRUE);
      $page->add('checkbox', 'pcp_display_in_roll', ts('Show my support in the public honor roll'),
        ['onclick' => "showHideByValue('pcp_display_in_roll','','nameID|nickID|personalNoteID','block','radio',false); pcpAnonymous( );"],
        FALSE
      );
      $extraOption = ['onclick' => "return pcpAnonymous( );"];
      $page->addRadio('pcp_is_anonymous', '', [ts('Include my name and message'), ts('List my support anonymously')], [], '&nbsp;&nbsp;&nbsp;', FALSE, [$extraOption, $extraOption]);
      $page->_defaults['pcp_is_anonymous'] = 0;

      $page->add('text', 'pcp_roll_nickname', ts('Name'), ['maxlength' => 30]);
      $page->addField('pcp_personal_note', ['entity' => 'ContributionSoft', 'context' => 'create', 'style' => 'height: 3em; width: 40em;']);
    }
    else {
      $page->assign('is_honor_roll', FALSE);
    }
  }

  /**
   * Process a PCP contribution.
   *
   * @param int $pcpId
   * @param string $component
   * @param string $entity
   *
   * @return array
   */
  public static function handlePcp($pcpId, $component, $entity) {

    self::getPcpEntityTable($component);

    if (!$pcpId) {
      return FALSE;
    }

    $pcpStatus = CRM_PCP_BAO_PCP::buildOptions('status_id');
    $approvedId = array_search('Approved', $pcpStatus);

    $params = ['id' => $pcpId];
    CRM_Core_DAO::commonRetrieve('CRM_PCP_DAO_PCP', $params, $pcpInfo);

    $params = ['id' => $pcpInfo['pcp_block_id']];
    CRM_Core_DAO::commonRetrieve('CRM_PCP_DAO_PCPBlock', $params, $pcpBlock);

    $params = ['id' => $pcpInfo['page_id']];
    $now = time();

    if ($component == 'event') {
      // figure out where to redirect if an exception occurs below based on target entity
      $urlBase = 'civicrm/event/register';

      // ignore startDate for events - PCP's can be active long before event start date
      $startDate = 0;
      $endDate = CRM_Utils_Date::unixTime($entity['end_date'] ?? '');
    }
    elseif ($component == 'contribute') {
      $urlBase = 'civicrm/contribute/transact';
      //start and end date of the contribution page
      $startDate = CRM_Utils_Date::unixTime($entity['start_date'] ?? '');
      $endDate = CRM_Utils_Date::unixTime($entity['end_date'] ?? '');
    }

    // define redirect url back to contrib page or event if needed
    $url = CRM_Utils_System::url($urlBase, "reset=1&id={$pcpBlock['entity_id']}", FALSE, NULL, FALSE, TRUE);
    $currentPCPStatus = CRM_Core_PseudoConstant::getName('CRM_PCP_BAO_PCP', 'status_id', $pcpInfo['status_id']);

    if ($pcpBlock['target_entity_id'] != $entity['id']) {
      $statusMessage = ts('This page is not related to the Personal Campaign Page you have just visited. However you can still make a contribution here.');
      CRM_Core_Error::statusBounce($statusMessage, $url);
    }
    elseif ($currentPCPStatus !== 'Approved') {
      $statusMessage = ts('The Personal Campaign Page you have just visited is currently %1. However you can still support the campaign here.', [1 => $pcpStatus[$pcpInfo['status_id']]]);
      CRM_Core_Error::statusBounce($statusMessage, $url);
    }
    elseif (empty($pcpBlock['is_active'])) {
      $statusMessage = ts('Personal Campaign Pages are currently not enabled for this contribution page. However you can still support the campaign here.');
      CRM_Core_Error::statusBounce($statusMessage, $url);
    }
    elseif (empty($pcpInfo['is_active'])) {
      $statusMessage = ts('The Personal Campaign Page you have just visited is currently inactive. However you can still support the campaign here.');
      CRM_Core_Error::statusBounce($statusMessage, $url);
    }
    // Check if we're in range for contribution page start and end dates. for events, check if after event end date
    elseif (($startDate && $startDate > $now) || ($endDate && $endDate < $now)) {
      $customStartDate = CRM_Utils_Date::customFormat($entity['start_date'] ?? '');
      $customEndDate = CRM_Utils_Date::customFormat($entity['end_date'] ?? '');
      if ($startDate && $endDate) {
        $statusMessage = ts('The Personal Campaign Page you have just visited is only active from %1 to %2. However you can still support the campaign here.',
          [1 => $customStartDate, 2 => $customEndDate]
        );
        CRM_Core_Error::statusBounce($statusMessage, $url);
      }
      elseif ($startDate) {
        $statusMessage = ts('The Personal Campaign Page you have just visited will be active beginning on %1. However you can still support the campaign here.', [1 => $customStartDate]);
        CRM_Core_Error::statusBounce($statusMessage, $url);
      }
      elseif ($endDate) {
        if ($component == 'event') {
          // Target_entity is an event and the event is over, redirect to event info instead of event registration page.
          $url = CRM_Utils_System::url('civicrm/event/info',
            "reset=1&id={$pcpBlock['entity_id']}",
            FALSE, NULL, FALSE, TRUE
          );
          $statusMessage = ts('The event linked to the Personal Campaign Page you have just visited is over (as of %1).', [1 => $customEndDate]);
          CRM_Core_Error::statusBounce($statusMessage, $url);
        }
        else {
          $statusMessage = ts('The Personal Campaign Page you have just visited is no longer active (as of %1). However you can still support the campaign here.', [1 => $customEndDate]);
          CRM_Core_Error::statusBounce($statusMessage, $url);
        }
      }
    }

    return [
      'pcpId' => $pcpId,
      'pcpBlock' => $pcpBlock,
      'pcpInfo' => $pcpInfo,
    ];
  }

  /**
   * Approve / Reject the campaign page.
   *
   * @param int $id
   *   Campaign page id.
   *
   * @param bool $is_active
   */
  public static function setIsActive($id, $is_active) {
    switch ($is_active) {
      case 0:
        $is_active = 3;
        break;

      case 1:
        $is_active = 2;
        break;
    }

    CRM_Core_DAO::setFieldValue('CRM_PCP_DAO_PCP', $id, 'status_id', $is_active);

    $pcpTitle = CRM_Core_DAO::getFieldValue('CRM_PCP_DAO_PCP', $id, 'title');
    $pcpPageType = CRM_Core_DAO::getFieldValue('CRM_PCP_DAO_PCP', $id, 'page_type');

    $pcpStatus = CRM_Core_OptionGroup::values("pcp_status");
    $pcpStatus = $pcpStatus[$is_active];

    CRM_Core_Session::setStatus(ts("%1 status has been updated to %2.", [
      1 => $pcpTitle,
      2 => $pcpStatus,
    ]), ts('Status Updated'), 'success');

    // send status change mail
    $result = self::sendStatusUpdate($id, $is_active, FALSE, $pcpPageType);

    if ($result) {
      CRM_Core_Session::setStatus(ts("A notification email has been sent to the supporter."), ts('Email Sent'), 'success');
    }
  }

  /**
   * Send notification email to supporter.
   *
   * 1. when their PCP status is changed by site admin.
   * 2. when supporter initially creates a Personal Campaign Page ($isInitial set to true).
   *
   * @param int $pcpId
   *   Campaign page id.
   * @param int $newStatus
   *   Pcp status id.
   * @param bool|int $isInitial is it the first time, campaign page has been created by the user
   *
   * @param string $component
   *
   * @throws Exception
   * @return bool
   */
  public static function sendStatusUpdate($pcpId, $newStatus, $isInitial = FALSE, $component = 'contribute') {
    $pcpStatusName = CRM_Core_OptionGroup::values("pcp_status", FALSE, FALSE, FALSE, NULL, 'name');
    $pcpStatus = CRM_Core_OptionGroup::values("pcp_status");
    $config = CRM_Core_Config::singleton();

    if (!isset($pcpStatus[$newStatus])) {
      return FALSE;
    }

    //set loginUrl
    $loginURL = $config->userSystem->getLoginURL();

    // used in subject templates
    $contribPageTitle = self::getPcpPageTitle($pcpId, $component);

    $tplParams = [
      'loginUrl' => $loginURL,
      'contribPageTitle' => $contribPageTitle,
      'pcpId' => $pcpId,
    ];

    //get the default domain email address.
    [$domainEmailName, $domainEmailAddress] = CRM_Core_BAO_Domain::getNameAndEmail();

    if (!$domainEmailAddress || $domainEmailAddress == 'info@EXAMPLE.ORG') {
      $fixUrl = CRM_Utils_System::url('civicrm/admin/options/site_email_address');
      throw new CRM_Core_Exception(ts('The site administrator needs to enter a valid "Site From Email Address" in <a href="%1">Administer CiviCRM &raquo; Communications &raquo; Site Email Addresses</a>. The email address used may need to be a valid mail account with your email service provider.', [1 => $fixUrl]));
    }

    $receiptFrom = '"' . $domainEmailName . '" <' . $domainEmailAddress . '>';

    // get recipient (supporter) name and email
    $params = ['id' => $pcpId];
    CRM_Core_DAO::commonRetrieve('CRM_PCP_DAO_PCP', $params, $pcpInfo);
    [$name, $address] = CRM_Contact_BAO_Contact_Location::getEmailDetails($pcpInfo['contact_id']);

    // get pcp block info
    [$blockId, $eid] = self::getPcpBlockEntityId($pcpId, $component);
    $params = ['id' => $blockId];
    CRM_Core_DAO::commonRetrieve('CRM_PCP_DAO_PCPBlock', $params, $pcpBlockInfo);

    // assign urls required in email template
    if ($pcpStatusName[$newStatus] == 'Approved') {
      $tplParams['isTellFriendEnabled'] = $pcpBlockInfo['is_tellfriend_enabled'];
      if ($pcpBlockInfo['is_tellfriend_enabled']) {
        $pcpTellFriendURL = CRM_Utils_System::url('civicrm/friend',
          "reset=1&eid=$pcpId&blockId=$blockId&pcomponent=pcp",
          TRUE, NULL, FALSE, TRUE
        );
        $tplParams['pcpTellFriendURL'] = $pcpTellFriendURL;
      }
    }
    $pcpInfoURL = CRM_Utils_System::url('civicrm/pcp/info',
      "reset=1&id=$pcpId",
      TRUE, NULL, FALSE, TRUE
    );
    $tplParams['pcpInfoURL'] = $pcpInfoURL;
    $tplParams['contribPageTitle'] = $contribPageTitle;
    $emails = $pcpBlockInfo['notify_email'] ?? NULL;
    if ($emails) {
      $emailArray = explode(',', $emails);
      $tplParams['pcpNotifyEmailAddress'] = $emailArray[0];
    }
    // get appropriate message based on status
    $tplParams['pcpStatus'] = $pcpStatus[$newStatus];

    $tplName = $isInitial ? 'pcp_supporter_notify' : 'pcp_status_change';

    [$sent, $subject, $message, $html] = CRM_Core_BAO_MessageTemplate::sendTemplate(
      [
        'groupName' => 'msg_tpl_workflow_contribution',
        'workflow' => $tplName,
        'contactId' => $pcpInfo['contact_id'],
        'tplParams' => $tplParams,
        'from' => $receiptFrom,
        'toName' => $name,
        'toEmail' => $address,
      ]
    );
    return $sent;
  }

  /**
   * Enable / Disable the campaign page.
   *
   * @param int $id
   *   Campaign page id.
   *
   * @param bool $is_active
   *
   * @return bool
   *   true if we found and updated the object, else false
   */
  public static function setDisable($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_PCP_DAO_PCP', $id, 'is_active', $is_active);
  }

  /**
   * Get pcp block is active.
   *
   * @param int $pcpId
   * @param string $component
   *
   * @return int
   */
  public static function getStatus($pcpId, $component) {
    $query = "
         SELECT pb.is_active
         FROM civicrm_pcp pcp
         LEFT JOIN civicrm_pcp_block pb ON ( pcp.page_id = pb.entity_id )
         WHERE pcp.id = %1
         AND pb.entity_table = %2";

    $entity_table = self::getPcpEntityTable($component);

    $params = [1 => [$pcpId, 'Integer'], 2 => [$entity_table, 'String']];
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * Get pcp block is enabled for component page.
   *
   * @param int $pageId
   * @param string $component
   *
   * @return string
   */
  public static function getPcpBlockStatus($pageId, $component) {
    $query = "
     SELECT pb.link_text as linkText
     FROM civicrm_pcp_block pb
     WHERE pb.is_active = 1 AND
     pb.entity_id = %1 AND
     pb.entity_table = %2";

    $entity_table = self::getPcpEntityTable($component);

    $params = [1 => [$pageId, 'Integer'], 2 => [$entity_table, 'String']];
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * Find out if the PCP block is in use by one or more PCP page.
   *
   * @param int $id
   *   Pcp block id.
   *
   * @return Bool
   */
  public static function getPcpBlockInUse($id) {
    $query = "
     SELECT count(*)
     FROM civicrm_pcp pcp
     WHERE pcp.pcp_block_id = %1";

    $params = [1 => [$id, 'Integer']];
    $result = CRM_Core_DAO::singleValueQuery($query, $params);
    return $result > 0;
  }

  /**
   * Get email is enabled for supporter's profile
   *
   * @param int $profileId
   *   Supporter's profile id.
   *
   * @return bool
   */
  public static function checkEmailProfile($profileId) {
    $query = "
SELECT field_name
FROM civicrm_uf_field
WHERE field_name like 'email%' And is_active = 1 And uf_group_id = %1";

    $params = [1 => [$profileId, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if (!$dao->fetch()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Obtain the title of page associated with a pcp.
   *
   * @param int $pcpId
   * @param $component
   *
   * @return string
   */
  public static function getPcpPageTitle($pcpId, $component) {
    if ($component == 'contribute') {
      $query = "
  SELECT cp.title
  FROM civicrm_pcp pcp
  LEFT JOIN civicrm_contribution_page as cp ON ( cp.id =  pcp.page_id )
  WHERE pcp.id = %1";
    }
    elseif ($component == 'event') {
      $query = "
  SELECT ce.title
  FROM civicrm_pcp pcp
  LEFT JOIN civicrm_event as ce ON ( ce.id =  pcp.page_id )
  WHERE pcp.id = %1";
    }

    $params = [1 => [$pcpId, 'Integer']];
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * Get pcp block & entity id given pcp id
   *
   * @param int $pcpId
   * @param string $component
   *
   * @return int[]
   */
  public static function getPcpBlockEntityId($pcpId, $component) {
    $entity_table = self::getPcpEntityTable($component);

    $query = "
SELECT pb.id as pcpBlockId, pb.entity_id
FROM civicrm_pcp pcp
LEFT JOIN civicrm_pcp_block pb ON ( pb.entity_id = pcp.page_id AND pb.entity_table = %2 )
WHERE pcp.id = %1";

    $params = [1 => [$pcpId, 'Integer'], 2 => [$entity_table, 'String']];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch()) {
      return [$dao->pcpBlockId, $dao->entity_id];
    }

    return [];
  }

  /**
   * Get pcp entity table given a component.
   *
   * @param string $component
   *
   * @return string
   */
  public static function getPcpEntityTable($component) {
    $entity_table_map = [
      'event' => 'civicrm_event',
      'civicrm_event' => 'civicrm_event',
      'contribute' => 'civicrm_contribution_page',
      'civicrm_contribution_page' => 'civicrm_contribution_page',
    ];
    return $entity_table_map[$component] ?? FALSE;
  }

  /**
   * Get supporter profile id.
   *
   * @param int $component_id
   * @param string $component
   *
   * @return int
   */
  public static function getSupporterProfileId($component_id, $component = 'contribute') {
    $entity_table = self::getPcpEntityTable($component);

    $query = "
SELECT pcp.supporter_profile_id
FROM civicrm_pcp_block pcp
INNER JOIN civicrm_uf_group ufgroup
      ON pcp.supporter_profile_id = ufgroup.id
      WHERE pcp.entity_id = %1
      AND pcp.entity_table = %2
      AND ufgroup.is_active = 1";

    $params = [1 => [$component_id, 'Integer'], 2 => [$entity_table, 'String']];
    if (!$supporterProfileId = CRM_Core_DAO::singleValueQuery($query, $params)) {
      throw new CRM_Core_Exception(ts('Supporter profile is not set for this Personal Campaign Page or the profile is disabled. Please contact the site administrator if you need assistance.'));
    }
    else {
      return $supporterProfileId;
    }
  }

  /**
   * Get owner notification id.
   *
   * @param int $component_id
   * @param string $component
   *
   * @return int
   */
  public static function getOwnerNotificationId($component_id, $component = 'contribute') {
    $entity_table = self::getPcpEntityTable($component);
    $query = "
         SELECT pb.owner_notify_id
         FROM civicrm_pcp_block pb
         WHERE pb.entity_id = %1 AND pb.entity_table = %2";
    $params = [1 => [$component_id, 'Integer'], 2 => [$entity_table, 'String']];
    if (!$ownerNotificationId = CRM_Core_DAO::singleValueQuery($query, $params)) {
      throw new CRM_Core_Exception(ts('Owner Notification is not set for this Personal Campaign Page. Please contact the site administrator if you need assistance.'));
    }
    else {
      return $ownerNotificationId;
    }
  }

  /**
   * Get the title of the pcp.
   *
   * @param string $component
   * @param int $id
   *
   * @return bool|string|null
   */
  protected static function getPcpTitle(string $component, int $id) {
    if ($component === 'contribute') {
      return CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Contribution', 'contribution_page_id', $id);
    }
    return CRM_Core_PseudoConstant::getLabel('CRM_Event_BAO_Participant', 'event_id', $id);
  }

  /**
   * Possible values for the page_type field, used for dynamic foreign key lookups
   *
   * This is a non-standard dfk. Normally the columns would be named "entity_table" & "entity_id",
   * and normally the `entity_table` field would contain a table name like "civicrm_contribution"
   * instead of an arbitrary string like 'contribute'.
   *
   * @return array
   */
  public static function pageTypeOptions(): array {
    return [
      [
        'id' => 'contribute',
        'name' => 'ContributionPage',
        'label' => ts('Contributions'),
      ],
      [
        'id' => 'event',
        'name' => 'Event',
        'label' => ts('Events'),
      ],
    ];
  }

}
