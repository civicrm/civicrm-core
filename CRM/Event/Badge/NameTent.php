<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
class CRM_Event_Badge_NameTent extends CRM_Event_Badge {
  /**
   */
  public function __construct() {
    parent::__construct();
    // A4
    $pw = 297;
    $ph = 210;
    $this->lMargin = 10;
    $this->tMargin = 0;
    $w = $pw - 2 * $this->lMargin;
    $h = $ph - 2 * $this->tMargin;
    $this->format = array(
      'name' => 'A4 horiz',
      'paper-size' => 'A4',
      'metric' => 'mm',
      'lMargin' => 0,
      'tMargin' => 0,
      'NX' => 1,
      'NY' => 1,
      'SpaceX' => 0,
      'SpaceY' => 0,
      'width' => $w,
      'height' => $h,
      'font-size' => 36,
    );
    //      $this->setDebug ();
  }

  public function pdfExtraFormat() {
    $this->pdf->setPageFormat('A4', 'L');
  }

  /**
   * @param $participant
   */
  protected function writeOneSide(&$participant) {
    $this->pdf->SetXY(0, $this->pdf->height / 2);
    $this->printBackground(TRUE);
    $txt = $participant['display_name'];
    $this->pdf->SetXY(0, $this->pdf->height / 2 + 20);
    $this->pdf->SetFontSize(54);
    $this->pdf->Write(0, $txt, NULL, NULL, 'C');
    $this->pdf->SetXY(0, $this->pdf->height / 2 + 50);
    $this->pdf->SetFontSize(36);
    $this->pdf->Write(0, $participant['current_employer'], NULL, NULL, 'C');
  }

  /**
   * @param $participant
   */
  public function generateLabel($participant) {
    $this->writeOneSide($participant);
    $this->pdf->StartTransform();
    $this->pdf->Rotate(180, $this->pdf->width / 2 + $this->pdf->marginLeft, $this->pdf->height / 2);
    $this->writeOneSide($participant);
    $this->pdf->StopTransform();
  }

}
