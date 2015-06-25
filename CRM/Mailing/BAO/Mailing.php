<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
require_once 'Mail/mime.php';

/**
 * Class CRM_Mailing_BAO_Mailing
 */
class CRM_Mailing_BAO_Mailing extends CRM_Mailing_DAO_Mailing {

  /**
   * An array that holds the complete templates
   * including any headers or footers that need to be prepended
   * or appended to the body
   */
  private $preparedTemplates = NULL;

  /**
   * An array that holds the complete templates
   * including any headers or footers that need to be prepended
   * or appended to the body
   */
  private $templates = NULL;

  /**
   * An array that holds the tokens that are specifically found in our text and html bodies
   */
  private $tokens = NULL;

  /**
   * An array that holds the tokens that are specifically found in our text and html bodies
   */
  private $flattenedTokens = NULL;

  /**
   * The header associated with this mailing
   */
  private $header = NULL;

  /**
   * The footer associated with this mailing
   */
  private $footer = NULL;

  /**
   * The HTML content of the message
   */
  private $html = NULL;

  /**
   * The text content of the message
   */
  private $text = NULL;

  /**
   * Cached BAO for the domain
   */
  private $_domain = NULL;

  /**
   * Class constructor
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * @param int $job_id
   * @param int $mailing_id
   * @param null $mode
   *
   * @return int
   */
  public static function &getRecipientsCount($job_id, $mailing_id = NULL, $mode = NULL) {
    // need this for backward compatibility, so we can get count for old mailings
    // please do not use this function if possible
    $eq = self::getRecipients($job_id, $mailing_id);
    return $eq->N;
  }

  /**
   * note that $job_id is used only as a variable in the temp table construction
   * and does not play a role in the queries generated
   * @param int $job_id
   *   (misnomer) a nonce value used to name temporary tables.
   * @param int $mailing_id
   * @param null $offset
   * @param null $limit
   * @param bool $storeRecipients
   * @param bool $dedupeEmail
   * @param null $mode
   *
   * @return CRM_Mailing_Event_BAO_Queue|string
   */
  public static function &getRecipients(
    $job_id,
    $mailing_id = NULL,
    $offset = NULL,
    $limit = NULL,
    $storeRecipients = FALSE,
    $dedupeEmail = FALSE,
    $mode = NULL) {
    $mailingGroup = new CRM_Mailing_DAO_MailingGroup();

    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_MailingJob::getTableName();
    $mg = CRM_Mailing_DAO_MailingGroup::getTableName();
    $eq = CRM_Mailing_Event_DAO_Queue::getTableName();
    $ed = CRM_Mailing_Event_DAO_Delivered::getTableName();
    $eb = CRM_Mailing_Event_DAO_Bounce::getTableName();

    $email = CRM_Core_DAO_Email::getTableName();
    if ($mode == 'sms') {
      $phone = CRM_Core_DAO_Phone::getTableName();
    }
    $contact = CRM_Contact_DAO_Contact::getTableName();

    $group = CRM_Contact_DAO_Group::getTableName();
    $g2contact = CRM_Contact_DAO_GroupContact::getTableName();

    $m = new CRM_Mailing_DAO_Mailing();
    $m->id = $mailing_id;
    $m->find(TRUE);

    $email_selection_method = $m->email_selection_method;
    $location_type_id = $m->location_type_id;

    // Note: When determining the ORDER that results are returned, it's
    // the record that comes last that counts. That's because we are
    // INSERT'ing INTO a table with a primary id so that last record
    // over writes any previous record.
    switch ($email_selection_method) {
      case 'location-exclude':
        $location_filter = "($email.location_type_id != $location_type_id)";
        // If there is more than one email that doesn't match the location,
        // prefer the one marked is_bulkmail, followed by is_primary.
        $order_by = "ORDER BY $email.is_bulkmail, $email.is_primary";
        break;

      case 'location-only':
        $location_filter = "($email.location_type_id = $location_type_id)";
        // If there is more than one email of the desired location, prefer
        // the one marked is_bulkmail, followed by is_primary.
        $order_by = "ORDER BY $email.is_bulkmail, $email.is_primary";
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
        $order_by = "ORDER BY FIELD($email.location_type_id, $location_type_id), $email.is_bulkmail, $email.is_primary";
        break;

      case 'automatic':
        // fall through to default
      default:
        $location_filter = "($email.is_bulkmail = 1 OR $email.is_primary = 1)";
        $order_by = "ORDER BY $email.is_bulkmail";
    }

    /* Create a temp table for contact exclusion */
    $mailingGroup->query(
      "CREATE TEMPORARY TABLE X_$job_id
            (contact_id int primary key)
            ENGINE=HEAP"
    );

    /* Add all the members of groups excluded from this mailing to the temp
     * table */

    $excludeSubGroup = "INSERT INTO        X_$job_id (contact_id)
                    SELECT  DISTINCT    $g2contact.contact_id
                    FROM                $g2contact
                    INNER JOIN          $mg
                            ON          $g2contact.group_id = $mg.entity_id AND $mg.entity_table = '$group'
                    WHERE
                                        $mg.mailing_id = {$mailing_id}
                        AND             $g2contact.status = 'Added'
                        AND             $mg.group_type = 'Exclude'";
    $mailingGroup->query($excludeSubGroup);

    /* Add all unsubscribe members of base group from this mailing to the temp
     * table */

    $unSubscribeBaseGroup = "INSERT INTO        X_$job_id (contact_id)
                    SELECT  DISTINCT    $g2contact.contact_id
                    FROM                $g2contact
                    INNER JOIN          $mg
                            ON          $g2contact.group_id = $mg.entity_id AND $mg.entity_table = '$group'
                    WHERE
                                        $mg.mailing_id = {$mailing_id}
                        AND             $g2contact.status = 'Removed'
                        AND             $mg.group_type = 'Base'";
    $mailingGroup->query($unSubscribeBaseGroup);

    /* Add all the (intended) recipients of an excluded prior mailing to
     * the temp table */

    $excludeSubMailing = "INSERT IGNORE INTO X_$job_id (contact_id)
                    SELECT  DISTINCT    $eq.contact_id
                    FROM                $eq
                    INNER JOIN          $job
                            ON          $eq.job_id = $job.id
                    INNER JOIN          $mg
                            ON          $job.mailing_id = $mg.entity_id AND $mg.entity_table = '$mailing'
                    WHERE
                                        $mg.mailing_id = {$mailing_id}
                        AND             $mg.group_type = 'Exclude'";
    $mailingGroup->query($excludeSubMailing);

    // get all the saved searches AND hierarchical groups
    // and load them in the cache
    $sql = "
SELECT     $group.id, $group.cache_date, $group.saved_search_id, $group.children
FROM       $group
INNER JOIN $mg ON $mg.entity_id = $group.id
WHERE      $mg.entity_table = '$group'
  AND      $mg.group_type = 'Exclude'
  AND      $mg.mailing_id = {$mailing_id}
  AND      ( saved_search_id != 0
   OR        saved_search_id IS NOT NULL
   OR        children IS NOT NULL )
";

    $groupDAO = CRM_Core_DAO::executeQuery($sql);
    while ($groupDAO->fetch()) {
      if ($groupDAO->cache_date == NULL) {
        CRM_Contact_BAO_GroupContactCache::load($groupDAO);
      }

      $smartGroupExclude = "
INSERT IGNORE INTO X_$job_id (contact_id)
SELECT c.contact_id
FROM   civicrm_group_contact_cache c
WHERE  c.group_id = {$groupDAO->id}
";
      $mailingGroup->query($smartGroupExclude);
    }

    $tempColumn = 'email_id';
    if ($mode == 'sms') {
      $tempColumn = 'phone_id';
    }

    /* Get all the group contacts we want to include */

    $mailingGroup->query(
      "CREATE TEMPORARY TABLE I_$job_id
            ($tempColumn int, contact_id int primary key)
            ENGINE=HEAP"
    );

    /* Get the group contacts, but only those which are not in the
     * exclusion temp table */

    $query = "REPLACE INTO       I_$job_id (email_id, contact_id)

                    SELECT DISTINCT     $email.id as email_id,
                                        $contact.id as contact_id
                    FROM                $email
                    INNER JOIN          $contact
                            ON          $email.contact_id = $contact.id
                    INNER JOIN          $g2contact
                            ON          $contact.id = $g2contact.contact_id
                    INNER JOIN          $mg
                            ON          $g2contact.group_id = $mg.entity_id
                                AND     $mg.entity_table = '$group'
                    LEFT JOIN           X_$job_id
                            ON          $contact.id = X_$job_id.contact_id
                    WHERE
                                       ($mg.group_type = 'Include')
                        AND             $mg.search_id IS NULL
                        AND             $g2contact.status = 'Added'
                        AND             $contact.do_not_email = 0
                        AND             $contact.is_opt_out = 0
                        AND             $contact.is_deceased <> 1
                        AND             $location_filter
                        AND             $email.email IS NOT NULL
                        AND             $email.email != ''
                        AND             $email.on_hold = 0
                        AND             $mg.mailing_id = {$mailing_id}
                        AND             X_$job_id.contact_id IS null
                    $order_by";

    if ($mode == 'sms') {
      $phoneTypes = CRM_Core_OptionGroup::values('phone_type', TRUE, FALSE, FALSE, NULL, 'name');
      $query = "REPLACE INTO       I_$job_id (phone_id, contact_id)

                    SELECT DISTINCT     $phone.id as phone_id,
                                        $contact.id as contact_id
                    FROM                $phone
                    INNER JOIN          $contact
                            ON          $phone.contact_id = $contact.id
                    INNER JOIN          $g2contact
                            ON          $contact.id = $g2contact.contact_id
                    INNER JOIN          $mg
                            ON          $g2contact.group_id = $mg.entity_id
                                AND     $mg.entity_table = '$group'
                    LEFT JOIN           X_$job_id
                            ON          $contact.id = X_$job_id.contact_id
                    WHERE
                                       ($mg.group_type = 'Include')
                        AND             $mg.search_id IS NULL
                        AND             $g2contact.status = 'Added'
                        AND             $contact.do_not_sms = 0
                        AND             $contact.is_opt_out = 0
                        AND             $contact.is_deceased <> 1
                        AND             $phone.phone_type_id = {$phoneTypes['Mobile']}
                        AND             $phone.phone IS NOT NULL
                        AND             $phone.phone != ''
                        AND             $mg.mailing_id = {$mailing_id}
                        AND             X_$job_id.contact_id IS null";
    }
    $mailingGroup->query($query);

    /* Query prior mailings */

    $query = "REPLACE INTO       I_$job_id (email_id, contact_id)
                    SELECT DISTINCT     $email.id as email_id,
                                        $contact.id as contact_id
                    FROM                $email
                    INNER JOIN          $contact
                            ON          $email.contact_id = $contact.id
                    INNER JOIN          $eq
                            ON          $eq.contact_id = $contact.id
                    INNER JOIN          $job
                            ON          $eq.job_id = $job.id
                    INNER JOIN          $mg
                            ON          $job.mailing_id = $mg.entity_id AND $mg.entity_table = '$mailing'
                    LEFT JOIN           X_$job_id
                            ON          $contact.id = X_$job_id.contact_id
                    WHERE
                                       ($mg.group_type = 'Include')
                        AND             $contact.do_not_email = 0
                        AND             $contact.is_opt_out = 0
                        AND             $contact.is_deceased <> 1
                        AND             $location_filter
                        AND             $email.on_hold = 0
                        AND             $mg.mailing_id = {$mailing_id}
                        AND             X_$job_id.contact_id IS null
                    $order_by";

    if ($mode == 'sms') {
      $query = "REPLACE INTO       I_$job_id (phone_id, contact_id)
                    SELECT DISTINCT     $phone.id as phone_id,
                                        $contact.id as contact_id
                    FROM                $phone
                    INNER JOIN          $contact
                            ON          $phone.contact_id = $contact.id
                    INNER JOIN          $eq
                            ON          $eq.contact_id = $contact.id
                    INNER JOIN          $job
                            ON          $eq.job_id = $job.id
                    INNER JOIN          $mg
                            ON          $job.mailing_id = $mg.entity_id AND $mg.entity_table = '$mailing'
                    LEFT JOIN           X_$job_id
                            ON          $contact.id = X_$job_id.contact_id
                    WHERE
                                       ($mg.group_type = 'Include')
                        AND             $contact.do_not_sms = 0
                        AND             $contact.is_opt_out = 0
                        AND             $contact.is_deceased <> 1
                        AND             $phone.phone_type_id = {$phoneTypes['Mobile']}
                        AND             $mg.mailing_id = {$mailing_id}
                        AND             X_$job_id.contact_id IS null";
    }
    $mailingGroup->query($query);

    $sql = "
SELECT     $group.id, $group.cache_date, $group.saved_search_id, $group.children
FROM       $group
INNER JOIN $mg ON $mg.entity_id = $group.id
WHERE      $mg.entity_table = '$group'
  AND      $mg.group_type = 'Include'
  AND      $mg.search_id IS NULL
  AND      $mg.mailing_id = {$mailing_id}
  AND      ( saved_search_id != 0
   OR        saved_search_id IS NOT NULL
   OR        children IS NOT NULL )
";

    $groupDAO = CRM_Core_DAO::executeQuery($sql);
    while ($groupDAO->fetch()) {
      if ($groupDAO->cache_date == NULL) {
        CRM_Contact_BAO_GroupContactCache::load($groupDAO);
      }

      $smartGroupInclude = "
REPLACE INTO I_$job_id (email_id, contact_id)
SELECT     civicrm_email.id as email_id, c.id as contact_id
FROM       civicrm_contact c
INNER JOIN civicrm_email                ON civicrm_email.contact_id         = c.id
INNER JOIN civicrm_group_contact_cache gc ON gc.contact_id        = c.id
LEFT  JOIN X_$job_id                      ON X_$job_id.contact_id = c.id
WHERE      gc.group_id = {$groupDAO->id}
  AND      c.do_not_email = 0
  AND      c.is_opt_out = 0
  AND      c.is_deceased <> 1
  AND      $location_filter
  AND      civicrm_email.on_hold = 0
  AND      X_$job_id.contact_id IS null
$order_by
";
      if ($mode == 'sms') {
        $smartGroupInclude = "
REPLACE INTO I_$job_id (phone_id, contact_id)
SELECT     p.id as phone_id, c.id as contact_id
FROM       civicrm_contact c
INNER JOIN civicrm_phone p                ON p.contact_id         = c.id
INNER JOIN civicrm_group_contact_cache gc ON gc.contact_id        = c.id
LEFT  JOIN X_$job_id                      ON X_$job_id.contact_id = c.id
WHERE      gc.group_id = {$groupDAO->id}
  AND      c.do_not_sms = 0
  AND      c.is_opt_out = 0
  AND      c.is_deceased <> 1
  AND      p.phone_type_id = {$phoneTypes['Mobile']}
  AND      X_$job_id.contact_id IS null";
      }
      $mailingGroup->query($smartGroupInclude);
    }

    /**
     * Construct the filtered search queries
     */
    $query = "
SELECT search_id, search_args, entity_id
FROM   $mg
WHERE  $mg.search_id IS NOT NULL
AND    $mg.mailing_id = {$mailing_id}
";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $customSQL = CRM_Contact_BAO_SearchCustom::civiMailSQL($dao->search_id,
        $dao->search_args,
        $dao->entity_id
      );
      $query = "REPLACE INTO       I_$job_id ({$tempColumn}, contact_id)
                         $customSQL";
      $mailingGroup->query($query);
    }

    /* Get the emails with only location override */

    $query = "REPLACE INTO       I_$job_id (email_id, contact_id)
                    SELECT DISTINCT     $email.id as local_email_id,
                                        $contact.id as contact_id
                    FROM                $email
                    INNER JOIN          $contact
                            ON          $email.contact_id = $contact.id
                    INNER JOIN          $g2contact
                            ON          $contact.id = $g2contact.contact_id
                    INNER JOIN          $mg
                            ON          $g2contact.group_id = $mg.entity_id
                    LEFT JOIN           X_$job_id
                            ON          $contact.id = X_$job_id.contact_id
                    WHERE
                                        $mg.entity_table = '$group'
                        AND             $mg.group_type = 'Include'
                        AND             $g2contact.status = 'Added'
                        AND             $contact.do_not_email = 0
                        AND             $contact.is_opt_out = 0
                        AND             $contact.is_deceased <> 1
                        AND             $location_filter
                        AND             $email.on_hold = 0
                        AND             $mg.mailing_id = {$mailing_id}
                        AND             X_$job_id.contact_id IS null
                    $order_by";
    if ($mode == "sms") {
      $query = "REPLACE INTO       I_$job_id (phone_id, contact_id)
                    SELECT DISTINCT     $phone.id as phone_id,
                                        $contact.id as contact_id
                    FROM                $phone
                    INNER JOIN          $contact
                            ON          $phone.contact_id = $contact.id
                    INNER JOIN          $g2contact
                            ON          $contact.id = $g2contact.contact_id
                    INNER JOIN          $mg
                            ON          $g2contact.group_id = $mg.entity_id
                    LEFT JOIN           X_$job_id
                            ON          $contact.id = X_$job_id.contact_id
                    WHERE
                                        $mg.entity_table = '$group'
                        AND             $mg.group_type = 'Include'
                        AND             $g2contact.status = 'Added'
                        AND             $contact.do_not_sms = 0
                        AND             $contact.is_opt_out = 0
                        AND             $contact.is_deceased <> 1
                        AND             $phone.phone_type_id = {$phoneTypes['Mobile']}
                        AND             $mg.mailing_id = {$mailing_id}
                        AND             X_$job_id.contact_id IS null";
    }
    $mailingGroup->query($query);

    $results = array();

    $eq = new CRM_Mailing_Event_BAO_Queue();

    list($aclFrom, $aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause();
    $aclWhere = $aclWhere ? "WHERE {$aclWhere}" : '';
    $limitString = NULL;
    if ($limit && $offset !== NULL) {
      $offset = CRM_Utils_Type::escape($offset, 'Int');
      $limit = CRM_Utils_Type::escape($limit, 'Int');

      $limitString = "LIMIT $offset, $limit";
    }

    if ($storeRecipients && $mailing_id) {
      $sql = "
DELETE
FROM   civicrm_mailing_recipients
WHERE  mailing_id = %1
";
      $params = array(1 => array($mailing_id, 'Integer'));
      CRM_Core_DAO::executeQuery($sql, $params);

      // CRM-3975
      $groupBy = $groupJoin = '';
      if ($dedupeEmail) {
        $groupJoin = " INNER JOIN civicrm_email e ON e.id = i.email_id";
        $groupBy = " GROUP BY e.email ";
      }

      $sql = "
INSERT INTO civicrm_mailing_recipients ( mailing_id, contact_id, {$tempColumn} )
SELECT %1, i.contact_id, i.{$tempColumn}
FROM       civicrm_contact contact_a
INNER JOIN I_$job_id i ON contact_a.id = i.contact_id
           $groupJoin
           {$aclFrom}
           {$aclWhere}
           $groupBy
ORDER BY   i.contact_id, i.{$tempColumn}
";

      CRM_Core_DAO::executeQuery($sql, $params);

      // if we need to add all emails marked bulk, do it as a post filter
      // on the mailing recipients table
      if (CRM_Core_BAO_Email::isMultipleBulkMail()) {
        self::addMultipleEmails($mailing_id);
      }
    }

    /* Delete the temp table */

    $mailingGroup->reset();
    $mailingGroup->query("DROP TEMPORARY TABLE X_$job_id");
    $mailingGroup->query("DROP TEMPORARY TABLE I_$job_id");

    return $eq;
  }

  /**
   * @param string $type
   *
   * @return array
   */
  private function _getMailingGroupIds($type = 'Include') {
    $mailingGroup = new CRM_Mailing_DAO_MailingGroup();
    $group = CRM_Contact_DAO_Group::getTableName();
    if (!isset($this->id)) {
      // we're just testing tokens, so return any group
      $query = "SELECT   id AS entity_id
                      FROM     $group
                      ORDER BY id
                      LIMIT 1";
    }
    else {
      $query = "SELECT entity_id
                      FROM   $mg
                      WHERE  mailing_id = {$this->id}
                      AND    group_type = '$type'
                      AND    entity_table = '$group'";
    }
    $mailingGroup->query($query);

    $groupIds = array();
    while ($mailingGroup->fetch()) {
      $groupIds[] = $mailingGroup->entity_id;
    }

    return $groupIds;
  }

  /**
   * Returns the regex patterns that are used for preparing the text and html templates.
   */
  private function &getPatterns($onlyHrefs = FALSE) {

    $patterns = array();

    $protos = '(https?|ftp)';
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

    $patterns = '{' . implode('|', $patterns) . '}im';

    return $patterns;
  }

  /**
   * Returns an array that denotes the type of token that we are dealing with
   * we use the type later on when we are doing a token replacement lookup
   *
   * @param string $token
   *   The token for which we will be doing adata lookup.
   *
   * @return array
   *   An array that holds the token itself and the type.
   *                             the type will tell us which function to use for the data lookup
   *                             if we need to do a lookup at all
   */
  public function &getDataFunc($token) {
    static $_categories = NULL;
    static $_categoryString = NULL;
    if (!$_categories) {
      $_categories = array(
        'domain' => NULL,
        'action' => NULL,
        'mailing' => NULL,
        'contact' => NULL,
      );

      CRM_Utils_Hook::tokens($_categories);
      $_categoryString = implode('|', array_keys($_categories));
    }

    $funcStruct = array('type' => NULL, 'token' => $token);
    $matches = array();
    if ((preg_match('/^href/i', $token) || preg_match('/^http/i', $token))) {
      // it is a url so we need to check to see if there are any tokens embedded
      // if so then call this function again to get the token dataFunc
      // and assign the type 'embedded'  so that the data retrieving function
      // will know what how to handle this token.
      if (preg_match_all('/(\{\w+\.\w+\})/', $token, $matches)) {
        $funcStruct['type'] = 'embedded_url';
        $funcStruct['embed_parts'] = $funcStruct['token'] = array();
        foreach ($matches[1] as $match) {
          $preg_token = '/' . preg_quote($match, '/') . '/';
          $list = preg_split($preg_token, $token, 2);
          $funcStruct['embed_parts'][] = $list[0];
          $token = $list[1];
          $funcStruct['token'][] = $this->getDataFunc($match);
        }
        // fixed truncated url, CRM-7113
        if ($token) {
          $funcStruct['embed_parts'][] = $token;
        }
      }
      else {
        $funcStruct['type'] = 'url';
      }
    }
    elseif (preg_match('/^\{(' . $_categoryString . ')\.(\w+)\}$/', $token, $matches)) {
      $funcStruct['type'] = $matches[1];
      $funcStruct['token'] = $matches[2];
    }
    elseif (preg_match('/\\\\\{(\w+\.\w+)\\\\\}|\{\{(\w+\.\w+)\}\}/', $token, $matches)) {
      // we are an escaped token
      // so remove the escape chars
      $unescaped_token = preg_replace('/\{\{|\}\}|\\\\\{|\\\\\}/', '', $matches[0]);
      $funcStruct['token'] = '{' . $unescaped_token . '}';
    }
    return $funcStruct;
  }

  /**
   *
   * Prepares the text and html templates
   * for generating the emails and returns a copy of the
   * prepared templates
   */
  private function getPreparedTemplates() {
    if (!$this->preparedTemplates) {
      $patterns['html'] = $this->getPatterns(TRUE);
      $patterns['subject'] = $patterns['text'] = $this->getPatterns();
      $templates = $this->getTemplates();

      $this->preparedTemplates = array();

      foreach (array(
                 'html',
                 'text',
                 'subject',
               ) as $key) {
        if (!isset($templates[$key])) {
          continue;
        }

        $matches = array();
        $tokens = array();
        $split_template = array();

        $email = $templates[$key];
        preg_match_all($patterns[$key], $email, $matches, PREG_PATTERN_ORDER);
        foreach ($matches[0] as $idx => $token) {
          $preg_token = '/' . preg_quote($token, '/') . '/im';
          list($split_template[], $email) = preg_split($preg_token, $email, 2);
          array_push($tokens, $this->getDataFunc($token));
        }
        if ($email) {
          $split_template[] = $email;
        }
        $this->preparedTemplates[$key]['template'] = $split_template;
        $this->preparedTemplates[$key]['tokens'] = $tokens;
      }
    }
    return ($this->preparedTemplates);
  }

  /**
   * Retrieve a ref to an array that holds the email and text templates for this email
   * assembles the complete template including the header and footer
   * that the user has uploaded or declared (if they have dome that)
   *
   * @return array
   *   reference to an assoc array
   */
  private function &getTemplates() {
    if (!$this->templates) {
      $this->getHeaderFooter();
      $this->templates = array();

      if ($this->body_text) {
        $template = array();
        if ($this->header) {
          $template[] = $this->header->body_text;
        }

        $template[] = $this->body_text;

        if ($this->footer) {
          $template[] = $this->footer->body_text;
        }

        $this->templates['text'] = implode("\n", $template);
      }

      if ($this->body_html) {

        $template = array();
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
        if (!$this->body_text) {
          $this->templates['text'] = CRM_Utils_String::htmlToText($this->templates['html']);
        }
      }

      if ($this->subject) {
        $template = array();
        $template[] = $this->subject;
        $this->templates['subject'] = implode("\n", $template);
      }
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

      $this->tokens = array('html' => array(), 'text' => array(), 'subject' => array());

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
        $this->tokens[$prop][$type] = array();
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
   *
   * @return void
   */
  public function getTestRecipients($testParams) {
    if (array_key_exists($testParams['test_group'], CRM_Core_PseudoConstant::group())) {
      $contacts = civicrm_api('contact', 'get', array(
          'version' => 3,
          'group' => $testParams['test_group'],
          'return' => 'id',
          'options' => array(
            'limit' => 100000000000,
          ),
        )
      );

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
          $params = array(
            'job_id' => $testParams['job_id'],
            'email_id' => $dao->email_id,
            'contact_id' => $groupContact,
          );
          CRM_Mailing_Event_BAO_Queue::create($params);
        }
      }
    }
  }

  /**
   * Load this->header and this->footer.
   */
  private function getHeaderFooter() {
    if (!$this->header and $this->header_id) {
      $this->header = new CRM_Mailing_BAO_Component();
      $this->header->id = $this->header_id;
      $this->header->find(TRUE);
      $this->header->free();
    }

    if (!$this->footer and $this->footer_id) {
      $this->footer = new CRM_Mailing_BAO_Component();
      $this->footer->id = $this->footer_id;
      $this->footer->find(TRUE);
      $this->footer->free();
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
   *                                wherever possible
   * @param string $job_id
   *   Job ID component of the generated message ID.
   * @param string $event_queue_id
   *   Event Queue ID component of the generated message ID.
   * @param string $hash
   *   Hash component of the generated message ID.
   *
   * @return void
   */
  public static function addMessageIdHeader(&$headers, $prefix, $job_id, $event_queue_id, $hash) {
    $config = CRM_Core_Config::singleton();
    $localpart = CRM_Core_BAO_MailSettings::defaultLocalpart();
    $emailDomain = CRM_Core_BAO_MailSettings::defaultDomain();
    $includeMessageId = CRM_Core_BAO_MailSettings::includeMessageId();

    if ($includeMessageId && (!array_key_exists('Message-ID', $headers))) {
      $headers['Message-ID'] = '<' . implode($config->verpSeparator,
          array(
            $localpart . $prefix,
            $job_id,
            $event_queue_id,
            $hash,
          )
        ) . "@{$emailDomain}>";
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
   * @param string $email
   *   Destination address.
   *
   * @return array
   *   (reference) array    array ref that hold array refs to the verp info and urls
   */
  public static function getVerpAndUrls($job_id, $event_queue_id, $hash, $email) {
    // create a skeleton object and set its properties that are required by getVerpAndUrlsAndHeaders()
    $config = CRM_Core_Config::singleton();
    $bao = new CRM_Mailing_BAO_Mailing();
    $bao->_domain = CRM_Core_BAO_Domain::getDomain();
    $bao->from_name = $bao->from_email = $bao->subject = '';

    // use $bao's instance method to get verp and urls
    list($verp, $urls, $_) = $bao->getVerpAndUrlsAndHeaders($job_id, $event_queue_id, $hash, $email);
    return array($verp, $urls);
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
   * @param string $email
   *   Destination address.
   *
   * @param bool $isForward
   *
   * @return array
   *   array ref that hold array refs to the verp info, urls, and headers
   */
  private function getVerpAndUrlsAndHeaders($job_id, $event_queue_id, $hash, $email, $isForward = FALSE) {
    $config = CRM_Core_Config::singleton();

    /**
     * Inbound VERP keys:
     *  reply:          user replied to mailing
     *  bounce:         email address bounced
     *  unsubscribe:    contact opts out of all target lists for the mailing
     *  resubscribe:    contact opts back into all target lists for the mailing
     *  optOut:         contact unsubscribes from the domain
     */
    $verp = array();
    $verpTokens = array(
      'reply' => 'r',
      'bounce' => 'b',
      'unsubscribe' => 'u',
      'resubscribe' => 'e',
      'optOut' => 'o',
    );

    $localpart = CRM_Core_BAO_MailSettings::defaultLocalpart();
    $emailDomain = CRM_Core_BAO_MailSettings::defaultDomain();

    foreach ($verpTokens as $key => $value) {
      $verp[$key] = implode($config->verpSeparator,
          array(
            $localpart . $value,
            $job_id,
            $event_queue_id,
            $hash,
          )
        ) . "@$emailDomain";
    }

    //handle should override VERP address.
    $skipEncode = FALSE;

    if ($job_id &&
      self::overrideVerp($job_id)
    ) {
      $verp['reply'] = "\"{$this->from_name}\" <{$this->from_email}>";
    }

    $urls = array(
      'forward' => CRM_Utils_System::url('civicrm/mailing/forward',
        "reset=1&jid={$job_id}&qid={$event_queue_id}&h={$hash}",
        TRUE, NULL, TRUE, TRUE
      ),
      'unsubscribeUrl' => CRM_Utils_System::url('civicrm/mailing/unsubscribe',
        "reset=1&jid={$job_id}&qid={$event_queue_id}&h={$hash}",
        TRUE, NULL, TRUE, TRUE
      ),
      'resubscribeUrl' => CRM_Utils_System::url('civicrm/mailing/resubscribe',
        "reset=1&jid={$job_id}&qid={$event_queue_id}&h={$hash}",
        TRUE, NULL, TRUE, TRUE
      ),
      'optOutUrl' => CRM_Utils_System::url('civicrm/mailing/optout',
        "reset=1&jid={$job_id}&qid={$event_queue_id}&h={$hash}",
        TRUE, NULL, TRUE, TRUE
      ),
      'subscribeUrl' => CRM_Utils_System::url('civicrm/mailing/subscribe',
        'reset=1',
        TRUE, NULL, TRUE, TRUE
      ),
    );

    $headers = array(
      'Reply-To' => $verp['reply'],
      'Return-Path' => $verp['bounce'],
      'From' => "\"{$this->from_name}\" <{$this->from_email}>",
      'Subject' => $this->subject,
      'List-Unsubscribe' => "<mailto:{$verp['unsubscribe']}>",
    );
    self::addMessageIdHeader($headers, 'm', $job_id, $event_queue_id, $hash);
    if ($isForward) {
      $headers['Subject'] = "[Fwd:{$this->subject}]";
    }
    return array(&$verp, &$urls, &$headers);
  }

  /**
   * Compose a message.
   *
   * @param int $job_id
   *   ID of the Job associated with this message.
   * @param int $event_queue_id
   *   ID of the EventQueue.
   * @param string $hash
   *   Hash of the EventQueue.
   * @param string $contactId
   *   ID of the Contact.
   * @param string $email
   *   Destination address.
   * @param string $recipient
   *   To: of the recipient.
   * @param bool $test
   *   Is this mailing a test?.
   * @param $contactDetails
   * @param $attachments
   * @param bool $isForward
   *   Is this mailing compose for forward?.
   * @param string $fromEmail
   *   Email address of who is forwardinf it.
   *
   * @param null $replyToEmail
   *
   * @return Mail_mime               The mail object
   */
  public function &compose(
    $job_id, $event_queue_id, $hash, $contactId,
    $email, &$recipient, $test,
    $contactDetails, &$attachments, $isForward = FALSE,
    $fromEmail = NULL, $replyToEmail = NULL
  ) {
    $config = CRM_Core_Config::singleton();
    $knownTokens = $this->getTokens();

    if ($this->_domain == NULL) {
      $this->_domain = CRM_Core_BAO_Domain::getDomain();
    }

    list($verp, $urls, $headers) = $this->getVerpAndUrlsAndHeaders(
      $job_id,
      $event_queue_id,
      $hash,
      $email,
      $isForward
    );

    //set from email who is forwarding it and not original one.
    if ($fromEmail) {
      unset($headers['From']);
      $headers['From'] = "<{$fromEmail}>";
    }

    if ($replyToEmail && ($fromEmail != $replyToEmail)) {
      $headers['Reply-To'] = "{$replyToEmail}";
    }

    if ($contactDetails) {
      $contact = $contactDetails;
    }
    elseif ($contactId === 0) {
      //anonymous user
      $contact = array();
      CRM_Utils_Hook::tokenValues($contact, $contactId, $job_id);
    }
    else {
      $params = array(array('contact_id', '=', $contactId, 0, 0));
      list($contact, $_) = CRM_Contact_BAO_Query::apiQuery($params);

      //CRM-4524
      $contact = reset($contact);

      if (!$contact || is_a($contact, 'CRM_Core_Error')) {
        CRM_Core_Error::debug_log_message(ts('CiviMail will not send email to a non-existent contact: %1',
          array(1 => $contactId)
        ));
        // setting this because function is called by reference
        //@todo test not calling function by reference
        $res = NULL;
        return $res;
      }

      // also call the hook to get contact details
      CRM_Utils_Hook::tokenValues($contact, $contactId, $job_id);
    }

    $pTemplates = $this->getPreparedTemplates();
    $pEmails = array();

    foreach ($pTemplates as $type => $pTemplate) {
      $html = ($type == 'html') ? TRUE : FALSE;
      $pEmails[$type] = array();
      $pEmail = &$pEmails[$type];
      $template = &$pTemplates[$type]['template'];
      $tokens = &$pTemplates[$type]['tokens'];
      $idx = 0;
      if (!empty($tokens)) {
        foreach ($tokens as $idx => $token) {
          $token_data = $this->getTokenData($token, $html, $contact, $verp, $urls, $event_queue_id);
          array_push($pEmail, $template[$idx]);
          array_push($pEmail, $token_data);
        }
      }
      else {
        array_push($pEmail, $template[$idx]);
      }

      if (isset($template[($idx + 1)])) {
        array_push($pEmail, $template[($idx + 1)]);
      }
    }

    $html = NULL;
    if (isset($pEmails['html']) && is_array($pEmails['html']) && count($pEmails['html'])) {
      $html = &$pEmails['html'];
    }

    $text = NULL;
    if (isset($pEmails['text']) && is_array($pEmails['text']) && count($pEmails['text'])) {
      $text = &$pEmails['text'];
    }

    // push the tracking url on to the html email if necessary
    if ($this->open_tracking && $html) {
      array_push($html, "\n" . '<img src="' . $config->userFrameworkResourceURL .
        "extern/open.php?q=$event_queue_id\" width='1' height='1' alt='' border='0'>"
      );
    }

    $message = new Mail_mime("\n");

    $useSmarty = defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY ? TRUE : FALSE;
    if ($useSmarty) {
      $smarty = CRM_Core_Smarty::singleton();
      // also add the contact tokens to the template
      $smarty->assign_by_ref('contact', $contact);
    }

    $mailParams = $headers;
    if ($text && ($test || $contact['preferred_mail_format'] == 'Text' ||
        $contact['preferred_mail_format'] == 'Both' ||
        ($contact['preferred_mail_format'] == 'HTML' && !array_key_exists('html', $pEmails))
      )
    ) {
      $textBody = implode('', $text);
      if ($useSmarty) {
        $textBody = $smarty->fetch("string:$textBody");
      }
      $mailParams['text'] = $textBody;
    }

    if ($html && ($test || ($contact['preferred_mail_format'] == 'HTML' ||
          $contact['preferred_mail_format'] == 'Both'
        ))
    ) {
      $htmlBody = implode('', $html);
      if ($useSmarty) {
        $htmlBody = $smarty->fetch("string:$htmlBody");
      }
      $mailParams['html'] = $htmlBody;
    }

    if (empty($mailParams['text']) && empty($mailParams['html'])) {
      // CRM-9833
      // something went wrong, lets log it and return null (by reference)
      CRM_Core_Error::debug_log_message(ts('CiviMail will not send an empty mail body, Skipping: %1',
        array(1 => $email)
      ));
      $res = NULL;
      return $res;
    }

    $mailParams['attachments'] = $attachments;

    $mailingSubject = CRM_Utils_Array::value('subject', $pEmails);
    if (is_array($mailingSubject)) {
      $mailingSubject = implode('', $mailingSubject);
    }
    $mailParams['Subject'] = $mailingSubject;

    $mailParams['toName'] = CRM_Utils_Array::value('display_name',
      $contact
    );
    $mailParams['toEmail'] = $email;

    // Add job ID to mailParams for external email delivery service to utilise
    $mailParams['job_id'] = $job_id;

    CRM_Utils_Hook::alterMailParams($mailParams, 'civimail');

    // CRM-10699 support custom email headers
    if (!empty($mailParams['headers'])) {
      $headers = array_merge($headers, $mailParams['headers']);
    }
    //cycle through mailParams and set headers array
    foreach ($mailParams as $paramKey => $paramValue) {
      //exclude values not intended for the header
      if (!in_array($paramKey, array(
        'text',
        'html',
        'attachments',
        'toName',
        'toEmail',
      ))
      ) {
        $headers[$paramKey] = $paramValue;
      }
    }

    if (!empty($mailParams['text'])) {
      $message->setTxtBody($mailParams['text']);
    }

    if (!empty($mailParams['html'])) {
      $message->setHTMLBody($mailParams['html']);
    }

    if (!empty($mailParams['attachments'])) {
      foreach ($mailParams['attachments'] as $fileID => $attach) {
        $message->addAttachment($attach['fullPath'],
          $attach['mime_type'],
          $attach['cleanName']
        );
      }
    }

    //pickup both params from mail params.
    $toName = trim($mailParams['toName']);
    $toEmail = trim($mailParams['toEmail']);
    if ($toName == $toEmail ||
      strpos($toName, '@') !== FALSE
    ) {
      $toName = NULL;
    }
    else {
      $toName = CRM_Utils_Mail::formatRFC2822Name($toName);
    }

    $headers['To'] = "$toName <$toEmail>";

    $headers['Precedence'] = 'bulk';
    // Will test in the mail processor if the X-VERP is set in the bounced email.
    // (As an option to replace real VERP for those that can't set it up)
    $headers['X-CiviMail-Bounce'] = $verp['bounce'];

    //CRM-5058
    //token replacement of subject
    $headers['Subject'] = $mailingSubject;

    CRM_Utils_Mail::setMimeParams($message);
    $headers = $message->headers($headers);

    //get formatted recipient
    $recipient = $headers['To'];

    // make sure we unset a lot of stuff
    unset($verp);
    unset($urls);
    unset($params);
    unset($contact);
    unset($ids);

    return $message;
  }

  /**
   *
   * get mailing object and replaces subscribeInvite,
   * domain and mailing tokens
   */
  public static function tokenReplace(&$mailing) {
    $domain = CRM_Core_BAO_Domain::getDomain();

    foreach (array('text', 'html') as $type) {
      $tokens = $mailing->getTokens();
      if (isset($mailing->templates[$type])) {
        $mailing->templates[$type] = CRM_Utils_Token::replaceSubscribeInviteTokens($mailing->templates[$type]);
        $mailing->templates[$type] = CRM_Utils_Token::replaceDomainTokens(
          $mailing->templates[$type],
          $domain,
          $type == 'html' ? TRUE : FALSE,
          $tokens[$type]
        );
        $mailing->templates[$type] = CRM_Utils_Token::replaceMailingTokens($mailing->templates[$type], $mailing, NULL, $tokens[$type]);
      }
    }
  }

  /**
   *
   *  getTokenData receives a token from an email
   *  and returns the appropriate data for the token
   */
  private function getTokenData(&$token_a, $html = FALSE, &$contact, &$verp, &$urls, $event_queue_id) {
    $type = $token_a['type'];
    $token = $token_a['token'];
    $data = $token;

    $useSmarty = defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY ? TRUE : FALSE;

    if ($type == 'embedded_url') {
      $embed_data = array();
      foreach ($token as $t) {
        $embed_data[] = $this->getTokenData($t, $html = FALSE, $contact, $verp, $urls, $event_queue_id);
      }
      $numSlices = count($embed_data);
      $url = '';
      for ($i = 0; $i < $numSlices; $i++) {
        $url .= "{$token_a['embed_parts'][$i]}{$embed_data[$i]}";
      }
      if (isset($token_a['embed_parts'][$numSlices])) {
        $url .= $token_a['embed_parts'][$numSlices];
      }
      // add trailing quote since we've gobbled it up in a previous regex
      // function getPatterns, line 431
      if (preg_match('/^href[ ]*=[ ]*\'/', $url)) {
        $url .= "'";
      }
      elseif (preg_match('/^href[ ]*=[ ]*\"/', $url)) {
        $url .= '"';
      }
      $data = $url;
    }
    elseif ($type == 'url') {
      if ($this->url_tracking) {
        $data = CRM_Mailing_BAO_TrackableURL::getTrackerURL($token, $this->id, $event_queue_id);
        if (!empty($html)) {
          $data = htmlentities($data, ENT_NOQUOTES);
        }
      }
      else {
        $data = $token;
      }
    }
    elseif ($type == 'contact') {
      $data = CRM_Utils_Token::getContactTokenReplacement($token, $contact, FALSE, FALSE, $useSmarty);
    }
    elseif ($type == 'action') {
      $data = CRM_Utils_Token::getActionTokenReplacement($token, $verp, $urls, $html);
    }
    elseif ($type == 'domain') {
      $domain = CRM_Core_BAO_Domain::getDomain();
      $data = CRM_Utils_Token::getDomainTokenReplacement($token, $domain, $html);
    }
    elseif ($type == 'mailing') {
      if ($token == 'name') {
        $data = $this->name;
      }
      elseif ($token == 'group') {
        $groups = $this->getGroupNames();
        $data = implode(', ', $groups);
      }
    }
    else {
      $data = CRM_Utils_Array::value("{$type}.{$token}", $contact);
    }
    return $data;
  }

  /**
   * Return a list of group names for this mailing.  Does not work with
   * prior-mailing targets.
   *
   * @return array
   *   Names of groups receiving this mailing
   */
  public function &getGroupNames() {
    if (!isset($this->id)) {
      return array();
    }
    $mg = new CRM_Mailing_DAO_MailingGroup();
    $mgtable = CRM_Mailing_DAO_MailingGroup::getTableName();
    $group = CRM_Contact_BAO_Group::getTableName();

    $mg->query("SELECT      $group.title as name FROM $mgtable
                    INNER JOIN  $group ON $mgtable.entity_id = $group.id
                    WHERE       $mgtable.mailing_id = {$this->id}
                        AND     $mgtable.entity_table = '$group'
                        AND     $mgtable.group_type = 'Include'
                    ORDER BY    $group.name");

    $groups = array();
    while ($mg->fetch()) {
      $groups[] = $mg->name;
    }
    $mg->free();
    return $groups;
  }

  /**
   * Add the mailings.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   * @param array $ids
   *   Reference array contains the id.
   *
   *
   * @return CRM_Mailing_DAO_Mailing
   */
  public static function add(&$params, $ids = array()) {
    $id = CRM_Utils_Array::value('mailing_id', $ids, CRM_Utils_Array::value('id', $params));

    if ($id) {
      CRM_Utils_Hook::pre('edit', 'Mailing', $id, $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'Mailing', NULL, $params);
    }

    $mailing = new static();
    if ($id) {
      $mailing->id = $id;
      $mailing->find(TRUE);
    }
    $mailing->domain_id = CRM_Utils_Array::value('domain_id', $params, CRM_Core_Config::domainID());

    if (!isset($params['replyto_email']) &&
      isset($params['from_email'])
    ) {
      $params['replyto_email'] = $params['from_email'];
    }

    $mailing->copyValues($params);

    $result = $mailing->save();

    if (!empty($ids['mailing'])) {
      CRM_Utils_Hook::post('edit', 'Mailing', $mailing->id, $mailing);
    }
    else {
      CRM_Utils_Hook::post('create', 'Mailing', $mailing->id, $mailing);
    }

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
   *  - _evil_bao_validator_: string|callable
   *
   * </twowrongsmakesaright>
   *
   * @params array $params
   *   Form values.
   *
   * @param array $params
   * @param array $ids
   *
   * @return object
   *   $mailing      The new mailing object
   * @throws \Exception
   */
  public static function create(&$params, $ids = array()) {
    // WTH $ids
    if (empty($ids) && isset($params['id'])) {
      $ids['mailing_id'] = $ids['id'] = $params['id'];
    }

    // CRM-12430
    // Do the below only for an insert
    // for an update, we should not set the defaults
    if (!isset($ids['id']) && !isset($ids['mailing_id'])) {
      // Retrieve domain email and name for default sender
      $domain = civicrm_api(
        'Domain',
        'getsingle',
        array(
          'version' => 3,
          'current_domain' => 1,
          'sequential' => 1,
        )
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
        $session =& CRM_Core_Session::singleton();
        $params['created_id'] = $session->get('userID');
      }
      $defaults = array(
        // load the default config settings for each
        // eg reply_id, unsubscribe_id need to use
        // correct template IDs here
        'override_verp' => TRUE,
        'forward_replies' => FALSE,
        'open_tracking' => TRUE,
        'url_tracking' => TRUE,
        'visibility' => 'Public Pages',
        'replyto_email' => $domain_email,
        'header_id' => CRM_Mailing_PseudoConstant::defaultComponent('header_id', ''),
        'footer_id' => CRM_Mailing_PseudoConstant::defaultComponent('footer_id', ''),
        'from_email' => $domain_email,
        'from_name' => $domain_name,
        'msg_template_id' => NULL,
        'created_id' => $params['created_id'],
        'approver_id' => NULL,
        'auto_responder' => 0,
        'created_date' => date('YmdHis'),
        'scheduled_date' => NULL,
        'approval_date' => NULL,
      );

      // Get the default from email address, if not provided.
      if (empty($defaults['from_email'])) {
        $defaultAddress = CRM_Core_OptionGroup::values('from_email_address', NULL, NULL, NULL, ' AND is_default = 1');
        foreach ($defaultAddress as $id => $value) {
          if (preg_match('/"(.*)" <(.*)>/', $value, $match)) {
            $defaults['from_email'] = $match[2];
            $defaults['from_name'] = $match[1];
          }
        }
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

    $mailing = self::add($params, $ids);

    if (is_a($mailing, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $mailing;
    }
    // update mailings with hash values
    CRM_Contact_BAO_Contact_Utils::generateChecksum($mailing->id, NULL, NULL, NULL, 'mailing', 16);

    $groupTableName = CRM_Contact_BAO_Group::getTableName();
    $mailingTableName = CRM_Mailing_BAO_Mailing::getTableName();

    /* Create the mailing group record */
    $mg = new CRM_Mailing_DAO_MailingGroup();
    $groupTypes = array('include' => 'Include', 'exclude' => 'Exclude', 'base' => 'Base');
    foreach (array('groups', 'mailings') as $entity) {
      foreach (array('include', 'exclude', 'base') as $type) {
        if (isset($params[$entity][$type])) {
          self::replaceGroups($mailing->id, $groupTypes[$type], $entity, $params[$entity][$type]);
        }
      }
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

    // If we're going to autosend, then check validity before saving.
    if (!empty($params['scheduled_date']) && $params['scheduled_date'] != 'null' && !empty($params['_evil_bao_validator_'])) {
      $cb = Civi\Core\Resolver::singleton()->get($params['_evil_bao_validator_']);
      $errors = call_user_func($cb, $mailing);
      if (!empty($errors)) {
        $fields = implode(',', array_keys($errors));
        throw new CRM_Core_Exception("Mailing cannot be sent. There are missing or invalid fields ($fields).", 'cannot-send', $errors);
      }
    }

    $transaction->commit();

    // Create parent job if not yet created.
    // Condition on the existence of a scheduled date.
    if (!empty($params['scheduled_date']) && $params['scheduled_date'] != 'null' && empty($params['_skip_evil_bao_auto_schedule_'])) {
      $job = new CRM_Mailing_BAO_MailingJob();
      $job->mailing_id = $mailing->id;
      $job->status = 'Scheduled';
      $job->is_test = 0;

      if (!$job->find(TRUE)) {
        $job->scheduled_date = $params['scheduled_date'];
        $job->save();
      }

      // Populate the recipients.
      if (empty($params['_skip_evil_bao_auto_recipients_'])) {
        self::getRecipients($job->id, $mailing->id, NULL, NULL, TRUE, $mailing->dedupe_email);
      }
    }

    return $mailing;
  }

  /**
   * @param CRM_Mailing_DAO_Mailing $mailing
   *   The mailing which may or may not be sendable.
   * @return array
   *   List of error messages.
   */
  public static function checkSendable($mailing) {
    $errors = array();
    foreach (array('subject', 'name', 'from_name', 'from_email') as $field) {
      if (empty($mailing->{$field})) {
        $errors[$field] = ts('Field "%1" is required.', array(
          1 => $field,
        ));
      }
    }
    if (empty($mailing->body_html) && empty($mailing->body_text)) {
      $errors['body'] = ts('Field "body_html" or "body_text" is required.');
    }

    if (!CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME, 'disable_mandatory_tokens_check')) {
      $header = $mailing->header_id && $mailing->header_id != 'null' ? CRM_Mailing_BAO_Component::findById($mailing->header_id) : NULL;
      $footer = $mailing->footer_id && $mailing->footer_id != 'null' ? CRM_Mailing_BAO_Component::findById($mailing->footer_id) : NULL;
      foreach (array('body_html', 'body_text') as $field) {
        if (empty($mailing->{$field})) {
          continue;
        }
        $str = ($header ? $header->{$field} : '') . $mailing->{$field} . ($footer ? $footer->{$field} : '');
        $err = CRM_Utils_Token::requiredTokens($str);
        if ($err !== TRUE) {
          foreach ($err as $token => $desc) {
            $errors["{$field}:{$token}"] = ts('This message is missing a required token - {%1}: %2',
              array(1 => $token, 2 => $desc)
            );
          }
        }
      }
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
   * @param array <int> $entityIds
   * @throws CiviCRM_API3_Exception
   */
  public static function replaceGroups($mailingId, $type, $entity, $entityIds) {
    $values = array();
    foreach ($entityIds as $entityId) {
      $values[] = array('entity_id' => $entityId);
    }
    civicrm_api3('mailing_group', 'replace', array(
      'mailing_id' => $mailingId,
      'group_type' => $type,
      'entity_table' => ($entity == 'groups') ? CRM_Contact_BAO_Group::getTableName() : CRM_Mailing_BAO_Mailing::getTableName(),
      'values' => $values,
    ));
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
    if (CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME, 'hash_mailing_url')) {
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
   *
   * @param bool $isSMS
   *
   * @return array
   *   Associative array of reporting data
   */
  public static function &report($id, $skipDetails = FALSE, $isSMS = FALSE) {
    $mailing_id = CRM_Utils_Type::escape($id, 'Integer');

    $mailing = new CRM_Mailing_BAO_Mailing();

    $t = array(
      'mailing' => self::getTableName(),
      'mailing_group' => CRM_Mailing_DAO_MailingGroup::getTableName(),
      'group' => CRM_Contact_BAO_Group::getTableName(),
      'job' => CRM_Mailing_BAO_MailingJob::getTableName(),
      'queue' => CRM_Mailing_Event_BAO_Queue::getTableName(),
      'delivered' => CRM_Mailing_Event_BAO_Delivered::getTableName(),
      'opened' => CRM_Mailing_Event_BAO_Opened::getTableName(),
      'reply' => CRM_Mailing_Event_BAO_Reply::getTableName(),
      'unsubscribe' => CRM_Mailing_Event_BAO_Unsubscribe::getTableName(),
      'bounce' => CRM_Mailing_Event_BAO_Bounce::getTableName(),
      'forward' => CRM_Mailing_Event_BAO_Forward::getTableName(),
      'url' => CRM_Mailing_BAO_TrackableURL::getTableName(),
      'urlopen' => CRM_Mailing_Event_BAO_TrackableURLOpen::getTableName(),
      'component' => CRM_Mailing_BAO_Component::getTableName(),
      'spool' => CRM_Mailing_BAO_Spool::getTableName(),
    );

    $report = array();
    $additionalWhereClause = " AND ";
    if (!$isSMS) {
      $additionalWhereClause .= " {$t['mailing']}.sms_provider_id IS NULL ";
    }
    else {
      $additionalWhereClause .= " {$t['mailing']}.sms_provider_id IS NOT NULL ";
    }

    /* Get the mailing info */

    $mailing->query("
            SELECT          {$t['mailing']}.*
            FROM            {$t['mailing']}
            WHERE           {$t['mailing']}.id = $mailing_id {$additionalWhereClause}");

    $mailing->fetch();

    $report['mailing'] = array();
    foreach (array_keys(self::fields()) as $field) {
      $report['mailing'][$field] = $mailing->$field;
    }

    //get the campaign
    if ($campaignId = CRM_Utils_Array::value('campaign_id', $report['mailing'])) {
      $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns($campaignId);
      $report['mailing']['campaign'] = $campaigns[$campaignId];
    }

    //mailing report is called by activity
    //we dont need all detail report
    if ($skipDetails) {
      return $report;
    }

    /* Get the component info */

    $query = array();

    $components = array(
      'header' => ts('Header'),
      'footer' => ts('Footer'),
      'reply' => ts('Reply'),
      'unsubscribe' => ts('Unsubscribe'),
      'optout' => ts('Opt-Out'),
    );
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

    $report['component'] = array();
    while ($mailing->fetch()) {
      $report['component'][] = array(
        'type' => $components[$mailing->type],
        'name' => $mailing->name,
        'link' => CRM_Utils_System::url('civicrm/mailing/component',
          "reset=1&action=update&id={$mailing->id}"
        ),
      );
    }

    /* Get the recipient group info */

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

    $report['group'] = array('include' => array(), 'exclude' => array(), 'base' => array());
    while ($mailing->fetch()) {
      $row = array();
      if (isset($mailing->group_id)) {
        $row['id'] = $mailing->group_id;
        $row['name'] = $mailing->group_title;
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

      /* Rename hidden groups */

      if ($mailing->group_hidden == 1) {
        $row['name'] = "Search Results";
      }

      if ($mailing->group_type == 'Include') {
        $report['group']['include'][] = $row;
      }
      elseif ($mailing->group_type == 'Base') {
        $report['group']['base'][] = $row;
      }
      else {
        $report['group']['exclude'][] = $row;
      }
    }

    /* Get the event totals, grouped by job (retries) */

    $mailing->query("
            SELECT          {$t['job']}.*,
                            COUNT(DISTINCT {$t['queue']}.id) as queue,
                            COUNT(DISTINCT {$t['delivered']}.id) as delivered,
                            COUNT(DISTINCT {$t['reply']}.id) as reply,
                            COUNT(DISTINCT {$t['forward']}.id) as forward,
                            COUNT(DISTINCT {$t['bounce']}.id) as bounce,
                            COUNT(DISTINCT {$t['urlopen']}.id) as url,
                            COUNT(DISTINCT {$t['spool']}.id) as spool
            FROM            {$t['job']}
            LEFT JOIN       {$t['queue']}
                    ON      {$t['queue']}.job_id = {$t['job']}.id
            LEFT JOIN       {$t['reply']}
                    ON      {$t['reply']}.event_queue_id = {$t['queue']}.id
            LEFT JOIN       {$t['forward']}
                    ON      {$t['forward']}.event_queue_id = {$t['queue']}.id
            LEFT JOIN       {$t['bounce']}
                    ON      {$t['bounce']}.event_queue_id = {$t['queue']}.id
            LEFT JOIN       {$t['delivered']}
                    ON      {$t['delivered']}.event_queue_id = {$t['queue']}.id
                    AND     {$t['bounce']}.id IS null
            LEFT JOIN       {$t['urlopen']}
                    ON      {$t['urlopen']}.event_queue_id = {$t['queue']}.id
            LEFT JOIN       {$t['spool']}
                    ON      {$t['spool']}.job_id = {$t['job']}.id
            WHERE           {$t['job']}.mailing_id = $mailing_id
                    AND     {$t['job']}.is_test = 0
            GROUP BY        {$t['job']}.id");

    $report['jobs'] = array();
    $report['event_totals'] = array();
    $elements = array(
      'queue',
      'delivered',
      'url',
      'forward',
      'reply',
      'unsubscribe',
      'optout',
      'opened',
      'bounce',
      'spool',
    );

    // initialize various counters
    foreach ($elements as $field) {
      $report['event_totals'][$field] = 0;
    }

    while ($mailing->fetch()) {
      $row = array();
      foreach ($elements as $field) {
        if (isset($mailing->$field)) {
          $row[$field] = $mailing->$field;
          $report['event_totals'][$field] += $mailing->$field;
        }
      }

      // compute open total separately to discount duplicates
      // CRM-1258
      $row['opened'] = CRM_Mailing_Event_BAO_Opened::getTotalCount($mailing_id, $mailing->id, TRUE);
      $report['event_totals']['opened'] += $row['opened'];

      // compute unsub total separately to discount duplicates
      // CRM-1783
      $row['unsubscribe'] = CRM_Mailing_Event_BAO_Unsubscribe::getTotalCount($mailing_id, $mailing->id, TRUE, TRUE);
      $report['event_totals']['unsubscribe'] += $row['unsubscribe'];

      $row['optout'] = CRM_Mailing_Event_BAO_Unsubscribe::getTotalCount($mailing_id, $mailing->id, TRUE, FALSE);
      $report['event_totals']['optout'] += $row['optout'];

      foreach (array_keys(CRM_Mailing_BAO_MailingJob::fields()) as $field) {
        $row[$field] = $mailing->$field;
      }

      if ($mailing->queue) {
        $row['delivered_rate'] = (100.0 * $mailing->delivered) / $mailing->queue;
        $row['bounce_rate'] = (100.0 * $mailing->bounce) / $mailing->queue;
        $row['unsubscribe_rate'] = (100.0 * $row['unsubscribe']) / $mailing->queue;
        $row['optout_rate'] = (100.0 * $row['optout']) / $mailing->queue;
      }
      else {
        $row['delivered_rate'] = 0;
        $row['bounce_rate'] = 0;
        $row['unsubscribe_rate'] = 0;
        $row['optout_rate'] = 0;
      }

      $row['links'] = array(
        'clicks' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=click&mid=$mailing_id&jid={$mailing->id}"
        ),
        'queue' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=queue&mid=$mailing_id&jid={$mailing->id}"
        ),
        'delivered' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=delivered&mid=$mailing_id&jid={$mailing->id}"
        ),
        'bounce' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=bounce&mid=$mailing_id&jid={$mailing->id}"
        ),
        'unsubscribe' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=unsubscribe&mid=$mailing_id&jid={$mailing->id}"
        ),
        'forward' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=forward&mid=$mailing_id&jid={$mailing->id}"
        ),
        'reply' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=reply&mid=$mailing_id&jid={$mailing->id}"
        ),
        'opened' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=opened&mid=$mailing_id&jid={$mailing->id}"
        ),
      );

      foreach (array(
                 'scheduled_date',
                 'start_date',
                 'end_date',
               ) as $key) {
        $row[$key] = CRM_Utils_Date::customFormat($row[$key]);
      }
      $report['jobs'][] = $row;
    }

    $newTableSize = CRM_Mailing_BAO_Recipients::mailingSize($mailing_id);

    // we need to do this for backward compatibility, since old mailings did not
    // use the mailing_recipients table
    if ($newTableSize > 0) {
      $report['event_totals']['queue'] = $newTableSize;
    }
    else {
      $report['event_totals']['queue'] = self::getRecipientsCount($mailing_id, $mailing_id);
    }

    if (!empty($report['event_totals']['queue'])) {
      $report['event_totals']['delivered_rate'] = (100.0 * $report['event_totals']['delivered']) / $report['event_totals']['queue'];
      $report['event_totals']['bounce_rate'] = (100.0 * $report['event_totals']['bounce']) / $report['event_totals']['queue'];
      $report['event_totals']['unsubscribe_rate'] = (100.0 * $report['event_totals']['unsubscribe']) / $report['event_totals']['queue'];
      $report['event_totals']['optout_rate'] = (100.0 * $report['event_totals']['optout']) / $report['event_totals']['queue'];
    }
    else {
      $report['event_totals']['delivered_rate'] = 0;
      $report['event_totals']['bounce_rate'] = 0;
      $report['event_totals']['unsubscribe_rate'] = 0;
      $report['event_totals']['optout_rate'] = 0;
    }

    /* Get the click-through totals, grouped by URL */

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
            LEFT JOIN  {$t['job']}
                    ON  {$t['queue']}.job_id = {$t['job']}.id
            WHERE       {$t['url']}.mailing_id = $mailing_id
                    AND {$t['job']}.is_test = 0
            GROUP BY    {$t['url']}.id");

    $report['click_through'] = array();

    while ($mailing->fetch()) {
      $report['click_through'][] = array(
        'url' => $mailing->url,
        'link' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=click&mid=$mailing_id&uid={$mailing->id}"
        ),
        'link_unique' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=click&mid=$mailing_id&uid={$mailing->id}&distinct=1"
        ),
        'clicks' => $mailing->clicks,
        'unique' => $mailing->unique_clicks,
        'rate' => CRM_Utils_Array::value('delivered', $report['event_totals']) ? (100.0 * $mailing->unique_clicks) / $report['event_totals']['delivered'] : 0,
      );
    }

    $report['event_totals']['links'] = array(
      'clicks' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=click&mid=$mailing_id"
      ),
      'clicks_unique' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=click&mid=$mailing_id&distinct=1"
      ),
      'queue' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=queue&mid=$mailing_id"
      ),
      'delivered' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=delivered&mid=$mailing_id"
      ),
      'bounce' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=bounce&mid=$mailing_id"
      ),
      'unsubscribe' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=unsubscribe&mid=$mailing_id"
      ),
      'optout' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=optout&mid=$mailing_id"
      ),
      'forward' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=forward&mid=$mailing_id"
      ),
      'reply' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=reply&mid=$mailing_id"
      ),
      'opened' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=opened&mid=$mailing_id"
      ),
    );

    $actionLinks = array(CRM_Core_Action::VIEW => array('name' => ts('Report')));
    if (CRM_Core_Permission::check('view all contacts')) {
      $actionLinks[CRM_Core_Action::ADVANCED] = array(
        'name' => ts('Advanced Search'),
        'url' => 'civicrm/contact/search/advanced',
      );
    }
    $action = array_sum(array_keys($actionLinks));

    $report['event_totals']['actionlinks'] = array();
    foreach (array(
               'clicks',
               'clicks_unique',
               'queue',
               'delivered',
               'bounce',
               'unsubscribe',
               'forward',
               'reply',
               'opened',
               'optout',
             ) as $key) {
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

        case 'forward':
          $reportFilter .= "&is_forwarded_value=1";
          $searchFilter .= "&mailing_forward=1";
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
          $url = "mailing/opened";
          $searchFilter .= "&mailing_open_status=Y";
          break;

        case 'clicks':
        case 'clicks_unique':
          $url = "mailing/clicks";
          $searchFilter .= "&mailing_click_status=Y";
          break;
      }
      $actionLinks[CRM_Core_Action::VIEW]['url'] = CRM_Report_Utils_Report::getNextUrl($url, $reportFilter, FALSE, TRUE);
      if (array_key_exists(CRM_Core_Action::ADVANCED, $actionLinks)) {
        $actionLinks[CRM_Core_Action::ADVANCED]['qs'] = $searchFilter;
      }
      $report['event_totals']['actionlinks'][$key] = CRM_Core_Action::formLink(
        $actionLinks,
        $action,
        array('mid' => $mailing_id),
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
   * @param
   *
   * @return int
   *   Count
   */
  public function getCount() {
    $this->selectAdd();
    $this->selectAdd('COUNT(id) as count');

    $session = CRM_Core_Session::singleton();
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
      CRM_Core_Error::fatal(ts('You do not have permission to access this mailing report'));
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

    $mailingIDs = array();

    // get all the groups that this user can access
    // if they dont have universal access
    $groups = CRM_Core_PseudoConstant::group(NULL, FALSE);
    if (!empty($groups)) {
      $groupIDs = implode(',', array_keys($groups));

      // get all the mailings that are in this subset of groups
      $query = "
SELECT    DISTINCT( m.id ) as id
  FROM    civicrm_mailing m
LEFT JOIN civicrm_mailing_group g ON g.mailing_id   = m.id
 WHERE ( ( g.entity_table like 'civicrm_group%' AND g.entity_id IN ( $groupIDs ) )
    OR   ( g.entity_table IS NULL AND g.entity_id IS NULL ) )
";
      $dao = CRM_Core_DAO::executeQuery($query);

      $mailingIDs = array();
      while ($dao->fetch()) {
        $mailingIDs[] = $dao->id;
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
   *   The nmber of rows to return.
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
    $group = CRM_Mailing_DAO_MailingGroup::getTableName();
    $session = CRM_Core_Session::singleton();

    $mailingACL = self::mailingACL();

    //get all campaigns.
    $allCampaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);

    // we only care about parent jobs, since that holds all the info on
    // the mailing
    $query = "
            SELECT      $mailing.id,
                        $mailing.name,
                        $job.status,
                        $mailing.approval_status_id,
                        MIN($job.scheduled_date) as scheduled_date,
                        MIN($job.start_date) as start_date,
                        MAX($job.end_date) as end_date,
                        createdContact.sort_name as created_by,
                        scheduledContact.sort_name as scheduled_by,
                        $mailing.created_id as created_id,
                        $mailing.scheduled_id as scheduled_id,
                        $mailing.is_archived as archived,
                        $mailing.created_date as created_date,
                        campaign_id,
                        $mailing.sms_provider_id as sms_provider_id
            FROM        $mailing
            LEFT JOIN   $job ON ( $job.mailing_id = $mailing.id AND $job.is_test = 0 AND $job.parent_id IS NULL )
            LEFT JOIN   civicrm_contact createdContact ON ( civicrm_mailing.created_id = createdContact.id )
            LEFT JOIN   civicrm_contact scheduledContact ON ( civicrm_mailing.scheduled_id = scheduledContact.id )
            WHERE       $mailingACL $additionalClause
            GROUP BY    $mailing.id ";

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
      $additionalParams = array();
    }

    $dao = CRM_Core_DAO::executeQuery($query, $additionalParams);

    $rows = array();
    while ($dao->fetch()) {
      $rows[] = array(
        'id' => $dao->id,
        'name' => $dao->name,
        'status' => $dao->status ? $dao->status : 'Not scheduled',
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
      );
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
   */
  public static function del($id) {
    if (empty($id)) {
      CRM_Core_Error::fatal();
    }

    CRM_Utils_Hook::pre('delete', 'Mailing', $id, CRM_Core_DAO::$_nullArray);

    // delete all file attachments
    CRM_Core_BAO_File::deleteEntityFile('civicrm_mailing',
      $id
    );

    $dao = new CRM_Mailing_DAO_Mailing();
    $dao->id = $id;
    $dao->delete();

    CRM_Core_Session::setStatus(ts('Selected mailing has been deleted.'), ts('Deleted'), 'success');

    CRM_Utils_Hook::post('delete', 'Mailing', $id, $dao);
  }

  /**
   * Delete Jobss and all its associated records
   * related to test Mailings
   *
   * @param int $id
   *   Id of the Job to delete.
   *
   * @return void
   */
  public static function delJob($id) {
    if (empty($id)) {
      CRM_Core_Error::fatal();
    }

    $dao = new CRM_Mailing_BAO_MailingJob();
    $dao->id = $id;
    $dao->delete();
  }

  /**
   * @return array
   */
  public function getReturnProperties() {
    $tokens = &$this->getTokens();

    $properties = array();
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

    $returnProperties = array();
    $returnProperties['display_name'] = $returnProperties['contact_id'] = $returnProperties['preferred_mail_format'] = $returnProperties['hash'] = 1;

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
    $tokens = array();

    if (method_exists($form, 'listTokens')) {
      $tokens = array_merge($form->listTokens(), $tokens);
    }

    //sorted in ascending order tokens by ignoring word case
    $form->assign('tokens', CRM_Utils_Token::formatTokensForDisplay($tokens));

    $templates = array();

    $textFields = array('text_message' => ts('HTML Format'), 'sms_text_message' => ts('SMS Message'));
    $modePrefixes = array('Mail' => NULL, 'SMS' => 'SMS');

    $className = CRM_Utils_System::getClassName($form);

    if ($className != 'CRM_SMS_Form_Upload' && $className != 'CRM_Contact_Form_Task_SMS' &&
      $className != 'CRM_Contact_Form_Task_SMS'
    ) {
      $form->add('wysiwyg', 'html_message',
        ts('HTML Format'),
        array(
          'cols' => '80',
          'rows' => '8',
          'onkeyup' => "return verify(this)",
        )
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
        array(
          'cols' => '80',
          'rows' => '8',
          'onkeyup' => "return verify(this, '{$prefix}')",
        )
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
          array('' => ts('- select -')) + $templates[$prefix], FALSE,
          array('onChange' => "selectValue( this.value, '{$prefix}');")
        );
      }
      $form->add('checkbox', "{$prefix}updateTemplate", ts('Update Template'), NULL);

      $form->add('checkbox', "{$prefix}saveTemplate", ts('Save As New Template'), NULL, FALSE,
        array('onclick' => "showSaveDetails(this, '{$prefix}');")
      );
      $form->add('text', "{$prefix}saveTemplateName", ts('Template Title'));
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
    $mailingIDs = array();
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
    $htmlHeader = $textHeader = NULL;
    $htmlFooter = $textFooter = NULL;

    if (!$isSMS) {
      if ($report['mailing']['header_id']) {
        $header = new CRM_Mailing_BAO_Component();
        $header->id = $report['mailing']['header_id'];
        $header->find(TRUE);
        $htmlHeader = $header->body_html;
        $textHeader = $header->body_text;
      }

      if ($report['mailing']['footer_id']) {
        $footer = new CRM_Mailing_BAO_Component();
        $footer->id = $report['mailing']['footer_id'];
        $footer->find(TRUE);
        $htmlFooter = $footer->body_html;
        $textFooter = $footer->body_text;
      }
    }

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
    static $_cache = array();

    if (!isset($_cache[$jobID])) {
      $query = "
SELECT     override_verp
FROM       civicrm_mailing
INNER JOIN civicrm_mailing_job ON civicrm_mailing.id = civicrm_mailing_job.mailing_id
WHERE  civicrm_mailing_job.id = %1
";
      $params = array(1 => array($jobID, 'Integer'));
      $_cache[$jobID] = CRM_Core_DAO::singleValueQuery($query, $params);
    }
    return $_cache[$jobID];
  }

  /**
   * @param null $mode
   *
   * @return bool
   * @throws Exception
   */
  public static function processQueue($mode = NULL) {
    $config = &CRM_Core_Config::singleton();

    if ($mode == NULL && CRM_Core_BAO_MailSettings::defaultDomain() == "EXAMPLE.ORG") {
      throw new CRM_Core_Exception(ts('The <a href="%1">default mailbox</a> has not been configured. You will find <a href="%2">more info in the online user and administrator guide</a>', array(
            1 => CRM_Utils_System::url('civicrm/admin/mailSettings', 'reset=1'),
            2 => "http://book.civicrm.org/user/advanced-configuration/email-system-configuration/",
          )));
    }

    // check if we are enforcing number of parallel cron jobs
    // CRM-8460
    $gotCronLock = FALSE;

    if (property_exists($config, 'mailerJobsMax') && $config->mailerJobsMax && $config->mailerJobsMax > 1) {
      $lockArray = range(1, $config->mailerJobsMax);
      shuffle($lockArray);

      // check if we are using global locks
      $serverWideLock = CRM_Core_BAO_Setting::getItem(
        CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
        'civimail_server_wide_lock'
      );
      foreach ($lockArray as $lockID) {
        $cronLock = new CRM_Core_Lock("civimail.cronjob.{$lockID}", NULL, $serverWideLock);
        if ($cronLock->isAcquired()) {
          $gotCronLock = TRUE;
          break;
        }
      }

      // exit here since we have enuf cronjobs running
      if (!$gotCronLock) {
        CRM_Core_Error::debug_log_message('Returning early, since max number of cronjobs running');
        return TRUE;
      }
    }

    // load bootstrap to call hooks

    // Split up the parent jobs into multiple child jobs
    $mailerJobSize = (property_exists($config, 'mailerJobSize')) ? $config->mailerJobSize : NULL;
    CRM_Mailing_BAO_MailingJob::runJobs_pre($mailerJobSize, $mode);
    CRM_Mailing_BAO_MailingJob::runJobs(NULL, $mode);
    CRM_Mailing_BAO_MailingJob::runJobs_post($mode);

    // lets release the global cron lock if we do have one
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
    $params = array(1 => array($mailingID, 'Integer'));

    $dao = CRM_Core_DAO::executeQuery($sql, $params);
  }

  /**
   * @param bool $isSMS
   *
   * @return mixed
   */
  public static function getMailingsList($isSMS = FALSE) {
    static $list = array();
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
ORDER BY civicrm_mailing.name";
      $mailing = CRM_Core_DAO::executeQuery($query);

      while ($mailing->fetch()) {
        $list[$mailing->id] = "{$mailing->name} :: {$mailing->end_date}";
      }
    }

    return $list;
  }

  /**
   * @param int $mid
   *
   * @return null|string
   */
  public static function hiddenMailingGroup($mid) {
    $sql = "
SELECT     g.id
FROM       civicrm_mailing m
INNER JOIN civicrm_mailing_group mg ON mg.mailing_id = m.id
INNER JOIN civicrm_group g ON mg.entity_id = g.id AND mg.entity_table = 'civicrm_group'
WHERE      g.is_hidden = 1
AND        mg.group_type = 'Include'
AND        m.id = %1
";
    $params = array(1 => array($mid, 'Integer'));
    return CRM_Core_DAO::singleValueQuery($sql, $params);
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
    $params['sort'] = CRM_Utils_Array::value('sortBy', $params);
    $params['caseId'] = NULL;

    // get contact mailings
    $mailings = CRM_Mailing_BAO_Mailing::getContactMailings($params);

    // add total
    $params['total'] = CRM_Mailing_BAO_Mailing::getContactMailingsCount($params);

    //CRM-12814
    if (!empty($mailings)) {
      $openCounts = CRM_Mailing_Event_BAO_Opened::getMailingContactCount(array_keys($mailings), $params['contact_id']);
      $clickCounts = CRM_Mailing_Event_BAO_TrackableURLOpen::getMailingContactCount(array_keys($mailings), $params['contact_id']);
    }

    // format params and add links
    $contactMailings = array();
    foreach ($mailings as $mailingId => $values) {
      $mailing = array();
      $mailing['subject'] = $values['subject'];
      $mailing['creator_name'] = CRM_Utils_System::href(
        $values['creator_name'],
        'civicrm/contact/view',
        "reset=1&cid={$values['creator_id']}");
      $mailing['recipients'] = CRM_Utils_System::href(ts('(recipients)'), 'civicrm/mailing/report/event',
        "mid={$values['mailing_id']}&reset=1&cid={$params['contact_id']}&event=queue&context=mailing");
      $mailing['start_date'] = CRM_Utils_Date::customFormat($values['start_date']);
      //CRM-12814
      $mailing['openstats'] = "Opens: " .
        CRM_Utils_Array::value($values['mailing_id'], $openCounts, 0) .
        "<br />Clicks: " .
        CRM_Utils_Array::value($values['mailing_id'], $clickCounts, 0);

      $actionLinks = array(
        CRM_Core_Action::VIEW => array(
          'name' => ts('View'),
          'url' => 'civicrm/mailing/view',
          'qs' => "reset=1&id=%%mkey%%",
          'title' => ts('View Mailing'),
          'class' => 'crm-popup',
        ),
        CRM_Core_Action::BROWSE => array(
          'name' => ts('Mailing Report'),
          'url' => 'civicrm/mailing/report',
          'qs' => "mid=%%mid%%&reset=1&cid=%%cid%%&context=mailing",
          'title' => ts('View Mailing Report'),
        ),
      );

      $mailingKey = $values['mailing_id'];
      if ($hash = CRM_Mailing_BAO_Mailing::getMailingHash($mailingKey)) {
        $mailingKey = $hash;
      }

      $mailing['links'] = CRM_Core_Action::formLink(
        $actionLinks,
        NULL,
        array(
          'mid' => $values['mailing_id'],
          'cid' => $params['contact_id'],
          'mkey' => $mailingKey,
        ),
        ts('more'),
        FALSE,
        'mailing.contact.action',
        'Mailing',
        $values['mailing_id']
      );

      array_push($contactMailings, $mailing);
    }

    $contactMailingsDT = array();
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
  static public function getContactMailings(&$params) {
    $params['version'] = 3;
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['limit'] = $params['rp'];
    $params['sort'] = CRM_Utils_Array::value('sortBy', $params);

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
  static public function getContactMailingsCount(&$params) {
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
    $fieldPerms = array();
    foreach ($fieldNames as $fieldName) {
      if ($fieldName == 'id') {
        $fieldPerms[$fieldName] = array(
          array('access CiviMail', 'schedule mailings', 'approve mailings', 'create mailings'), // OR
        );
      }
      elseif (in_array($fieldName, array('scheduled_date', 'scheduled_id'))) {
        $fieldPerms[$fieldName] = array(
          array('access CiviMail', 'schedule mailings'), // OR
        );
      }
      elseif (in_array($fieldName, array('approval_date', 'approver_id', 'approval_status_id', 'approval_note'))) {
        $fieldPerms[$fieldName] = array(
          array('access CiviMail', 'approve mailings'), // OR
        );
      }
      else {
        $fieldPerms[$fieldName] = array(
          array('access CiviMail', 'create mailings'), // OR
        );
      }
    }
    return $fieldPerms;
  }

}
