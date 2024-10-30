<?php

class CRM_Afform_Page_Verify extends CRM_Core_Page {

  public function run() {
    $verified = FALSE;
    $this->assign('error_message', '');

    $token = CRM_Utils_Request::retrieve('token', 'String', $this, TRUE, NULL, 'GET');

    try {
      $decodedToken = \Civi::service('crypto.jwt')->decode($token);
      $sid = $decodedToken['submissionId'] ?? NULL;
    }
    catch (\Civi\Crypto\Exception\CryptoException $e) {
      if (str_contains($e->getMessage(), 'ExpiredException')) {
        $this->assign('error_message', ts('Token expired.'));
      }
      else {
        $this->assign('error_message', ts('An error occurred when processing the token.'));
        \Civi::log()->warning(
          __CLASS__ . ' cannot process a token due to a crypto exception.',
          ['exception' => $e]);
      }
    }

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
