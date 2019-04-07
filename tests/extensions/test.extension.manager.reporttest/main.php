<?php

/**
 * Class test_extension_manager_reporttest
 */
class test_extension_manager_reporttest extends CRM_Core_Report {

  /**
   * Class constructor.
   */
  public function __construct() {
    $logging        = new CRM_Logging_Schema();
    $this->tables[] = 'civicrm_contact';
    $this->tables   = array_merge($this->tables, array_keys($logging->customDataLogTables()));
    $this->tables[] = 'civicrm_email';
    $this->tables[] = 'civicrm_phone';
    $this->tables[] = 'civicrm_im';
    $this->tables[] = 'civicrm_openid';
    $this->tables[] = 'civicrm_website';
    $this->tables[] = 'civicrm_address';
    $this->tables[] = 'civicrm_note';
    $this->tables[] = 'civicrm_relationship';

    $this->detail = 'logging/contact/detail';
    $this->summary = 'logging/contact/summary';

    parent::__construct();
  }

  public function buildQuickForm() {
    $layout = CRM_Utils_Request::retrieve('layout', 'String', $this);
    $this->assign('layout', $layout);

    parent::buildQuickForm();

    if ($this->cid) {
      // link back to contact summary
      $this->assign('backURL', CRM_Utils_System::url('civicrm/contact/view', "reset=1&selectedChild=log&cid={$this->cid}", FALSE, NULL, FALSE));
      $this->assign('revertURL', self::$_template->get_template_vars('revertURL') . "&cid={$this->cid}");
    }
    else {
      // link back to summary report
      $this->assign('backURL', CRM_Report_Utils_Report::getNextUrl('logging/contact/summary', 'reset=1', FALSE, TRUE));
    }
  }

  /**
   * @return string
   */
  protected function whoWhomWhenSql() {
    return "
            SELECT who.id who_id, who.display_name who_name, whom.id whom_id, whom.display_name whom_name, l.is_deleted
            FROM `{$this->db}`.log_civicrm_contact l
            JOIN civicrm_contact who ON (l.log_user_id = who.id)
            JOIN civicrm_contact whom ON (l.id = whom.id)
            WHERE log_action = 'Update' AND log_conn_id = %1 AND log_date = %2 ORDER BY log_date DESC LIMIT 1
        ";
  }

}
