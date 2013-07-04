<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

class CRM_Badge_Format_Avery5395 extends CRM_Badge_Format_Badge {
  public function generateLabel($participant) {
    $this->lMarginLogo = 20;
    $this->tMarginName = 20;

    $x = $this->pdf->GetAbsX();
    $y = $this->pdf->GetY();

    $this->printImage($participant['image_1']);

    $this->pdf->SetLineStyle(array('width' => 0.1, 'cap' => 'round', 'join' => 'round', 'dash' => '2,2', 'color' => array(0, 0, 200)));

    $this->pdf->SetFontSize(9);
    $this->pdf->MultiCell($this->pdf->width - $this->lMarginLogo, 0, $participant['token'][1]['value'], $this->border, "L", 0, 1, $x + $this->lMarginLogo, $y);

    $this->pdf->SetFontSize(20);
    $this->pdf->MultiCell($this->pdf->width, 10, $participant['token'][2]['value'], $this->border, "C", 0, 1, $x, $y + $this->tMarginName);
    $this->pdf->SetFontSize(15);
    $this->pdf->MultiCell($this->pdf->width, 0, $participant['token'][3]['value'], $this->border, "C", 0, 1, $x, $this->pdf->getY());

    $this->pdf->SetFontSize(9);
    $this->pdf->SetXY($x, $y + $this->pdf->height - 5);
    $date = CRM_Utils_Date::customFormat($participant['token'][4]['value'], "%e %b");
    $this->pdf->Cell($this->pdf->width, 0, $date, $this->border, 2, "R");
  }
}
