<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */

/**
 * This class handle grant related functions
 *
 */
class CRM_Grant_Page_PrintPDF extends CRM_Contact_Page_View {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;
  public $_permission = NULL;
  public $_contactId = NULL;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $context       = CRM_Utils_Request::retrieve('context', 'String', $this);
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->_id     = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    if ($context == 'standalone') {
      $this->_action = CRM_Core_Action::ADD;
    }
    else {
      $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
      $displayName = CRM_Contact_BAO_Contact::displayName($this->_contactId);
      $this->assign('contactId', $this->_contactId);
      $this->assign('displayName', $displayName);

      // check logged in url permission
      CRM_Contact_Page_View::checkUserPermission($this);

      // set page title
      CRM_Contact_Page_View::setTitle($this->_contactId);
    }
    $this->assign('action', $this->_action);

    if ($this->_permission == CRM_Core_Permission::EDIT && !CRM_Core_Permission::check('edit grants')) {
      // demote to view since user does not have edit grant rights
      $this->_permission = CRM_Core_Permission::VIEW;
      $this->assign('permission', 'view');
    }
  }

  /**
   * This function is the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * return null
   * @access public
   */
  function run() {
    $this->preProcess();    
    
    if ($this->_action & CRM_Core_Action::EXPORT) {
      $this->pdfGenerate();
    }
  }

  function pdfGenerate() {
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    $this->_id        = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $context          = CRM_Utils_Request::retrieve('context', 'String', $this);
    $config = CRM_Core_Config::singleton();
    $appRecDate = $decDate = $moneyTxnDate = $grantDueDate = NULL;
    $values = array();
    $params['id'] = $this->_id;
    CRM_Grant_BAO_Grant::retrieve($params, $values);
    $values['attachment'] = CRM_Core_BAO_File::getEntityFile('civicrm_grant', $this->_id);
    $custom = CRM_Core_BAO_CustomValueTable::getEntityValues($this->_id, 'Grant');
    $ids = array_keys($custom);
    $count = 0;
    foreach ($ids as $key => $val) {
      $customData[$count]['label'] = CRM_Core_DAO::getFieldValue("CRM_Core_DAO_CustomField", $val, "label", "id");
      $customData[$count]['html_type'] = CRM_Core_DAO::getFieldValue("CRM_Core_DAO_CustomField", $val, "html_type", "id");
      $customData[$count]['data_type'] = CRM_Core_DAO::getFieldValue("CRM_Core_DAO_CustomField", $val, "data_type", "id");
      $customData[$count]['value'] = $custom[$val];
      $count++;
    }
    if (isset($this->_id)) {
      $noteDAO = new CRM_Core_BAO_Note();
      $noteDAO->entity_table = 'civicrm_grant';
      $noteDAO->entity_id = $this->_id;
      if ($noteDAO->find(TRUE)) {
        $values['noteId'] = $noteDAO->note;
      }
    }
    $values['display_name']=CRM_Contact_BAO_Contact::displayName($this->_contactID);
    if (CRM_Utils_Array::value('application_received_date', $values)) {
      $values['app_rec_date'] = date('jS F Y', strtotime($values['application_received_date']));
    }
    if (CRM_Utils_Array::value('decision_date', $values)) {
      $values['dec_date'] = date('jS F Y', strtotime($values['decision_date']));
    }
    if (CRM_Utils_Array::value('money_transfer_date', $values)) {
      $values['money_trxn_date'] = date('jS F Y', strtotime($values['money_transfer_date']));
    }
    if (CRM_Utils_Array::value('grant_due_date', $values)) {
      $values['due_date'] = date('jS F Y', strtotime($values['grant_due_date']));
    }

    require_once("packages/dompdf/dompdf_config.inc.php");
    spl_autoload_register('DOMPDF_autoload');
    $fileName = 'Grant_'.$this->_contactID.'_'.$this->_id.'.pdf';
    $filePath = "{$config->customFileUploadDir}/$fileName";
    $fileArray[] = $filePath;
    $tplFile = $this->getTemplateFileName();
    $this->assign('values', $values);
    $html = self::getTemplate()->fetch($tplFile);
    
    if (!empty($customData)) {
      $fileDAO = new CRM_Core_BAO_File();
      foreach ($customData as $keys => $vals) {
        if ( ( $vals['html_type'] == "Text" || $vals['html_type'] == "Autocomplete-Select" || $vals['html_type'] == "Radio"
               || $vals['html_type'] == "Select Date") && $vals['data_type'] != "ContactReference" ) {
          $html .="<tr><td><b>".$vals['label']."<b></td><td>".$vals['value']."</td></tr>";
        } elseif ( ( $vals['html_type'] == "AdvMulti-Select" || $vals['html_type'] == "Multi-Select" || $vals['html_type'] == "CheckBox" ) && !empty($vals['value']) ) {
            $key = explode(CRM_Core_DAO::VALUE_SEPARATOR, $vals['value']);
            $key = array_filter($key);
            $key = implode(', ', $key);
            $html .="<tr><td><b>".$vals['label']."<b></td><td>".$key."</td></tr>";
        } elseif ( $vals['data_type'] == "ContactReference" && !empty($vals['value']) ) {
          $html .="<tr><td><b>".$vals['label']."<b></td><td>".CRM_Contact_BAO_Contact::displayName($vals['value'])."</td></tr>";
        } elseif ( $vals['html_type'] == "RichTextEditor" ) {
          $html .="<tr><td><b>".$vals['label']."<b></td><td>".strip_tags($vals['value'])."</td></tr>";
        } elseif ( $vals['html_type'] == "File" ) {
          $fileDAO->id = $vals['value'];
          if( $fileDAO->find(true) ) {
            $source = explode($config->useFrameworkRelativeBase, $config->customFileUploadDir);
            $source = $config->userFrameworkBaseURL.$source[1];
            switch( $fileDAO->mime_type ) {
              case "text/plain":
                $raw = file($source);
                $data = implode('<br>', $raw);
                $html .="<tr><td><b>".$vals['label']."<b></td><td>".$data."</td></tr>";
                break;
              case "image/jpeg":
              case "image/png":
                $html .="<tr><td><b>{$vals['label']}<b></td><td><img src={$source}{$fileDAO->uri} /></td></tr>";
              break;
              case "application/rtf":
                $raw = file($source);
                foreach ( $raw as $plain ) {
                  $text[] = strip_tags($plain);
                }
                $data = implode('<br>', $text);
                $html .="<tr><td><b>Attachment<b></td><td>".$data."</td></tr>";
                break;
              default:
                break;
            }
          }
        }
      }
    }
    if ( !empty($values['attachment']) ) {
      foreach( $values['attachment'] as $attachKey => $attachValue ) {
        switch( $attachValue['mime_type'] ) {
        case "image/jpeg":
        case "image/png":
          $source = explode($config->useFrameworkRelativeBase, $config->customFileUploadDir);
          $source = $config->userFrameworkBaseURL.$source[1];
          $html .="<tr><td><b>Attachment<b></td><td><img src={$source}{$attachValue['fileName']} /></td></tr>";
        break;
        case "text/plain":
          $raw = file($attachValue['fullPath']);
          $data = implode('<br>', $raw);
          $html .="<tr><td><b>Attachment<b></td><td>".$data."</td></tr>";
          break;
        case "application/rtf":
          $raw = file($attachValue['fullPath']);
          foreach ( $raw as $plain ) {
            $text[] = strip_tags($plain);
          }
          $data = implode('<br>', $text);
          $html .="<tr><td><b>Attachment<b></td><td>".$data."</td></tr>";
          break;
        default:
        break;
      }
    }
  } 
  $html .="
</table>
</body>
</html>";
  $dompdf = new DOMPDF();
  
  $dompdf->load_html($html);
  $dompdf->render();

  file_put_contents($filePath, $dompdf->output());
     

  if (file_exists($filePath)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.basename($filePath));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    ob_clean();
    flush();
    readfile($filePath);
    CRM_Utils_System::civiExit();
  }
  }

  function getTemplateFileName() {
    return 'CRM/Grant/Page/PrintPDF.tpl';
  }
}

