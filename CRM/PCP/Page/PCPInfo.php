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
 * PCP Info Page - Summary about the PCP
 */
class CRM_PCP_Page_PCPInfo extends CRM_Core_Page {
  public $_component;

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   *
   * @return void
   */
  public function run() {
    $session = CRM_Core_Session::singleton();
    $config = CRM_Core_Config::singleton();
    $permissionCheck = FALSE;
    $statusMessage = '';

    $permissionCheck = CRM_Core_Permission::check('administer CiviCRM');
    //get the pcp id.
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);

    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE);

    $prms = ['id' => $this->_id];

    CRM_Core_DAO::commonRetrieve('CRM_PCP_DAO_PCP', $prms, $pcpInfo);
    $this->_component = $pcpInfo['page_type'];

    if (empty($pcpInfo)) {
      $statusMessage = ts('The personal campaign page you requested is currently unavailable.');
      CRM_Core_Error::statusBounce($statusMessage,
        $config->userFrameworkBaseURL
      );
    }

    CRM_Utils_System::setTitle($pcpInfo['title']);
    $this->assign('pcp', $pcpInfo);
    $this->assign('currency', $pcpInfo['currency']);

    $pcpStatus = CRM_Core_OptionGroup::values("pcp_status");
    $approvedId = CRM_Core_PseudoConstant::getKey('CRM_PCP_BAO_PCP', 'status_id', 'Approved');

    $pcpBlock = new CRM_PCP_DAO_PCPBlock();
    $pcpBlock->entity_table = CRM_PCP_BAO_PCP::getPcpEntityTable($pcpInfo['page_type']);
    $pcpBlock->entity_id = $pcpInfo['page_id'];
    $pcpBlock->find(TRUE);

    // Redirect back to source page in case of error.
    if ($pcpInfo['page_type'] == 'contribute') {
      $urlBase = 'civicrm/contribute/transact';
    }
    elseif ($pcpInfo['page_type'] == 'event') {
      $urlBase = 'civicrm/event/register';
    }

    // check if PCP is created by anonymous user
    $anonymousPCP = CRM_Utils_Request::retrieve('ap', 'Boolean', $this);
    if ($anonymousPCP) {
      $loginURL = $config->userSystem->getLoginURL();
      $anonMessage = ts('Once you\'ve received your new account welcome email, you can <a href=%1>click here</a> to login and promote your campaign page.', [1 => $loginURL]);
      CRM_Core_Session::setStatus($anonMessage, ts('Success'), 'success');
      CRM_Utils_System::redirect(CRM_Utils_System::url($urlBase,
          "reset=1&id=" . $pcpInfo['page_id'],
          FALSE, NULL, FALSE, TRUE
      ));
    }
    else {
      $statusMessage = ts('The personal campaign page you requested is currently unavailable. However you can still support the campaign by making a contribution here.');
    }

    if ($pcpInfo['status_id'] != $approvedId || !$pcpInfo['is_active']) {
      if ($pcpInfo['contact_id'] != $session->get('userID') && !$permissionCheck) {
        CRM_Core_Error::statusBounce($statusMessage, CRM_Utils_System::url($urlBase,
          "reset=1&id=" . $pcpInfo['page_id'],
          FALSE, NULL, FALSE, TRUE
        ));
      }
    }
    else {
      $getStatus = CRM_PCP_BAO_PCP::getStatus($this->_id, $this->_component);
      if (!$getStatus) {
        // PCP not enabled for this contribution page. Forward everyone to source page
        CRM_Core_Error::statusBounce($statusMessage, CRM_Utils_System::url($urlBase,
          "reset=1&id=" . $pcpInfo['page_id'],
          FALSE, NULL, FALSE, TRUE
        ));
      }
    }

    $default = [];

    if ($pcpBlock->target_entity_type == 'contribute') {
      $urlBase = 'civicrm/contribute/transact';
    }
    elseif ($pcpBlock->target_entity_type == 'event') {
      $urlBase = 'civicrm/event/register';
    }

    if ($pcpBlock->entity_table == 'civicrm_event') {
      $page_class = 'CRM_Event_DAO_Event';
      $this->assign('pageName', CRM_Event_PseudoConstant::event($pcpInfo['page_id']));
      CRM_Core_DAO::commonRetrieveAll($page_class, 'id',
        $pcpInfo['page_id'], $default, [
          'start_date',
          'end_date',
          'registration_start_date',
          'registration_end_date',
        ]
      );
    }
    elseif ($pcpBlock->entity_table == 'civicrm_contribution_page') {
      $page_class = 'CRM_Contribute_DAO_ContributionPage';
      $this->assign('pageName', CRM_Contribute_PseudoConstant::contributionPage($pcpInfo['page_id'], TRUE));
      CRM_Core_DAO::commonRetrieveAll($page_class, 'id',
        $pcpInfo['page_id'], $default, ['start_date', 'end_date']
      );
    }

    $pageInfo = $default[$pcpInfo['page_id']];

    if ($pcpInfo['contact_id'] == $session->get('userID')) {
      $owner = $pageInfo;
      $owner['status'] = $pcpStatus[$pcpInfo['status_id']] ?? NULL;

      $this->assign('owner', $owner);

      $link = CRM_PCP_BAO_PCP::pcpLinks($pcpInfo['id']);

      $hints = [
        CRM_Core_Action::UPDATE => ts('Change the content and appearance of your page'),
        CRM_Core_Action::DETACH => ts('Send emails inviting your friends to support your campaign!'),
        CRM_Core_Action::VIEW => ts('Copy this link to share directly with your network!'),
        CRM_Core_Action::BROWSE => ts('Update your personal contact information'),
        CRM_Core_Action::DISABLE => ts('De-activate the page (you can re-activate it later)'),
        CRM_Core_Action::ENABLE => ts('Activate the page (you can de-activate it later)'),
        CRM_Core_Action::DELETE => ts('Remove the page (this cannot be undone!)'),
      ];

      $replace = [
        'id' => $this->_id,
        'block' => $pcpBlock->id,
        'pageComponent' => $this->_component,
      ];

      if (!$pcpBlock->is_tellfriend_enabled || ($pcpInfo['status_id'] ?? NULL) != $approvedId) {
        unset($link['all'][CRM_Core_Action::DETACH]);
      }

      switch ($pcpInfo['is_active']) {
        case 1:
          unset($link['all'][CRM_Core_Action::ENABLE]);
          break;

        case 0:
          unset($link['all'][CRM_Core_Action::DISABLE]);
          break;
      }

      $this->assign('links', $link['all']);
      $this->assign('hints', $hints);
      $this->assign('replace', $replace);
    }

    $honor = CRM_PCP_BAO_PCP::honorRoll($this->_id);

    $entityFile = CRM_Core_BAO_File::getEntityFile('civicrm_pcp', $this->_id);
    if (!empty($entityFile)) {
      $fileInfo = reset($entityFile);
      $fileId = $fileInfo['fileID'];
      $altText = htmlspecialchars($fileInfo['description'] ?? '');
      $fileHash = CRM_Core_BAO_File::generateFileHash(NULL, $fileId);
      $image = '<img src="' . CRM_Utils_System::url('civicrm/file',
          "reset=1&id=$fileId&fcs={$fileHash}"
        ) . '" alt="' . $altText . '"/>';
      $this->assign('image', $image);
    }

    $totalAmount = CRM_PCP_BAO_PCP::thermoMeter($this->_id);
    $achieved = round($totalAmount / $pcpInfo['goal_amount'] * 100, 2);

    if ($pcpBlock->is_active == 1) {
      $linkTextUrl = CRM_Utils_System::url('civicrm/contribute/campaign',
        "action=add&reset=1&pageId={$pcpInfo['page_id']}&component={$pcpInfo['page_type']}",
        TRUE, NULL, TRUE,
        TRUE
      );
    }
    $this->assign('linkTextUrl', $linkTextUrl ?? NULL);
    $this->assign('linkText', $pcpBlock->link_text ?? NULL);

    $this->assign('honor', $honor);
    $this->assign('total', $totalAmount ?: '0.0');
    $this->assign('achieved', $achieved <= 100 ? $achieved : 100);

    if ($achieved <= 100) {
      $this->assign('remaining', 100 - $achieved);
    }
    // make sure that we are between contribution page start and end dates OR registration start date and end dates if they are set
    if ($pcpBlock->entity_table == 'civicrm_event') {
      $startDate = CRM_Utils_Date::unixTime($pageInfo['registration_start_date'] ?? '');
      $endDate = CRM_Utils_Date::unixTime($pageInfo['registration_end_date'] ?? '');
    }
    else {
      $startDate = CRM_Utils_Date::unixTime($pageInfo['start_date'] ?? '');
      $endDate = CRM_Utils_Date::unixTime($pageInfo['end_date'] ?? '');
    }

    $now = time();
    $validDate = TRUE;
    if ($startDate && $startDate >= $now) {
      $validDate = FALSE;
    }
    if ($endDate && $endDate < $now) {
      $validDate = FALSE;
    }

    $this->assign('validDate', $validDate);

    // form parent page url
    if ($action == CRM_Core_Action::PREVIEW) {
      $parentUrl = CRM_Utils_System::url($urlBase,
        "id={$pcpInfo['page_id']}&reset=1&action=preview",
        TRUE, NULL, TRUE,
        TRUE
      );
    }
    else {
      $parentUrl = CRM_Utils_System::url($urlBase,
        "id={$pcpInfo['page_id']}&reset=1",
        TRUE, NULL, TRUE,
        TRUE
      );
    }

    $this->assign('parentURL', $parentUrl);

    if ($validDate) {

      $contributionText = ts('Contribute Now');
      if (!empty($pcpInfo['donate_link_text'])) {
        $contributionText = $pcpInfo['donate_link_text'];
      }

      $this->assign('contributionText', $contributionText);

      // we always generate urls for the front end in joomla
      if ($action == CRM_Core_Action::PREVIEW) {
        $url = CRM_Utils_System::url($urlBase,
          "id=" . $pcpBlock->target_entity_id . "&pcpId={$this->_id}&reset=1&action=preview",
          TRUE, NULL, TRUE,
          TRUE
        );
      }
      else {
        $url = CRM_Utils_System::url($urlBase,
          "id=" . $pcpBlock->target_entity_id . "&pcpId={$this->_id}&reset=1",
          TRUE, NULL, TRUE,
          TRUE
        );
      }
      $this->assign('contributeURL', $url);
    }

    // we do not want to display recently viewed items, so turn off
    $this->assign('displayRecent', FALSE);

    $single = $permission = FALSE;
    switch ($action) {
      case CRM_Core_Action::BROWSE:
        $subForm = 'PCPAccount';
        $form = "CRM_PCP_Form_$subForm";
        $single = TRUE;
        break;

      case CRM_Core_Action::UPDATE:
        $subForm = 'Campaign';
        $form = "CRM_PCP_Form_$subForm";
        $single = TRUE;
        break;
    }

    $userID = $session->get('userID');
    //make sure the user has "administer CiviCRM" permission
    //OR has created the PCP
    if (CRM_Core_Permission::check('administer CiviCRM') ||
      ($userID && (CRM_Core_DAO::getFieldValue('CRM_PCP_DAO_PCP', $this->_id, 'contact_id') == $userID))
    ) {
      $permission = TRUE;
    }
    if ($single && $permission) {
      $controller = new CRM_Core_Controller_Simple($form, $subForm, $action);
      $controller->set('id', $this->_id);
      $controller->set('single', TRUE);
      $controller->process();
      return $controller->run();
    }
    $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'reset=1&id=' . $this->_id));
    parent::run();
  }

  /**
   * @return string
   */
  public function getTemplateFileName() {
    if ($this->_id) {
      $templateFile = "CRM/PCP/Page/{$this->_id}/PCPInfo.tpl";
      $template = &CRM_Core_Page::getTemplate();
      if ($template->template_exists($templateFile)) {
        return $templateFile;
      }
    }
    return parent::getTemplateFileName();
  }

}
