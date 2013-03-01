<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Profile_Page_MultipleRecordFieldsListing extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  protected $_fields = NULL;

  protected $_profileId = NULL;
  
  protected $_contactId = NULL;

  protected $_customGroupTitle = NULL;  
  /**
   * Get BAO Name
   *
   * @return string Classname of BAO.
   */
  function getBAOName() {
    return '';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_links)) {
      // helper variable for nicer formatting
      $links = array();

      $view = array_search(CRM_Core_Action::VIEW, CRM_Core_Action::$_names);
      $update = array_search(CRM_Core_Action::UPDATE, CRM_Core_Action::$_names);
      $delete = array_search(CRM_Core_Action::DELETE, CRM_Core_Action::$_names);
      
      $links[CRM_Core_Action::VIEW] = array(
        'name' => ts('View'),
        'url' => 'civicrm/profile/view',
        'qs' => "id=%%id%%&recordId=%%recordId%%&gid=%%gid%%&multiRecord={$view}&snippet=1&context=multiProfileDialog",
        'title' => ts('View %1', array( 1 => $this->_customGroupTitle . ' record')),
      );

      $links[CRM_Core_Action::UPDATE] = array(
        'name' => ts('Edit'),
        'url' => 'civicrm/profile/edit',
        'qs' => "id=%%id%%&recordId=%%recordId%%&gid=%%gid%%&multiRecord={$update}&snippet=1&context=multiProfileDialog",
        'title' => ts('Edit %1', array( 1 => $this->_customGroupTitle . ' record')),
      );

      $links[CRM_Core_Action::DELETE] = array(
        'name' => ts('Delete'),
        'url' => 'civicrm/profile/edit',
        'qs' => "id=%%id%%&recordId=%%recordId%%&gid=%%gid%%&multiRecord={$delete}&snippet=1&context=multiProfileDialog",
        'title' => ts('Delete %1', array( 1 => $this->_customGroupTitle . ' record')),
      );
      
      self::$_links = $links;
    }
    return self::$_links;
  }

  /**
   * Run the page
   *
   * This method is called after the page is created. It checks for the type
   * of action and executes that action. Finally it calls the parent's run
   * method.
   *
   * @return void
   * @access public
   *
   */
  function run() {
    // get the requested action, default to 'browse'
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, FALSE);

    // assign vars to templates
    $this->assign('action', $action);
    $profileId = CRM_Utils_Request::retrieve('profileId', 'Positive', $this, FALSE);
    if (!is_array($profileId) && is_numeric($profileId)) {
      $this->_profileId = $profileId;      
    }
    //record id
    $recid = CRM_Utils_Request::retrieve('recid', 'Positive', $this, FALSE, 0);
    //custom group id
    $groupId = CRM_Utils_Request::retrieve('groupId', 'Positive', $this, FALSE, 0);
    
    $this->_contactId = CRM_Utils_Request::retrieve('contactId', 'Positive', $this, FALSE);
  
    if ($action & CRM_Core_Action::BROWSE) {
      //browse 
      $this->browse();
      return;
    }
    // parent run
    return parent::run();
  }

  /**
   * Browse the listing
   *
   * @return void
   * @access public
   */
  function browse() {
    if ($this->_profileId) {
      $fields = CRM_Core_BAO_UFGroup::getFields($this->_profileId, FALSE, NULL,
        NULL, NULL,
        FALSE, NULL,
        FALSE,
        NULL,
        CRM_Core_Permission::EDIT
      );
      $multiRecordFields = array( );
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
   
    if ($fieldIDs && !empty($fieldIDs) && $this->_contactId) {
      $options = array( );
      $returnProperities = array('html_type', 'data_type', 'date_format', 'time_format');
      foreach ($fieldIDs as $key => $fieldID) {
        $fieldIDs[$key] = CRM_Core_BAO_CustomField::getKeyID($fieldID);
        $param = array('id' => $fieldIDs[$key]);
        $returnValues = array( );
        CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_CustomField', $param, $returnValues, $returnProperities);
        
        $optionValuePairs = CRM_Core_BAO_CustomOption::getCustomOption($fieldIDs[$key]);
        if (!empty($optionValuePairs)) {
          foreach ($optionValuePairs as $optionPairs) {
            $options[$fieldIDs[$key]][$optionPairs['value']] = $optionPairs['label'];
          }
        }
       
        $options[$fieldIDs[$key]]['attributes']['html_type'] = $returnValues['html_type'];
        $options[$fieldIDs[$key]]['attributes']['data_type'] = $returnValues['data_type'];
        
        $options[$fieldIDs[$key]]['attributes']['format'] = 
          $options[$fieldIDs[$key]]['attributes']['date_format'] = CRM_Utils_Array::value('date_format', $returnValues);
        $options[$fieldIDs[$key]]['attributes']['time_format'] = CRM_Utils_Array::value('time_format', $returnValues);
      }
   
      $result = CRM_Core_BAO_CustomValueTable::getEntityValues($this->_contactId, NULL, $fieldIDs, TRUE);
 
     if (!empty($fieldIDs)) {
       //get the group info of multi rec fields in listing view
       $fieldInput = $fieldIDs;
       $fieldIdInput = $fieldIDs[0];
     } else {
       //if no listing fields exist, take the group title for display
       $nonListingFieldIds = array_keys($multiRecordFields);
       $singleField = CRM_Core_BAO_CustomField::getKeyID($nonListingFieldIds[0]);
       $fieldIdInput = $singleField;
       $singleField = array($singleField);
       $fieldInput  = $singleField;
     }
     $customGroupInfo = CRM_Core_BAO_CustomGroup::getGroupTitles($fieldInput);
     $this->_customGroupTitle = $customGroupInfo[$fieldIdInput]['groupTitle'];
     
     if ($result && !empty($result)) {
       $links = self::links();
       $pageCheckSum = $this->get('pageCheckSum');
       if ($pageCheckSum) {
         foreach ($links as $key => $link) {
           $links[$key] = $link['qs'] . "&cs=%%cs%%";
         }
       }
       $linkAction = array_sum(array_keys($this->links()));
       
       foreach ($result as $recId => &$value) {
         foreach ($value as $fieldId => &$val) {
           if (is_numeric($fieldId)) {
             $customValue = &$val;
             $customValue = CRM_Core_BAO_CustomField::getDisplayValue($customValue, $fieldId, $options);
             if (!$customValue) {
               $customValue = "";
             }
             $actionParams = array('recordId' => $recId, 'gid' => $this->_profileId,
               'id' => $this->_contactId);
             if ($pageCheckSum) {
               $actionParams['cs'] = $pageCheckSum;
             }
             $value['action'] =
               CRM_Core_Action::formLink($links, $linkAction, $actionParams);
           }
         }
       }
     }
    }
        
    $headers = array(  );
    if (!empty($fieldIDs)) {
      foreach ($fieldIDs as $fieldID) {
        $headers[$fieldID] = $customGroupInfo[$fieldID]['fieldLabel'];
      }
    }
    $this->assign('customGroupTitle', $this->_customGroupTitle);
    $this->assign('headers', $headers);
    $this->assign('records', $result);
  }

  /**
   * Get name of edit form
   *
   * @return string  classname of edit form
   */
  function editForm() {
    return '';
  }

  /**
   * Get edit form name
   *
   * @return string  name of this page
   */
  function editName() {
    return '';
  }

  /**
   * Get user context
   *
   * @return string  user context
   */
  function userContext($mode = NULL) {
    return '';
  }
}

