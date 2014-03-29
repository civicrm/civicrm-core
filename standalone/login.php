<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bootstrap_common.php';

global $civicrm_root, $civicrm_auth_domain, $civicrm_auth_openid;

// If we have an authenticated user, can it access CiviCRM?
$auth_email = CRM_Utils_Array::value('SSO_auth_email', $_SESSION);
if ($auth_email) {
  $user = new CRM_Standalone_User( $auth_email, $auth_email );

  $allow_login = CRM_Utils_System_Standalone::getAllowedToLogin( $user );
  if ( !$allow_login && (!defined('CIVICRM_ALLOW_ALL') || !CIVICRM_ALLOW_ALL ) ) {
    $message = sprintf( 'You authentified as \'%s\'. This user is not allowed to login.', $auth_email );
    $session->set( 'msg' , $message );
    $session->set( 'goahead', "no" );
  } else {
    CRM_Utils_System_Standalone::getUserID( $user );

    if ( ! $session->get('userID') ) {
      $message = sprintf( 'You authentified as \'%s\'. This user is not authorized to login.', $auth_email );
      $session->set( 'msg' , $message );
      $session->set( 'goahead', "no" );
    }
  }
  CRM_Utils_Hook::singleton()->invoke(1, $user, $user, $user, $user, $user, 'civicrm_login');
  header("Location: index.php");
}

// Go to the SSO login page
header("Location: /sso/login.php");