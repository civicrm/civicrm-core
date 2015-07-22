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
class CRM_Profile_Page_MultipleRecordFieldsListing extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  static $_links = NULL;

  protected $_fields = NULL;

  protected $_profileId = NULL;

  protected $_contactId = NULL;

  protected $_customGroupTitle = NULL;

  protected $_pageViewType = NULL;

  protected $_contactType = NULL;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return '';
  }

  /**
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links[$this->_pageViewType])) {
      // helper variable for nicer formatting
      $links = array();

      $view = array_search(CRM_Core_Action::VIEW, CRM_Core_Action::$_names);
      $update = array_search(CRM_Core_Action::UPDATE, CRM_Core_Action::$_names);
      $delete = array_search(CRM_Core_Action::DELETE, CRM_Core_Action::$_names);

      // names and titles
      $links[CRM_Core_Action::VIEW] = array(
        'name' => ts('View'),
        'title' => ts('View %1', array(1 => $this->_customGroupTitle . ' record')),
      );

      $links[CRM_Core_Action::UPDATE] = array(
        'name' => ts('Edit'),
        'title' => ts('Edit %1', array(1 => $this->_customGroupTitle . ' record')),
      );

      $links[CRM_Core_Action::DELETE] = array(
        'name' => ts('Delete'),
        'title' => ts('Delete %1', array(1 => $this->_customGroupTitle . ' record')),
      );

      // urls and queryStrings
      if ($this->_pageViewType == 'profileDataView') {
        $links[CRM_Core_Action::VIEW]['url'] = 'civicrm/profile/view';
        $links[CRM_Core_Action::VIEW]['qs'] = "reset=1&id=%%id%%&recordId=%%recordId%%&gid=%%gid%%&multiRecord={$view}";

        $links[CRM_Core_Action::UPDATE]['url'] = 'civicrm/profile/edit';
        $links[CRM_Core_Action::UPDATE]['qs'] = "reset=1&id=%%id%%&recordId=%%recordId%%&gid=%%gid%%&multiRecord={$update}";

        $links[CRM_Core_Action::DELETE]['url'] = 'civicrm/profile/edit';
        $links[CRM_Core_Action::DELETE]['qs'] = "reset=1&id=%%id%%&recordId=%%recordId%%&gid=%%gid%%&multiRecord={$delete}";

      }
      elseif ($this->_pageViewType == 'customDataView') {
        // custom data specific view links
        $links[CRM_Core_Action::VIEW]['url'] = 'civicrm/contact/view/cd';
        $links[CRM_Core_Action::VIEW]['qs'] = 'reset=1&gid=%%gid%%&cid=%%cid%%&recId=%%recId%%&multiRecordDisplay=single&mode=view';

        // custom data specific update links
        $links[CRM_Core_Action::UPDATE]['url'] = 'civicrm/contact/view/cd/edit';
        $links[CRM_Core_Action::UPDATE]['qs'] = 'reset=1&type=%%type%%&groupID=%%groupID%%&entityID=%%entityID%%&cgcount=%%cgcount%%&multiRecordDisplay=single&mode=edit';
        // NOTE : links for DELETE action for customDataView is handled in browse

        // copy action
        $links[CRM_Core_Action::COPY] = array(
          'name' => ts('Copy'),
          'title' => ts('Copy %1', array(1 => $this->_customGroupTitle . ' record')),
          'url' => 'civicrm/contact/view/cd/edit',
          'qs' => 'reset=1&type=%%type%%&groupID=%%groupID%%&entityID=%%entityID%%&cgcount=%%newCgCount%%&multiRecordDisplay=single&copyValueId=%%cgcount%%&mode=copy',
        );
      }

      self::$_links[$this->_pageViewType] = $links;
    }
    return self::$_links[$this->_pageViewType];
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the type
   * of action and executes that action. Finally it calls the parent's run
   * method.
   *
   * @return void
   */
  public function run() {
    // get the requested action, default to 'browse'
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, FALSE);

    // assign vars to templates
    $this->assign('action', $action);
    $profileId = CRM_Utils_Request::retrieve('profileId', 'Positive', $this, FALSE);
    if (!is_array($profileId) && is_numeric($profileId)) {
      $this->_profileId = $profileId;
    }

    $this->_contactId = CRM_Utils_Request::retrieve('contactId', 'Positive', $this, FALSE);
    $this->_pageViewType = CRM_Utils_Request::retrieve('pageViewType', 'Positive', $this, FALSE, 'profileDataView');
    $this->_customGroupId = CRM_Utils_Request::retrieve('customGroupId', 'Positive', $this, FALSE, 0);
    $this->_contactType = CRM_Utils_Request::retrieve('contactType', 'String', $this, FALSE);
    if ($action & CRM_Core_Action::BROWSE) {
      //browse
      $this->browse();
      return;
    }
    // parent run
    return parent::run();
  }

  /**
   * Browse the listing.
   *
   * @return void
   */
  public function browse() {
    $dateFields = NULL;
    $cgcount = 0;
    $attributes = array();
    $dateFieldsVals = NULL;
    if ($this->_pageViewType == 'profileDataView' && $this->_profileId) {
      $fields = CRM_Core_BAO_UFGroup::getFields($this->_profileId, FALSE, NULL,
        NULL, NULL,
        FALSE, NULL,
        FALSE,
        NULL,
        CRM_Core_Permission::EDIT
      );
      $multiRecordFields = array();
      $fieldIDs = NULL;
      $result = NULL;
      $multiRecordFieldsWithSummaryListing = CRM_Core_BAO_UFGroup::shiftMultiRecordFields($fields, $multiRecordFields, TRUE);

      $multiFieldId = CRM_Core_BAO_CustomField::getKeyID(key($multiRecordFields));
      $customGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $multiFieldId, 'custom_group_id');
      $reached = CRM_Core_BAO_CustomGroup::hasReachedMaxLimit($customGroupId, $this->_contactId);
      if (!$reached) {
        $this->assign('contactId', $this->_contactId);
        $this->assign('gid', $this->_profileId);
      }
      $this->assign('reachedMax', $reached);

      if ($multiRecordFieldsWithSummaryListing && !empty($multiRecordFieldsWithSummaryListing)) {
        $fieldIDs = array_keys($multiRecordFieldsWithSummaryListing);
      }
    }
    elseif ($this->_pageViewType == 'customDataView') {
      // require custom group id for _pageViewType of customDataView
      $customGroupId = $this->_customGroupId;
      $reached = CRM_Core_BAO_CustomGroup::hasReachedMaxLimit($customGroupId, $this->_contactId);
      if (!$reached) {
        $this->assign('contactId', $this->_contactId);
        $this->assign('customGroupId', $customGroupId);
        $this->assign('ctype', $this->_contactType);
      }
      $this->assign('reachedMax', $reached);
      // custom group info : this consists of the field title of group fields
      $groupDetail = CRM_Core_BAO_CustomGroup::getGroupDetail($customGroupId, NULL, CRM_Core_DAO::$_nullObject, TRUE);
      // field ids of fields in_selector for the custom group id provided
      $fieldIDs = array_keys($groupDetail[$customGroupId]['fields']);
      // field labels for headers
      $fieldLabels = $groupDetail[$customGroupId]['fields'];

      // from the above customGroupInfo we can get $this->_customGroupTitle
      $this->_customGroupTitle = $groupDetail[$customGroupId]['title'];
    }
    if ($fieldIDs && !empty($fieldIDs) && $this->_contactId) {
      $options = array();
      $returnProperities = array(
        'html_type',
        'data_type',
        'date_format',
        'time_format',
        'default_value',
        'is_required',
      );
      foreach ($fieldIDs as $key => $fieldID) {
        $fieldIDs[$key] = !is_numeric($fieldID) ? CRM_Core_BAO_CustomField::getKeyID($fieldID) : $fieldID;
        $param = array('id' => $fieldIDs[$key]);
        $returnValues = array();
        CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_CustomField', $param, $returnValues, $returnProperities);
        if ($returnValues['data_type'] == 'Date') {
          $dateFields[$fieldIDs[$key]] = 1;
          $actualPHPFormats = CRM_Core_SelectValues::datePluginToPHPFormats();
          $dateFormat = (array) CRM_Utils_Array::value($returnValues['date_format'], $actualPHPFormats);
          $timeFormat = CRM_Utils_Array::value('time_format', $returnValues);
        }

        $optionValuePairs = CRM_Core_BAO_CustomOption::getCustomOption($fieldIDs[$key]);
        if (!empty($optionValuePairs)) {
          foreach ($optionValuePairs as $optionPairs) {
            $options[$fieldIDs[$key]][$optionPairs['value']] = $optionPairs['label'];
          }
        }

        $options[$fieldIDs[$key]]['attributes']['html_type'] = $returnValues['html_type'];
        $options[$fieldIDs[$key]]['attributes']['data_type'] = $returnValues['data_type'];
        $options[$fieldIDs[$key]]['attributes']['is_required'] = !empty($returnValues['is_required']);
        $options[$fieldIDs[$key]]['attributes']['default_value'] = CRM_Utils_Array::value('default_value', $returnValues);

        $options[$fieldIDs[$key]]['attributes']['format']
          = $options[$fieldIDs[$key]]['attributes']['date_format'] = CRM_Utils_Array::value('date_format', $returnValues);
        $options[$fieldIDs[$key]]['attributes']['time_format'] = CRM_Utils_Array::value('time_format', $returnValues);
      }

      // commonly used for both views i.e profile listing view (profileDataView) and custom data listing view (customDataView)
      $result = CRM_Core_BAO_CustomValueTable::getEntityValues($this->_contactId, NULL, $fieldIDs, TRUE);

      if ($this->_pageViewType == 'profileDataView') {
        if (!empty($fieldIDs)) {
          //get the group info of multi rec fields in listing view
          $fieldInput = $fieldIDs;
          $fieldIdInput = $fieldIDs[0];
        }
        else {
          //if no listing fields exist, take the group title for display
          $nonListingFieldIds = array_keys($multiRecordFields);
          $singleField = CRM_Core_BAO_CustomField::getKeyID($nonListingFieldIds[0]);
          $fieldIdInput = $singleField;
          $singleField = array($singleField);
          $fieldInput = $singleField;
        }
        $customGroupInfo = CRM_Core_BAO_CustomGroup::getGroupTitles($fieldInput);
        $this->_customGroupTitle = $customGroupInfo[$fieldIdInput]['groupTitle'];
      }
      // $cgcount is defined before 'if' condition as enitiy may have no record
      // and $cgcount is used to build new record url
      $cgcount = 1;
      if ($result && !empty($result)) {
        $links = self::links();
        if ($this->_pageViewType == 'profileDataView') {
          $pageCheckSum = $this->get('pageCheckSum');
          if ($pageCheckSum) {
            foreach ($links as $key => $link) {
              $links[$key] = $link['qs'] . "&cs=%%cs%%";
            }
          }
        }
        $linkAction = array_sum(array_keys($this->links()));

        if ($reached) {
          unset($links[CRM_Core_Action::COPY]);
        }
        $newCgCount = (!$reached) ? count($result) + 1 : NULL;
        foreach ($result as $recId => &$value) {
          foreach ($value as $fieldId => &$val) {
            if (is_numeric($fieldId)) {
              $customValue = &$val;
              if (!empty($dateFields) && array_key_exists($fieldId, $dateFields)) {
                // formated date capture value capture
                $dateFieldsVals[$fieldId][$recId] = CRM_Core_BAO_CustomField::getDisplayValue($customValue, $fieldId, $options);

                //set date and time format
                switch ($timeFormat) {
                  case 1:
                    $dateFormat[1] = 'g:iA';
                    break;

                  case 2:
                    $dateFormat[1] = 'G:i';
                    break;

                  default:
                    // if time is not selected remove time from value
                    $result[$recId][$fieldId] = substr($result[$recId][$fieldId], 0, 10);
                }
                $result[$recId][$fieldId] = CRM_Utils_Date::processDate($result[$recId][$fieldId], NULL, FALSE, implode(" ", $dateFormat));
              }
              else {
                // assign to $result
                $customValue = CRM_Core_BAO_CustomField::getDisplayValue($customValue, $fieldId, $options);
              }
              // FIXME: getDisplayValue should always return a string so why is this necessary?
              if (!$customValue && $customValue !== '0') {
                $customValue = "";
              }

              // Set field attributes to support crmEditable
              // Note that $fieldAttributes[data-type] actually refers to the html type not the sql data type
              // TODO: Not all widget types and validation rules are supported by crmEditable so some fields will not be in-place editable
              $fieldAttributes = array('class' => "crmf-custom_{$fieldId}_$recId");
              $editable = FALSE;
              if ($linkAction & CRM_Core_Action::UPDATE) {
                $spec = $options[$fieldId]['attributes'];
                switch ($spec['html_type']) {
                  case 'Text':
                    // Other data types like money would require some extra validation
                    // FIXME: crmEditable currently does not support any validation rules :(
                    $supportedDataTypes = array('Float', 'String', 'Int');
                    $editable = in_array($spec['data_type'], $supportedDataTypes);
                    break;

                  case 'TextArea':
                    $editable = TRUE;
                    $fieldAttributes['data-type'] = 'textarea';
                    break;

                  case 'Radio':
                  case 'Select':
                  case 'Select Country':
                  case 'Select State/Province':
                    $editable = TRUE;
                    $fieldAttributes['data-type'] = $spec['data_type'] == 'Boolean' ? 'boolean' : 'select';
                    if (!$spec['is_required']) {
                      $fieldAttributes['data-empty-option'] = ts('- none -');
                    }
                    break;
                }
              }
              if ($editable) {
                $fieldAttributes['class'] .= ' crm-editable';
              }
              $attributes[$fieldId][$recId] = $fieldAttributes;

              $op = NULL;
              if ($this->_pageViewType == 'profileDataView') {
                $actionParams = array(
                  'recordId' => $recId,
                  'gid' => $this->_profileId,
                  'id' => $this->_contactId,
                );
                $op = 'profile.multiValue.row';
              }
              else {
                // different set of url params
                $actionParams['gid'] = $actionParams['groupID'] = $this->_customGroupId;
                $actionParams['cid'] = $actionParams['entityID'] = $this->_contactId;
                $actionParams['recId'] = $recId;
                $actionParams['type'] = $this->_contactType;
                $actionParams['cgcount'] = $cgcount;
                $actionParams['newCgCount'] = $newCgCount;

                // DELETE action links
                $deleteData = array(
                  'valueID' => $recId,
                  'groupID' => $this->_customGroupId,
                  'contactId' => $this->_contactId,
                  'key' => CRM_Core_Key::get('civicrm/ajax/customvalue'),
                );
                $links[CRM_Core_Action::DELETE]['url'] = '#';
                $links[CRM_Core_Action::DELETE]['extra'] = ' data-delete_params="' . htmlspecialchars(json_encode($deleteData)) . '"';
                $links[CRM_Core_Action::DELETE]['class'] = 'delete-custom-row';
              }
              if (!empty($pageCheckSum)) {
                $actionParams['cs'] = $pageCheckSum;
              }

              $value['action'] = CRM_Core_Action::formLink(
                $links,
                $linkAction,
                $actionParams,
                ts('more'),
                FALSE,
                $op,
                'customValue',
                $fieldId // not ideal, but the one thing not sent in $actionParams
              );
            }
          }
          $cgcount++;
        }
      }
    }

    $headers = array();
    if (!empty($fieldIDs)) {
      foreach ($fieldIDs as $fieldID) {
        $headers[$fieldID] = ($this->_pageViewType == 'profileDataView') ? $customGroupInfo[$fieldID]['fieldLabel'] : $fieldLabels[$fieldID]['label'];
      }
    }
    $this->assign('dateFields', $dateFields);
    $this->assign('dateFieldsVals', $dateFieldsVals);
    $this->assign('cgcount', $cgcount);
    $this->assign('customGroupTitle', $this->_customGroupTitle);
    $this->assign('headers', $headers);
    $this->assign('records', $result);
    $this->assign('attributes', $attributes);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   classname of edit form
   */
  public function editForm() {
    return '';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page
   */
  public function editName() {
    return '';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context
   */
  public function userContext($mode = NULL) {
    return '';
  }

}
