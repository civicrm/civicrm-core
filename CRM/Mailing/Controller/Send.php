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
class CRM_Mailing_Controller_Send extends CRM_Core_Controller {

  /**
   * Class constructor.
   *
   * @param string $title
   * @param bool|int $action
   * @param bool $modal
   *
   * @throws \Exception
   */
  public function __construct($title = NULL, $action = CRM_Core_Action::NONE, $modal = TRUE) {
    parent::__construct($title, $modal, NULL, FALSE, TRUE);

    // New:            civicrm/mailing/send?reset=1
    // Re-use:         civicrm/mailing/send?reset=1&mid=%%mid%%
    // Continue:       civicrm/mailing/send?reset=1&mid=%%mid%%&continue=true
    $mid = CRM_Utils_Request::retrieve('mid', 'Positive');
    $continue = CRM_Utils_Request::retrieve('continue', 'String');
    if (!$mid) {
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/a/', NULL, TRUE, '/mailing/new'));
    }
    if ($mid && $continue) {
      //CRM-15979 - check if abtest exist for mailing then redirect accordingly
      $abtest = CRM_Mailing_BAO_MailingAB::getABTest($mid);
      if (!empty($abtest) && !empty($abtest->id)) {
        $redirect = CRM_Utils_System::url('civicrm/a/', NULL, TRUE, '/abtest/' . $abtest->id);
      }
      else {
        $redirect = CRM_Utils_System::url('civicrm/a/', NULL, TRUE, '/mailing/' . $mid);
      }
      CRM_Utils_System::redirect($redirect);
    }
    if ($mid && !$continue) {
      $clone = civicrm_api3('Mailing', 'clone', ['id' => $mid]);
      civicrm_api3('Mailing', 'create', [
        'id' => $clone['id'],
        'name' => ts('Copy of %1', [1 => $clone['values'][$clone['id']]['name']]),
      ]);

      // Remove non active groups from clone
      $mailingGroups = \Civi\Api4\MailingGroup::get(FALSE)
        ->addSelect('id', 'group.title', 'group.id')
        ->addJoin('Group AS group', 'INNER', ['entity_id', '=', 'group.id'], ['group.is_active', '=', FALSE])
        ->addWhere('mailing_id', '=', $clone['id'])
        ->execute();
      $removeGroups = [];
      foreach ($mailingGroups as $mailingGroup) {
        // We use the group title to construct HTML for setStatus()
        $removeGroups[$mailingGroup['id']] = htmlentities($mailingGroup['group.title']);
      }
      if (!empty($removeGroups)) {
        $results = \Civi\Api4\MailingGroup::delete(FALSE)
          ->addWhere('id', 'IN', array_keys($removeGroups))
          ->addWhere('mailing_id', '=', $clone['id'])
          ->execute();
        CRM_Core_Session::setStatus(
          ts('Remove %1 disabled group(s) while copying: <ul><li>%2</li></ul>Please check recipients.', [
            1 => count($removeGroups),
            2 => implode('</li><li>', $removeGroups),
          ]),
          ts('Removed disabled groups')
        );
      }

      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/a/', NULL, TRUE, '/mailing/' . $clone['id']));
    }
  }

}
