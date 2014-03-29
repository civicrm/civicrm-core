<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
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
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

require_once 'CRM/Core/Form.php';

class CRM_Standalone_Form_Register extends CRM_Core_Form {

    protected $_profileID;

    protected $_fields = array( );
    
    protected $_openID;
    

    function preProcess( ) {
        // pick the first profile ID that has user register checked
        require_once 'CRM/Core/BAO/UFGroup.php';
        $ufGroups =& CRM_Core_BAO_UFGroup::getModuleUFGroup('User Registration');

        if ( count( $ufGroups ) > 1 ) {
            CRM_Core_Error::fatal( ts( 'You have more than one profile that has been enabled for user registration.' ) );
        }

        foreach ( $ufGroups as $id => $dontCare ) {
            $this->_profileID = $id;
        }
        
        require_once 'CRM/Core/Session.php';
        $session =& CRM_Core_Session::singleton( );
        $this->_openID = $session->get( 'openid' );
    }
    
    function setDefaultValues( ) {
        $defaults = array( );
        
        $defaults['user_unique_id'] = $this->_openID;
        
        return $defaults;
    }

    function buildQuickForm( ) {
        $this->add( 'text',
                    'user_unique_id', 
                    ts( 'OpenID' ),
                    CRM_Core_DAO::getAttribute( 'CRM_Contact_DAO_Contact', 'user_unique_id' ),
                    true );
                    
        $this->add( 'text',
                    'email',
                    ts( 'Email' ),
                    CRM_Core_DAO::getAttribute( 'CRM_Contact_DAO_Contact', 'email' ),
                    true );

        $fields = CRM_Core_BAO_UFGroup::getFields( $this->_profileID,
                                                   false,
                                                   CRM_Core_Action::ADD,
                                                   null, null, false,
                                                   null, true );
        $this->assign( 'custom', $fields );
        
        require_once 'CRM/Profile/Form.php';
        foreach ( $fields as $key => $field ) {
            CRM_Core_BAO_UFGroup::buildProfile( $this,
                                                $field,
                                                CRM_Profile_Form::MODE_CREATE );
            $this->_fields[$key] = $field;
        }
        
        $this->addButtons( array(
                                 array ( 'type'      => 'next',
                                         'name'      => ts('Save'),
                                         'isDefault' => true   ),
                                 array ( 'type'      => 'cancel',
                                         'name'      => ts('Cancel') ),
                                 )
                           );
    }

    function postProcess( ) {
        $formValues = $this->controller->exportValues( $this->_name );
        
        require_once 'CRM/Standalone/User.php';
        require_once 'CRM/Utils/System/Standalone.php';
        require_once 'CRM/Core/BAO/OpenID.php';

        $user = new CRM_Standalone_User( $formValues['user_unique_id'], 
                                         $formValues['email'], 
                                         $formValues['first_name'], 
                                         $formValues['last_name']
                                         );
        CRM_Utils_System_Standalone::getUserID( $user );
        
        require_once 'CRM/Core/Session.php';
        $session =& CRM_Core_Session::singleton( );
        $contactId = $session->get( 'userID' );

        $query   = "SELECT count(id) FROM civicrm_uf_match";
        $ufCount = CRM_Core_DAO::singleValueQuery( $query );

        if ( ($ufCount == 1) || defined('ALLOWED_TO_LOGIN') ) {
            $openId = new CRM_Core_DAO_OpenID();
            $openId->contact_id = $contactId;
            $openId->find( true );
            $openId->allowed_to_login = 1;
            $openId->update( );
        }
        
        // add first user to admin group 
        if ( $ufCount == 1 ) {
            require_once 'CRM/Contact/BAO/GroupContact.php';
            require_once 'CRM/Contact/DAO/Group.php';
            $group = new CRM_Contact_DAO_Group();
            $group->name       = 'Administrators';
            $group->is_active  = 1;
            if ( $group->find(true) ) {
                $contactIds = array( $contactId );
                CRM_Contact_BAO_GroupContact::addContactsToGroup( $contactIds, $group->id,
                                                                  'Web', 'Added' );
            }
        } else if ( $ufCount > 1 && !defined('CIVICRM_ALLOW_ALL') ) {
            $session->set( 'msg' , 'You are not allowed to login. Login failed. Contact your Administrator.' );	
            $session->set( 'goahead', "no" );
        }
        
        // Set this to false if the registration is successful
        $session->set('new_install', false);
        
        header( "Location: index.php" );
        exit();
    }
}
