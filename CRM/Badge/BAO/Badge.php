<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Class CRM_Badge_Format_Badge
 *
 * parent class for building name badges
 */
class CRM_Badge_BAO_Badge {

  public $debug = FALSE;

  public $border = 0;

  /**
   *  This function is called to create name label pdf
   *
   * @param   array $participants associated array with participant info
   * @param   array $layoutInfo   associated array which contains meta data about format/layout
   *
   * @return  void
   * @access  public
   */
  public function createLabels(&$participants, &$layoutInfo) {
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

    $this->pdf->Output(CRM_Utils_String::munge($layoutInfo['title'], '_', 64) . '.pdf', 'D');
    CRM_Utils_System::civiExit(1);
  }

  /**
   * Funtion to create structure and add meta data according to layout
   *
   * @param array $row row element that needs to be formatted
   * @param array $layout layout meta data
   *
   * @return array $formattedRow row with meta data
   */
  static function formatLabel(&$row, &$layout) {
    $formattedRow = array('labelFormat' => $layout['label_format_name']);

    if (CRM_Utils_Array::value('rowElements', $layout['data'])) {
      foreach ($layout['data']['rowElements'] as $key => $element) {
        $value = '';
        if ($element) {
          $value = $row[$element];
          // hack to fix date field display format
          if (strpos($element, '_date')) {
            $value = CRM_Utils_Date::customFormat($value, "%e %b");
          }
        }

        $formattedRow['token'][$key] = array(
          'value' => $value,
          'font_name' => $layout['data']['font_name'][$key],
          'font_size' => $layout['data']['font_size'][$key],
          'text_alignment' => $layout['data']['text_alignment'][$key],
        );
      }
    }

    if (CRM_Utils_Array::value('image_1', $layout['data'])) {
      $formattedRow['image_1'] = $layout['data']['image_1'];
    }

    if (CRM_Utils_Array::value('image_2', $layout['data'])) {
      $formattedRow['image_2'] = $layout['data']['image_2'];
    }

    if (CRM_Utils_Array::value('add_barcode', $layout['data'])) {
      $formattedRow['barcode'] = $layout['data']['barcode_alignment'];
    }

    // finally assign all the row values, so that we can use it for barcode etc
    $formattedRow['values'] = $row;

    return $formattedRow;
  }

  public function generateLabel($formattedRow) {
    switch ($formattedRow['labelFormat']) {
      case 'Avery 5395':
        self::labelAvery5395($formattedRow);
        break;
    }
  }

  public function labelAvery5395(&$formattedRow) {
    $this->lMarginLogo = 18;
    $this->tMarginName = 20;

    $x = $this->pdf->GetAbsX();
    $y = $this->pdf->GetY();

    $titleWidth = $titleLeftMargin = 0;
    if (CRM_Utils_Array::value('image_1', $formattedRow)) {
      $this->printImage($formattedRow['image_1']);
      $titleWidth = $titleLeftMargin = $this->lMarginLogo;
    }

    $titleRightMargin = 0;
    if (CRM_Utils_Array::value('image_2', $formattedRow)) {
      $this->printImage($formattedRow['image_2'], $x + 68);
      $titleRightMargin = 36;
      $titleWidth = $this->lMarginLogo;
    }

    $this->pdf->SetLineStyle(array(
      'width' => 0.1,
      'cap' => 'round',
      'join' => 'round',
      'dash' => '2,2',
      'color' => array(0, 0, 200)
    ));

    if ($titleLeftMargin && $titleRightMargin) {
      $titleWidth = $titleRightMargin;
    }

    $this->pdf->SetFont($formattedRow['token'][1]['font_name'], '', $formattedRow['token'][1]['font_size']);
    $this->pdf->MultiCell($this->pdf->width - $titleWidth, 0, $formattedRow['token'][1]['value'],
      $this->border, $formattedRow['token'][1]['text_alignment'], 0, 1, $x + $titleLeftMargin, $y);

    $this->pdf->SetFont($formattedRow['token'][2]['font_name'], '', $formattedRow['token'][2]['font_size']);
    $this->pdf->MultiCell($this->pdf->width, 10, $formattedRow['token'][2]['value'],
      $this->border, $formattedRow['token'][2]['text_alignment'], 0, 1, $x, $y + $this->tMarginName);

    $this->pdf->SetFont($formattedRow['token'][3]['font_name'], '', $formattedRow['token'][3]['font_size']);
    $this->pdf->MultiCell($this->pdf->width, 0, $formattedRow['token'][3]['value'],
      $this->border, $formattedRow['token'][3]['text_alignment'], 0, 1, $x, $this->pdf->getY());

    $this->pdf->SetFont($formattedRow['token'][4]['font_name'], '', $formattedRow['token'][4]['font_size']);
    $this->pdf->MultiCell($this->pdf->width, 0, $formattedRow['token'][4]['value'],
      $this->border, $formattedRow['token'][4]['text_alignment'], 0, 1, $x, $y + $this->pdf->height - 5);

    if (CRM_Utils_Array::value('barcode', $formattedRow)) {
      $style = array(
        'position' => $formattedRow['barcode'],
        'align' => $formattedRow['barcode'],
        'stretch' => FALSE,
        'fitwidth' => TRUE,
        'cellfitalign' => '',
        'border' => FALSE,
        'hpadding' => 13.5,
        'vpadding' => 'auto',
        'fgcolor' => array(0, 0, 0),
        'bgcolor' => FALSE,
        'text' => FALSE,
        'font' => 'helvetica',
        'fontsize' => 8,
        'stretchtext' => 0,
      );

      $data = array();
      $data['current_value'] = $formattedRow['values']['contact_id'] . '-' . $formattedRow['values']['participant_id'];
      $this->pdf->write1DBarcode($data['current_value'], 'C128', $x, $y  + $this->pdf->height - 10, '', 12, 0.4, $style, 'B');
    }
  }

  /**
   * Helper function to print images
   * @param string $img image url
   *
   * @return void
   * @access public
   */
  function printImage($img, $x = '', $y = '') {
    if (!$x) {
      $x = $this->pdf->GetAbsX();
    }

    if (!$y) {
      $y = $this->pdf->GetY();
    }

    $this->imgRes = 300;

    if ($img) {
      $imgsize = getimagesize($img);
      // mm
      $f = $this->imgRes / 25.4;
      $w = $imgsize[0] / $f;
      $h = $imgsize[1] / $f;
      $this->pdf->Image($img, $x, $y, $w, $h, '', '', '', FALSE, 72, '', FALSE,
        FALSE, $this->debug, FALSE, FALSE, FALSE);
    }
    $this->pdf->SetXY($x, $y);
  }
}

