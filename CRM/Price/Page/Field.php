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
 * Create a page for displaying Price Fields.
 *
 * Heart of this class is the run method which checks
 * for action type and then displays the appropriate
 * page.
 *
 */
class CRM_Price_Page_Field extends CRM_Core_Page {

  public $useLivePageJS = TRUE;

  /**
   * The price set group id of the field.
   *
   * @var int
   */
  protected $_sid;

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  private static $_actionLinks;

  /**
   * The price set is reserved or not.
   *
   * @var bool
   */
  protected $_isSetReserved = FALSE;

  /**
   * Get the action links for this page.
   *
   * @return array
   *   array of action links that we need to display for the browse screen
   */
  public static function &actionLinks() {
    if (!isset(self::$_actionLinks)) {
      self::$_actionLinks = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit Price Field'),
          'url' => 'civicrm/admin/price/field/edit',
          'qs' => 'action=update&reset=1&sid=%%sid%%&fid=%%fid%%',
          'title' => ts('Edit Price'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
        CRM_Core_Action::PREVIEW => [
          'name' => ts('Preview Field'),
          'url' => 'civicrm/admin/price/field/edit',
          'qs' => 'action=preview&reset=1&sid=%%sid%%&fid=%%fid%%',
          'title' => ts('Preview Price'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::PREVIEW),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Price'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DISABLE),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Price'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ENABLE),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/price/field/edit',
          'qs' => 'action=delete&reset=1&sid=%%sid%%&fid=%%fid%%',
          'title' => ts('Delete Price'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ],
      ];
    }
    return self::$_actionLinks;
  }

  /**
   * Browse all price set fields.
   */
  public function browse() {
    $resourceManager = CRM_Core_Resources::singleton();
    if (!empty($_GET['new']) && $resourceManager->ajaxPopupsEnabled) {
      $resourceManager->addScriptFile('civicrm', 'js/crm.addNew.js', 999, 'html-header');
    }

    $priceField = [];
    $priceFieldBAO = new CRM_Price_BAO_PriceField();

    // fkey is sid
    $priceFieldBAO->price_set_id = $this->_sid;
    $priceFieldBAO->orderBy('weight, label');
    $priceFieldBAO->find();

    // display taxTerm for priceFields
    $taxTerm = Civi::settings()->get('tax_term');
    $getTaxDetails = FALSE;
    $taxRate = CRM_Core_PseudoConstant::getTaxRates();
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialTypes);
    while ($priceFieldBAO->fetch()) {
      $priceField[$priceFieldBAO->id] = [];
      CRM_Core_DAO::storeValues($priceFieldBAO, $priceField[$priceFieldBAO->id]);

      // get price if it's a text field
      if ($priceFieldBAO->html_type == 'Text') {
        $optionValues = [];
        $params = ['price_field_id' => $priceFieldBAO->id];

        CRM_Price_BAO_PriceFieldValue::retrieve($params, $optionValues);
        $priceField[$priceFieldBAO->id]['price'] = $optionValues['amount'] ?? NULL;
        $financialTypeId = $optionValues['financial_type_id'];
        if (Civi::settings()->get('invoicing') && isset($taxRate[$financialTypeId])) {
          $priceField[$priceFieldBAO->id]['tax_rate'] = $taxRate[$financialTypeId];
          $getTaxDetails = TRUE;
        }
        if (isset($priceField[$priceFieldBAO->id]['tax_rate'])) {
          $taxAmount = CRM_Contribute_BAO_Contribution_Utils::calculateTaxAmount($priceField[$priceFieldBAO->id]['price'], $priceField[$priceFieldBAO->id]['tax_rate']);
          $priceField[$priceFieldBAO->id]['tax_amount'] = $taxAmount['tax_amount'];
        }
      }

      $action = array_sum(array_keys(self::actionLinks()));

      if ($this->_isSetReserved) {
        $action -= CRM_Core_Action::UPDATE + CRM_Core_Action::DELETE + CRM_Core_Action::ENABLE + CRM_Core_Action::DISABLE;
      }
      else {
        if ($priceFieldBAO->is_active) {
          $action -= CRM_Core_Action::ENABLE;
        }
        else {
          $action -= CRM_Core_Action::DISABLE;
        }
      }

      if (!isset($priceField[$priceFieldBAO->id]['active_on']) || $priceFieldBAO->active_on == '0000-00-00 00:00:00') {
        $priceField[$priceFieldBAO->id]['active_on'] = '';
      }

      if (!isset($priceField[$priceFieldBAO->id]['expire_on']) || $priceFieldBAO->expire_on == '0000-00-00 00:00:00') {
        $priceField[$priceFieldBAO->id]['expire_on'] = '';
      }

      // need to translate html types from the db
      $htmlTypes = CRM_Price_BAO_PriceField::htmlTypes();
      $priceField[$priceFieldBAO->id]['html_type_display'] = $htmlTypes[$priceField[$priceFieldBAO->id]['html_type']];
      $priceField[$priceFieldBAO->id]['order'] = $priceField[$priceFieldBAO->id]['weight'];
      $priceField[$priceFieldBAO->id]['action'] = CRM_Core_Action::formLink(
        self::actionLinks(),
        $action,
        [
          'fid' => $priceFieldBAO->id,
          'sid' => $this->_sid,
        ],
        ts('more'),
        FALSE,
        'priceField.row.actions',
        'PriceField',
        $priceFieldBAO->id
      );
      $this->assign('taxTerm', $taxTerm);
      $this->assign('getTaxDetails', $getTaxDetails);
    }

    $returnURL = CRM_Utils_System::url('civicrm/admin/price/field', "reset=1&action=browse&sid={$this->_sid}");
    $filter = "price_set_id = {$this->_sid}";
    CRM_Utils_Weight::addOrder($priceField, 'CRM_Price_DAO_PriceField',
      'id', $returnURL, $filter
    );
    $this->assign('priceField', $priceField);
  }

  /**
   * Edit price data.
   *
   * editing would involved modifying existing fields + adding data to new fields.
   *
   * @param string $action
   *   The action to be invoked.
   */
  public function edit($action) {
    // create a simple controller for editing price data
    $controller = new CRM_Core_Controller_Simple('CRM_Price_Form_Field', ts('Price Field'), $action);

    // set the userContext stack
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/price/field', 'reset=1&action=browse&sid=' . $this->_sid));

    $controller->set('sid', $this->_sid);
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   *
   * @return void
   */
  public function run() {

    // get the group id
    $this->_sid = CRM_Utils_Request::retrieve('sid', 'Positive',
      $this
    );
    $fid = CRM_Utils_Request::retrieve('fid', 'Positive',
      $this, FALSE, 0
    );
    $action = CRM_Utils_Request::retrieve('action', 'String',
      // default to 'browse'
      $this, FALSE, 'browse'
    );

    if ($this->_sid) {
      $usedBy = CRM_Price_BAO_PriceSet::getUsedBy($this->_sid);
      $this->assign('usedBy', $usedBy);
      $this->_isSetReserved = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_sid, 'is_reserved');
      $this->assign('isReserved', $this->_isSetReserved);

      CRM_Price_BAO_PriceSet::checkPermission($this->_sid);
      $comps = [
        'Event' => 'civicrm_event',
        'Contribution' => 'civicrm_contribution_page',
        'EventTemplate' => 'civicrm_event_template',
      ];
      $priceSetContexts = [];
      foreach ($comps as $name => $table) {
        if (array_key_exists($table, $usedBy)) {
          $priceSetContexts[] = $name;
        }
      }
      $this->assign('contexts', $priceSetContexts);
    }

    if ($action & (CRM_Core_Action::DELETE) && !$this->_isSetReserved) {
      if (empty($usedBy)) {
        // prompt to delete
        $session = CRM_Core_Session::singleton();
        $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/price/field', 'reset=1&action=browse&sid=' . $this->_sid));
        $controller = new CRM_Core_Controller_Simple('CRM_Price_Form_DeleteField', 'Delete Price Field', '');
        $controller->set('fid', $fid);
        $controller->setEmbedded(TRUE);
        $controller->process();
        $controller->run();
      }
      else {
        // add breadcrumb
        $url = CRM_Utils_System::url('civicrm/admin/price/field', 'reset=1');
        CRM_Utils_System::appendBreadCrumb([
          [
            'title' => ts('Price'),
            'url' => $url,
          ],
        ]);
        $this->assign('usedPriceSetTitle', CRM_Price_BAO_PriceField::getTitle($fid));
      }
    }

    if ($action & CRM_Core_Action::DELETE) {
      CRM_Utils_System::setTitle(ts('Delete Price Field'));
    }
    elseif ($this->_sid) {
      $groupTitle = CRM_Price_BAO_PriceSet::getTitle($this->_sid);
      $this->assign('sid', $this->_sid);
      $this->assign('groupTitle', $groupTitle);
      CRM_Utils_System::setTitle(ts('%1 - Price Fields', [1 => $groupTitle]));
    }

    // assign vars to templates
    $this->assign('action', $action);

    // what action to take ?
    if ($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD) && !$this->_isSetReserved) {
      // no browse for edit/update/view
      $this->edit($action);
    }
    elseif ($action & CRM_Core_Action::PREVIEW) {
      $this->preview($fid);
    }
    else {
      $this->browse();
    }

    // Call the parents run method
    return parent::run();
  }

  /**
   * Preview price field.
   *
   * @param int $fid
   *
   * @internal param int $id price field id
   *
   * @return void
   */
  public function preview($fid) {
    $controller = new CRM_Core_Controller_Simple('CRM_Price_Form_Preview', ts('Preview Form Field'), CRM_Core_Action::PREVIEW);
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/price/field', 'reset=1&action=browse&sid=' . $this->_sid));
    $controller->set('fieldId', $fid);
    $controller->set('groupId', $this->_sid);
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();
  }

}
