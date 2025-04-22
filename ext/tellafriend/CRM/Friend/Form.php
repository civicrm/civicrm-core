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
 * This class generates form components for Tell A Friend Form For End User
 *
 */
class CRM_Friend_Form extends CRM_Core_Form {

  /**
   * Constants for number of friend contacts.
   */
  const NUM_OPTION = 3;

  /**
   * The id of the entity that we are processing.
   *
   * @var int
   */
  protected $_entityId;

  /**
   * Tell a friend id in db.
   *
   * @var int
   */
  public $_friendId;

  /**
   * The table name of the entity that we are processing.
   *
   * @var string
   */
  protected $_entityTable;

  protected $_campaignId;

  /**
   * The contact ID.
   *
   * @var int
   */
  protected $_contactID;

  public function preProcess() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);
    $this->_entityId = CRM_Utils_Request::retrieve('eid', 'Positive', $this, TRUE);

    $pcomponent = CRM_Utils_Request::retrieve('pcomponent', 'String', $this, TRUE);

    if (in_array($pcomponent, ['contribute', 'event'])) {
      $values = [];
      $params = ['id' => $this->_entityId];
      CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_ContributionPage',
        $params, $values, ['title', 'campaign_id', 'is_share']
      );
      $this->_title = $values['title'] ?? NULL;
      $this->_campaignId = $values['campaign_id'] ?? NULL;
      $this->_entityTable = 'civicrm_contribution_page';
      if ($pcomponent === 'event') {
        $this->_entityTable = 'civicrm_event';
        $isShare = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->_entityId, 'is_share');
        $this->_title = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->_entityId, 'title');
      }
      else {
        $isShare = $values['is_share'] ?? NULL;
      }
      // Tell Form.tpl whether to include SocialNetwork.tpl for social media sharing
      $this->assign('isShare', $isShare);
    }
    elseif ($pcomponent === 'pcp') {
      $this->_pcpBlockId = CRM_Utils_Request::retrieve('blockId', 'Positive', $this, TRUE);

      $values = [];
      $params = ['id' => $this->_pcpBlockId];
      CRM_Core_DAO::commonRetrieve('CRM_PCP_DAO_PCPBlock',
        $params, $values, ['is_tellfriend_enabled', 'tellfriend_limit']
      );

      if (empty($values['is_tellfriend_enabled'])) {
        CRM_Core_Error::statusBounce(ts('Tell Friend is disable for this Personal Campaign Page'));
      }

      $this->_mailLimit = $values['tellfriend_limit'];
      $this->_entityTable = 'civicrm_pcp';

      $sql = '
   SELECT  pcp.title,
           contrib.campaign_id
  FROM  civicrm_pcp pcp
    INNER JOIN  civicrm_contribution_page contrib ON ( pcp.page_id = contrib.id AND pcp.page_type = "contribute" )
  WHERE  pcp.id = %1';
      $pcp = CRM_Core_DAO::executeQuery($sql, [1 => [$this->_entityId, 'Positive']]);
      while ($pcp->fetch()) {
        $this->_title = $pcp->title;
        $this->_campaignId = $pcp->campaign_id;
      }

      $this->assign('pcpTitle', $this->_title);
    }
    else {
      CRM_Core_Error::statusBounce(ts('page argument missing or invalid'));
    }
    $this->assign('context', $pcomponent);

    $this->_contactID = CRM_Core_Session::getLoggedInContactID();
    if (!$this->_contactID) {
      $this->_contactID = CRM_Core_Session::singleton()->get('transaction.userID');
    }

    if (!$this->_contactID) {
      CRM_Core_Error::statusBounce(ts('To prevent spam, this feature requires either a valid transaction or a user account.'));
    }

    // we do not want to display recently viewed items, so turn off
    $this->assign('displayRecent', FALSE);
  }

  /**
   * Set default values for the form.
   *
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];

    $defaults['entity_id'] = $this->_entityId;
    $defaults['entity_table'] = $this->_entityTable;

    CRM_Friend_BAO_Friend::getValues($defaults);
    $this->setTitle($defaults['title'] ?? NULL);

    $this->assign('title', $defaults['title'] ?? NULL);
    $this->assign('intro', $defaults['intro'] ?? NULL);
    $this->assign('message', $defaults['suggested_message'] ?? NULL);
    $this->assign('entityID', $this->_entityId);

    list($fromName, $fromEmail) = CRM_Contact_BAO_Contact::getContactDetails($this->_contactID);

    $defaults['from_name'] = $fromName;
    $defaults['from_email'] = $fromEmail;

    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->applyFilter('__ALL__', 'trim');
    // Details of User
    $name = &$this->add('text',
      'from_name',
      ts('From'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'first_name')
    );
    $name->freeze();

    $email = &$this->add('text',
      'from_email',
      ts('Your Email'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_Email', 'email'),
      TRUE
    );
    $email->freeze();

    $this->add('wysiwyg', 'suggested_message', ts('Your Message'), CRM_Core_DAO::getAttribute('CRM_Friend_DAO_Friend', 'suggested_message'));
    $friend = [];
    $mailLimit = self::NUM_OPTION;
    if ($this->_entityTable === 'civicrm_pcp') {
      $mailLimit = $this->_mailLimit;
    }
    $this->assign('mailLimit', $mailLimit + 1);
    for ($i = 1; $i <= $mailLimit; $i++) {
      $this->add('text', "friend[$i][first_name]", ts("Friend's First Name"));
      $this->add('text', "friend[$i][last_name]", ts("Friend's Last Name"));
      $this->add('text', "friend[$i][email]", ts("Friend's Email"));
      $this->addRule("friend[$i][email]", ts('The format of this email address is not valid.'), 'email');
    }

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Send Your Message'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);

    $this->addFormRule(['CRM_Friend_Form', 'formRule']);
  }

  /**
   * Validation.
   *
   * @param array $fields
   *
   * @return bool|array
   *   mixed true or array of errors
   */
  public static function formRule($fields) {

    $errors = [];

    $valid = FALSE;
    foreach ($fields['friend'] as $key => $val) {
      if (trim($val['first_name']) || trim($val['last_name']) || trim($val['email'])) {
        $valid = TRUE;

        if (!trim($val['first_name'])) {
          $errors["friend[{$key}][first_name]"] = ts('Please enter your friend\'s first name.');
        }

        if (!trim($val['last_name'])) {
          $errors["friend[{$key}][last_name]"] = ts('Please enter your friend\'s last name.');
        }

        if (!trim($val['email'])) {
          $errors["friend[{$key}][email]"] = ts('Please enter your friend\'s email address.');
        }
      }
    }

    if (!$valid) {
      $errors['friend[1][first_name]'] = ts("Please enter at least one friend's information, or click Cancel if you don't want to send emails at this time.");
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form submission.
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    // get the submitted form values.
    $formValues = $this->controller->exportValues($this->_name);

    $formValues['entity_id'] = $this->_entityId;
    $formValues['entity_table'] = $this->_entityTable;
    $formValues['source_contact_id'] = $this->_contactID;
    $formValues['is_test'] = $this->_action ? 1 : 0;
    $formValues['title'] = $this->_title;
    $formValues['campaign_id'] = $this->_campaignId;

    CRM_Friend_BAO_Friend::create($formValues);

    $this->assign('status', 'thankyou');
    $defaults = [];

    $defaults['entity_id'] = $this->_entityId;
    $defaults['entity_table'] = $this->_entityTable;

    CRM_Friend_BAO_Friend::getValues($defaults);
    if ($this->_entityTable === 'civicrm_pcp') {
      $defaults['thankyou_text'] = $defaults['thankyou_title'] = ts('Thank you for your support');
      $defaults['thankyou_text'] = ts('Thanks for supporting this campaign by spreading the word to your friends.');
    }
    elseif ($this->_entityTable === 'civicrm_contribution_page') {
      // If this is tell a friend after contributing, give donor link to create their own fundraising page
      if ($linkText = CRM_PCP_BAO_PCP::getPcpBlockStatus($defaults['entity_id'], $defaults['entity_table'])) {

        $linkTextUrl = CRM_Utils_System::url('civicrm/contribute/campaign',
          "action=add&reset=1&pageId={$defaults['entity_id']}&component=contribute",
          FALSE, NULL, TRUE,
          TRUE
        );
      }
    }
    elseif ($this->_entityTable === 'civicrm_event') {
      // If this is tell a friend after registering for an event, give donor link to create their own fundraising page
      require_once 'CRM/PCP/BAO/PCP.php';
      if ($linkText = CRM_PCP_BAO_PCP::getPcpBlockStatus($defaults['entity_id'], $defaults['entity_table'])) {
        $linkTextUrl = CRM_Utils_System::url('civicrm/contribute/campaign',
          "action=add&reset=1&pageId={$defaults['entity_id']}&component=event",
          FALSE, NULL, TRUE,
          TRUE);
      }
    }
    $this->assign('linkTextUrl', $linkTextUrl ?? NULL);
    $this->assign('linkText', $linkText ?? NULL);
    $this->setTitle($defaults['thankyou_title']);
    $this->assign('thankYouText', $defaults['thankyou_text']);
  }

}
