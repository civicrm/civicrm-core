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

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\MailingGroup;

require_once 'Mail/mime.php';

/**
 * Class CRM_Mailing_BAO_Mailing
 */
class CRM_Mailing_BAO_Mailing extends CRM_Mailing_DAO_Mailing implements \Civi\Core\HookInterface {

  /**
   * An array that holds the complete templates
   * including any headers or footers that need to be prepended
   * or appended to the body.
   * @var array
   */
  private $preparedTemplates = NULL;

  /**
   * An array that holds the complete templates
   * including any headers or footers that need to be prepended
   * or appended to the body.
   * @var array
   */
  private $templates = NULL;

  /**
   * An array that holds the tokens that are specifically found in our text and html bodies.
   * @var array
   */
  private $tokens = NULL;

  /**
   * An array that holds the tokens that are specifically found in our text and html bodies.
   * @var array
   */
  private $flattenedTokens = NULL;

  /**
   * The header associated with this mailing.
   * @var CRM_Mailing_BAO_MailingComponent
   */
  private $header = NULL;

  /**
   * The footer associated with this mailing.
   * @var CRM_Mailing_BAO_MailingComponent
   */
  private $footer = NULL;

  /**
   * Cached BAO for the domain.
   * @var int
   */
  private $_domain = NULL;

  /**
   * This function retrieve recipients of selected mailing groups.
   *
   * @param int $mailingID
   *
   * @return void
   */
  public static function getRecipients($mailingID) {
    // load mailing object
    $mailingObj = new self();
    $mailingObj->id = $mailingID;
    $mailingObj->find(TRUE);

    $contact = CRM_Contact_DAO_Contact::getTableName();
    $isSMSmode = (!CRM_Utils_System::isNull($mailingObj->sms_provider_id));

    $mailingGroup = new CRM_Mailing_DAO_MailingGroup();
    $recipientsGroup = $excludeSmartGroupIDs = $includeSmartGroupIDs = $priorMailingIDs = [];
    $dao = CRM_Utils_SQL_Select::from('civicrm_mailing_group')
      ->select('GROUP_CONCAT(DISTINCT entity_id SEPARATOR ",") as group_ids, group_type, entity_table')
      ->where('mailing_id = #mailing_id AND entity_table RLIKE "^civicrm_(group.*|mailing)$" ')
      ->groupBy(['group_type', 'entity_table'])
      ->param('!groupTableName', CRM_Contact_BAO_Group::getTableName())
      ->param('#mailing_id', $mailingID)
      ->execute();
    while ($dao->fetch()) {
      if ($dao->entity_table === 'civicrm_mailing') {
        $priorMailingIDs[$dao->group_type] = explode(',', $dao->group_ids);
      }
      else {
        $recipientsGroup[$dao->group_type] = empty($recipientsGroup[$dao->group_type]) ? explode(',', $dao->group_ids) : array_merge($recipientsGroup[$dao->group_type], explode(',', $dao->group_ids));
      }
    }

    // there is no need to proceed further if no mailing group is selected to include recipients,
    // but before return clear the mailing recipients populated earlier since as per current params no group is selected
    if (empty($recipientsGroup['Include']) && empty($priorMailingIDs['Include'])) {
      CRM_Core_DAO::executeQuery(" DELETE FROM civicrm_mailing_recipients WHERE  mailing_id = %1 ", [
        1 => [
          $mailingID,
          'Integer',
        ],
      ]);
      return;
    }

    [$location_filter, $order_by] = self::getLocationFilterAndOrderBy($mailingObj->email_selection_method, $mailingObj->location_type_id);

    // get all the saved searches AND hierarchical groups
    // and load them in the cache
    foreach ($recipientsGroup as $groupType => $groupIDs) {
      $groupDAO = CRM_Utils_SQL_Select::from('civicrm_group')
        ->where('id IN (#groupIDs)')
        ->where('saved_search_id != 0 OR saved_search_id IS NOT NULL OR children IS NOT NULL')
        ->param('#groupIDs', $groupIDs)
        ->execute();
      while ($groupDAO->fetch()) {
        // hidden smart groups always have a cache date and there is no other way
        //  we can rebuilt the contact list from UI so consider such smart group
        if ($groupDAO->cache_date == NULL || $groupDAO->is_hidden) {
          CRM_Contact_BAO_GroupContactCache::load($groupDAO);
        }
        if ($groupType === 'Include') {
          $includeSmartGroupIDs[] = $groupDAO->id;
        }
        elseif ($groupType === 'Exclude') {
          $excludeSmartGroupIDs[] = $groupDAO->id;
        }
        //NOTE: Do nothing for base
      }
    }

    // Create a temp table for contact exclusion.
    $excludeTempTable = CRM_Utils_SQL_TempTable::build()->setCategory('exrecipient')->setMemory()->createWithColumns('contact_id int primary key');
    $excludeTempTablename = $excludeTempTable->getName();
    // populate exclude temp-table with recipients to be excluded from the list
    //  on basis of selected recipients groups and/or previous mailing
    if (!empty($recipientsGroup['Exclude'])) {
      CRM_Utils_SQL_Select::from('civicrm_group_contact')
        ->select('DISTINCT contact_id')
        ->where('status = "Added" AND group_id IN (#groups)')
        ->param('#groups', $recipientsGroup['Exclude'])
        ->insertInto($excludeTempTablename, ['contact_id'])
        ->execute();

      if (count($excludeSmartGroupIDs)) {
        CRM_Utils_SQL_Select::from('civicrm_group_contact_cache')
          ->select('contact_id')
          ->where('group_id IN (#groups)')
          ->param('#groups', $excludeSmartGroupIDs)
          ->insertIgnoreInto($excludeTempTablename, ['contact_id'])
          ->execute();
      }
    }
    if (!empty($priorMailingIDs['Exclude'])) {
      CRM_Utils_SQL_Select::from('civicrm_mailing_recipients')
        ->select('DISTINCT contact_id')
        ->where('mailing_id IN (#mailings)')
        ->param('#mailings', $priorMailingIDs['Exclude'])
        ->insertIgnoreInto($excludeTempTablename, ['contact_id'])
        ->execute();
    }

    if (!empty($recipientsGroup['Base'])) {
      CRM_Utils_SQL_Select::from('civicrm_group_contact')
        ->select('DISTINCT contact_id')
        ->where('status = "Removed" AND group_id IN (#groups)')
        ->param('#groups', $recipientsGroup['Base'])
        ->insertIgnoreInto($excludeTempTablename, ['contact_id'])
        ->execute();
    }

    $entityColumn = $isSMSmode ? 'phone_id' : 'email_id';
    $entityTable = $isSMSmode ? CRM_Core_DAO_Phone::getTableName() : CRM_Core_DAO_Email::getTableName();
    // Get all the group contacts we want to include.
    $includedTempTable = CRM_Utils_SQL_TempTable::build()->setCategory('inrecipient')->setMemory()->createWithColumns('contact_id int primary key, ' . $entityColumn . ' int');
    $includedTempTablename = $includedTempTable->getName();

    if ($isSMSmode) {
      $criteria = [
        'is_deleted' => CRM_Utils_SQL_Select::fragment()->where("$contact.is_deleted = 0"),
        'is_opt_out' => CRM_Utils_SQL_Select::fragment()->where("$contact.is_opt_out = 0"),
        'is_deceased' => CRM_Utils_SQL_Select::fragment()->where("$contact.is_deceased <> 1"),
        'do_not_sms' => CRM_Utils_SQL_Select::fragment()->where("$contact.do_not_sms = 0"),
        'location_filter' => CRM_Utils_SQL_Select::fragment()->where("$entityTable.phone_type_id = " . CRM_Core_PseudoConstant::getKey('CRM_Core_DAO_Phone', 'phone_type_id', 'Mobile')),
        'phone_not_null' => CRM_Utils_SQL_Select::fragment()->where("$entityTable.phone IS NOT NULL"),
        'phone_not_empty' => CRM_Utils_SQL_Select::fragment()->where("$entityTable.phone != ''"),
        'mailing_id' => CRM_Utils_SQL_Select::fragment()->where("mg.mailing_id = #mailingID"),
        'temp_contact_null' => CRM_Utils_SQL_Select::fragment()->where('temp.contact_id IS null'),
        'order_by' => CRM_Utils_SQL_Select::fragment()->orderBy("$entityTable.is_primary"),
      ];
    }
    else {
      // Criteria to filter recipients that need to be included
      $criteria = [
        'is_deleted' => CRM_Utils_SQL_Select::fragment()->where("$contact.is_deleted = 0"),
        'do_not_email' => CRM_Utils_SQL_Select::fragment()->where("$contact.do_not_email = 0"),
        'is_opt_out' => CRM_Utils_SQL_Select::fragment()->where("$contact.is_opt_out = 0"),
        'is_deceased' => CRM_Utils_SQL_Select::fragment()->where("$contact.is_deceased <> 1"),
        'location_filter' => CRM_Utils_SQL_Select::fragment()->where($location_filter),
        'email_not_null' => CRM_Utils_SQL_Select::fragment()->where("$entityTable.email IS NOT NULL"),
        'email_not_empty' => CRM_Utils_SQL_Select::fragment()->where("$entityTable.email != ''"),
        'email_not_on_hold' => CRM_Utils_SQL_Select::fragment()->where("$entityTable.on_hold = 0"),
        'mailing_id' => CRM_Utils_SQL_Select::fragment()->where("mg.mailing_id = #mailingID"),
        'temp_contact_null' => CRM_Utils_SQL_Select::fragment()->where('temp.contact_id IS NULL'),
        'order_by' => CRM_Utils_SQL_Select::fragment()->orderBy($order_by),
      ];
    }

    // Allow user to alter query responsible to fetch mailing recipients before build,
    //   by changing the mail filters identified $params
    CRM_Utils_Hook::alterMailingRecipients($mailingObj, $criteria, 'pre');

    // Get the group contacts, but only those which are not in the
    // exclusion temp table.
    if (!empty($recipientsGroup['Include'])) {
      CRM_Utils_SQL_Select::from($entityTable)
        ->select("$contact.id as contact_id, $entityTable.id as $entityColumn")
        ->join($contact, " INNER JOIN $contact ON $entityTable.contact_id = $contact.id ")
        ->join('gc', " INNER JOIN civicrm_group_contact gc ON gc.contact_id = $contact.id ")
        ->join('mg', " INNER JOIN civicrm_mailing_group mg  ON  gc.group_id = mg.entity_id AND mg.search_id IS NULL ")
        ->join('temp', " LEFT JOIN $excludeTempTablename temp ON $contact.id = temp.contact_id ")
        ->where('gc.group_id IN (#groups) AND gc.status = "Added"')
        ->merge($criteria)
        ->groupBy(["$contact.id", "$entityTable.id"])
        ->replaceInto($includedTempTablename, ['contact_id', $entityColumn])
        ->param('#groups', $recipientsGroup['Include'])
        ->param('#mailingID', $mailingID)
        ->execute();
    }

    // Get recipients selected in prior mailings
    if (!empty($priorMailingIDs['Include'])) {
      CRM_Utils_SQL_Select::from('civicrm_mailing_recipients')
        ->select("DISTINCT civicrm_mailing_recipients.contact_id, $entityColumn")
        ->join('temp', " LEFT JOIN $excludeTempTablename temp ON civicrm_mailing_recipients.contact_id = temp.contact_id ")
        ->where('mailing_id IN (#mailings)')
        ->where('temp.contact_id IS NULL')
        ->param('#mailings', $priorMailingIDs['Include'])
        ->insertIgnoreInto($includedTempTablename, [
          'contact_id',
          $entityColumn,
        ])
        ->execute();
    }

    if (count($includeSmartGroupIDs)) {
      $query = CRM_Utils_SQL_Select::from($contact)
        ->select("$contact.id as contact_id, $entityTable.id as $entityColumn")
        ->join($entityTable, " INNER JOIN $entityTable ON $entityTable.contact_id = $contact.id ")
        ->join('gc', " INNER JOIN civicrm_group_contact_cache gc ON $contact.id = gc.contact_id ")
        ->join('gcr', " LEFT JOIN civicrm_group_contact gcr ON gc.group_id = gcr.group_id AND gc.contact_id = gcr.contact_id")
        ->join('mg', " INNER JOIN civicrm_mailing_group mg  ON  gc.group_id = mg.entity_id AND mg.search_id IS NULL ")
        ->join('temp', " LEFT JOIN $excludeTempTablename temp ON $contact.id = temp.contact_id ")
        ->where('gc.group_id IN (#groups)')
        ->where('gcr.status IS NULL OR gcr.status != "Removed"')
        ->merge($criteria)
        ->replaceInto($includedTempTablename, ['contact_id', $entityColumn])
        ->param('#groups', $includeSmartGroupIDs)
        ->param('#mailingID', $mailingID)
        ->execute();
    }

    [$aclFrom, $aclWhere] = CRM_Contact_BAO_Contact_Permission::cacheClause();

    // clear all the mailing recipients before populating
    CRM_Core_DAO::executeQuery(' DELETE FROM civicrm_mailing_recipients WHERE  mailing_id = %1 ', [
      1 => [
        $mailingID,
        'Integer',
      ],
    ]);

    $selectClause = ['#mailingID', 'i.contact_id', "i.$entityColumn"];
    // CRM-3975
    $orderBy = ["i.contact_id", "i.$entityColumn"];

    $query = CRM_Utils_SQL_Select::from('civicrm_contact contact_a')
      ->join('i', " INNER JOIN {$includedTempTablename} i ON contact_a.id = i.contact_id ");
    if (!$isSMSmode && $mailingObj->dedupe_email) {
      $orderBy = ["MIN(i.contact_id)", "MIN(i.$entityColumn)"];
      $query = $query->join('e', " INNER JOIN civicrm_email e ON e.id = i.email_id ")
        ->groupBy("e.email");
      if (CRM_Utils_SQL::supportsFullGroupBy()) {
        $selectClause = [
          '#mailingID',
          'ANY_VALUE(i.contact_id) contact_id',
          "ANY_VALUE(i.$entityColumn) $entityColumn",
          "e.email",
        ];
      }
    }

    $query = $query->select($selectClause)->orderBy($orderBy);
    if (!CRM_Utils_System::isNull($aclFrom)) {
      $query = $query->join('acl', $aclFrom);
    }
    if (!CRM_Utils_System::isNull($aclWhere)) {
      $query = $query->where($aclWhere);
    }

    // this mean if dedupe_email AND the mysql 5.7 supports ONLY_FULL_GROUP_BY mode then as
    //  SELECT must contain 'email' column as its used in GROUP BY, so in order to resolve This
    //  here the whole SQL code is wrapped up in FROM table i and not selecting email column for INSERT
    if ($key = array_search('e.email', $selectClause)) {
      unset($selectClause[$key]);
      $sql = $query->toSQL();
      CRM_Utils_SQL_Select::from("( $sql ) AS i ")
        ->select($selectClause)
        ->insertInto('civicrm_mailing_recipients', ['mailing_id', 'contact_id', $entityColumn])
        ->param('#mailingID', $mailingID)
        ->execute();
    }
    else {
      $query->insertInto('civicrm_mailing_recipients', ['mailing_id', 'contact_id', $entityColumn])
        ->param('#mailingID', $mailingID)
        ->execute();
    }

    // if we need to add all emails marked bulk, do it as a post filter
    // on the mailing recipients table
    if (CRM_Core_BAO_Email::isMultipleBulkMail()) {
      self::addMultipleEmails($mailingID);
    }

    // Delete the temp table.
    $mailingGroup->reset();
    $excludeTempTable->drop();
    $includedTempTable->drop();

    CRM_Utils_Hook::alterMailingRecipients($mailingObj, $criteria, 'post');
  }

  /**
   * Function to retrieve location filter and order by clause later used by SQL query that is used to fetch and include mailing recipients
   *
   * @param string $email_selection_method
   * @param int $location_type_id
   *
   * @return array
   */
  public static function getLocationFilterAndOrderBy($email_selection_method, $location_type_id) {
    if ($email_selection_method !== 'automatic' && !$location_type_id) {
      throw new \CRM_Core_Exception(ts('You have selected an email Selection Method without specifying a Location Type. Please go back and change your recipient settings (using the wrench icon next to "Recipients").'));
    }
    $email = CRM_Core_DAO_Email::getTableName();
    // Note: When determining the ORDER that results are returned, it's
    // the record that comes last that counts. That's because we are
    // INSERT'ing INTO a table with a primary id so that last record
    // over writes any previous record.
    switch ($email_selection_method) {
      case 'location-exclude':
        $location_filter = "($email.location_type_id != $location_type_id)";
        // If there is more than one email that doesn't match the location,
        // prefer the one marked is_bulkmail, followed by is_primary.
        $orderBy = ["$email.is_bulkmail", "$email.is_primary"];
        break;

      case 'location-only':
        $location_filter = "($email.location_type_id = $location_type_id)";
        // If there is more than one email of the desired location, prefer
        // the one marked is_bulkmail, followed by is_primary.
        $orderBy = ["$email.is_bulkmail", "$email.is_primary"];
        break;

      case 'location-prefer':
        $location_filter = "($email.is_bulkmail = 1 OR $email.is_primary = 1 OR $email.location_type_id = $location_type_id)";
        // ORDER BY is more complicated because we have to set an arbitrary
        // order that prefers the location that we want. We do that using
        // the FIELD function. For more info, see:
        // https://dev.mysql.com/doc/refman/5.5/en/string-functions.html#function_field
        // We assign the location type we want the value "1" by putting it
        // in the first position after we name the field. All other location
        // types are left out, so they will be assigned the value 0. That
        // means, they will all be equally tied for first place, with our
        // location being last.
        $orderBy = [
          "FIELD($email.location_type_id, $location_type_id)",
          "$email.is_bulkmail",
          "$email.is_primary",
        ];
        break;

      case 'automatic':
        // fall through to default
      default:
        $location_filter = "($email.is_bulkmail = 1 OR $email.is_primary = 1)";
        $orderBy = ["$email.is_bulkmail"];
    }

    return [$location_filter, $orderBy];
  }

  /**
   * Process parameters to ensure workflow permissions are respected.
   *
   * 'schedule mailings' and 'approve mailings' can update certain fields,
   * but can't create.
   *
   * @param array $params
   *
   * @return array
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected static function processWorkflowPermissions(array $params): array {
    if (empty($params['id']) && !CRM_Core_Permission::check('access CiviMail') && !CRM_Core_Permission::check('create mailings')) {
      throw new UnauthorizedException("Cannot create new mailing. Required permission: 'access CiviMail' or 'create mailings'");
    }

    $safeParams = [];
    $fieldPerms = CRM_Mailing_BAO_Mailing::getWorkflowFieldPerms();
    foreach (array_keys($params) as $field) {
      if (CRM_Core_Permission::check($fieldPerms[$field])) {
        $safeParams[$field] = $params[$field];
      }
    }
    return $safeParams;
  }

  /**
   * Do Submit actions.
   *
   * When submitting (as opposed to creating or updating) a mailing it should
   * be scheduled.
   *
   * This function creates the initial job and the recipient list.
   *
   * @param array $params
   * @param \CRM_Mailing_DAO_Mailing $mailing
   */
  protected static function doSubmitActions(array $params, CRM_Mailing_DAO_Mailing $mailing): void {
    // Create parent job if not yet created.
    // Condition on the existence of a scheduled date.
    if (!empty($params['scheduled_date']) && $params['scheduled_date'] !== 'null' && empty($params['_skip_evil_bao_auto_schedule_'])) {

      $job = new CRM_Mailing_BAO_MailingJob();
      $job->mailing_id = $mailing->id;
      // If we are creating a new Completed mailing (e.g. import from another system) set the job to completed.
      // Keeping former behaviour when an id is present is precautionary and may warrant reconsideration later.
      $job->status = ((empty($params['is_completed']) || !empty($params['id'])) ? 'Scheduled' : 'Complete');
      $job->is_test = 0;

      if (!$job->find(TRUE)) {
        // Don't schedule job until we populate the recipients.
        $job->scheduled_date = NULL;
        $job->save();
      }
      // Schedule the job now that it has recipients.
      $job->scheduled_date = $params['scheduled_date'];
      $job->save();
    }

    // Populate the recipients.
    if (empty($params['_skip_evil_bao_auto_recipients_'])) {
      if ((!isset($params['is_completed']) || $params['is_completed'] !== 1)
        && !empty($params['scheduled_date']) && $params['scheduled_date'] !== 'null'
        && empty($params['_skip_evil_bao_auto_schedule_'])
      ) {
        self::refreshMailingGroupCache($mailing->id);
      }
      self::getRecipients($mailing->id);
    }
  }

  /**
   * Refresh the group cache for groups relevant to the mailing.
   *
   * @param int $mailingID
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   *
   * @internal not supported for use from outside of core. Function has always
   * been internal so may be moved / removed / alters at any time without
   * regard for external users.
   */
  public static function refreshMailingGroupCache(int $mailingID): void {
    $mailingGroups = MailingGroup::get()
      ->addSelect('group.id')
      ->addJoin('Group AS group', 'LEFT', ['entity_id', '=', 'group.id'])
      ->addWhere('mailing_id', '=', $mailingID)
      ->addWhere('entity_table', '=', 'civicrm_group')
      ->addWhere('group_type', 'IN', ['Include', 'Exclude'])
      ->addClause('OR', ['group.saved_search_id', 'IS NOT NULL'], ['group.children', 'IS NOT NULL'])
      ->execute();
    foreach ($mailingGroups as $mailingGroup) {
      CRM_Contact_BAO_GroupContactCache::invalidateGroupContactCache($mailingGroup['group.id']);
      $group = new CRM_Contact_DAO_Group();
      $group->find(TRUE);
      $group->id = $mailingGroup['group.id'];
      CRM_Contact_BAO_GroupContactCache::load($group);
    }
  }

  /**
   * Returns the regex patterns that are used for preparing the text and html templates.
   *
   * @param bool $onlyHrefs
   *
   * @return array|string
   */
  private function getPatterns($onlyHrefs = FALSE) {

    $patterns = [];

    $protos = '(https?|ftp|mailto)';
    $letters = '\w';
    $gunk = '\{\}/#~:.?+=&;%@!\,\-\|\(\)\*';
    $punc = '.:?\-';
    $any = "{$letters}{$gunk}{$punc}";
    if ($onlyHrefs) {
      $pattern = "\\bhref[ ]*=[ ]*([\"'])?(($protos:[$any]+?(?=[$punc]*[^$any]|$)))([\"'])?";
    }
    else {
      $pattern = "\\b($protos:[$any]+?(?=[$punc]*[^$any]|$))";
    }

    $patterns[] = $pattern;
    $patterns[] = '\\\\\{\w+\.\w+\\\\\}|\{\{\w+\.\w+\}\}';
    $patterns[] = '\{\w+\.\w+\}';

    $patterns = '{' . implode('|', $patterns) . '}imu';

    return $patterns;
  }

  /**
   * Retrieve a ref to an array that holds the email and text templates for this email
   * assembles the complete template including the header and footer
   * that the user has uploaded or declared (if they have done that)
   *
   * @return array
   *   reference to an assoc array
   */
  public function getTemplates() {
    if (!$this->templates) {
      $this->getHeaderFooter();
      $this->templates = [];
      if ($this->body_text || !empty($this->header)) {
        $template = [];
        if (!empty($this->header->body_text)) {
          $template[] = $this->header->body_text;
        }
        elseif (!empty($this->header->body_html)) {
          $template[] = CRM_Utils_String::htmlToText($this->header->body_html);
        }

        if ($this->body_text) {
          $template[] = $this->body_text;
        }
        else {
          $template[] = CRM_Utils_String::htmlToText($this->body_html);
        }

        if (!empty($this->footer->body_text)) {
          $template[] = $this->footer->body_text;
        }
        elseif (!empty($this->footer->body_html)) {
          $template[] = CRM_Utils_String::htmlToText($this->footer->body_html);
        }

        $this->templates['text'] = implode("\n", $template);
      }

      // To check for an html part strip tags
      if (trim(strip_tags(($this->body_html ?? ''), '<img>'))) {

        $template = [];
        if ($this->header) {
          $template[] = $this->header->body_html;
        }

        $template[] = $this->body_html;

        if ($this->footer) {
          $template[] = $this->footer->body_html;
        }

        $this->templates['html'] = implode("\n", $template);

        // this is where we create a text template from the html template if the text template did not exist
        // this way we ensure that every recipient will receive an email even if the pref is set to text and the
        // user uploads an html email only
        if (empty($this->templates['text'])) {
          $this->templates['text'] = CRM_Utils_String::htmlToText($this->templates['html']);
        }
      }

      if ($this->subject) {
        $template = [];
        $template[] = $this->subject;
        $this->templates['subject'] = implode("\n", $template);
      }

      $this->templates['mailingID'] = $this->id;
      $this->templates['campaign_id'] = $this->campaign_id;
      $this->templates['template_type'] = $this->template_type;
      CRM_Utils_Hook::alterMailContent($this->templates);
    }
    return $this->templates;
  }

  /**
   *
   *  Retrieve a ref to an array that holds all of the tokens in the email body
   *  where the keys are the type of token and the values are ordinal arrays
   *  that hold the token names (even repeated tokens) in the order in which
   *  they appear in the body of the email.
   *
   *  note: the real work is done in the _getTokens() function
   *
   *  this function needs to have some sort of a body assigned
   *  either text or html for this to have any meaningful impact
   *
   * @return array
   *   reference to an assoc array
   */
  public function &getTokens() {
    if (!$this->tokens) {

      $this->tokens = ['html' => [], 'text' => [], 'subject' => []];

      if ($this->body_html) {
        $this->_getTokens('html');
        if (!$this->body_text) {
          // Since the text template was created from html, use the html tokens.
          // @see CRM_Mailing_BAO_Mailing::getTemplates()
          $this->tokens['text'] = $this->tokens['html'];
        }
      }

      if ($this->body_text) {
        $this->_getTokens('text');
      }

      if ($this->subject) {
        $this->_getTokens('subject');
      }
    }

    return $this->tokens;
  }

  /**
   * Returns the token set for all 3 parts as one set. This allows it to be sent to the
   * hook in one call and standardizes it across other token workflows
   *
   * @return array
   *   reference to an assoc array
   */
  public function &getFlattenedTokens() {
    if (!$this->flattenedTokens) {
      $tokens = $this->getTokens();

      $this->flattenedTokens = CRM_Utils_Token::flattenTokens($tokens);
    }

    return $this->flattenedTokens;
  }

  /**
   *
   *  _getTokens parses out all of the tokens that have been
   *  included in the html and text bodies of the email
   *  we get the tokens and then separate them into an
   *  internal structure named tokens that has the same
   *  form as the static tokens property(?) of the CRM_Utils_Token class.
   *  The difference is that there might be repeated token names as we want the
   *  structures to represent the order in which tokens were found from left to right, top to bottom.
   *
   *
   * @param string $prop name of the property that holds the text that we want to scan for tokens (html, text).
   *   Name of the property that holds the text that we want to scan for tokens (html, text).
   *
   * @return void
   */
  private function _getTokens($prop) {
    $templates = $this->getTemplates();

    $newTokens = CRM_Utils_Token::getTokens($templates[$prop]);

    foreach ($newTokens as $type => $names) {
      if (!isset($this->tokens[$prop][$type])) {
        $this->tokens[$prop][$type] = [];
      }
      foreach ($names as $key => $name) {
        $this->tokens[$prop][$type][] = $name;
      }
    }
  }

  /**
   * Generate an event queue for a test job.
   *
   * @param array $testParams
   *   Contains form values.
   * @param int $mailingID
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function getTestRecipients(array $testParams, int $mailingID): void {
    if (!empty($testParams['test_group']) && array_key_exists($testParams['test_group'], CRM_Core_PseudoConstant::group())) {
      $contacts = civicrm_api('contact', 'get', [
        'version' => 3,
        'group' => $testParams['test_group'],
        'return' => 'id',
        'options' => [
          'limit' => 100000000000,
        ],
      ]);

      foreach (array_keys($contacts['values']) as $groupContact) {
        $query = "
SELECT     civicrm_email.id AS email_id,
           civicrm_email.is_primary as is_primary,
           civicrm_email.is_bulkmail as is_bulkmail
FROM       civicrm_email
INNER JOIN civicrm_contact ON civicrm_email.contact_id = civicrm_contact.id
WHERE      (civicrm_email.is_bulkmail = 1 OR civicrm_email.is_primary = 1)
AND        civicrm_contact.id = {$groupContact}
AND        civicrm_contact.do_not_email = 0
AND        civicrm_contact.is_deceased <> 1
AND        civicrm_email.on_hold = 0
AND        civicrm_contact.is_opt_out = 0
GROUP BY   civicrm_email.id
ORDER BY   civicrm_email.is_bulkmail DESC
";
        $dao = CRM_Core_DAO::executeQuery($query);
        if ($dao->fetch()) {
          $params = [
            'job_id' => $testParams['job_id'],
            'email_id' => $dao->email_id,
            'contact_id' => $groupContact,
            'mailing_id' => $mailingID,
            'is_test' => TRUE,
          ];
          CRM_Mailing_Event_BAO_MailingEventQueue::create($params);
        }
      }
    }
  }

  /**
   * Load this->header and this->footer.
   */
  private function getHeaderFooter() {
    if (!$this->header and $this->header_id) {
      $this->header = new CRM_Mailing_BAO_MailingComponent();
      $this->header->id = $this->header_id;
      $this->header->find(TRUE);
    }

    if (!$this->footer and $this->footer_id) {
      $this->footer = new CRM_Mailing_BAO_MailingComponent();
      $this->footer->id = $this->footer_id;
      $this->footer->find(TRUE);
    }
  }

  /**
   * Given and array of headers and a prefix, job ID, event queue ID, and hash,
   * add a Message-ID header if needed.
   *
   * i.e. if the global includeMessageId is set and there isn't already a
   * Message-ID in the array.
   * The message ID is structured the same way as a verp. However no interpretation
   * is placed on the values received, so they do not need to follow the verp
   * convention.
   *
   * @param array $headers
   *   Array of message headers to update, in-out.
   * @param string $prefix
   *   Prefix for the message ID, use same prefixes as verp.
   *   wherever possible
   * @param null $theVoid
   *   Stare into the abyss.
   * @param string $event_queue_id
   *   Event Queue ID component of the generated message ID.
   * @param string $hash
   *   Hash component of the generated message ID.
   *
   * @return void
   */
  public static function addMessageIdHeader(&$headers, $prefix, $theVoid, $event_queue_id, $hash): void {
    $config = CRM_Core_Config::singleton();
    $localpart = CRM_Core_BAO_MailSettings::defaultLocalpart();
    $emailDomain = CRM_Core_BAO_MailSettings::defaultDomain();
    $includeMessageId = CRM_Core_BAO_MailSettings::includeMessageId();
    $fields = [];
    $fields[] = 'Message-ID';
    // CRM-17754 check if Resent-Message-id is set also if not add it in when re-laying reply email
    if ($prefix === 'r') {
      $fields[] = 'Resent-Message-ID';
    }
    foreach ($fields as $field) {
      if ($includeMessageId && (!array_key_exists($field, $headers))) {
        $headers[$field] = '<' . implode($config->verpSeparator,
            [
              $localpart . $prefix,
              $event_queue_id,
              $hash,
            ]
          ) . "@{$emailDomain}>";
      }
    }

  }

  /**
   * Static wrapper for getting verp and urls.
   *
   * @param int $job_id
   *   ID of the Job associated with this message.
   * @param int $event_queue_id
   *   ID of the EventQueue.
   * @param string $hash
   *   Hash of the EventQueue.
   *
   * @return array
   *   (reference) array    array ref that hold array refs to the verp info and urls
   */
  public static function getVerpAndUrls($job_id, $event_queue_id, $hash) {
    // create a skeleton object and set its properties that are required by getVerpAndUrlsAndHeaders()
    $bao = new CRM_Mailing_BAO_Mailing();
    $bao->_domain = CRM_Core_BAO_Domain::getDomain();
    $bao->from_name = $bao->from_email = $bao->subject = '';

    // use $bao's instance method to get verp and urls
    [$verp, $urls, $_] = $bao->getVerpAndUrlsAndHeaders($job_id, $event_queue_id, $hash);
    return [$verp, $urls];
  }

  /**
   * Get verp, urls and headers
   *
   * @param int $job_id
   *   ID of the Job associated with this message.
   * @param int $event_queue_id
   *   ID of the EventQueue.
   * @param string $hash
   *   Hash of the EventQueue.
   *
   * @return array
   *   array ref that hold array refs to the verp info, urls, and headers
   */
  public function getVerpAndUrlsAndHeaders($job_id, $event_queue_id, $hash) {
    $config = CRM_Core_Config::singleton();

    /**
     * Inbound VERP keys:
     *  reply:          user replied to mailing
     *  bounce:         email address bounced
     *  unsubscribe:    contact opts out of all target lists for the mailing
     *  resubscribe:    contact opts back into all target lists for the mailing
     *  optOut:         contact unsubscribes from the domain
     */
    $verp = [];
    $verpTokens = [
      'reply' => 'r',
      'bounce' => 'b',
      'unsubscribe' => 'u',
      'resubscribe' => 'e',
      'optOut' => 'o',
    ];

    $localpart = CRM_Core_BAO_MailSettings::defaultLocalpart();
    $emailDomain = CRM_Core_BAO_MailSettings::defaultDomain();
    // Make sure the user configured the site correctly, otherwise you just get "Could not identify any recipients. Perhaps the group is empty?" from the mailing UI
    if (empty($emailDomain)) {
      Civi::log()->error('Error setting verp parameters, defaultDomain is NULL.  Did you configure the bounce processing account for this domain?');
    }

    foreach ($verpTokens as $key => $value) {
      $verp[$key] = implode($config->verpSeparator,
          [
            $localpart . $value,
            $job_id,
            $event_queue_id,
            $hash,
          ]
        ) . "@$emailDomain";
    }

    //handle should override VERP address.
    $skipEncode = FALSE;

    if ($job_id &&
      self::overrideVerp($job_id)
    ) {
      $verp['reply'] = "\"{$this->from_name}\" <{$this->from_email}>";
    }

    // Generating URLs is expensive, so we only call it once for each of these 5 URLs.
    $genericURL = CRM_Utils_System::url('civicrm/mailing/genericUrlPath', "reset=1&jid={$job_id}&qid={$event_queue_id}&h={$hash}", TRUE, NULL, TRUE, TRUE);
    $urls = [
      'unsubscribeUrl' => str_replace('genericUrlPath', 'unsubscribe', $genericURL),
      'resubscribeUrl' => str_replace('genericUrlPath', 'resubscribe', $genericURL),
      'optOutUrl' => str_replace('genericUrlPath', 'optout', $genericURL),
      'subscribeUrl' => str_replace('genericUrlPath', 'subscribe', $genericURL),
    ];

    $headers = [
      'Reply-To' => $verp['reply'],
      'Return-Path' => $verp['bounce'],
      'From' => "\"{$this->from_name}\" <{$this->from_email}>",
      'Subject' => $this->subject,
      'List-Unsubscribe' => "<mailto:{$verp['unsubscribe']}>",
    ];
    self::addMessageIdHeader($headers, 'm', NULL, $event_queue_id, $hash);
    return [&$verp, &$urls, &$headers];
  }

  /**
   * Return a list of group names for this mailing.  Does not work with
   * prior-mailing targets.
   *
   * @deprecated since 5.71 will be removed around 5.77
   *
   * @return array
   *   Names of groups receiving this mailing
   */
  public function &getGroupNames() {
    CRM_Core_Error::deprecatedWarning('unused function');
    if (!isset($this->id)) {
      return [];
    }

    /*
    This bypasses permissions to maintain compatibility with the SQL it replaced.  This should ideally not bypass
    permissions in the future, but it's called by some extensions during mail processing, when cron isn't necessarily
    called with a logged-in user.
     */
    $mailingGroups = MailingGroup::get(FALSE)
      ->addSelect('group.title', 'group.frontend_title')
      ->addJoin('Group AS group', 'LEFT', ['entity_id', '=', 'group.id'])
      ->addWhere('mailing_id', '=', $this->id)
      ->addWhere('entity_table', '=', 'civicrm_group')
      ->addWhere('group_type', '=', 'Include')
      ->execute();

    $groupNames = [];

    foreach ($mailingGroups as $mg) {
      $name = $mg['group.frontend_title'] ?? $mg['group.title'];
      if ($name) {
        $groupNames[] = $name;
      }
    }

    return $groupNames;
  }

  /**
   * Add the mailings.
   *
   * @param array $params
   *
   * @return CRM_Mailing_DAO_Mailing
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function add($params) {
    $id = $params['id'] ?? NULL;

    if (!empty($params['check_permissions']) && CRM_Mailing_Info::workflowEnabled()) {
      $params = self::processWorkflowPermissions($params);
    }
    if (!$id) {
      $params['domain_id'] ??= CRM_Core_Config::domainID();
    }
    if (
      ((!$id && empty($params['replyto_email'])) || !isset($params['replyto_email'])) &&
      isset($params['from_email'])
    ) {
      $params['replyto_email'] = $params['from_email'];
    }
    // CRM-20892 Unset Modifed Date here so that MySQL can correctly set an updated modfied date.
    unset($params['modified_date']);

    $result = static::writeRecord($params);

    // CRM-20892 Re find record after saing so we can set the updated modified date in the result.
    $result->find(TRUE);

    return $result;
  }

  /**
   * Construct a new mailing object, along with job and mailing_group
   * objects, from the form values of the create mailing wizard.
   *
   * This function is a bit evil. It not only merges $params and saves
   * the mailing -- it also schedules the mailing and chooses the recipients.
   * Since it merges $params, it's also the only place to correctly trigger
   * multi-field validation. It should be broken up.
   *
   * In the mean time, use-cases which break under the weight of this
   * evil may find reprieve in these extra evil params:
   *
   *  - _skip_evil_bao_auto_recipients_: bool
   *  - _skip_evil_bao_auto_schedule_: bool
   *
   * </twowrongsmakesaright>
   *
   * @params array $params
   *   Form values.
   *
   * @param array $params
   *
   * @return object
   *   $mailing      The new mailing object
   *
   * @throws \CRM_Core_Exception
   */
  public static function create(array $params) {

    // CRM-#1843
    // If it is a mass sms, set url_tracking to false
    if (!empty($params['sms_provider_id'])) {
      $params['url_tracking'] = 0;
    }

    // CRM-12430
    // Do the below only for an insert
    // for an update, we should not set the defaults
    if (!isset($params['id'])) {
      // Retrieve domain email and name for default sender
      $domain = civicrm_api(
        'Domain',
        'getsingle',
        [
          'version' => 3,
          'current_domain' => 1,
          'sequential' => 1,
        ]
      );
      if (isset($domain['from_email'])) {
        $domain_email = $domain['from_email'];
        $domain_name = $domain['from_name'];
      }
      else {
        $domain_email = 'info@EXAMPLE.ORG';
        $domain_name = 'EXAMPLE.ORG';
      }
      if (!isset($params['created_id'])) {
        $params['created_id'] = CRM_Core_Session::getLoggedInContactID();
      }
      $defaults = [
        // load the default config settings for each
        // eg reply_id, unsubscribe_id need to use
        // correct template IDs here
        'override_verp' => TRUE,
        'forward_replies' => FALSE,
        'open_tracking' => Civi::settings()->get('open_tracking_default'),
        'url_tracking' => Civi::settings()->get('url_tracking_default'),
        'visibility' => 'Public Pages',
        'replyto_email' => $domain_email,
        'header_id' => NULL,
        'footer_id' => NULL,
        'from_email' => $domain_email,
        'from_name' => $domain_name,
        'msg_template_id' => NULL,
        'created_id' => $params['created_id'],
        'approver_id' => NULL,
        'auto_responder' => 0,
        'created_date' => date('YmdHis'),
        'scheduled_date' => NULL,
        'approval_date' => NULL,
        'status' => 'Draft',
        'start_date' => NULL,
        'end_date' => NULL,
      ];
      if (CRM_Utils_System::isNull($params['sms_provider_id'] ?? NULL)) {
        $defaults['header_id'] = CRM_Mailing_PseudoConstant::defaultComponent('Header', '');
        $defaults['footer_id'] = CRM_Mailing_PseudoConstant::defaultComponent('Footer', '');
      }

      // Get the default from email address, if not provided.
      if (empty($defaults['from_email'])) {
        $defaultAddress = CRM_Core_BAO_Domain::getNameAndEmail(TRUE);
        $defaults['from_email'] = $defaultAddress[1];
        $defaults['from_name'] = $defaultAddress[0];
      }

      $params = array_merge($defaults, $params);
    }

    /**
     * Could check and warn for the following cases:
     *
     * - groups OR mailings should be populated.
     * - body html OR body text should be populated.
     */

    $transaction = new CRM_Core_Transaction();

    $mailing = self::add($params);

    // update mailings with hash values
    CRM_Contact_BAO_Contact_Utils::generateChecksum($mailing->id, NULL, NULL, NULL, 'mailing', 16);

    $groupTableName = CRM_Contact_BAO_Group::getTableName();

    /* Create the mailing group record */
    $mg = new CRM_Mailing_DAO_MailingGroup();
    $groupTypes = [
      'include' => 'Include',
      'exclude' => 'Exclude',
      'base' => 'Base',
    ];
    foreach (['groups', 'mailings'] as $entity) {
      foreach (['include', 'exclude', 'base'] as $type) {
        if (isset($params[$entity][$type])) {
          self::replaceGroups($mailing->id, $groupTypes[$type], $entity, $params[$entity][$type]);
        }
      }
    }

    // If we are scheduling vai Mailing.create then also update the status to scheduled.
    if (empty($params['skip_legacy_scheduling']) && !empty($params['scheduled_date']) && $params['scheduled_date'] !== 'null' && empty($params['_skip_evil_bao_auto_schedule_'])) {
      $mailing->status = 'Scheduled';
    }
    if (!empty($params['search_id']) && !empty($params['group_id'])) {
      $mg->reset();
      $mg->mailing_id = $mailing->id;
      $mg->entity_table = $groupTableName;
      $mg->entity_id = $params['group_id'];
      $mg->search_id = $params['search_id'];
      $mg->search_args = $params['search_args'];
      $mg->group_type = 'Include';
      $mg->save();
    }

    // check and attach and files as needed
    CRM_Core_BAO_File::processAttachment($params, 'civicrm_mailing', $mailing->id);

    $transaction->commit();

    // These actions are really 'submit' not create actions.
    // In v4 of the api they are not available via CRUD. At some
    // point we will create a 'submit' function which will do the crud+submit
    // but for now only CRUD is available via v4 api.
    if (empty($params['skip_legacy_scheduling'])) {
      self::doSubmitActions($params, $mailing);
    }

    return $mailing;
  }

  /**
   * @deprecated
   *
   * @todo - this just does an sms has-body-text check now - it would be clearer just
   * to do this in the sms function that calls this & remove it.
   *
   * @param CRM_Mailing_DAO_Mailing|array $mailing
   *   The mailing which may or may not be sendable.
   * @return array
   *   List of error messages.
   */
  public static function checkSendable($mailing) {
    if (is_array($mailing)) {
      $params = $mailing;
      $mailing = new \CRM_Mailing_BAO_Mailing();
      $mailing->id = $params['id'] ?? NULL;
      if ($mailing->id) {
        $mailing->find(TRUE);
      }
      $mailing->copyValues($params);
    }
    $errors = [];
    if (empty($mailing->body_text)) {
      $errors['body'] = ts('Field "body_text" is required.');
    }
    return $errors;
  }

  /**
   * Replace the list of recipients on a given mailing.
   *
   * @param int $mailingId
   * @param string $type
   *   'include' or 'exclude'.
   * @param string $entity
   *   'groups' or 'mailings'.
   * @param array $entityIds
   * @throws CRM_Core_Exception
   */
  public static function replaceGroups($mailingId, $type, $entity, $entityIds) {
    $values = [];
    foreach ($entityIds as $entityId) {
      $values[] = ['entity_id' => $entityId];
    }
    civicrm_api3('mailing_group', 'replace', [
      'mailing_id' => $mailingId,
      'group_type' => $type,
      'entity_table' => ($entity === 'groups') ? CRM_Contact_BAO_Group::getTableName() : CRM_Mailing_BAO_Mailing::getTableName(),
      'values' => $values,
    ]);
  }

  /**
   * Get hash value of the mailing.
   *
   * @param $id
   *
   * @return null|string
   */
  public static function getMailingHash($id) {
    $hash = NULL;
    if (Civi::settings()->get('hash_mailing_url') && !empty($id)) {
      $hash = CRM_Core_DAO::getFieldValue('CRM_Mailing_BAO_Mailing', $id, 'hash', 'id');
    }
    return $hash;
  }

  /**
   * Generate a report.  Fetch event count information, mailing data, and job
   * status.
   *
   * @param int $id
   *   The mailing id to report.
   * @param bool $skipDetails
   *   Whether return all detailed report.
   * @param bool $isSMS
   *   Deprecated argument, will be removed.
   *
   * @return array
   *   Associative array of reporting data
   */
  public static function report($id, $skipDetails = FALSE, $isSMS = FALSE) {
    $mailing_id = CRM_Utils_Type::escape($id, 'Integer');
    $mailing = new CRM_Mailing_BAO_Mailing();

    if ($isSMS) {
      CRM_Core_Error::deprecatedFunctionWarning("isSMS param is deprecated");
    }

    $t = [
      'mailing' => self::getTableName(),
      'mailing_group' => CRM_Mailing_DAO_MailingGroup::getTableName(),
      'group' => CRM_Contact_BAO_Group::getTableName(),
      'queue' => CRM_Mailing_Event_BAO_MailingEventQueue::getTableName(),
      'delivered' => CRM_Mailing_Event_BAO_MailingEventDelivered::getTableName(),
      'opened' => CRM_Mailing_Event_BAO_MailingEventOpened::getTableName(),
      'reply' => CRM_Mailing_Event_BAO_MailingEventReply::getTableName(),
      'unsubscribe' => CRM_Mailing_Event_BAO_MailingEventUnsubscribe::getTableName(),
      'bounce' => CRM_Mailing_Event_BAO_MailingEventBounce::getTableName(),
      'url' => CRM_Mailing_BAO_MailingTrackableURL::getTableName(),
      'urlopen' => CRM_Mailing_Event_BAO_MailingEventTrackableURLOpen::getTableName(),
      'component' => CRM_Mailing_BAO_MailingComponent::getTableName(),
    ];

    // Get the mailing info
    $mailing->query("
            SELECT          {$t['mailing']}.*
            FROM            {$t['mailing']}
            WHERE           {$t['mailing']}.id = $mailing_id");
    $mailing->fetch();

    $report = [];
    $report['mailing'] = [];
    foreach (array_keys(self::fields()) as $field) {
      $field = self::fields()[$field]['name'];
      $report['mailing'][$field] = $mailing->$field;
    }

    // Get the campaign
    $campaignId = $report['mailing']['campaign_id'] ?? NULL;
    if ($campaignId) {
      $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns($campaignId);
      $report['mailing']['campaign'] = $campaigns[$campaignId];
    }

    //mailing report is called by activity
    //we dont need all detail report
    if ($skipDetails) {
      return $report;
    }

    // Get the component info
    $query = [];

    $components = [
      'header' => ts('Header'),
      'footer' => ts('Footer'),
      'reply' => ts('Reply'),
      'optout' => ts('Opt-Out'),
      'resubscribe' => ts('Resubscribe'),
      'unsubscribe' => ts('Unsubscribe'),
    ];
    foreach (array_keys($components) as $type) {
      $query[] = "SELECT          {$t['component']}.name as name,
                                        '$type' as type,
                                        {$t['component']}.id as id
                        FROM            {$t['component']}
                        INNER JOIN      {$t['mailing']}
                                ON      {$t['mailing']}.{$type}_id =
                                                {$t['component']}.id
                        WHERE           {$t['mailing']}.id = $mailing_id";
    }
    $q = '(' . implode(') UNION (', $query) . ')';
    $mailing->query($q);

    $report['component'] = [];
    while ($mailing->fetch()) {
      $report['component'][] = [
        'type' => $components[$mailing->type],
        'name' => $mailing->name,
        'link' => CRM_Utils_System::url('civicrm/mailing/component', "reset=1&action=update&id={$mailing->id}"),
      ];
    }

    // Get the recipient group info
    $mailing->query("
            SELECT          {$t['mailing_group']}.group_type as group_type,
                            {$t['group']}.id as group_id,
                            {$t['group']}.title as group_title,
                            {$t['group']}.is_hidden as group_hidden,
                            {$t['mailing']}.id as mailing_id,
                            {$t['mailing']}.name as mailing_name
            FROM            {$t['mailing_group']}
            LEFT JOIN       {$t['group']}
                    ON      {$t['mailing_group']}.entity_id = {$t['group']}.id
                    AND     {$t['mailing_group']}.entity_table =
                                                                '{$t['group']}'
            LEFT JOIN       {$t['mailing']}
                    ON      {$t['mailing_group']}.entity_id =
                                                            {$t['mailing']}.id
                    AND     {$t['mailing_group']}.entity_table =
                                                            '{$t['mailing']}'

            WHERE           {$t['mailing_group']}.mailing_id = $mailing_id
            ");

    $report['group'] = ['include' => [], 'exclude' => [], 'base' => []];
    while ($mailing->fetch()) {
      $row = [];
      if (isset($mailing->group_id)) {
        $row['id'] = $mailing->group_id;
        $row['name'] = $mailing->group_title;
        $row['mailing'] = FALSE;
        $row['link'] = CRM_Utils_System::url('civicrm/group/search',
          "reset=1&force=1&context=smog&gid={$row['id']}"
        );
      }
      else {
        $row['id'] = $mailing->mailing_id;
        $row['name'] = $mailing->mailing_name;
        $row['mailing'] = TRUE;
        $row['link'] = CRM_Utils_System::url('civicrm/mailing/report',
          "mid={$row['id']}"
        );
      }

      // Rename hidden groups
      if ($mailing->group_hidden == 1) {
        $row['name'] = "Search Results";
      }

      if ($mailing->group_type === 'Include') {
        $report['group']['include'][] = $row;
      }
      elseif ($mailing->group_type === 'Base') {
        $report['group']['base'][] = $row;
      }
      else {
        $report['group']['exclude'][] = $row;
      }
    }

    // Get the event totals, grouped by job (retries)
    $mailing->query("
            SELECT          civicrm_mailing_job.*,
                            COUNT(DISTINCT {$t['queue']}.id) as queue,
                            COUNT(DISTINCT {$t['delivered']}.id) as delivered,
                            COUNT(DISTINCT {$t['reply']}.id) as reply,
                            COUNT(DISTINCT {$t['bounce']}.id) as bounce,
                            COUNT(DISTINCT {$t['urlopen']}.id) as url,
                            COUNT(DISTINCT civicrm_mailing_spool.id) as spool
            FROM            civicrm_mailing_job
            LEFT JOIN       {$t['queue']}
                    ON      {$t['queue']}.job_id = civicrm_mailing_job.id
            LEFT JOIN       {$t['reply']}
                    ON      {$t['reply']}.event_queue_id = {$t['queue']}.id
            LEFT JOIN       {$t['bounce']}
                    ON      {$t['bounce']}.event_queue_id = {$t['queue']}.id
            LEFT JOIN       {$t['delivered']}
                    ON      {$t['delivered']}.event_queue_id = {$t['queue']}.id
                    AND     {$t['bounce']}.id IS null
            LEFT JOIN       {$t['urlopen']}
                    ON      {$t['urlopen']}.event_queue_id = {$t['queue']}.id
            LEFT JOIN       civicrm_mailing_spool
                    ON      civicrm_mailing_spool.job_id = civicrm_mailing_job.id
            WHERE           civicrm_mailing_job.mailing_id = $mailing_id
                    AND     civicrm_mailing_job.is_test = 0
            GROUP BY        civicrm_mailing_job.id");

    $report['jobs'] = [];
    $report['event_totals'] = [];
    $path = 'civicrm/mailing/report/event';
    $elements = [
      'recipients',
      'queue',
      'delivered',
      'url',
      'reply',
      'unsubscribe',
      'optout',
      'opened',
      'total_opened',
      'bounce',
      'spool',
    ];

    // initialize various counters
    foreach ($elements as $field) {
      $report['event_totals'][$field] = 0;
    }

    while ($mailing->fetch()) {
      $row = [];
      foreach ($elements as $field) {
        if (isset($mailing->$field)) {
          $row[$field] = $mailing->$field;
          $report['event_totals'][$field] += $mailing->$field;
        }
      }

      // compute open total separately to discount duplicates
      // CRM-1258
      $row['opened'] = CRM_Mailing_Event_BAO_MailingEventOpened::getTotalCount($mailing_id, $mailing->id, TRUE);
      $report['event_totals']['opened'] += $row['opened'];
      $row['total_opened'] = CRM_Mailing_Event_BAO_MailingEventOpened::getTotalCount($mailing_id, $mailing->id);
      $report['event_totals']['total_opened'] += $row['total_opened'];

      // compute unsub total separately to discount duplicates
      // CRM-1783
      $row['unsubscribe'] = CRM_Mailing_Event_BAO_MailingEventUnsubscribe::getTotalCount($mailing_id, $mailing->id, TRUE, TRUE);
      $report['event_totals']['unsubscribe'] += $row['unsubscribe'];

      $row['optout'] = CRM_Mailing_Event_BAO_MailingEventUnsubscribe::getTotalCount($mailing_id, $mailing->id, TRUE, FALSE);
      $report['event_totals']['optout'] += $row['optout'];

      foreach (array_keys(CRM_Mailing_BAO_MailingJob::fields()) as $field) {
        // Get the field name from the MailingJob fields as that will not have any prefixing.
        // dev/mailing#56
        $field = CRM_Mailing_BAO_MailingJob::fields()[$field]['name'];
        $row[$field] = $mailing->$field;
      }

      if ($mailing->queue) {
        $row['delivered_rate'] = (100.0 * $mailing->delivered) / $mailing->queue;
        $row['bounce_rate'] = (100.0 * $mailing->bounce) / $mailing->queue;
        $row['unsubscribe_rate'] = (100.0 * $row['unsubscribe']) / $mailing->queue;
        $row['optout_rate'] = (100.0 * $row['optout']) / $mailing->queue;
        $row['opened_rate'] = $mailing->delivered ? (($row['opened'] / $mailing->delivered) * 100.0) : 0;
        $row['clickthrough_rate'] = $mailing->delivered ? (($mailing->url / $mailing->delivered) * 100.0) : 0;
      }
      else {
        $row['delivered_rate'] = 0;
        $row['bounce_rate'] = 0;
        $row['unsubscribe_rate'] = 0;
        $row['optout_rate'] = 0;
        $row['opened_rate'] = 0;
        $row['clickthrough_rate'] = 0;
      }

      $arg = "reset=1&mid=$mailing_id&jid={$mailing->id}";
      $row['links'] = [
        'clicks' => CRM_Utils_System::url($path, "$arg&event=click"),
        'queue' => CRM_Utils_System::url($path, "$arg&event=queue"),
        'delivered' => CRM_Utils_System::url($path, "$arg&event=delivered"),
        'bounce' => CRM_Utils_System::url($path, "$arg&event=bounce"),
        'unsubscribe' => CRM_Utils_System::url($path, "$arg&event=unsubscribe"),
        'reply' => CRM_Utils_System::url($path, "$arg&event=reply"),
        'opened' => CRM_Utils_System::url($path, "$arg&event=opened"),
      ];

      foreach (['scheduled_date', 'start_date', 'end_date'] as $key) {
        $row[$key] = CRM_Utils_Date::customFormat($row[$key]);
      }
      $report['jobs'][] = $row;
    }

    $report['event_totals']['recipients'] = CRM_Mailing_BAO_MailingRecipients::mailingSize($mailing_id);

    if (!empty($report['event_totals']['queue'])) {
      $report['event_totals']['delivered_rate'] = (100.0 * $report['event_totals']['delivered']) / $report['event_totals']['queue'];
      $report['event_totals']['bounce_rate'] = (100.0 * $report['event_totals']['bounce']) / $report['event_totals']['queue'];
      $report['event_totals']['unsubscribe_rate'] = (100.0 * $report['event_totals']['unsubscribe']) / $report['event_totals']['queue'];
      $report['event_totals']['optout_rate'] = (100.0 * $report['event_totals']['optout']) / $report['event_totals']['queue'];
      $report['event_totals']['opened_rate'] = !empty($report['event_totals']['delivered']) ? (($report['event_totals']['opened'] / $report['event_totals']['delivered']) * 100.0) : 0;
      $report['event_totals']['clickthrough_rate'] = !empty($report['event_totals']['delivered']) ? (($report['event_totals']['url'] / $report['event_totals']['delivered']) * 100.0) : 0;
    }
    else {
      $report['event_totals']['delivered_rate'] = 0;
      $report['event_totals']['bounce_rate'] = 0;
      $report['event_totals']['unsubscribe_rate'] = 0;
      $report['event_totals']['optout_rate'] = 0;
      $report['event_totals']['opened_rate'] = 0;
      $report['event_totals']['clickthrough_rate'] = 0;
    }

    // Get the click-through totals, grouped by URL
    $mailing->query("
            SELECT      {$t['url']}.url,
                        {$t['url']}.id,
                        COUNT({$t['urlopen']}.id) as clicks,
                        COUNT(DISTINCT {$t['queue']}.id) as unique_clicks
            FROM        {$t['url']}
            LEFT JOIN   {$t['urlopen']}
                    ON  {$t['urlopen']}.trackable_url_id = {$t['url']}.id
            LEFT JOIN  {$t['queue']}
                    ON  {$t['urlopen']}.event_queue_id = {$t['queue']}.id
            LEFT JOIN  civicrm_mailing_job
                    ON  {$t['queue']}.job_id = civicrm_mailing_job.id
            WHERE       {$t['url']}.mailing_id = $mailing_id
                    AND civicrm_mailing_job.is_test = 0
            GROUP BY    {$t['url']}.id
            ORDER BY    unique_clicks DESC");

    $report['click_through'] = [];

    while ($mailing->fetch()) {
      $report['click_through'][] = [
        'url' => $mailing->url,
        'link' => CRM_Utils_System::url($path, "reset=1&event=click&mid=$mailing_id&uid={$mailing->id}"),
        'link_unique' => CRM_Utils_System::url($path, "reset=1&event=click&mid=$mailing_id&uid={$mailing->id}&distinct=1"),
        'clicks' => $mailing->clicks,
        'unique' => $mailing->unique_clicks,
        'rate' => !empty($report['event_totals']['delivered']) ? (100.0 * $mailing->unique_clicks) / $report['event_totals']['delivered'] : 0,
        'report' => CRM_Report_Utils_Report::getNextUrl('mailing/clicks', "reset=1&mailing_id_value={$mailing_id}&url_value=" . rawurlencode($mailing->url), FALSE, TRUE),
      ];
    }

    $arg = "reset=1&mid=$mailing_id";
    $report['event_totals']['links'] = [
      'clicks' => CRM_Utils_System::url($path, "$arg&event=click"),
      'clicks_unique' => CRM_Utils_System::url($path, "$arg&event=click&distinct=1"),
      'queue' => CRM_Utils_System::url($path, "$arg&event=queue"),
      'delivered' => CRM_Utils_System::url($path, "$arg&event=delivered"),
      'bounce' => CRM_Utils_System::url($path, "$arg&event=bounce"),
      'unsubscribe' => CRM_Utils_System::url($path, "$arg&event=unsubscribe"),
      'optout' => CRM_Utils_System::url($path, "$arg&event=optout"),
      'reply' => CRM_Utils_System::url($path, "$arg&event=reply"),
      'opened' => CRM_Utils_System::url($path, "$arg&event=opened"),
    ];

    $actionLinks = [CRM_Core_Action::VIEW => ['name' => ts('Report')]];
    $actionLinks[CRM_Core_Action::ADVANCED] = [
      'name' => ts('Advanced Search'),
      'url' => 'civicrm/contact/search/advanced',
    ];
    $action = array_sum(array_keys($actionLinks));

    $report['event_totals']['actionlinks'] = [];
    foreach (['clicks', 'clicks_unique', 'queue', 'delivered', 'bounce', 'unsubscribe', 'reply', 'opened', 'opened_unique', 'optout'] as $key) {
      $url = 'mailing/detail';
      $reportFilter = "reset=1&mailing_id_value={$mailing_id}";
      $searchFilter = "force=1&mailing_id=%%mid%%";
      switch ($key) {
        case 'delivered':
          $reportFilter .= "&delivery_status_value=successful";
          $searchFilter .= "&mailing_delivery_status=Y";
          break;

        case 'bounce':
          $url = "mailing/bounce";
          $searchFilter .= "&mailing_delivery_status=N";
          break;

        case 'reply':
          $reportFilter .= "&is_replied_value=1";
          $searchFilter .= "&mailing_reply_status=Y";
          break;

        case 'unsubscribe':
          $reportFilter .= "&is_unsubscribed_value=1";
          $searchFilter .= "&mailing_unsubscribe=1";
          break;

        case 'optout':
          $reportFilter .= "&is_optout_value=1";
          $searchFilter .= "&mailing_optout=1";
          break;

        case 'opened':
          // do not use group by clause in report, because same report used for total and unique open
          $reportFilter .= '&distinct=0';
        case 'opened_unique':
          $url = 'mailing/opened';
          $searchFilter .= '&mailing_open_status=Y';
          break;

        case 'clicks':
        case 'clicks_unique':
          $url = 'mailing/clicks';
          $searchFilter .= '&mailing_click_status=Y';
          break;
      }
      $actionLinks[CRM_Core_Action::VIEW]['url'] = CRM_Report_Utils_Report::getNextUrl($url, $reportFilter, FALSE, TRUE);
      $actionLinks[CRM_Core_Action::VIEW]['weight'] = -20;
      if (array_key_exists(CRM_Core_Action::ADVANCED, $actionLinks)) {
        $actionLinks[CRM_Core_Action::ADVANCED]['qs'] = $searchFilter;
        $actionLinks[CRM_Core_Action::ADVANCED]['weight'] = 10;
      }
      $report['event_totals']['actionlinks'][$key] = CRM_Core_Action::formLink(
        $actionLinks,
        $action,
        ['mid' => $mailing_id],
        ts('more'),
        FALSE,
        'mailing.report.action',
        'Mailing',
        $mailing_id
      );
    }

    return $report;
  }

  /**
   * Get the count of mailings.
   *
   * @return int
   *   Count
   */
  public function getCount() {
    $this->selectAdd();
    $this->selectAdd('COUNT(id) as count');
    $this->find(TRUE);

    return $this->count;
  }

  /**
   * @param int $id
   *
   * @throws Exception
   */
  public static function checkPermission($id) {
    if (!$id) {
      return;
    }

    $mailingIDs = self::mailingACLIDs();
    if ($mailingIDs === TRUE) {
      return;
    }

    if (!in_array($id, $mailingIDs)) {
      throw new CRM_Core_Exception(ts('You do not have permission to access this mailing report'));
    }
  }

  /**
   * @param null $alias
   *
   * @return string
   */
  public static function mailingACL($alias = NULL) {
    $mailingACL = " ( 0 ) ";

    $mailingIDs = self::mailingACLIDs();
    if ($mailingIDs === TRUE) {
      return " ( 1 ) ";
    }

    if (!empty($mailingIDs)) {
      $mailingIDs = implode(',', $mailingIDs);
      $tableName = !$alias ? self::getTableName() : $alias;
      $mailingACL = " $tableName.id IN ( $mailingIDs ) ";
    }
    return $mailingACL;
  }

  /**
   * Returns all the mailings that this user can access. This is dependent on
   * all the groups that the user has access to.
   * However since most civi installs dont use ACL's we special case the condition
   * where the user has access to ALL groups, and hence ALL mailings and return a
   * value of TRUE (to avoid the downstream where clause with a list of mailing list IDs
   *
   * @return bool|array
   *   TRUE if the user has access to all mailings, else array of mailing IDs (possibly empty).
   */
  public static function mailingACLIDs() {
    // CRM-11633
    // optimize common case where admin has access
    // to all mailings
    if (
      CRM_Core_Permission::check('view all contacts') ||
      CRM_Core_Permission::check('edit all contacts')
    ) {
      return TRUE;
    }

    $mailingIDs = [];

    // get all the groups that this user can access
    // if they dont have universal access
    $groupNames = civicrm_api3('Group', 'get', [
      'check_permissions' => TRUE,
      'return' => ['title', 'id'],
      'options' => ['limit' => 0],
    ]);
    foreach ($groupNames['values'] as $group) {
      $groups[$group['id']] = $group['title'];
    }
    if (!empty($groups)) {
      $groupIDs = implode(',', array_keys($groups));
      $domain_id = CRM_Core_Config::domainID();

      // get all the mailings that are in this subset of groups
      $query = "
SELECT    DISTINCT( m.id ) as id
  FROM    civicrm_mailing m
LEFT JOIN civicrm_mailing_group g ON g.mailing_id   = m.id
 WHERE ( ( g.entity_table like 'civicrm_group%' AND g.entity_id IN ( $groupIDs ) )
    OR   ( g.entity_table IS NULL AND g.entity_id IS NULL AND m.domain_id = $domain_id ) )
";
      $dao = CRM_Core_DAO::executeQuery($query);

      $mailingIDs = [];
      while ($dao->fetch()) {
        $mailingIDs[] = $dao->id;
      }
      //CRM-18181 Get all mailings that use the mailings found earlier as receipients
      if (!empty($mailingIDs)) {
        $mailings = implode(',', $mailingIDs);
        $mailingQuery = "
           SELECT DISTINCT ( m.id ) as id
           FROM civicrm_mailing m
           LEFT JOIN civicrm_mailing_group g ON g.mailing_id = m.id
           WHERE g.entity_table like 'civicrm_mailing%' AND g.entity_id IN ($mailings)";
        $mailingDao = CRM_Core_DAO::executeQuery($mailingQuery);
        while ($mailingDao->fetch()) {
          $mailingIDs[] = $mailingDao->id;
        }
      }
    }

    return $mailingIDs;
  }

  /**
   * Get the rows for a browse operation.
   *
   * @param int $offset
   *   The row number to start from.
   * @param int $rowCount
   *   The number of rows to return.
   * @param string $sort
   *   The sql string that describes the sort order.
   *
   * @param null $additionalClause
   * @param array $additionalParams
   *
   * @return array
   *   The rows
   */
  public function &getRows($offset, $rowCount, $sort, $additionalClause = NULL, $additionalParams = NULL) {
    $mailing = self::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();
    $mailingACL = self::mailingACL();

    //get all campaigns.
    $allCampaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);
    $select = [
      "$mailing.id",
      "$mailing.name",
      "$job.status",
      "$mailing.approval_status_id",
      "createdContact.sort_name as created_by",
      "scheduledContact.sort_name as scheduled_by",
      "$mailing.created_id as created_id",
      "$mailing.scheduled_id as scheduled_id",
      "$mailing.is_archived as archived",
      "$mailing.created_date as created_date",
      "campaign_id",
      "$mailing.sms_provider_id as sms_provider_id",
      "$mailing.language",
    ];

    // we only care about parent jobs, since that holds all the info on
    // the mailing
    $selectClause = implode(', ', $select);
    $groupFromSelect = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($select, "$mailing.id");
    $query = "
            SELECT      {$selectClause},
                        MIN($job.scheduled_date) as scheduled_date,
                        MIN($job.start_date) as start_date,
                        MAX($job.end_date) as end_date
            FROM        $mailing
            LEFT JOIN   $job ON ( $job.mailing_id = $mailing.id AND $job.is_test = 0 AND $job.parent_id IS NULL )
            LEFT JOIN   civicrm_contact createdContact ON ( civicrm_mailing.created_id = createdContact.id )
            LEFT JOIN   civicrm_contact scheduledContact ON ( civicrm_mailing.scheduled_id = scheduledContact.id )
            WHERE       $mailingACL $additionalClause";

    if (!empty($groupFromSelect)) {
      $query .= $groupFromSelect;
    }

    if ($sort) {
      $orderBy = trim($sort->orderBy());
      if (!empty($orderBy)) {
        $query .= " ORDER BY $orderBy";
      }
    }

    if ($rowCount) {
      $offset = CRM_Utils_Type::escape($offset, 'Int');
      $rowCount = CRM_Utils_Type::escape($rowCount, 'Int');

      $query .= " LIMIT $offset, $rowCount ";
    }

    if (!$additionalParams) {
      $additionalParams = [];
    }

    $dao = CRM_Core_DAO::executeQuery($query, $additionalParams);

    $rows = [];
    while ($dao->fetch()) {
      $rows[] = [
        'id' => $dao->id,
        'name' => $dao->name,
        'status' => $dao->status ?: 'Not scheduled',
        'created_date' => CRM_Utils_Date::customFormat($dao->created_date),
        'scheduled' => CRM_Utils_Date::customFormat($dao->scheduled_date),
        'scheduled_iso' => $dao->scheduled_date,
        'start' => CRM_Utils_Date::customFormat($dao->start_date),
        'end' => CRM_Utils_Date::customFormat($dao->end_date),
        'created_by' => $dao->created_by,
        'scheduled_by' => $dao->scheduled_by,
        'created_id' => $dao->created_id,
        'scheduled_id' => $dao->scheduled_id,
        'archived' => $dao->archived,
        'approval_status_id' => $dao->approval_status_id,
        'campaign_id' => $dao->campaign_id,
        'campaign' => empty($dao->campaign_id) ? NULL : $allCampaigns[$dao->campaign_id],
        'sms_provider_id' => $dao->sms_provider_id,
        'language' => $dao->language,
      ];
    }
    return $rows;
  }

  /**
   * Show detail Mailing report.
   *
   * @param int $id
   *
   * @return string
   */
  public static function showEmailDetails($id) {
    return CRM_Utils_System::url('civicrm/mailing/report', "mid=$id");
  }

  /**
   * Delete Mails and all its associated records.
   *
   * @param int $id
   *   Id of the mail to delete.
   *
   * @return void
   *
   * @deprecated
   */
  public static function del($id) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    static::deleteRecord(['id' => $id]);
  }

  /**
   * Callback for hook_civicrm_pre().
   * @param \Civi\Core\Event\PreEvent $event
   * @throws CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if ($event->action === 'create') {
      $params = &$event->params;
      $params['created_id'] ??= CRM_Core_Session::singleton()->getLoggedInContactID();
      $params['override_verp'] ??= !Civi::settings()->get('track_civimail_replies');
      $params['visibility'] ??= 'Public Pages';
      $params['dedupe_email'] ??= Civi::settings()->get('dedupe_email_default');
      $params['open_tracking'] ??= Civi::settings()->get('open_tracking_default');
      $params['url_tracking'] ??= Civi::settings()->get('url_tracking_default');
      if (CRM_Utils_System::isNull($params['sms_provider_id'] ?? NULL)) {
        $params['header_id'] ??= CRM_Mailing_PseudoConstant::defaultComponent('Header', '');
        $params['footer_id'] ??= CRM_Mailing_PseudoConstant::defaultComponent('Footer', '');
      }
      $params['optout_id'] ??= CRM_Mailing_PseudoConstant::defaultComponent('OptOut', '');
      $params['reply_id'] ??= CRM_Mailing_PseudoConstant::defaultComponent('Reply', '');
      $params['resubscribe_id'] ??= CRM_Mailing_PseudoConstant::defaultComponent('Resubscribe', '');
      $params['unsubscribe_id'] ??= CRM_Mailing_PseudoConstant::defaultComponent('Unsubscribe', '');
      $params['mailing_type'] ??= 'standalone';
      $params['status'] ??= 'Draft';
      $params['start_date'] ??= 'null';
      $params['end_date'] ??= 'null';
    }
    if ($event->action === 'delete' && $event->id) {
      // Delete all file attachments
      CRM_Core_BAO_File::deleteEntityFile('civicrm_mailing', $event->id);
    }
  }

  /**
   * @deprecated
   *   This is used by CiviMail but will be made redundant by FlexMailer/TokenProcessor.
   * @return array
   */
  public function getReturnProperties() {
    $tokens = &$this->getTokens();
    CRM_Core_Error::deprecatedWarning('function no longer called - use flexmailer');
    $properties = [];
    if (isset($tokens['html']) &&
      isset($tokens['html']['contact'])
    ) {
      $properties = array_merge($properties, $tokens['html']['contact']);
    }

    if (isset($tokens['text']) &&
      isset($tokens['text']['contact'])
    ) {
      $properties = array_merge($properties, $tokens['text']['contact']);
    }

    if (isset($tokens['subject']) &&
      isset($tokens['subject']['contact'])
    ) {
      $properties = array_merge($properties, $tokens['subject']['contact']);
    }

    $returnProperties = [];
    $returnProperties['display_name'] = $returnProperties['contact_id'] = $returnProperties['hash'] = 1;

    foreach ($properties as $p) {
      $returnProperties[$p] = 1;
    }

    return $returnProperties;
  }

  /**
   * Build the  compose mail form.
   *
   * @param CRM_Core_Form $form
   *
   * @return void
   */
  public static function commonCompose(&$form) {
    //get the tokens.
    $tokens = [];

    if (method_exists($form, 'listTokens')) {
      $tokens = array_merge($form->listTokens(), $tokens);
    }

    //sorted in ascending order tokens by ignoring word case
    $form->assign('tokens', CRM_Utils_Token::formatTokensForDisplay($tokens));

    $templates = [];

    $textFields = [
      'text_message' => ts('HTML Format'),
      'sms_text_message' => ts('SMS Message'),
    ];
    $modePrefixes = ['Mail' => NULL, 'SMS' => 'SMS'];

    $className = CRM_Utils_System::getClassName($form);

    if ($className != 'CRM_SMS_Form_Upload' && $className != 'CRM_Contact_Form_Task_SMS' &&
      $className != 'CRM_Contact_Form_Task_SMS'
    ) {
      $form->add('wysiwyg', 'html_message',
        strstr($className, 'PDF') ? ts('Document Body') : ts('HTML Format'),
        [
          'cols' => '80',
          'rows' => '8',
          'onkeyup' => "return verify(this)",
        ]
      );

      if ($className != 'CRM_Admin_Form_ScheduleReminders') {
        unset($modePrefixes['SMS']);
      }
    }
    else {
      unset($textFields['text_message']);
      unset($modePrefixes['Mail']);
    }

    //insert message Text by selecting "Select Template option"
    foreach ($textFields as $id => $label) {
      $prefix = NULL;
      if ($id == 'sms_text_message') {
        $prefix = "SMS";
        $form->assign('max_sms_length', CRM_SMS_Provider::MAX_SMS_CHAR);
      }
      $form->add('textarea', $id, $label,
        [
          'cols' => '80',
          'rows' => '8',
          'onkeyup' => "return verify(this, '{$prefix}')",
        ]
      );
    }

    foreach ($modePrefixes as $prefix) {
      if ($prefix == 'SMS') {
        $templates[$prefix] = CRM_Core_BAO_MessageTemplate::getMessageTemplates(FALSE, TRUE);
      }
      else {
        $templates[$prefix] = CRM_Core_BAO_MessageTemplate::getMessageTemplates(FALSE);
      }
      if (!empty($templates[$prefix])) {
        $form->assign('templates', TRUE);

        $form->add('select', "{$prefix}template", ts('Use Template'),
          ['' => ts('- select -')] + $templates[$prefix], FALSE,
          ['onChange' => "selectValue( this.value, '{$prefix}');", 'class' => 'crm-select2 huge']
        );
      }
      if (\CRM_Core_Permission::check('edit message templates')) {
        $form->add('checkbox', "{$prefix}updateTemplate", ts('Update Template'), NULL);
        $form->add('checkbox', "{$prefix}saveTemplate", ts('Save As New Template'), ['onclick' => "showSaveDetails(this, '{$prefix}');"]);
        $form->add('text', "{$prefix}saveTemplateName", ts('Template Title'));
      }
    }

    // I'm not sure this is ever called.
    $action = CRM_Utils_Request::retrieve('action', 'String', $form, FALSE);
    if ((CRM_Utils_System::getClassName($form) == 'CRM_Contact_Form_Task_PDF') &&
      $action == CRM_Core_Action::VIEW
    ) {
      $form->freeze('html_message');
    }
  }

  /**
   * Get the search based mailing Ids.
   *
   * @return array
   *   , searched base mailing ids.
   */
  public function searchMailingIDs() {
    $group = CRM_Mailing_DAO_MailingGroup::getTableName();
    $mailing = self::getTableName();

    $query = "
SELECT  $mailing.id as mailing_id
  FROM  $mailing, $group
 WHERE  $group.mailing_id = $mailing.id
   AND  $group.group_type = 'Base'";

    $searchDAO = CRM_Core_DAO::executeQuery($query);
    $mailingIDs = [];
    while ($searchDAO->fetch()) {
      $mailingIDs[] = $searchDAO->mailing_id;
    }

    return $mailingIDs;
  }

  /**
   * Get the content/components of mailing based on mailing Id
   *
   * @param array $report
   *   of mailing report.
   *
   * @param $form
   *   Reference of this.
   *
   * @param bool $isSMS
   *
   * @return array
   *   array content/component.
   */
  public static function getMailingContent(&$report, &$form, $isSMS = FALSE) {
    $mailingKey = $form->_mailing_id;
    if (!$isSMS) {
      if ($hash = CRM_Mailing_BAO_Mailing::getMailingHash($mailingKey)) {
        $mailingKey = $hash;
      }
    }

    if (!empty($report['mailing']['body_text'])) {
      $url = CRM_Utils_System::url('civicrm/mailing/view', 'reset=1&text=1&id=' . $mailingKey);
      $form->assign('textViewURL', $url);
    }

    if (!$isSMS) {
      if (!empty($report['mailing']['body_html'])) {
        $url = CRM_Utils_System::url('civicrm/mailing/view', 'reset=1&id=' . $mailingKey);
        $form->assign('htmlViewURL', $url);
      }
    }

    if (!$isSMS) {
      $report['mailing']['attachment'] = CRM_Core_BAO_File::attachmentInfo('civicrm_mailing', $form->_mailing_id);
    }
    return $report;
  }

  /**
   * @param int $jobID
   *
   * @return mixed
   */
  public static function overrideVerp($jobID) {
    static $_cache = [];

    if (!isset($_cache[$jobID])) {
      $query = "
SELECT     override_verp
FROM       civicrm_mailing
INNER JOIN civicrm_mailing_job ON civicrm_mailing.id = civicrm_mailing_job.mailing_id
WHERE  civicrm_mailing_job.id = %1
";
      $params = [1 => [$jobID, 'Integer']];
      $_cache[$jobID] = CRM_Core_DAO::singleValueQuery($query, $params);
    }
    return $_cache[$jobID];
  }

  /**
   * @param string|null $mode
   *   Either 'sms' or null
   *
   * @return bool
   * @throws Exception
   */
  public static function processQueue($mode = NULL) {
    $config = CRM_Core_Config::singleton();

    if ($mode == NULL && CRM_Core_BAO_MailSettings::defaultDomain() == "EXAMPLE.ORG") {
      // Using forceBackend=TRUE because WordPress sometimes fails to detect cron
      throw new CRM_Core_Exception(ts('The <a href="%1">default mailbox</a> has not been configured. You will find <a href="%2">more info in the online system administrator guide</a>', [
        1 => CRM_Utils_System::url('civicrm/admin/mailSettings', 'reset=1', FALSE, NULL, TRUE, FALSE, TRUE),
        2 => "https://docs.civicrm.org/sysadmin/en/latest/setup/civimail/",
      ]));
    }

    // check if we are enforcing number of parallel cron jobs
    // CRM-8460
    $gotCronLock = FALSE;

    $mailerJobsMax = Civi::settings()->get('mailerJobsMax');
    if (is_numeric($mailerJobsMax) && $mailerJobsMax > 0) {
      $lockArray = range(1, $mailerJobsMax);

      // Shuffle the array to improve chances of quickly finding an open thread
      shuffle($lockArray);

      // Check if we are using global locks
      foreach ($lockArray as $lockID) {
        $cronLock = Civi::lockManager()
          ->acquire("worker.mailing.send.{$lockID}");
        if ($cronLock->isAcquired()) {
          $gotCronLock = TRUE;
          break;
        }
      }

      // Exit here since we have enough mailing processes running
      if (!$gotCronLock) {
        CRM_Core_Error::debug_log_message('Returning early, since the maximum number of mailing processes are running');
        return TRUE;
      }

      if (getenv('CIVICRM_CRON_HOLD')) {
        // In testing, we may need to simulate some slow activities.
        sleep(getenv('CIVICRM_CRON_HOLD'));
      }
    }

    // Split up the parent jobs into multiple child jobs
    $mailerJobSize = (int) Civi::settings()->get('mailerJobSize');
    CRM_Mailing_BAO_MailingJob::runJobs_pre($mailerJobSize, $mode);
    CRM_Mailing_BAO_MailingJob::runJobs(NULL, $mode);
    CRM_Mailing_BAO_MailingJob::runJobs_post($mode);

    // Release the global lock if we do have one
    if ($gotCronLock) {
      $cronLock->release();
    }

    return TRUE;
  }

  /**
   * @param int $mailingID
   */
  private static function addMultipleEmails($mailingID) {
    $sql = "
INSERT INTO civicrm_mailing_recipients
    (mailing_id, email_id, contact_id)
SELECT %1, e.id, e.contact_id FROM civicrm_email e
WHERE  e.on_hold = 0
AND    e.is_bulkmail = 1
AND    e.contact_id IN
    ( SELECT contact_id FROM civicrm_mailing_recipients mr WHERE mailing_id = %1 )
AND    e.id NOT IN ( SELECT email_id FROM civicrm_mailing_recipients mr WHERE mailing_id = %1 )
";
    $params = [1 => [$mailingID, 'Integer']];

    $dao = CRM_Core_DAO::executeQuery($sql, $params);
  }

  /**
   * @param bool $isSMS
   *
   * @return mixed
   */
  public static function getMailingsList($isSMS = FALSE) {
    static $list = [];
    $where = " WHERE ";
    if (!$isSMS) {
      $where .= " civicrm_mailing.sms_provider_id IS NULL ";
    }
    else {
      $where .= " civicrm_mailing.sms_provider_id IS NOT NULL ";
    }

    if (empty($list)) {
      $query = "
SELECT civicrm_mailing.id, civicrm_mailing.name, civicrm_mailing_job.end_date
FROM   civicrm_mailing
INNER JOIN civicrm_mailing_job ON civicrm_mailing.id = civicrm_mailing_job.mailing_id {$where}
ORDER BY civicrm_mailing.id DESC";
      $mailing = CRM_Core_DAO::executeQuery($query);

      while ($mailing->fetch()) {
        $list[$mailing->id] = "{$mailing->name} :: {$mailing->end_date}";
      }
    }

    return $list;
  }

  /**
   * wrapper for ajax activity selector.
   *
   * @param array $params
   *   Associated array for params record id.
   *
   * @return array
   *   associated array of contact activities
   */
  public static function getContactMailingSelector(&$params) {
    // format the params
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];
    $params['sort'] = $params['sortBy'] ?? NULL;
    $params['caseId'] = NULL;

    // get contact mailings
    $mailings = CRM_Mailing_BAO_Mailing::getContactMailings($params);

    // add total
    $params['total'] = CRM_Mailing_BAO_Mailing::getContactMailingsCount($params);

    //CRM-12814
    if (!empty($mailings)) {
      $openCounts = CRM_Mailing_Event_BAO_MailingEventOpened::getMailingContactCount(array_keys($mailings), $params['contact_id']);
      $clickCounts = CRM_Mailing_Event_BAO_MailingEventTrackableURLOpen::getMailingContactCount(array_keys($mailings), $params['contact_id']);
    }

    // format params and add links
    $contactMailings = [];
    foreach ($mailings as $mailingId => $values) {
      $mailing = [];
      $mailing['subject'] = $values['subject'];
      $mailing['creator_name'] = CRM_Utils_System::href(
        $values['creator_name'],
        'civicrm/contact/view',
        "reset=1&cid={$values['creator_id']}");
      $mailing['recipients'] = CRM_Utils_System::href(ts('(recipients)'), 'civicrm/mailing/report/event',
        "mid={$values['mailing_id']}&reset=1&cid={$params['contact_id']}&event=queue&context=mailing");
      $mailing['start_date'] = CRM_Utils_Date::customFormat($values['start_date']);
      //CRM-12814
      $clicks = $clickCounts[$values['mailing_id']] ?? 0;
      $opens = $openCounts[$values['mailing_id']] ?? 0;
      $mailing['openstats'] = ts('Opens: %1', [1 => $opens]) . '<br />' .
        ts('Clicks: %1', [1 => $clicks]);

      $actionLinks = [
        CRM_Core_Action::VIEW => [
          'name' => ts('View'),
          'url' => 'civicrm/mailing/view',
          'qs' => "reset=1&id=%%mkey%%&cid=%%cid%%&cs=%%cs%%",
          'title' => ts('View Mailing'),
          'class' => 'crm-popup',
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::VIEW),
        ],
        CRM_Core_Action::BROWSE => [
          'name' => ts('Mailing Report'),
          'url' => 'civicrm/mailing/report',
          'qs' => "mid=%%mid%%&reset=1&cid=%%cid%%&context=mailing",
          'title' => ts('View Mailing Report'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::BROWSE),
        ],
      ];

      $mailingKey = $values['mailing_id'];
      if ($hash = CRM_Mailing_BAO_Mailing::getMailingHash($mailingKey)) {
        $mailingKey = $hash;
      }

      $mailing['links'] = CRM_Core_Action::formLink(
        $actionLinks,
        NULL,
        [
          'mid' => $values['mailing_id'],
          'cid' => $params['contact_id'],
          'mkey' => $mailingKey,
          'cs' => CRM_Contact_BAO_Contact_Utils::generateChecksum($params['contact_id'], NULL, 'inf'),
        ],
        ts('more'),
        FALSE,
        'mailing.contact.action',
        'Mailing',
        $values['mailing_id']
      );

      array_push($contactMailings, $mailing);
    }

    $contactMailingsDT = [];
    $contactMailingsDT['data'] = $contactMailings;
    $contactMailingsDT['recordsTotal'] = $params['total'];
    $contactMailingsDT['recordsFiltered'] = $params['total'];

    return $contactMailingsDT;
  }

  /**
   * Retrieve contact mailing.
   *
   * @param array $params
   *
   * @return array
   *   Array of mailings for a contact
   *
   */
  public static function getContactMailings(&$params) {
    $params['version'] = 3;
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['limit'] = $params['rp'];
    $params['sort'] = $params['sortBy'] ?? NULL;

    $result = civicrm_api('MailingContact', 'get', $params);
    return $result['values'];
  }

  /**
   * Retrieve contact mailing count.
   *
   * @param array $params
   *
   * @return int
   *   count of mailings for a contact
   *
   */
  public static function getContactMailingsCount(&$params) {
    $params['version'] = 3;
    return civicrm_api('MailingContact', 'getcount', $params);
  }

  /**
   * Get a list of permissions required for CRUD'ing each field
   * (when workflow is enabled).
   *
   * @return array
   *   Array (string $fieldName => string $permName)
   */
  public static function getWorkflowFieldPerms() {
    $fieldNames = array_keys(CRM_Mailing_DAO_Mailing::fields());
    $fieldPerms = [];
    foreach ($fieldNames as $fieldName) {
      if ($fieldName == 'id') {
        $fieldPerms[$fieldName] = [
          // OR
          [
            'access CiviMail',
            'schedule mailings',
            'approve mailings',
          ],
        ];
      }
      elseif (in_array($fieldName, ['scheduled_date', 'scheduled_id'])) {
        $fieldPerms[$fieldName] = [
          // OR
          ['access CiviMail', 'schedule mailings'],
        ];
      }
      elseif (in_array($fieldName, [
        'approval_date',
        'approver_id',
        'approval_status_id',
        'approval_note',
      ])) {
        $fieldPerms[$fieldName] = [
          // OR
          ['access CiviMail', 'approve mailings'],
        ];
      }
      else {
        $fieldPerms[$fieldName] = [
          // OR
          ['access CiviMail', 'create mailings'],
        ];
      }
    }
    return $fieldPerms;
  }

  /**
   * White-list of possible values for the entity_table field.
   *
   * @return array
   */
  public static function mailingGroupEntityTables() {
    return [
      [
        'id' => CRM_Contact_BAO_Group::getTableName(),
        'name' => 'Group',
        'label' => ts('Group'),
      ],
      [
        'id' => CRM_Mailing_BAO_Mailing::getTableName(),
        'name' => 'Mailing',
        'label' => ts('Mailing'),
      ],
    ];
  }

  /**
   * Get the public view url.
   *
   * @param int $id
   * @param bool $absolute
   *
   * @return string
   */
  public static function getPublicViewUrl($id, $absolute = TRUE) {
    if ((civicrm_api3('Mailing', 'getvalue', [
      'id' => $id,
      'return' => 'visibility',
    ])) === 'Public Pages') {

      // if hash setting is on then we change the public url into a hash
      $hash = CRM_Mailing_BAO_Mailing::getMailingHash($id);
      if (!empty($hash)) {
        $id = $hash;
      }

      return CRM_Utils_System::url('civicrm/mailing/view', ['id' => $id], $absolute, NULL, TRUE, TRUE);
    }
  }

  /**
   * Get a list of template types which can be used as `civicrm_mailing.template_type`.
   *
   * @return array
   *   A list of template-types, keyed numerically. Each defines:
   *     - name: string, a short symbolic name
   *     - editorUrl: string, Angular template name
   *
   *   Ex: $templateTypes[0] === array('name' => 'mosaico', 'editorUrl' => '~/crmMosaico/editor.html').
   */
  public static function getTemplateTypes() {
    if (!isset(Civi::$statics[__CLASS__]['templateTypes'])) {
      $types = [];
      $types[] = [
        'name' => 'traditional',
        'label' => ts('Traditional'),
        'description' => ts('Standard CiviMail interface with wysiwyg editor.'),
        'editorUrl' => CRM_Mailing_Info::workflowEnabled() ? '~/crmMailing/EditMailingCtrl/workflow.html' : '~/crmMailing/EditMailingCtrl/2step.html',
        'weight' => 0,
      ];

      CRM_Utils_Hook::mailingTemplateTypes($types);

      $defaults = ['weight' => 0];
      foreach (array_keys($types) as $typeName) {
        $types[$typeName] = array_merge($defaults, $types[$typeName]);
      }
      usort($types, function ($a, $b) {
        if ($a['weight'] === $b['weight']) {
          return 0;
        }
        return $a['weight'] < $b['weight'] ? -1 : 1;
      });

      Civi::$statics[__CLASS__]['templateTypes'] = $types;
    }

    return Civi::$statics[__CLASS__]['templateTypes'];
  }

  /**
   * Pseudoconstant callback for `template_type` field.
   *
   * @return array
   */
  public static function getTemplateTypeNames(): array {
    $types = [];
    foreach (self::getTemplateTypes() as $type) {
      $types[] = [
        'id' => $type['name'],
        'name' => $type['name'],
        'label' => $type['label'] ?? ucfirst($type['name']),
        'description' => $type['description'] ?? NULL,
      ];
    }
    return $types;
  }

}
