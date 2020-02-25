<?php

/**
 * @file This administrative page provides simple access to recent transactions
 * and an opportunity for the system to warn administrators about failing
 * crons .*/

require_once 'CRM/Core/Page.php';
/**
 *
 */
class CRM_Iats_Page_iATSAdmin extends CRM_Core_Page {

  /**
   *
   */
  public function run() {
    // Reset the saved version of the extension.
    $iats_extension_version = CRM_Iats_iATSServiceRequest::iats_extension_version(1);
    // The current time.
    $this->assign('currentVersion', $iats_extension_version);
    $this->assign('currentTime', date('Y-m-d H:i:s'));
    $this->assign('jobLastRunWarning', '0');
    // Check if I've got any recurring contributions setup. In theory I should only worry about iATS, but it's a problem regardless ..
    $result = civicrm_api3('ContributionRecur', 'getcount');
    if (!empty($result)) {
      $this->assign('jobLastRunWarning', '1');
      $params = ['api_action' => 'Iatsrecurringcontributions', 'is_active' => 1, 'sequential' => 1, 'options' => ['sort' => 'last_run']];
      $jobs = civicrm_api3('Job', 'get', $params);
      $job_last_run = count($jobs['values']) > 0 ? strtotime($jobs['values'][0]['last_run']) : 0;
      $this->assign('jobLastRun', ($job_last_run ? date('Y-m-d H:i:s', $job_last_run) : ''));
      $this->assign('jobOverdue', '');
      $overdueHours  = (time() - $job_last_run) / (60 * 60);
      if (36 < $overdueHours) {
        $this->assign('jobOverdue', $overdueHours);
      }
    }
    // Load the most recent requests and responses from the log files.
    foreach (array('cc', 'auth_result') as $key) {
      $search[$key] = empty($_GET['search_' . $key]) ? '' : filter_var($_GET['search_' . $key], FILTER_SANITIZE_STRING);
    }
    $log = $this->getLog($search);
    // $log[] = array('cc' => 'test', 'ip' => 'whatever', 'auth_result' => 'blah');.
    $this->assign('iATSLog', $log);
    $this->assign('search', $search);
    parent::run();
  }

  /**
   *
   */
  public function getLog($search = array(), $n = 40) {
    // Avoid sql injection attacks.
    $n = (int) $n;
    $filter = array();
    foreach ($search as $key => $value) {
      if (!empty($value)) {
        $filter[] = "$key RLIKE '$value'";
      }
    }
    $where = empty($filter) ? '' : " WHERE " . implode(' AND ', $filter);
    $sql = "SELECT request.*,response.*,contrib.contact_id,contact.sort_name,pp.url_site,pp.user_name,pp.password
      FROM civicrm_iats_request_log request 
      LEFT JOIN civicrm_iats_response_log response ON request.invoice_num = response.invoice_num
      LEFT JOIN civicrm_contribution contrib ON request.invoice_num = contrib.invoice_id
      LEFT JOIN civicrm_contact contact ON contrib.contact_id = contact.id
      LEFT JOIN civicrm_contribution_recur recur ON contrib.contribution_recur_id = recur.id
      LEFT JOIN civicrm_payment_processor pp ON recur.payment_processor_id = pp.id
     $where ORDER BY request.id DESC LIMIT $n";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $log = array();
    $params = array('version' => 3, 'sequential' => 1, 'return' => 'contribution_id');
    $className = get_class($dao);
    $internal = array_keys(get_class_vars($className));
    // Get some customer data while i'm at it
    // todo: fix iats_domain below
    // $iats_service_params = array('type' => 'customer', 'method' => 'get_customer_code_detail', 'iats_domain' => 'www.iatspayments.com');.
    while ($dao->fetch()) {
      $entry = get_object_vars($dao);
      // Ghost entry!
      unset($entry['']);
      // Remove internal fields.
      foreach ($internal as $key) {
        unset($entry[$key]);
      }
      $params['invoice_id'] = $entry['invoice_num'];
      $result = civicrm_api('Contribution', 'getsingle', $params);
      if (!empty($result['contribution_id'])) {
        $entry += $result;
        $entry['contributionURL'] = CRM_Utils_System::url('civicrm/contact/view/contribution', 'reset=1&id=' . $entry['contribution_id'] . '&cid=' . $entry['contact_id'] . '&action=view&selectedChild=Contribute');
      }
      if (!empty($result['contact_id'])) {
        $entry['contactURL'] = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $entry['contact_id']);
      }
      $log[] = $entry;
    }
    return $log;
  }

}
