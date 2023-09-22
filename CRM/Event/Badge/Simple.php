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

  /**
   * @param array $participant
   */
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
