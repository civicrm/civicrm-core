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
 * Create a page for displaying Custom Options.
 *
 * Heart of this class is the run method which checks
 * for action type and then displays the appropriate
 * page.
 *
 */
class CRM_Price_Page_Option extends CRM_Core_Page {

  use CRM_Financial_Form_SalesTaxTrait;

  public $useLivePageJS = TRUE;

  /**
   * The field id of the option.
   *
   * @var int
   */
  protected $_fid;

  /**
   * The field id of the option.
   *
   * @var int
   */
  protected $_sid;

  /**
   * The price set is reserved or not.
   *
   * @var bool
   */
  protected $_isSetReserved = FALSE;

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  private static $_actionLinks;

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
          'name' => ts('Edit Option'),
          'url' => 'civicrm/admin/price/field/option/edit',
          'qs' => 'reset=1&action=update&oid=%%oid%%&fid=%%fid%%&sid=%%sid%%',
          'title' => ts('Edit Price Option'),
          'weight' => -50,
        ],
        CRM_Core_Action::VIEW => [
          'name' => ts('View'),
          'url' => 'civicrm/admin/price/field/option/edit',
          'qs' => 'action=view&oid=%%oid%%',
          'title' => ts('View Price Option'),
          'weight' => CRM_Core_Action::getLabel(CRM_Core_Action::VIEW),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Price Option'),
          'weight' => CRM_Core_Action::getLabel(CRM_Core_Action::DISABLE),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Price Option'),
          'weight' => CRM_Core_Action::getLabel(CRM_Core_Action::ENABLE),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/price/field/option/edit',
          'qs' => 'action=delete&oid=%%oid%%',
          'title' => ts('Disable Price Option'),
          'weight' => CRM_Core_Action::getLabel(CRM_Core_Action::DELETE),
        ],
      ];
    }
    return self::$_actionLinks;
  }

  /**
   * Browse all price fields.
   *
   * @return void
   */
  public function browse(): void {
    $this->assign('usedBy', NULL);
    $priceOptions = civicrm_api3('PriceFieldValue', 'get', [
      'price_field_id' => $this->_fid,
         // Explicitly do not check permissions so we are not
         // restricted by financial type, so we can change them.
      'check_permissions' => FALSE,
      'options' => [
        'limit' => 0,
        'sort' => ['weight', 'label'],
      ],
    ]);
    $customOption = $priceOptions['values'];

    // CRM-15378 - check if these price options are in an Event price set
    $isEvent = FALSE;
    $extendComponentId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_sid, 'extends', 'id');
    $allComponents = explode(CRM_Core_DAO::VALUE_SEPARATOR, $extendComponentId);
    $eventComponentId = CRM_Core_Component::getComponentID('CiviEvent');
    if (in_array($eventComponentId, $allComponents)) {
      $isEvent = TRUE;
    }

    $taxRate = CRM_Core_PseudoConstant::getTaxRates();

    $getTaxDetails = FALSE;
    foreach ($customOption as $id => $values) {
      $action = array_sum(array_keys(self::actionLinks()));
      // Adding the required fields in the array
      if (isset($taxRate[$values['financial_type_id']])) {
        // Cast to float so trailing zero decimals are removed
        $customOption[$id]['tax_rate'] = (float) $taxRate[$values['financial_type_id']];
        if (Civi::settings()->get('invoicing') && isset($customOption[$id]['tax_rate'])) {
          $getTaxDetails = TRUE;
        }
        $taxAmount = CRM_Contribute_BAO_Contribution_Utils::calculateTaxAmount($customOption[$id]['amount'], $customOption[$id]['tax_rate']);
        $customOption[$id]['tax_amount'] = $taxAmount['tax_amount'];
      }
      if (!empty($values['financial_type_id'])) {
        $customOption[$id]['financial_type_id'] = CRM_Contribute_PseudoConstant::financialType($values['financial_type_id']);
      }
      // update enable/disable links depending on price_field properties.
      if ($this->_isSetReserved) {
        $action -= CRM_Core_Action::UPDATE + CRM_Core_Action::DELETE + CRM_Core_Action::DISABLE + CRM_Core_Action::ENABLE;
      }
      else {
        if ($values['is_active']) {
          $action -= CRM_Core_Action::ENABLE;
        }
        else {
          $action -= CRM_Core_Action::DISABLE;
        }
      }
      $customOption[$id]['order'] = $customOption[$id]['weight'];
      $customOption[$id]['help_pre'] ??= NULL;
      $customOption[$id]['help_post'] ??= NULL;
      $customOption[$id]['action'] = CRM_Core_Action::formLink(self::actionLinks(), $action,
        [
          'oid' => $id,
          'fid' => $this->_fid,
          'sid' => $this->_sid,
        ],
        ts('more'),
        FALSE,
        'priceFieldValue.row.actions',
        'PriceFieldValue',
        $id
      );
    }
    // Add order changing widget to selector
    $returnURL = CRM_Utils_System::url('civicrm/admin/price/field/option', "action=browse&reset=1&fid={$this->_fid}&sid={$this->_sid}");
    $filter = "price_field_id = {$this->_fid}";
    CRM_Utils_Weight::addOrder($customOption, 'CRM_Price_DAO_PriceFieldValue',
      'id', $returnURL, $filter
    );

    $this->assign('getTaxDetails', $getTaxDetails);
    $this->assign('customOption', $customOption);
    $this->assign('sid', $this->_sid);
    $this->assign('isEvent', $isEvent);
    $this->assign('taxTerm', $this->getSalesTaxTerm());
  }

  /**
   * Edit custom Option.
   *
   * editing would involved modifying existing fields + adding data to new fields.
   *
   * @param string $action
   *   The action to be invoked.
   *
   * @return void
   */
  public function edit($action) {
    $oid = CRM_Utils_Request::retrieve('oid', 'Positive',
      $this, FALSE, 0
    );
    $params = [];
    if ($oid) {
      $params['oid'] = $oid;
      $sid = CRM_Price_BAO_PriceSet::getSetId($params);

      $usedBy = CRM_Price_BAO_PriceSet::getUsedBy($sid);
    }
    $this->assign('usedBy', $usedBy ?? NULL);
    // set the userContext stack
    $session = CRM_Core_Session::singleton();

    $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/price/field/option',
      "reset=1&action=browse&fid={$this->_fid}&sid={$this->_sid}"
    ));
    $controller = new CRM_Core_Controller_Simple('CRM_Price_Form_Option', ts('Price Field Option'), $action);
    $controller->set('fid', $this->_fid);
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();

    if ($action & CRM_Core_Action::DELETE) {
      // add breadcrumb
      $url = CRM_Utils_System::url('civicrm/admin/price/field/option', 'reset=1');
      CRM_Utils_System::appendBreadCrumb([
        [
          'title' => ts('Price Option'),
          'url' => $url,
        ],
      ]);
      $this->assign('usedPriceSetTitle', CRM_Price_BAO_PriceFieldValue::getOptionLabel($oid));
      $comps = [
        'Event' => 'civicrm_event',
        'Contribution' => 'civicrm_contribution_page',
      ];
      $priceSetContexts = [];
      foreach ($comps as $name => $table) {
        if (array_key_exists($table, $usedBy)) {
          $priceSetContexts[] = $name;
        }
      }
      $this->assign('contexts', $priceSetContexts);
    }
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
    // get the field id
    $this->_fid = CRM_Utils_Request::retrieve('fid', 'Positive',
      $this, FALSE, 0
    );
    //get the price set id
    if (!$this->_sid) {
      $this->_sid = CRM_Utils_Request::retrieve('sid', 'Positive', $this);
    }

    if ($this->_sid) {
      CRM_Price_BAO_PriceSet::checkPermission($this->_sid);
      $this->_isSetReserved = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_sid, 'is_reserved');
      $this->assign('isReserved', $this->_isSetReserved);
    }
    //as url contain $sid so append breadcrumb dynamically.
    $breadcrumb = [
      [
        'title' => ts('Price Fields'),
        'url' => CRM_Utils_System::url('civicrm/admin/price/field', 'reset=1&sid=' . $this->_sid),
      ],
    ];
    CRM_Utils_System::appendBreadCrumb($breadcrumb);

    if ($this->_fid) {
      $fieldTitle = CRM_Price_BAO_PriceField::getTitle($this->_fid);
      $this->assign('fid', $this->_fid);
      $this->assign('fieldTitle', $fieldTitle);
      CRM_Utils_System::setTitle(ts('%1 - Price Options', [1 => $fieldTitle]));

      $htmlType = CRM_Core_DAO::getFieldValue('CRM_Price_BAO_PriceField', $this->_fid, 'html_type');
      $this->assign('addMoreFields', TRUE);
      //for text price field only single option present
      if ($htmlType == 'Text') {
        $this->assign('addMoreFields', FALSE);
      }
    }

    // get the requested action
    $action = CRM_Utils_Request::retrieve('action', 'String',
      // default to 'browse'
      $this, FALSE, 'browse'
    );

    // assign vars to templates
    $this->assign('action', $action);

    $oid = CRM_Utils_Request::retrieve('oid', 'Positive',
      $this, FALSE, 0
    );
    // what action to take ?
    if ($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD |
        CRM_Core_Action::VIEW | CRM_Core_Action::DELETE
      ) && !$this->_isSetReserved
    ) {
      // no browse for edit/update/view
      $this->edit($action);
    }
    else {
      $this->browse();
    }
    // Call the parents run method
    return parent::run();
  }

}
