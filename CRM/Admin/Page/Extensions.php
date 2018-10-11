<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * This is a part of CiviCRM extension management functionality.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This page displays the list of extensions registered in the system.
 */
class CRM_Admin_Page_Extensions extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  static $_links = NULL;

  /**
   * Obtains the group name from url and sets the title.
   */
  public function preProcess() {
    Civi::resources()->addStyleFile('civicrm', 'css/admin.css');

    CRM_Utils_System::setTitle(ts('CiviCRM Extensions'));
    $destination = CRM_Utils_System::url('civicrm/admin/extensions',
      'reset=1');

    $destination = urlencode($destination);
    $this->assign('destination', $destination);
  }

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Core_BAO_Extension';
  }

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::ADD => array(
          'name' => ts('Install'),
          'url' => 'civicrm/admin/extensions',
          'qs' => 'action=add&id=%%id%%&key=%%key%%',
          'title' => ts('Install'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'url' => 'civicrm/admin/extensions',
          'qs' => 'action=enable&id=%%id%%&key=%%key%%',
          'ref' => 'enable-action',
          'title' => ts('Enable'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'url' => 'civicrm/admin/extensions',
          'qs' => 'action=disable&id=%%id%%&key=%%key%%',
          'title' => ts('Disable'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Uninstall'),
          'url' => 'civicrm/admin/extensions',
          'qs' => 'action=delete&id=%%id%%&key=%%key%%',
          'title' => ts('Uninstall Extension'),
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Download'),
          'url' => 'civicrm/admin/extensions',
          'qs' => 'action=update&id=%%id%%&key=%%key%%',
          'title' => ts('Download Extension'),
        ),
      );
    }
    return self::$_links;
  }

  /**
   * Run the basic page (run essentially starts execution for that page).
   */
  public function run() {
    $this->preProcess();
    return parent::run();
  }

  /**
   * Browse all options.
   */
  public function browse() {

    // build announcements at the top of the page
    $this->assign('extAddNewEnabled', CRM_Extension_System::singleton()->getBrowser()->isEnabled());
    $reqs = CRM_Extension_System::singleton()->getDownloader()->checkRequirements();
    if (empty($reqs)) {
      $reqs = CRM_Extension_System::singleton()->getBrowser()->checkRequirements();
    }
    if (empty($reqs)) {
      $reqs = CRM_Extension_System::singleton()->getDefaultContainer()->checkRequirements();
    }
    $this->assign('extAddNewReqs', $reqs);

    $this->assign('extDbUpgrades', CRM_Extension_Upgrades::hasPending());
    $this->assign('extDbUpgradeUrl', CRM_Utils_System::url('civicrm/admin/extensions/upgrade', 'reset=1'));

    // TODO: Debate whether to immediately detect changes in underlying source tree
    // $manager->refresh();

    $localExtensionRows = $this->formatLocalExtensionRows();
    uasort($localExtensionRows, 'CRM_Admin_Page_Extensions::sortExtensionByStatus');
    $this->assign('localExtensionRows', $localExtensionRows);

    $remoteExtensionRows = $this->formatRemoteExtensionRows($localExtensionRows);
    $this->assign('remoteExtensionRows', $remoteExtensionRows);

    $extensionRows = array_replace($remoteExtensionRows, $localExtensionRows);

    $this->categoriseExtensions($extensionRows);
    $this->sortExtensions($extensionRows);
    $this->assign('extensionsByCategory', $extensionRows);

    $this->assignExtensionCategoryNames($extensionRows);
    $this->assignExtensionCategoryToTabMap($extensionRows);
  }

  /**
   * Get the list of local extensions and format them as a table with
   * status and action data.
   *
   * @return array
   */
  public function formatLocalExtensionRows() {
    $mapper = CRM_Extension_System::singleton()->getMapper();
    $manager = CRM_Extension_System::singleton()->getManager();

    $localExtensionRows = array(); // array($pseudo_id => extended_CRM_Extension_Info)
    $keys = array_keys($manager->getStatuses());
    sort($keys);
    foreach ($keys as $key) {
      try {
        $obj = $mapper->keyToInfo($key);
      }
      catch (CRM_Extension_Exception $ex) {
        CRM_Core_Session::setStatus(ts('Failed to read extension (%1). Please refresh the extension list.', array(1 => $key)));
        continue;
      }

      $row = self::createExtendedInfo($obj);
      $row['id'] = $obj->key;

      // assign actions
      $action = 0;
      switch ($row['status']) {
        case CRM_Extension_Manager::STATUS_UNINSTALLED:
          $action += CRM_Core_Action::ADD;
          break;

        case CRM_Extension_Manager::STATUS_DISABLED:
          $action += CRM_Core_Action::ENABLE;
          $action += CRM_Core_Action::DELETE;
          break;

        case CRM_Extension_Manager::STATUS_DISABLED_MISSING:
          $action += CRM_Core_Action::DELETE;
          break;

        case CRM_Extension_Manager::STATUS_INSTALLED:
        case CRM_Extension_Manager::STATUS_INSTALLED_MISSING:
          $action += CRM_Core_Action::DISABLE;
          break;

        default:
      }
      // TODO if extbrowser is enabled and extbrowser has newer version than extcontainer,
      // then $action += CRM_Core_Action::UPDATE
      $row['action'] = CRM_Core_Action::formLink(self::links(),
        $action,
        array(
          'id' => $row['id'],
          'key' => $obj->key,
        ),
        ts('more'),
        FALSE,
        'extension.local.action',
        'Extension',
        $row['id']
      );
      // Key would be better to send, but it's not an integer.  Moreover, sending the
      // values to hook_civicrm_links means that you can still get at the key

      $localExtensionRows[$row['id']] = $row;
    }
    return $localExtensionRows;
  }

  /**
   * Get the list of local extensions and format them as a table with
   * status and action data.
   *
   * @param array $localExtensionRows
   * @return array
   */
  public function formatRemoteExtensionRows($localExtensionRows) {
    try {
      $remoteExtensions = CRM_Extension_System::singleton()->getBrowser()->getExtensions();
    }
    catch (CRM_Extension_Exception $e) {
      $remoteExtensions = array();
      CRM_Core_Session::setStatus($e->getMessage(), ts('Extension download error'), 'error');
    }

    // build list of available downloads
    $remoteExtensionRows = array();
    foreach ($remoteExtensions as $info) {
      $row = (array) $info;
      $row['id'] = $info->key;
      $action = CRM_Core_Action::UPDATE;
      $row['action'] = CRM_Core_Action::formLink(self::links(),
        $action,
        array(
          'id' => $row['id'],
          'key' => $row['key'],
        ),
        ts('more'),
        FALSE,
        'extension.remote.action',
        'Extension',
        $row['id']
      );
      if (isset($localExtensionRows[$info->key])) {
        if (array_key_exists('version', $localExtensionRows[$info->key])) {
          if (version_compare($localExtensionRows[$info->key]['version'], $info->version, '<')) {
            $row['is_upgradeable'] = TRUE;
          }
        }
      }
      else {
        $row['status'] = CRM_Extension_Manager::STATUS_REMOTE;
      }
      $remoteExtensionRows[$row['id']] = $row;
    }

    return $remoteExtensionRows;
  }

  /**
   *
   * @param array $extensionDetails - only the key is looked at for the switch,
   *   but the category is needed later.
   * @return string
   */
  private function getExtensionCategory($extensionDetails) {
    // In the future, this hardcoding should not be needed. It is present until
    // the extension development standards catch up.

    $category = 'Uncategorised';

    switch ($extensionDetails['key']) {
      case 'nz.co.fuzion.extendedreport':
      case 'ca.bidon.reporterror':
      case 'uk.co.compucorp.civicrm.pivotreport':
      case 'biz.jmaconsulting.financialaclreport':
      case 'ca.civicrm.logviewer':
      case 'biz.jmaconsulting.printgrantpdfs':
      case 'com.joineryhq.percentagepricesetfield':
      case 'coop.palantetech.nodrilldown':
      case 'eu.tttp.civisualize':
        $category = 'Reports';
        break;

      case 'uk.co.compucorp.civicrm.giftaid':
      case 'uk.co.vedaconsulting.module.giftaidonline':
      case 'com.webaccessglobal.simpledonate':
      case 'org.project60.sepa':
      case 'com.drastikbydesign.stripe':
      case 'com.iatspayments.civicrm':
      case 'org.civicrm.cdntaxreceipts':
      case 'net.ourpowerbase.report.advancedfundraising':
      case 'nz.co.fuzion.omnipaymultiprocessor':
      case 'com.joineryhq.percentagepricesetfield':
      case 'com.chrischinchilla.ewayrecurring':
      case 'com.aghstrategies.idbsurvey':
      case 'biz.jmaconsulting.lineitemedit':
      case 'org.civicrm.module.cividiscount':
        $category = 'Finance';
        break;

      case 'com.osseed.eventcalendar':
      case 'com.aghstrategies.eventmembershipsignup':
      case 'com.fountaintribe.eventhelper':
        $category = 'Events';
        break;

      case 'com.pogstone.contenttokens':
      case 'nz.co.fuzion.civitoken':
      case 'org.civicrm.casetokens':
      case 'com.pogstone.fancytokens':
        $category = 'Tokens';
        break;

      case 'org.civicrm.api4':
      case 'org.civicoop.emailapi':
      case 'org.civicoop.smsapi':
      case 'org.civicoop.pdfapi':
      case 'com.cividesk.apikey':
        $category = 'APIs';
        break;

      case 'uk.co.vedaconsulting.module.civicrmpostcodelookup':
      case 'org.civicoop.postcodenl':
      case 'org.civicoop.areas':
      case 'com.aghstrategies.uscounties':
        $category = 'Geography';
        break;

      case 'org.wikimedia.rip':
      case 'eu.tttp.group2summary':
      case 'net.ourpowerbase.sumfields':
      case 'eu.tttp.normalise':
      case 'org.wikimedia.contacteditor':
      case 'com.ginkgostreet.nickfix':
      case 'org.civicoop.relationship2summary':
      case 'eu.tttp.noverwrite':
      case 'org.wikimedia.relationshipblock':
      case 'org.civicrm.contactlayout':
      case 'org.woolman.genderselfidentify':
      case 'nz.co.fuzion.relatedpermissions':
        $category = 'Contacts';
        break;

      case 'uk.co.vedaconsulting.mosaico':
      case 'org.wikimedia.unsubscribeemail':
      case 'uk.co.vedaconsulting.mailchimp':
      case 'com.cividesk.email.sparkpost':
      case 'biz.jmaconsulting.ode':
      case 'biz.jmaconsulting.olarkchat':
      case 'uk.co.vedaconsulting.gotowebinar':
      case 'org.civicoop.templateattachments':
        $category = 'Communication';
        break;

      case 'org.civicrm.sms.clickatell':
      case 'org.civicrm.sms.twilio':
      case 'io.3sd.dummysms':
      case 'com.aghstrategies.tinymce':
        $category = 'SMSProviders';
        break;

      case 'com.webaccessglobal.module.civimobile':
      case 'eu.tttp.bootstrapvisualize':
      case 'com.aghstrategies.slicknav':
      case 'nz.co.fuzion.environmentindicator':
      case 'org.civicrm.recentmenu':
      case 'com.megaphonetech.fastactionlinks':
        $category = 'Interface';
        break;

      case 'org.civicoop.documents':
      case 'org.civicrm.multisite':
      case 'uk.co.compucorp.civicrm.booking':
      case 'biz.jmaconsulting.grantapplications':
      case 'org.civicrm.volunteer':
      case 'org.civicoop.civirules':
        $category = 'Modules';
        break;

      case 'com.megaphonetech.entitytemplates':
      case 'form-processor':
      case 'nz.co.fuzion.csvimport':
      case 'nz.co.fuzion.entitysetting':
      case 'ca.bidon.imagecrop':
      case 'ca.bidon.civiexportexcel':
      case 'biz.jmaconsulting.bugp':
      case 'com.joineryhq.activityical':
      case 'uk.co.vedaconsulting.gdpr':
        $category = 'Utilities';
        break;

      case 'test.extension.manager.paymenttest':
      case 'test.extension.manager.moduletest':
      case 'org.civicrm.angularex':
      case 'test.extension.manager.searchtest':
      case 'test.extension.manager.reporttest':
      case 'org.civicrm.angularprofiles':
      case 'org.civicrm.demoqueue':
        $category = 'Developers';
        break;

      default:
        $category = 'Uncategorised';
        break;
    }

    // Override it with whatever's in the info file.
    if (array_key_exists('category', $extensionDetails)) {
      if (!empty($extensionDetails['category'])) {
        // Spaces can break the behaviour. We remove them here and add them in
        // the label list in case any slip past the reviewers.
        $category = str_replace(' ', '', $extensionDetails['category']);
      }
    }

    return $category;
  }

  private function categoriseExtensions(&$extensions) {
    $categorisedExtensions = array();

    foreach ($extensions as $extensionKey => $eachExtension) {
      $category = $this->getExtensionCategory($eachExtension);

      if (!array_key_exists($category, $categorisedExtensions)) {
        $categorisedExtensions[$category] = array();
      }

      $categorisedExtensions[$category][$eachExtension['key']] = $eachExtension;

      unset($extensions[$extensionKey]); // Remove old entry to prevent memory spike.
    }

    $extensions = $categorisedExtensions;
  }

  private function sortExtensions(&$extensionsByCategory) {
    ksort($extensionsByCategory); // Sort by category array key.
    foreach ($extensionsByCategory as &$eachCategory) {
      usort($eachCategory, 'CRM_Admin_Page_Extensions::sortExtensionByStatus');
    }
  }

  private static function sortExtensionByStatus($a, $b) {
    if ($a['status'] == $b['status']) {
      return 0;
    }
    elseif ($a['status'] < $b['status']) {
      return -1;
    }
    elseif ($a['status'] > $b['status']) {
      return 1;
    }
  }

  /**
   * Assigns neat labels - including spaces - to be used by the Smarty templates.
   * This is needed because the categoryName forms part of the HTML elements.
   *
   * @param array $extensionRows
   */
  private function assignExtensionCategoryNames($extensionRows) {
    $extensionCategoryNames = array();
    // Translation of extension categories to their normal names.
    foreach ($extensionRows as $eachCategoryName => $eachCategoryRows) {
      $extensionCategoryNames[$eachCategoryName] = $eachCategoryName;
    }
    $extensionCategoryNames['DataCleaning'] = 'Data Cleaning';
    $extensionCategoryNames['SMSProviders'] = 'SMS Providers';
    $this->assign('extensionCategoryNames', $extensionCategoryNames);
  }

  /**
   * Creates and assigns a Category to Tab map to be used by Smarty templates.
   * @param array $extensionRows
   */
  private function assignExtensionCategoryToTabMap($extensionRows) {
    $extensionCategoryToTabMap = array();

    // By default, everything is mapped to itself. This makes it future proof.
    foreach ($extensionRows as $eachCategoryName => $eachCategoryRows) {
      $extensionCategoryToTabMap[$eachCategoryName] = array($eachCategoryName);
    }

    // We then merge categories manually underneath.
    $extensionCategoryToTabMap['Developers'][] = 'APIs';
    unset ($extensionCategoryToTabMap['APIs']);
    $extensionCategoryToTabMap['Communication'][] = 'Tokens';
    unset ($extensionCategoryToTabMap['Tokens']);
    $extensionCategoryToTabMap['Communication'][] = 'SMSProviders';
    unset ($extensionCategoryToTabMap['SMSProviders']);
    $extensionCategoryToTabMap['Utilities'][] = 'DataCleaning';
    unset ($extensionCategoryToTabMap['DataCleaning']);

    $this->assign('extensionCategoryToTabMap', $extensionCategoryToTabMap);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Admin_Form_Extensions';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'CRM_Admin_Form_Extensions';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context.
   */
  public function userContext($mode = NULL) {
    return 'civicrm/admin/extensions';
  }

  /**
   * Get userContext params.
   *
   * @param int $mode
   *   Mode that we are in.
   *
   * @return string
   */
  public function userContextParams($mode = NULL) {
    return 'reset=1&action=browse';
  }

  /**
   * Take an extension's raw XML info and add information about the
   * extension's status on the local system.
   *
   * The result format resembles the old CRM_Core_Extensions_Extension.
   *
   * @param CRM_Extension_Info $obj
   *
   * @return array
   */
  public static function createExtendedInfo(CRM_Extension_Info $obj) {
    return CRM_Extension_System::createExtendedInfo($obj);
  }

}
