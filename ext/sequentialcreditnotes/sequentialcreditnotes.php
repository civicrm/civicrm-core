<?php

require_once 'sequentialcreditnotes.civix.php';
use Civi\Api4\Contribution;

/**
 * Add a creditnote_id if appropriate.
 *
 * If the contribution is created with cancelled or refunded status, add credit note id
 * do the same for chargeback
 * - creditnotes for chargebacks entered the code 'accidentally' but since it did we maintain it.
 *
 * @param \CRM_Core_DAO $op
 * @param string $objectName
 * @param int|null $id
 * @param array $params
 *
 * @throws \CRM_Core_Exception
 */
function sequentialcreditnotes_civicrm_pre($op, $objectName, $id, &$params) {
  if ($objectName === 'Contribution' && !empty($params['contribution_status_id'])) {
    $reversalStatuses = ['Cancelled', 'Chargeback', 'Refunded'];
    if (empty($params['creditnote_id']) && in_array(CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $params['contribution_status_id']), $reversalStatuses, TRUE)) {
      if ($id) {
        $existing = Contribution::get(FALSE)->addWhere('id', '=', (int) $id)->setSelect(['creditnote_id'])->execute()->first();
        if ($existing['creditnote_id']) {
          // Since we have it adding it makes is clearer.
          $params['creditnote_id'] = $existing['creditnote_id'];
          return;
        }
      }
      $params['creditnote_id'] = sequentialcreditnotes_create_credit_note_id();
    }
  }
}

/**
 * Generate credit note id with next available number
 *
 * @return string
 *   Credit Note Id.
 *
 * @throws \CRM_Core_Exception
 */
function sequentialcreditnotes_create_credit_note_id() {

  $creditNoteNum = CRM_Core_DAO::singleValueQuery("SELECT count(creditnote_id) as creditnote_number FROM civicrm_contribution WHERE creditnote_id IS NOT NULL");
  $creditNoteId = NULL;

  do {
    $creditNoteNum++;
    $creditNoteId = Civi::settings()->get('credit_notes_prefix') . '' . $creditNoteNum;
    $result = civicrm_api3('Contribution', 'getcount', [
      'sequential' => 1,
      'creditnote_id' => $creditNoteId,
    ]);
  } while ($result > 0);

  return $creditNoteId;
}
