<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Contact_Form_Search_Custom_FullText_File extends CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery {

  /**
   * @var DrupalApacheSolrServiceInterface
   *
   * At time of writing, this interface is fairly minimal and doesn't seem to require Drupalisms.
   */
  protected $solrService;

  public function __construct() {
    parent::__construct('File', ts('Files'));
  }

  public function isActive() {
    return
      function_exists('apachesolr_get_solr') // Drupal site with apachesolr module
      && function_exists('apachesolr_civiAttachments_solr_document') // Drupal site with apachesolr_civiAttachments module
      && CRM_Core_Permission::check('access uploaded files');
  }

  /**
   * @return DrupalApacheSolrServiceInterface
   */
  public function getSolrService() {
    if ($this->solrService === NULL) {
      $this->solrService = apachesolr_get_solr();
    }
    return $this->solrService;
  }

  /**
   * {@inheritdoc}
   */
  public function fillTempTable($queryText, $entityIDTableName, $toTable, $queryLimit, $detailLimit) {
    $solrResponse = $this->doSearch($queryText, $queryLimit);
    if (!$solrResponse) {
      CRM_Core_Session::setStatus(ts('Search service (%1) returned an invalid response', array(1 => 'Solr')), ts('File Search'), 'error');
      return 0;
    }
    $fileIds = $this->extractFileIds($solrResponse, $detailLimit);
    $matches = $this->formatFileMatches($fileIds);
    $this->insertMatches($toTable, $matches);

    if (count($matches) < count($fileIds)) {
      CRM_Core_Session::setStatus(
        ts('The search service returned %1 file match(es), but only %2 match(es) exist.',
          array(1 => count($fileIds), 2 => count($matches))
        ),
        ts('File Search')
      );
    }
    return count($solrResponse->docs);
    //return $solrResponse->numFound;
    //return count($matches);
  }

  /**
   * @param string $queryText
   * @param array|NULL $limit
   * @return object|NULL
   */
  public function doSearch($queryText, $limit) {
    $params = array();
    if (is_array($limit)) {
      list ($params['rows'], $params['start']) = $limit;
      if (!$params['start']) {
        $params['start'] = 0;
      }
    }
    $query = $this->getSolrService()->search("entity_type:civiFile AND content:($queryText)", $params);
    if ($query->code == 200) {
      return $query->response;
    }
    else {
      CRM_Core_Error::debug_var('failedSolrQuery', $query);
      return NULL;
    }
  }

  /**
   * Extract the list of file ID#'s from a Solr response.
   *
   * @param array $solrResponse
   * @param array|NULL $limit
   * @return array<int>
   * @throws CRM_Core_Exception
   */
  public function extractFileIds($solrResponse, $limit) {
    $fileIds = array();
    if (!empty($solrResponse->docs)) {
      if ($limit) {
        list($rowCount, $offset) = $limit;
        $docs = array_slice($solrResponse->docs, $offset ? $offset : 0, $rowCount);
      }
      else {
        $docs = $solrResponse->docs;
      }

      foreach ($docs as $doc) {
        if ($doc->entity_type == 'civiFile') {
          if (isset($doc->entity_id)) {
            $fileIds[] = $doc->entity_id;
          }
          else {
            CRM_Core_Session::setStatus(ts('Incorrect response type'), ts('File Search'));
          }
        }
      }
    }
    return $fileIds;
  }

  /**
   * Given a list of matching $fileIds, prepare a list of match records
   * with details about the file (such as file-name and URL).
   *
   * @param array<int> $fileIds
   * @return array
   */
  public function formatFileMatches($fileIds) {
    $fileIdsCsv = implode(',', array_filter($fileIds, 'is_numeric'));
    if (empty($fileIdsCsv)) {
      return array();
    }

    $selectFilesSql = "
      SELECT     f.*, ef.*, ef.id as entity_file_id
      FROM       civicrm_file f
      INNER JOIN civicrm_entity_file ef ON f.id = ef.file_id
      WHERE      f.id IN ({$fileIdsCsv})
    ";
    $selectFilesDao = CRM_Core_DAO::executeQuery($selectFilesSql);

    $matches = array();
    while ($selectFilesDao->fetch()) {
      $match = array(
        'table_name' => $this->getName(),
        'file_id' => $selectFilesDao->file_id,
        'file_name' => CRM_Utils_File::cleanFileName($selectFilesDao->uri),
        'file_url' => CRM_Utils_System::url('civicrm/file', "reset=1&id={$selectFilesDao->file_id}&eid={$selectFilesDao->entity_id}"),
        'file_mime_type' => $selectFilesDao->mime_type,
      );

      if ($selectFilesDao->entity_table == 'civicrm_note') {
        // For notes, we go up an extra level to the note's parent
        $note = new CRM_Core_DAO_Note();
        $note->id = $selectFilesDao->entity_id;
        $note->find();
        if ($note->fetch()) {
          $match['file_entity_table'] = $note->entity_table;
          $match['file_entity_id'] = $note->entity_id;
        }
        else {
          continue; // skip; perhaps an orphan?
        }
      }
      else {
        $match['file_entity_table'] = $selectFilesDao->entity_table;
        $match['file_entity_id'] = $selectFilesDao->entity_id;
      }
      $matches[] = $match;
    }

    // When possible, add 'contact_id' to matches
    foreach (array_keys($matches) as $matchKey) {
      switch ($matches[$matchKey]['file_entity_table']) {
        case'civicrm_contact':
          $matches[$matchKey]['contact_id'] = $matches[$matchKey]['file_entity_id'];
          //$matches[$matchKey]['sort_name'] = NULL;
          //$matches[$matchKey]['display_name'] = NULL;
          break;
        default:
          $matches[$matchKey]['contact_id'] = NULL;
        //$matches[$matchKey]['sort_name'] = NULL;
        //$matches[$matchKey]['display_name'] = NULL;
      }
    }

    return $matches;
  }

  /**
   * @param string $toTable
   * @param array $matches each $match is an array which defines a row in $toTable
   */
  public function insertMatches($toTable, $matches) {
    if (empty($matches)) {
      return;
    }
    $insertContactSql = CRM_Utils_SQL_Insert::into($toTable)->rows($matches)->toSQL();
    CRM_Core_DAO::executeQuery($insertContactSql);
  }
}
