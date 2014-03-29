<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
 +--------------------------------------------------------------------+
 | Copyright U.S. PIRG Education Fund (c) 2007                        |
 | Licensed to CiviCRM under the Academic Free License version 3.0.   |
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
 * @copyright U.S. PIRG Education Fund 2007
 * $Id$
 *
 */

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bootstrap_common.php';

function invoke() {
    $session = CRM_Core_Session::singleton( );
    $config  = CRM_Core_Config::singleton( );

    // display error if any
    showError( $session );
    
    $urlVar = $config->userFrameworkURLVar;

    require_once 'CRM/Core/Invoke.php';
    if ( $session->get('userID') == null || $session->get('userID') == '' ) {
        if (empty($_GET[$urlVar])) {
            require_once "CRM/Core/BAO/UFMatch.php";
            if ( CRM_Core_BAO_UFMatch::isEmptyTable( ) == false ) {
                if (CIVICRM_UF_AUTH == 'SAML20') {
                    $authrequest = new SamlAuthRequest(saml_get_settings());
                    $url = $authrequest->create();
                    header("Location: $url");
                } else {
                    include('login.php');
                }
            } else {
                $session->set( 'new_install', true );
                include('new_install.html');
            }
            exit(1);
        } else {
            $str = '';
            if ( $session->get('new_install') !== true &&
                 $_GET[$urlVar] !== "civicrm/standalone/register" ) {
// Commented as otherwise is shown on non-login pages
//                $str = "<a href=\"{$config->userFrameworkBaseURL}\">Login here</a> if you have an account.\n";
            } elseif ($_GET[$urlVar] == "civicrm/standalone/register" && isset($_GET['reset'])) {
                // this is when user first registers with civicrm
                print "<head><style type=\"text/css\"> body {border: 1px #CCC solid;margin: 3em;padding: 1em 1em 1em 2em;} </style></head>\n";
            }
            print $str . CRM_Core_Invoke::invoke( explode('/', $_GET[$urlVar] ) );
        }
    } else {
        if ($_GET[$urlVar] == "") {
            print CRM_Core_Invoke::invoke( array("civicrm","dashboard") );
        } else {
            print CRM_Core_Invoke::invoke( explode('/', $_GET[$urlVar] ) );
        }
    }
}

function showError( &$session ) {
    // display errors if any
    if ( !empty( $error ) ) {
        print "<div class=\"error\">$error</div>\n";
    }
    
    if ( $session->get('msg') ) {
        $msg = $session->get('msg');
        print "<div class=\"msg\">$msg</div>\n";
        $session->set('msg', null);
    }

    if ( $session->get('goahead') == 'no' ) {
        $session->reset();
        print "<a href=\"index.php\">Home Page</a>\n";
        exit();
    }
}

invoke();

