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
class CRM_Upgrade_Page_Upgrade extends CRM_Core_Page {

  /**
   * Run upgrade.
   *
   * @throws \Exception
   */
  public function run() {
    set_time_limit(0);

    Civi::resources()->addStyleFile('civicrm', 'css/admin.css');

    $upgrade = new CRM_Upgrade_Form();
    [$currentVer, $latestVer] = $upgrade->getUpgradeVersions();

    CRM_Utils_System::setTitle(ts('Upgrade CiviCRM to Version %1',
      [1 => $latestVer]
    ));

    $template = CRM_Core_Smarty::singleton();
    $template->assign('pageTitle', ts('Upgrade CiviCRM to Version %1',
      [1 => $latestVer]
    ));
    $template->assign('cancelURL',
      CRM_Utils_System::url('civicrm/dashboard', 'reset=1')
    );

    $action = $_REQUEST['action'] ?? 'intro';
    switch ($action) {
      case 'intro':
        $this->runIntro();
        break;

      case 'begin':
        $this->runBegin();
        break;

      case 'finish':
        $this->runFinish();
        break;

      default:
        throw new CRM_Core_Exception(ts('Unrecognized upgrade action'));
    }
  }

  /**
   * Display an introductory screen with any pre-upgrade messages.
   */
  public function runIntro() {
    $upgrade = new CRM_Upgrade_Form();
    $template = CRM_Core_Smarty::singleton();
    [$currentVer, $latestVer] = $upgrade->getUpgradeVersions();
    CRM_Core_Smarty::singleton()->assign('sid', CRM_Utils_System::getSiteID());
    // Show success msg if db already upgraded
    if (version_compare($currentVer, $latestVer) == 0) {
      $template->assign('message', '');
      $template->assign('upgraded', TRUE);
      $template->assign('newVersion', $latestVer);
      CRM_Utils_System::setTitle(ts('Your database has already been upgraded to CiviCRM %1',
        [1 => $latestVer]
      ));
      $template->assign('pageTitle', ts('Your database has already been upgraded to CiviCRM %1',
        [1 => $latestVer]
      ));
    }

    // Throw error if db in unexpected condition
    elseif ($error = $upgrade->checkUpgradeableVersion($currentVer, $latestVer)) {
      throw new CRM_Core_Exception($error);
    }

    else {
      $config = CRM_Core_Config::singleton();

      // All cached content needs to be cleared because the civi codebase was just replaced
      CRM_Core_Resources::singleton()->flushStrings()->resetCacheCode();

      // cleanup only the templates_c directory
      $config->cleanup(1, FALSE);

      $preUpgradeMessage = NULL;
      $upgrade->setPreUpgradeMessage($preUpgradeMessage, $currentVer, $latestVer);

      $template->assign('preUpgradeMessage', $preUpgradeMessage);
      $template->assign('currentVersion', $currentVer);
      $template->assign('newVersion', $latestVer);
      $template->assign('upgradeTitle', ts('Upgrade CiviCRM from v %1 To v %2',
        [1 => $currentVer, 2 => $latestVer]
      ));
      $template->assign('upgraded', FALSE);
    }

    $content = $template->fetch('CRM/common/success.tpl');

    if ($this->_print) {
      // unexpected - trying to print the output of the upgrader?
      // @todo remove this case and just ignore $this->_print entirely
      // for now we use the original call, to maintain preexisting behaviour (however strange that is)
      \CRM_Core_Error::deprecatedWarning('Calling CRM_Utils_System::theme with $print and $maintenance is unexpected and may behave strangely. This codepath will be removed in a future release. If you need it, please comment on https://lab.civicrm.org/dev/core/-/issues/5803');
      echo CRM_Utils_System::theme($content, $this->_print, TRUE);
    }
    else {
      echo CRM_Utils_System::renderMaintenanceMessage($content);
    }
  }

  /**
   * Begin the upgrade by building a queue of tasks and redirecting to the queue-runner
   */
  public function runBegin() {
    $upgrade = new CRM_Upgrade_Form();
    [$currentVer, $latestVer] = $upgrade->getUpgradeVersions();

    if ($error = $upgrade->checkUpgradeableVersion($currentVer, $latestVer)) {
      throw new CRM_Core_Exception($error);
    }

    $config = CRM_Core_Config::singleton();

    $postUpgradeMessage = '<span class="bold">' . ts('Congratulations! Your upgrade was successful!') . '</span>';

    // lets drop all the triggers here
    CRM_Core_DAO::dropTriggers();

    $this->set('isUpgradePending', TRUE);

    // Persistent message storage across upgrade steps. TODO: Use structured message store
    // Note: In clustered deployments, this file must be accessible by all web-workers.
    $this->set('postUpgradeMessageFile', CRM_Utils_File::tempnam('civicrm-post-upgrade'));
    file_put_contents($this->get('postUpgradeMessageFile'), $postUpgradeMessage);

    $queueRunner = new CRM_Queue_Runner([
      'title' => ts('CiviCRM Upgrade Tasks'),
      'queue' => CRM_Upgrade_Form::buildQueue($currentVer, $latestVer, $this->get('postUpgradeMessageFile')),
      'isMinimal' => TRUE,
      'pathPrefix' => 'civicrm/upgrade/queue',
      'onEndUrl' => CRM_Utils_System::url('civicrm/upgrade', 'action=finish', FALSE, NULL, FALSE),
      'buttons' => ['retry' => $config->debug, 'skip' => $config->debug],
    ]);
    $queueRunner->runAllViaWeb();
    throw new CRM_Core_Exception(ts('Upgrade failed to redirect'));
  }

  /**
   * Display any final messages, clear caches, etc
   */
  public function runFinish() {
    $upgrade = new CRM_Upgrade_Form();
    $template = CRM_Core_Smarty::singleton();

    // If we're redirected from queue-runner, then isUpgradePending=true.
    // If user then reloads the finish page, the isUpgradePending will be unset. (Because the session has been cleared.)
    if ($this->get('isUpgradePending')) {
      // TODO: Use structured message store
      $postUpgradeMessage = file_get_contents($this->get('postUpgradeMessageFile'));

      // This destroys $session, so do it after get('postUpgradeMessageFile')
      CRM_Upgrade_Form::doFinish();
    }
    else {
      // Session was destroyed! Can't recover messages.
      $postUpgradeMessage = '';
    }

    // do a version check - after doFinish() sets the final version
    [$currentVer, $latestVer] = $upgrade->getUpgradeVersions();
    if ($error = $upgrade->checkCurrentVersion($currentVer, $latestVer)) {
      throw new CRM_Core_Exception($error);
    }

    $template->assign('message', $postUpgradeMessage);
    $template->assign('upgraded', TRUE);
    $template->assign('newVersion', $latestVer);
    $template->assign('sid', CRM_Utils_System::getSiteID());

    $content = $template->fetch('CRM/common/success.tpl');

    if ($this->_print) {
      // unexpected - trying to print the output of the upgrader?
      // @todo remove this case and just ignore $this->_print entirely
      // for now we use the original call, to maintain preexisting behaviour (however strange that is)
      \CRM_Core_Error::deprecatedWarning('Calling CRM_Utils_System::theme with $print and $maintenance is unexpected and may behave strangely. This codepath will be removed in a future release. If you need it, please comment on https://lab.civicrm.org/dev/core/-/issues/5803');
      echo CRM_Utils_System::theme($content, $this->_print, TRUE);
    }
    else {
      echo CRM_Utils_System::renderMaintenanceMessage($content);
    }
  }

}
