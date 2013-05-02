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

require_once 'CRM/Core/Page.php';
require_once 'CRM/Grant/BAO/GrantProgram.php';


/**
 * Page for displaying list of contribution types
 */
class CRM_Grant_Page_GrantProgram extends CRM_Core_Page
{

    protected $_id;
    /**
     * The action links that we need to display for the browse screen
     *
     * @var array
     */
    private static $_links;
    /**
     * Get action Links
     *
     * @return array (reference) of action links
     */
    function &links()
        {
            if (!(self::$_links)) {
                self::$_links = array(
                                      CRM_Core_Action::VIEW  => array(
                                                                      'name'  => ts('View'),
                                                                      'url'   => 'civicrm/grant_program',
                                                                      'qs'    => 'action=view&id=%%id%%&reset=1',
                                                                      'title' => ts('View Grant Program') 
                                                                      ),
                                      CRM_Core_Action::UPDATE  => array(
                                                                        'name'  => ts('Edit'),
                                                                        'url'   => 'civicrm/grant_program',
                                                                        'qs'    => 'action=update&id=%%id%%&reset=1',
                                                                        'title' => ts('Edit Grant Program') 
                                                                        ),
                                      CRM_Core_Action::DELETE  => array(
                                                                        'name'  => ts('Delete'),
                                                                        'url'   => 'civicrm/grant_program',
                                                                        'qs'    => 'action=delete&id=%%id%%',
                                                                        'title' => ts('Delete Grant Program') 
                                                                        )
                                      );
            }
            return self::$_links;
        }
    
    function browse( ) {
        
        $grantProgram = array();
        require_once 'CRM/Grant/DAO/GrantProgram.php';
        $dao = new CRM_Grant_DAO_GrantProgram();
        
        $dao->orderBy('label');
        $dao->find();
        
        while ($dao->fetch()) {
            $grantProgram[$dao->id] = array();
            CRM_Core_DAO::storeValues( $dao, $grantProgram[$dao->id]);
            $action = array_sum(array_keys($this->links()));

            $grantProgram[$dao->id]['action'] = CRM_Core_Action::formLink(self::links(), $action, 
                                                                          array('id' => $dao->id));
        }
        require_once 'CRM/Grant/PseudoConstant.php';
        $grantType   = CRM_Grant_PseudoConstant::grantType( );
        $grantStatus = CRM_Grant_PseudoConstant::grantProgramStatus( );
        foreach ( $grantProgram as $key => $value ) {
            $grantProgram[$key]['grant_type_id'] = $grantType[CRM_Grant_BAO_GrantProgram::getOptionValue($grantProgram[$key]['grant_type_id'])];
            $grantProgram[$key]['status_id'] = $grantStatus[CRM_Grant_BAO_GrantProgram::getOptionValue($grantProgram[$key]['status_id'])];
        }
        $this->assign('rows',$grantProgram );
    }
    
    function run( ) 
    {
        $action = CRM_Utils_Request::retrieve('action', 'String',
                                              $this, false, 0 );
        if ( $action & CRM_Core_Action::VIEW ) { 
            $this->view( $action); 
        } else if ( $action & ( CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::DELETE ) ) {
            $this->edit( $action);
        } else {
            $this->browse( ); 
        }
        $this->assign('action', $action);
        return parent::run( );
    }

    function edit($action)
    {
        $controller = new CRM_Core_Controller_Simple('CRM_Grant_Form_GrantProgram', ts(''), $action);
        $controller->setEmbedded(true);
        $result = $controller->process();
        $result = $controller->run();
    }

    function view( $action ) 
    {   
        $controller = new CRM_Core_Controller_Simple( 'CRM_Grant_Form_GrantProgramView', ts(''), $action );
        $controller->setEmbedded( true );  
        $result = $controller->process();
        $result = $controller->run();
    }
}
