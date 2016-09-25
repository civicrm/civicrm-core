<?php

/**
 * Class CRM_Event_Badge_Logo5395
 */
class CRM_Event_Badge_Logo5395 extends CRM_Event_Badge {
  /**
   */
  public function __construct() {
    parent::__construct();
    // A4
    $pw = 210;
    $ph = 297;
    $h = 59.2;
    $w = 85.7;
    $this->format = array(
      'name' => 'Avery 5395',
      'paper-size' => 'A4',
      'metric' => 'mm',
      'lMargin' => 13.5,
      'tMargin' => 3,
      'NX' => 2,
      'NY' => 4,
      'SpaceX' => 15,
      'SpaceY' => 8.5,
      'width' => $w,
      'height' => $h,
      'font-size' => 12,
    );
    $this->lMarginLogo = 20;
    $this->tMarginName = 20;
    //      $this->setDebug ();
  }

  /**
   * @param $participant
   */
  public function generateLabel($participant) {
    $x = $this->pdf->GetAbsX();
    $y = $this->pdf->GetY();
    $this->printBackground(TRUE);
    $this->pdf->SetLineStyle(array(
        'width' => 0.1,
        'cap' => 'round',
        'join' => 'round',
        'dash' => '2,2',
        'color' => array(0, 0, 200),
      ));

    $this->pdf->SetFontSize(9);
    $this->pdf->MultiCell($this->pdf->width - $this->lMarginLogo, 0, $participant['event_title'], $this->border, "L", 0, 1, $x + $this->lMarginLogo, $y);

    $this->pdf->SetXY($x, $y + $this->pdf->height - 5);
    $date = CRM_Utils_Date::customFormat($participant['event_start_date'], "%e %b");
    $this->pdf->Cell($this->pdf->width, 0, $date, $this->border, 2, "R");

    $this->pdf->SetFontSize(20);
    $this->pdf->MultiCell($this->pdf->width, 10, $participant['display_name'], $this->border, "C", 0, 1, $x, $y + $this->tMarginName);
    $this->pdf->SetFontSize(15);
    $this->pdf->MultiCell($this->pdf->width, 0, $participant['current_employer'], $this->border, "C", 0, 1, $x, $this->pdf->getY());
  }

}
