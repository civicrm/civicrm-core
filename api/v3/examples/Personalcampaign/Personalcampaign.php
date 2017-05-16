<?php
/**
 * Test Generated example demonstrating the Personalcampaign.get API.
 *
 * @return array
 *   API result array
 */
function personalcampaign_get_example() {

  try{
    $result = civicrm_api3('Personalcampaign', 'get', array(
      'sequential' => 1,
      'contact_id' => 202,
    ));
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'error' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    );
  }

  return $result;
}

/**
 * Test Generated example demonstrating the Personalcampaign.delete API.
*/
function personalcampaign_delete_example() {

  try{
    $result = civicrm_api3('Personalcampaign', 'delete', array(
      'sequential' => 1,
      'id' => 12,
    ));
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'error' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    );
  }

  return $result;
}
/**
 * Function returns array of result expected from previous function.
 *
 * @return array
 *   API result array
 */
function personalcampaign_get_expectedresult() {
  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    "values" => array(
        '0' => array(
            "pcpId" => "1",
            "pageTitle" => "Help Support CiviCRM!",
            "pcpTitle" => "My Personal Civi Fundraiser",
            "pcpStatus" => "Approved",
            "pcpGoalAmount" => "5000.00",
            "pcpAmount" => "260.00",
            "pcpLink" => "http =>//xxxxxxxxxxxxxxxxxxx.com/index.php?q=civicrm/pcp/info&reset=1&id=1",
            "pcpEditLink" => "xxxxxxxxxxxxxx/index.php?q=civicrm/pcp/info&action=update&reset=1&id=1&context=dashboard",
            "contribPage" => "Help Support CiviCRM!",
            "eventPage" => "Fall Fundraiser Dinner",
            "idscontributions" => array(
                "$ 10.00 Jones Family",
                "$ 250.00 Annie And The Kids"
            ),
            "noContribs" => "2"
        )
    );

  return $expectedResult;
}