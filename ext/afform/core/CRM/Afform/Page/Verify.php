<?php

class CRM_Afform_Page_Verify extends CRM_Core_Page {

  public function run() {
    $verified = FALSE;

    // get the submission id
    $sid = CRM_Utils_Request::retrieve('sid', 'Positive', $this, TRUE);

    if (!empty($sid)) {
      // check submission status
      $afformSubmissionData = \Civi\Api4\AfformSubmission::get(FALSE)
        ->addSelect('afform_name', 'status_id:name')
        ->addWhere('id', '=', $sid)
        ->execute()->first();

      if (!empty($afformSubmissionData) && $afformSubmissionData['status_id:name'] === 'Pending') {
        \Civi\Api4\Afform::process(FALSE)
          ->setName($afformSubmissionData['afform_name'])
          ->setSubmissionId($sid)
          ->execute();

        $verified = TRUE;
      }
    }

    $this->assign('verified', $verified);

    parent::run();
  }

}
