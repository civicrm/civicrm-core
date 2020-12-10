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
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class print the name badges for the participants
 * It isn't supposed to be called directly, but is the parent class of the classes in CRM/Event/Badges/XXX.php
 *
 */
class CRM_Event_Badge {

  /**
   */
  public function __construct() {
    $this->style = [
      'width' => 0.1,
      'cap' => 'round',
      'join' => 'round',
      'dash' => '2,2',
      'color' => [0, 0, 200],
    ];
    $this->format = '5160';
    $this->imgExtension = 'png';
    $this->imgRes = 300;
    $this->event = NULL;
    $this->setDebug(FALSE);
  }

  /**
   * @param bool $debug
   */
  public function setDebug($debug = TRUE) {
    if (!$debug) {
      $this->debug = FALSE;
      $this->border = 0;
    }
    else {
      $this->debug = TRUE;
      $this->border = "LTRB";
    }
  }

  /**
   * Create the labels (pdf).
   *
   * It assumes the participants are from the same event
   *
   * @param array $participants
   */
  public function run(&$participants) {
    // fetch the 1st participant, and take her event to retrieve its attributes
    $participant = reset($participants);
    $eventID = $participant['event_id'];
    $this->event = self::retrieveEvent($eventID);
    //call function to create labels
    self::createLabels($participants);
    CRM_Utils_System::civiExit();
  }

  /**
   * @param int $eventID
   *
   * @return CRM_Event_BAO_Event|null
   */
  protected function retrieveEvent($eventID) {
    $bao = new CRM_Event_BAO_Event();
    if ($bao->get('id', $eventID)) {
      return $bao;
    }
    return NULL;
  }

  /**
   * @param int $eventID
   * @param bool $img
   *
   * @return string
   */
  public function getImageFileName($eventID, $img = FALSE) {
    global $civicrm_root;
    $path = "CRM/Event/Badge";
    if ($img == FALSE) {
      return FALSE;
    }
    if ($img == TRUE) {
      $img = get_class($this) . "." . $this->imgExtension;
    }

    // CRM-13235 - leverage the Smarty path to get all templates directories
    $template = CRM_Core_Smarty::singleton();
    if (isset($template->template_dir) && $template->template_dir) {
      $dirs = is_array($template->template_dir) ? $template->template_dir : [$template->template_dir];
      foreach ($dirs as $dir) {
        foreach (["$dir/$path/$eventID/$img", "$dir/$path/$img"] as $imgFile) {
          if (file_exists($imgFile)) {
            return $imgFile;
          }
        }
      }
    }
    else {
      $imgFile = 'No template directories defined anywhere??';
    }

    // not sure it exists, but at least will display a meaniful fatal error in debug mode
    return $imgFile;
  }

  /**
   * @param bool $img
   */
  public function printBackground($img = FALSE) {
    $x = $this->pdf->GetAbsX();
    $y = $this->pdf->GetY();
    if ($this->debug) {
      $this->pdf->Rect($x, $y, $this->pdf->width, $this->pdf->height, 'D', [
        'all' => [
          'width' => 1,
          'cap' => 'round',
          'join' => 'round',
          'dash' => '2,10',
          'color' => [255, 0, 0],
        ],
      ]);
    }
    $img = $this->getImageFileName($this->event->id, $img);
    if ($img) {
      $imgsize = getimagesize($img);
      // mm
      $f = $this->imgRes / 25.4;
      $w = $imgsize[0] / $f;
      $h = $imgsize[1] / $f;
      $this->pdf->Image($img, $this->pdf->GetAbsX(), $this->pdf->GetY(), $w, $h, strtoupper($this->imgExtension), '', '', FALSE, 72, '', FALSE, FALSE, $this->debug, FALSE, FALSE, FALSE);
    }
    $this->pdf->SetXY($x, $y);
  }

  /**
   * This is supposed to be overridden.
   *
   * @param array $participant
   */
  public function generateLabel($participant) {
    $txt = "{$this->event['title']}
{$participant['display_name']}
{$participant['current_employer']}";

    $this->pdf->MultiCell($this->pdf->width, $this->pdf->lineHeight, $txt);
  }

  public function pdfExtraFormat() {
  }

  /**
   * Create labels (pdf).
   *
   * @param array $participants
   */
  public function createLabels(&$participants) {

    $this->pdf = new CRM_Utils_PDF_Label($this->format, 'mm');
    $this->pdfExtraFormat();
    $this->pdf->Open();
    $this->pdf->setPrintHeader(FALSE);
    $this->pdf->setPrintFooter(FALSE);
    $this->pdf->AddPage();
    $this->pdf->AddFont('DejaVu Sans', '', 'DejaVuSans.php');
    $this->pdf->SetFont('DejaVu Sans');
    $this->pdf->SetGenerator($this, "generateLabel");

    foreach ($participants as $participant) {
      $this->pdf->AddPdfLabel($participant);
    }
    $this->pdf->Output($this->event->title . '.pdf', 'D');
  }

}
