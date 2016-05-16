<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Upgrade_TwoOne_Page_Upgrade extends CRM_Core_Page {
  function run() {
    $upgrade = new CRM_Upgrade_Form();

    $message = ts('CiviCRM upgrade successful');
    if ($upgrade->checkVersion($upgrade->latestVersion)) {
      $message = ts('Your database has already been upgraded to CiviCRM %1',
        array(1 => $upgrade->latestVersion)
      );
    }
    elseif ($upgrade->checkVersion('2.1.2') ||
      $upgrade->checkVersion('2.1.3') ||
      $upgrade->checkVersion('2.1.4') ||
      $upgrade->checkVersion('2.1.5')
    ) {
      // do nothing, db version is changed for all upgrades
    }
    elseif ($upgrade->checkVersion('2.1.0') ||
      $upgrade->checkVersion('2.1') ||
      $upgrade->checkVersion('2.1.1')
    ) {
      // 2.1 to 2.1.2
      $this->runTwoOneTwo();
    }
    else {
      // 2.0 to 2.1
      for ($i = 1; $i <= 4; $i++) {
        $this->runForm($i);
      }
      // 2.1 to 2.1.2
      $this->runTwoOneTwo();
    }

    // just change the ver in the db, since nothing to upgrade
    $upgrade->setVersion($upgrade->latestVersion);

    // also cleanup the templates_c directory
    $config = CRM_Core_Config::singleton();
    $config->cleanup(1);

    $template = CRM_Core_Smarty::singleton();

    $template->assign('message', $message);
    $template->assign('pageTitle', ts('Upgrade CiviCRM to Version %1',
        array(1 => $upgrade->latestVersion)
      ));
    $template->assign('menuRebuildURL',
      CRM_Utils_System::url('civicrm/menu/rebuild',
        'reset=1'
      )
    );
    $contents = $template->fetch('CRM/common/success.tpl');
    echo $contents;
  }

  /**
   * @param $stepID
   *
   * @throws Exception
   */
  function runForm($stepID) {
    $formName = "CRM_Upgrade_TwoOne_Form_Step{$stepID}";
    $form = new $formName();

    $error = NULL;
    if (!$form->verifyPreDBState($error)) {
      if (!isset($error)) {
        $error = 'pre-condition failed for current upgrade step $stepID';
      }
      CRM_Core_Error::fatal($error);
    }

    if ($stepID == 4) {
      return;
    }

    $form->upgrade();

    if (!$form->verifyPostDBState($error)) {
      if (!isset($error)) {
        $error = 'post-condition failed for current upgrade step $stepID';
      }
      CRM_Core_Error::fatal($error);
    }
  }

  function runTwoOneTwo() {
    $formName = "CRM_Upgrade_TwoOne_Form_TwoOneTwo";
    $form = new $formName( '2.1.4' );

    $error = NULL;
    if (!$form->verifyPreDBState($error)) {
      if (!isset($error)) {
        $error = 'pre-condition failed for current upgrade for 2.1.2';
      }
      CRM_Core_Error::fatal($error);
    }

    $form->upgrade();

    if (!$form->verifyPostDBState($error)) {
      if (!isset($error)) {
        $error = 'post-condition failed for current upgrade for 2.1.2';
      }
      CRM_Core_Error::fatal($error);
    }
  }
}

