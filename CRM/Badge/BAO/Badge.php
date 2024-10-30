<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Token\TokenProcessor;

/**
 * Class CRM_Badge_Format_Badge.
 *
 * parent class for building name badges
 */
class CRM_Badge_BAO_Badge {

  /**
   * @var CRM_Utils_PDF_Label
   */
  public $pdf;

  /**
   * @var bool
   */
  public $debug = FALSE;

  /**
   * @var int
   */
  public $border = 0;

  /**
   *  This function is called to create name label pdf.
   *
   * @param array $participants
   *   Associated array with participant info.
   * @param array $layoutInfo
   *   Associated array which contains meta data about format/layout.
   */
  public function createLabels($participants, &$layoutInfo) {
    $this->pdf = new CRM_Utils_PDF_Label($layoutInfo['format'], 'mm');
    $this->pdf->Open();
    $this->pdf->setPrintHeader(FALSE);
    $this->pdf->setPrintFooter(FALSE);
    $this->pdf->AddPage();
    $this->pdf->SetGenerator($this, "generateLabel");

    // this is very useful for debugging, by default set to FALSE
    if ($this->debug) {
      $this->border = "LTRB";
    }

    foreach ($participants as $participant) {
      $formattedRow = self::formatLabel($participant, $layoutInfo);
      $this->pdf->AddPdfLabel($formattedRow);
    }

    if (CIVICRM_UF === 'UnitTests') {
      throw new CRM_Core_Exception_PrematureExitException('pdf output called', ['formattedRow' => $formattedRow]);
    }
    $this->pdf->Output(CRM_Utils_String::munge($layoutInfo['title'], '_', 64) . '.pdf', 'D');
    CRM_Utils_System::civiExit();
  }

  /**
   * Function to create structure and add meta data according to layout.
   *
   * @param array $row
   *   Row element that needs to be formatted.
   * @param array $layout
   *   Layout meta data.
   *
   * @return array
   *   row with meta data
   */
  public static function formatLabel(array $row, array $layout): array {
    $formattedRow = ['labelFormat' => $layout['label_format_name']];
    $formattedRow['labelTitle'] = $layout['title'];
    $formattedRow['labelId'] = $layout['id'];

    if (!empty($layout['data']['rowElements'])) {
      foreach ($layout['data']['rowElements'] as $key => $element) {
        $value = '';
        if ($element) {
          $value = $row[$element];
        }

        $formattedRow['token'][$key] = [
          'value' => $value,
          'font_name' => $layout['data']['font_name'][$key],
          'font_size' => $layout['data']['font_size'][$key],
          'font_style' => $layout['data']['font_style'][$key],
          'text_alignment' => $layout['data']['text_alignment'][$key],
          'token' => $layout['data']['token'][$key],
        ];
      }
    }

    if (!empty($layout['data']['image_1'])) {
      $formattedRow['image_1'] = $layout['data']['image_1'];
    }
    if (!empty($layout['data']['width_image_1'])) {
      $formattedRow['width_image_1'] = $layout['data']['width_image_1'];
    }
    if (!empty($layout['data']['height_image_1'])) {
      $formattedRow['height_image_1'] = $layout['data']['height_image_1'];
    }

    if (!empty($layout['data']['image_2'])) {
      $formattedRow['image_2'] = $layout['data']['image_2'];
    }
    if (!empty($layout['data']['width_image_2'])) {
      $formattedRow['width_image_2'] = $layout['data']['width_image_2'];
    }
    if (!empty($layout['data']['height_image_2'])) {
      $formattedRow['height_image_2'] = $layout['data']['height_image_2'];
    }
    if (!empty($row['image_URL']) && !empty($layout['data']['show_participant_image'])) {
      $formattedRow['participant_image'] = $row['image_URL'];
    }
    if (!empty($layout['data']['width_participant_image'])) {
      $formattedRow['width_participant_image'] = $layout['data']['width_participant_image'];
    }
    if (!empty($layout['data']['height_participant_image'])) {
      $formattedRow['height_participant_image'] = $layout['data']['height_participant_image'];
    }
    if (!empty($layout['data']['alignment_participant_image'])) {
      $formattedRow['alignment_participant_image'] = $layout['data']['alignment_participant_image'];
    }

    if (!empty($layout['data']['add_barcode'])) {
      $formattedRow['barcode'] = [
        'alignment' => $layout['data']['barcode_alignment'],
        'type' => $layout['data']['barcode_type'],
      ];
    }

    // finally assign all the row values, so that we can use it for barcode etc
    $formattedRow['values'] = $row;

    return $formattedRow;
  }

  /**
   * @param array $formattedRow
   */
  public function generateLabel(array $formattedRow): void {
    switch ($formattedRow['labelFormat']) {
      case 'A6 Badge Portrait 150x106':
      case 'Hanging Badge 3-3/4" x 4-3"/4':
        $this->labelCreator($formattedRow, 5);
        break;

      case 'Avery 5395':
      default:
        $this->labelCreator($formattedRow);
        break;
    }
  }

  /**
   * @param array $formattedRow
   * @param int $cellspacing
   */
  public function labelCreator($formattedRow, $cellspacing = 0) {
    $x = $this->pdf->GetAbsX();
    $y = $this->pdf->getY();

    //call hook alterBadge
    CRM_Utils_Hook::alterBadge($formattedRow['labelTitle'], $this, $formattedRow, $formattedRow['values']);

    $startOffset = 0;
    if (!empty($formattedRow['image_1'])) {
      $this->printImage($formattedRow['image_1'], NULL, NULL, CRM_Utils_Array::value('width_image_1', $formattedRow),
        CRM_Utils_Array::value('height_image_1', $formattedRow));
    }

    if (!empty($formattedRow['image_2'])) {
      $this->printImage($formattedRow['image_2'], $x + 68, NULL, CRM_Utils_Array::value('width_image_2', $formattedRow),
        CRM_Utils_Array::value('height_image_2', $formattedRow));
    }

    if ((CRM_Utils_Array::value('height_image_1', $formattedRow) >
        CRM_Utils_Array::value('height_image_2', $formattedRow)) && !empty($formattedRow['height_image_1'])
    ) {
      $startOffset = $formattedRow['height_image_1'] ?? NULL;
    }
    elseif (!empty($formattedRow['height_image_2'])) {
      $startOffset = $formattedRow['height_image_2'] ?? NULL;
    }

    if (!empty($formattedRow['participant_image'])) {
      $imageAlign = 0;
      switch ($formattedRow['alignment_participant_image'] ?? NULL) {
        case 'R':
          $imageAlign = 68;
          break;

        case 'L':
          $imageAlign = 0;
          break;

        default:
          break;
      }
      $this->pdf->Image($formattedRow['participant_image'], $x + $imageAlign, $y + $startOffset, CRM_Utils_Array::value('width_participant_image', $formattedRow), CRM_Utils_Array::value('height_participant_image', $formattedRow));
      if ($startOffset == NULL && !empty($formattedRow['height_participant_image'])) {
        $startOffset = $formattedRow['height_participant_image'];
      }
    }

    $this->pdf->SetLineStyle([
      'width' => 0.1,
      'cap' => 'round',
      'join' => 'round',
      'dash' => '2,2',
      'color' => [0, 0, 200],
    ]);

    $rowCount = CRM_Badge_Form_Layout::FIELD_ROWCOUNT;
    for ($i = 1; $i <= $rowCount; $i++) {
      if (!empty($formattedRow['token'][$i]['token'])) {
        $value = '';
        if ($formattedRow['token'][$i]['token'] !== 'spacer') {
          $value = $formattedRow['token'][$i]['value'];
        }

        $xAlign = $x;
        $rowWidth = $this->pdf->width;
        if (!empty($formattedRow['participant_image']) && !empty($formattedRow['width_participant_image'])) {
          $rowWidth = $this->pdf->width - $formattedRow['width_participant_image'];
          if ($formattedRow['alignment_participant_image'] == 'L') {
            $xAlign = $x + $formattedRow['width_participant_image'] + $imageAlign;
          }
        }
        $offset = $this->pdf->getY() + $startOffset + $cellspacing;

        $this->pdf->SetFont($formattedRow['token'][$i]['font_name'], $formattedRow['token'][$i]['font_style'],
          $formattedRow['token'][$i]['font_size']);
        $this->pdf->MultiCell($rowWidth, 0, $value,
          $this->border, $formattedRow['token'][$i]['text_alignment'], 0, 1, $xAlign, $offset);

        // set this to zero so that it is added only for first element
        $startOffset = 0;
      }
    }

    if (!empty($formattedRow['barcode'])) {
      $data = $formattedRow['values'];

      if ($formattedRow['barcode']['type'] === 'barcode') {
        $data['current_value'] = $formattedRow['values']['contact_id'] . '-' . $formattedRow['values']['participant_id'];
      }
      else {
        // view participant url
        $data['current_value'] = CRM_Utils_System::url('civicrm/contact/view/participant',
          'action=view&reset=1&cid=' . $formattedRow['values']['contact_id'] . '&id='
          . $formattedRow['values']['participant_id'],
          TRUE,
          NULL,
          FALSE
        );
      }

      // call hook alterBarcode
      CRM_Utils_Hook::alterBarcode($data, $formattedRow['barcode']['type']);

      if ($formattedRow['barcode']['type'] == 'barcode') {
        // barcode position
        $xAlign = $x;

        switch ($formattedRow['barcode']['alignment']) {
          case 'L':
            $xAlign += -14;
            break;

          case 'R':
            $xAlign += 27;
            break;

          case 'C':
            $xAlign += 9;
            break;
        }

        $style = [
          'position' => '',
          'align' => '',
          'stretch' => FALSE,
          'fitwidth' => TRUE,
          'cellfitalign' => '',
          'border' => FALSE,
          'hpadding' => 13.5,
          'vpadding' => 'auto',
          'fgcolor' => [0, 0, 0],
          'bgcolor' => FALSE,
          'text' => FALSE,
          'font' => 'helvetica',
          'fontsize' => 8,
          'stretchtext' => 0,
        ];

        $this->pdf->write1DBarcode($data['current_value'], 'C128', $xAlign, $y + $this->pdf->height - 10, '70',
          12, 0.4, $style, 'B');
      }
      else {
        // qr code position
        $xAlign = $x;

        switch ($formattedRow['barcode']['alignment']) {
          case 'L':
            $xAlign += -5;
            break;

          case 'R':
            $xAlign += 56;
            break;

          case 'C':
            $xAlign += 29;
            break;
        }

        $style = [
          'border' => FALSE,
          'hpadding' => 13.5,
          'vpadding' => 'auto',
          'fgcolor' => [0, 0, 0],
          'bgcolor' => FALSE,
          'position' => '',
        ];

        $this->pdf->write2DBarcode($data['current_value'], 'QRCODE,H', $xAlign, $y + $this->pdf->height - 26, 30,
          30, $style, 'B');
      }
    }
  }

  /**
   * Helper function to print images.
   *
   * @param string $img
   *   Image url.
   * @param string|null $x
   * @param string|null $y
   * @param int|null $w
   * @param int|null $h
   */
  public function printImage($img, $x = NULL, $y = NULL, $w = NULL, $h = NULL) {
    if (!$x) {
      $x = $this->pdf->GetAbsX();
    }

    if (!$y) {
      $y = $this->pdf->GetY();
    }

    if ($img) {
      [$w, $h] = self::getImageProperties($img, 300, $w, $h);
      $this->pdf->Image($img, $x, $y, $w, $h, '', '', '', FALSE, 72, '', FALSE,
        FALSE, $this->debug, FALSE, FALSE, FALSE);
    }
    $this->pdf->SetXY($x, $y);
  }

  /**
   * @param string $img
   *   Filename
   * @param int $imgRes
   * @param int|null $w
   * @param int|null $h
   *
   * @return int[]
   *   [width, height]
   */
  public static function getImageProperties($img, $imgRes = 300, $w = NULL, $h = NULL) {
    $imgsize = getimagesize($img);
    $f = $imgRes / 25.4;
    $w = !empty($w) ? $w : $imgsize[0] / $f;
    $h = !empty($h) ? $h : $imgsize[1] / $f;
    return [$w, $h];
  }

  /**
   * Build badges parameters before actually creating badges.
   *
   * @param array $params
   *   Associated array of submitted values.
   * @param CRM_Core_Form $form
   */
  public static function buildBadges(&$params, &$form) {
    // get name badge layout info
    $layoutInfo = CRM_Badge_BAO_Layout::buildLayout($params);
    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), ['schema' => ['participantId', 'eventId'], 'smarty' => FALSE]);
    // split/get actual field names from token and individual contact image URLs
    $processorTokens = [];
    if (!empty($layoutInfo['data']['token'])) {
      foreach ($layoutInfo['data']['token'] as $index => $value) {
        if ($value) {
          $tokenName = str_replace(['}', '{contact.', '{participant.', '{event.'], '', $value);
          $tokenProcessor->addMessage($tokenName, $value, 'text/plain');
          $processorTokens[] = $tokenName;
          $layoutInfo['data']['rowElements'][$index] = $tokenName;
        }
      }
    }

    $returnProperties = [
      'participant_id' => 1,
      'event_id' => 1,
      'contact_id' => 1,
    ];
    $sortOrder = $form->get(CRM_Utils_Sort::SORT_ORDER);

    if ($sortOrder) {
      $sortField = explode(' ', $sortOrder)[0];
      // Add to select so aliaising is handled.
      $returnProperties[trim(str_replace('`', ' ', $sortField))] = 1;
      $sortOrder = " ORDER BY $sortOrder";
    }
    if ($form->_single) {
      $queryParams = NULL;
    }
    else {
      $queryParams = $form->get('queryParams');
    }

    $query = new CRM_Contact_BAO_Query($queryParams, $returnProperties, NULL, FALSE, FALSE,
      CRM_Contact_BAO_Query::MODE_EVENT
    );

    [$select, $from, $where, $having] = $query->query();
    if (empty($where)) {
      $where = "WHERE {$form->_componentClause}";
    }
    else {
      $where .= " AND {$form->_componentClause}";
    }

    $queryString = "$select $from $where $having $sortOrder";

    $dao = CRM_Core_DAO::executeQuery($queryString);
    $rows = [];

    while ($dao->fetch()) {
      $tokenProcessor->addRow(['contactId' => $dao->contact_id, 'participantId' => $dao->participant_id, 'eventId' => $dao->event_id]);
    }
    $tokenProcessor->evaluate();
    foreach ($tokenProcessor->getRows() as $row) {
      $rows[$row->context['participantId']]['contact_id'] = $row->context['contactId'];
      $rows[$row->context['participantId']]['participant_id'] = $row->context['participantId'];
      foreach ($processorTokens as $processorToken) {
        $rows[$row->context['participantId']][$processorToken] = $row->render($processorToken);
      }
    }

    $eventBadgeClass = new CRM_Badge_BAO_Badge();
    $eventBadgeClass->createLabels($rows, $layoutInfo);
  }

}
