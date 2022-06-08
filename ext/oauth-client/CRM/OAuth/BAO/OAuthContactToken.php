<?php

class CRM_OAuth_BAO_OAuthContactToken extends CRM_OAuth_DAO_OAuthContactToken {

  /**
   * @inheritDoc
   */
  public function addSelectWhereClause() {
    $clauses = [];
    $loggedInContactID = CRM_Core_Session::getLoggedInContactID();

    // With 'manage all' permission, apply standard contact ACLs
    if (CRM_Core_Permission::check(['manage all OAuth contact tokens'])) {
      $clauses['contact_id'] = CRM_Utils_SQL::mergeSubquery('Contact');
    }
    // With 'manage my' permission, limit to just the current user
    elseif ($loggedInContactID && CRM_Core_Permission::check(['manage my OAuth contact tokens'])) {
      $clauses['contact_id'] = "= $loggedInContactID";
    }
    // No permission, return nothing
    else {
      $clauses['contact_id'] = "= -1";
    }
    CRM_Utils_Hook::selectWhereClause($this, $clauses);
    return $clauses;
  }

}
