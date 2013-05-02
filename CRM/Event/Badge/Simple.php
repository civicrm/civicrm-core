<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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


/*
* Copyright (C) 2010 Tech To The People
* Licensed to CiviCRM under the Academic Free License version 3.0.
*
*/

/**
 *
 * @package CRM
 *
 */
class CRM_Event_Badge_Simple extends CRM_Event_Badge {

  public function generateLabel($participant) {
    $date = CRM_Utils_Date::customFormat($participant['event_start_date'], "%e %b");
    $this->pdf->SetFontSize(8);
    $y = $this->pdf->GetY();
    $x = $this->pdf->GetAbsX();
    $this->pdf->Cell($this->pdf->width, $this->pdf->lineHeight, $participant['event_title'], 0, 1, "L");
    $this->pdf->SetXY($x, $y + 4);
    $this->pdf->Cell($this->pdf->width, $this->pdf->lineHeight, $date, 0, 2, "R");
    $this->pdf->SetFontSize(12);
    $this->pdf->SetXY($x, $this->pdf->GetY() + 5);
    $this->pdf->Cell($this->pdf->width, $this->pdf->lineHeight, $participant['display_name'], 0, 2, "C");
    $this->pdf->SetFontSize(10);
    $this->pdf->SetXY($x, $this->pdf->GetY() + 2);
    $this->pdf->Cell($this->pdf->width, $this->pdf->lineHeight, $participant['current_employer'], 0, 2, "C");
    //$this->pdf->MultiCell ($this->pdf->width, $this->pdf->lineHeight, $txt,1,"L");
  }
}

