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
class CRM_Badge_Format_Badge {
  function printImage($img) {
    $x = $this->pdf->GetAbsX();
    $y = $this->pdf->GetY();

    $this->imgRes = 300;

    if ($img) {
      $imgsize = getimagesize($img);
      // mm
      $f = $this->imgRes / 25.4;
      $w = $imgsize[0] / $f;
      $h = $imgsize[1] / $f;
      $this->pdf->Image($img, $this->pdf->GetAbsX(), $this->pdf->GetY(), $w, $h, '', '', '', FALSE, 72, '', FALSE, FALSE, FALSE, FALSE, FALSE, FALSE);
    }
    $this->pdf->SetXY($x, $y);
  }

  /**
   *  This function is called to create name label pdf
   *
   * @param   array    $participants associated array with participant info
   * @param   array    $layoutInfo   associated array which contains meta data about format/layout
   *
   * @return  void
   * @access  public
   */
  function createLabels(&$participants, &$layoutInfo) {
    $this->pdf = new CRM_Utils_PDF_Label($layoutInfo['format'], 'mm');
    $this->pdf->Open();
    $this->pdf->setPrintHeader(FALSE);
    $this->pdf->setPrintFooter(FALSE);
    $this->pdf->AddPage();
    $this->pdf->AddFont('DejaVu Sans', '', 'DejaVuSans.php');
    $this->pdf->SetFont('DejaVu Sans');
    $this->pdf->SetGenerator($this, "generateLabel");

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
    $formattedRow = array();
    if (CRM_Utils_Array::value('rowElements', $layout['data'])) {
      foreach($layout['data']['rowElements'] as $key => $element) {
        $formattedRow['token'][$key] = array(
          'value' => $row[$element],
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

    return $formattedRow;
  }
}

