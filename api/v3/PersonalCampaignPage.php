<?php

/**
 * API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */

function _civicrm_api3_personal_campaign_page_delete_spec(&$spec) {
  $spec['id']['api.required'] = 1;
}


function civicrm_api3_personal_campaign_page_delete($params) {
  
  if( CRM_PCP_BAO_PCP::deleteById($params['id'])){
    return civicrm_api3_create_success();
  }else{
    return civicrm_api3_create_error(ts('Error while deleting PCP'));
  }

}


function _civicrm_api3_personal_campaign_page_get_spec(&$spec) {
  $spec['contact_id']['api.required'] = 0;
  $spec['pcp_id']['api.required'] = 0;
}


function civicrm_api3_personal_campaign_page_get($params) {

  $pcpSummary = _get_personal_campaign_page_contact($params);

  if($pcpSummary != false){
    return civicrm_api3_create_success($pcpSummary, $params);  
  }else{
    return civicrm_api3_create_error(ts('Please enter any of one of contact_id or page_id'));
  }

}

function _get_personal_campaign_page_contact($params){

    //Init our array
    $pcpSummary = array();

    // Get list of status messages
    $status = CRM_PCP_BAO_PCP::buildOptions('status_id', 'create');

    $where = false;

    if(isset($params['contact_id']))  {
      $contact_id = $params['contact_id'];
      $where = "cp.contact_id = '$contact_id'";
    }

    if(isset($params['pcp_id']))  {
      $id = $params['pcp_id'];
      $where = "cp.id = '$id'";
    }

    if(!$where){        
        return false;
    }

    $query = "
        SELECT cp.*
        FROM civicrm_pcp cp
        WHERE $where ORDER BY cp.status_id";

    $pcp = CRM_Core_DAO::executeQuery($query);

    while ($pcp->fetch()) {

      $contact = CRM_Contact_BAO_Contact::getDisplayAndImage($pcp->contact_id);

      $page_type = $pcp->page_type;
      
      $page_id = (int) $pcp->page_id;

      //get page details
      $page = _get_page($page_type, $page_id);

      $honorRoll = CRM_PCP_BAO_PCP::honorRoll($pcp->id);

      $contributions = count($honorRoll);

      $pcpSummary[$pcp->id] = array(
        "id"              =>  $pcp->id,
        "contact_id"      => $pcp->contact_id,
        "title"           => $pcp->title,
        "intro_text"      => $pcp->intro_text,
        "page_text"       => $pcp->page_text,
        "donate_link_text"=> $pcp->donate_link_text,
        "pcp_block_id"    => $pcp->pcp_block_id,
        "is_thermometer"  => $pcp->is_thermometer,
        "is_honor_roll"   => $pcp->is_honor_roll,
        "goal_amount"     => $pcp->goal_amount,
        "currency"        => $pcp->currency,
        "is_active"       => $pcp->is_active,
        "is_notify"       => $pcp->is_notify,
        'page_id'         => $page_id,
        'page_title'      => $page['title'],        
        'contributions'   => $contributions,
        'achieved'        => CRM_PCP_BAO_PCP::thermoMeter($pcp->id),
        'page_type'       => $page_type,
        'start_date'      => $page['start_date'],
        'end_date'        => $page['end_date'],
        "status_id"       => $pcp->status_id,
        'status'          => $status[$pcp->status_id]
      );

     

    }

    return $pcpSummary;
}

function _get_page($page_type, $page_id){
      
      if($page_type === "contribute")
      {

        $query = "SELECT id, title, start_date, end_date FROM civicrm_contribution_page WHERE id = '$page_id'";
        $cpages = CRM_Core_DAO::executeQuery($query);
        while ($cpages->fetch()) {
          $pages['contribute'][$cpages->id]['id'] = $cpages->id;
          $pages['contribute'][$cpages->id]['title'] = $cpages->title;
          $pages['contribute'][$cpages->id]['start_date'] = $cpages->start_date;
          $pages['contribute'][$cpages->id]['end_date'] = $cpages->end_date;
        }

      }else{
        
        $query = "SELECT id, title, start_date, end_date, registration_start_date, registration_end_date
                    FROM civicrm_event
                    WHERE is_template IS NULL OR is_template != 1 AND id = '$page_id'";

        $epages = CRM_Core_DAO::executeQuery($query);
        while ($epages->fetch()) {
          $pages['event'][$epages->id]['id'] = $epages->id;
          $pages['event'][$epages->id]['title'] = $epages->title;
          $pages['event'][$epages->id]['start_date'] = $epages->registration_start_date;
          $pages['event'][$epages->id]['end_date'] = $epages->registration_end_date;
        }
      }

      if ($pages[$page_type][$page_id]['title'] == '' || $pages[$page_type][$page_id]['title'] == NULL) {
        $title = '(no title found for ' . $page_type . ' id ' . $page_id . ')';
      }
      else {
        $title = $pages[$page_type][$page_id]['title'];
      }

      return ['title' => $title, 'start_date' => $pages[$page_type][$page_id]['start_date'], 'end_date' => $pages[$page_type][$page_id]['end_date']];
}

