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
   * @var array
   */
  var $solrResponse;

  function __construct() {
    parent::__construct('File', ts('Files'));
  }

  function isActive() {
    return CRM_Core_Permission::check('access uploaded files');
  }

  /**
   * {@inheritdoc}
   */
  public function fillTempTable($queryText, $entityIDTableName, $toTable, $queryLimit, $detailLimit) {
    $this->solrResponse = $this->doSearch($queryText, $queryLimit);
    $fileIds = $this->findFileIds($this->solrResponse, $detailLimit);
    $matches = $this->formatFileMatches($fileIds);
    $this->insertMatches($toTable, $matches);

    return count($this->solrResponse['docs']);
  }

  public function doSearch($queryText, $limit) {
    // TODO use $queryText, $limit
    $json = '{
  "response": {
    "numFound": 14,
    "start": 0,
    "docs": [
      {
        "id": "pqlj2a/civiFile/2",
        "site": "http://localhost:8009/",
        "hash": "pqlj2a",
        "entity_id": 2,
        "entity_type": "civiFile",
        "bundle": "civiFile",
        "bundle_name": "civiFile",
        "ss_language": "und",
        "label": "CiviCRM_Scalability_DataSet_QA.doc",
        "spell": [
          "CiviCRM_Scalability_DataSet_QA.doc",
          "CiviCRM Scalability Initiative: Reproducing Large Data Sets Background The CiviCRM community provides contact and payment processing software for a wide-range of organizations – organizations whose datasets range from a few thousand records to [WM\'s actual size]. For small and mid-sized organizations running CiviCRM on modern hardware, scalability-testing is a low-priority issue; for large organizations, scalability is critical. Unfortunately, the CiviCRM development community currently tests scalability in an ad-hoc fashion – system implementors may run ad-hoc performance tests in their staging environments when evaluating a new CiviCRM upgrade, but code contributors and core developers do not have suitable resources to test performance during development. Goals Enable the community to assess CiviCRM performance with large data-sets on an on-going basis. Assess performance of listed use-cases (see below). Non-Goals This project only addresses testing of read-access to large datasets. High-performance transaction processing (OLTP) is left as a separate issue. Related Projects Continuous integration – [Comment on funding/progress and how it ties in] Developer VM/puppet scripts – [Comment on funding/progress and how it ties in] Long upgrade support – [Comment on funding/progress and how it ties in] Deliverables Dataset An analysis of selected contact, contribution, group, and mailing data-patterns in the Wikimedia dataset A redistributable “clean-room” data-generation script which parallels the Wikimedia dataset (without explicitly copying it) Three redistributable, “rendered” MySQL data-sets which can be (re)loaded into developer VM\'s. The three data-sets will be designated “0.25x”, “1x”, and “4x” (based on the size of the data-set relative to the example Wikimedia) Systems (Cross-Support for CI/VM Projects) [some kind of plan for getting hardware to run tests periodically – eg a system-image for EC2 or a new box at OSUOSL] Performance Tests A repository of scripts for testing listed use-cases A report on performance of listed use-cases [Non-commital] Patches and/or analyses of slow use-cases Performance Test-Cases Advanced search by contact email address Advanced search by contribution amount Advanced search by contribution date Advanced search by contribution amount and date Database upgrades and schema changes Budget Dataset: X hr Systems (Cross-Support for CI/VM): $X hardware + X hour Performance Tests: X hour Total: $X h/w + X hr"
        ],
        "url": "/civicrm/file?reset=1&id=2&eid=22",
        "ss_filemime": "application/msword",
        "content": "CiviCRM Scalability Initiative: Reproducing Large Data Sets Background The CiviCRM community provides contact and payment processing software for a wide-range of organizations – organizations whose datasets range from a few thousand records to [WM\'s actual size]. For small and mid-sized organizations running CiviCRM on modern hardware, scalability-testing is a low-priority issue; for large organizations, scalability is critical. Unfortunately, the CiviCRM development community currently tests scalability in an ad-hoc fashion – system implementors may run ad-hoc performance tests in their staging environments when evaluating a new CiviCRM upgrade, but code contributors and core developers do not have suitable resources to test performance during development. Goals Enable the community to assess CiviCRM performance with large data-sets on an on-going basis. Assess performance of listed use-cases (see below). Non-Goals This project only addresses testing of read-access to large datasets. High-performance transaction processing (OLTP) is left as a separate issue. Related Projects Continuous integration – [Comment on funding/progress and how it ties in] Developer VM/puppet scripts – [Comment on funding/progress and how it ties in] Long upgrade support – [Comment on funding/progress and how it ties in] Deliverables Dataset An analysis of selected contact, contribution, group, and mailing data-patterns in the Wikimedia dataset A redistributable “clean-room” data-generation script which parallels the Wikimedia dataset (without explicitly copying it) Three redistributable, “rendered” MySQL data-sets which can be (re)loaded into developer VM\'s. The three data-sets will be designated “0.25x”, “1x”, and “4x” (based on the size of the data-set relative to the example Wikimedia) Systems (Cross-Support for CI/VM Projects) [some kind of plan for getting hardware to run tests periodically – eg a system-image for EC2 or a new box at OSUOSL] Performance Tests A repository of scripts for testing listed use-cases A report on performance of listed use-cases [Non-commital] Patches and/or analyses of slow use-cases Performance Test-Cases Advanced search by contact email address Advanced search by contribution amount Advanced search by contribution date Advanced search by contribution amount and date Database upgrades and schema changes Budget Dataset: X hr Systems (Cross-Support for CI/VM): $X hardware + X hour Performance Tests: X hour Total: $X h/w + X hr",
        "teaser": "CiviCRM Scalability Initiative: Reproducing Large Data Sets Background The CiviCRM community provides contact and payment processing software for a wide-range of organizations – organizations whose datasets range from a few thousand records to [WM\'s actual size]. For small and mid-sized",
        "timestamp": "2014-05-29T22:28:29.852Z"
      }
    ]
  }
}';
    $matches = json_decode($json, TRUE);
    return $matches['response'];
  }

  /**
   * @param array $solrResponse
   * @param array|NULL $limit
   * @return array<int>
   * @throws CRM_Core_Exception
   */
  public function findFileIds($solrResponse, $limit) {
    $fileIds = array();
    if (!empty($solrResponse['docs'])) {
      if ($limit) {
        list($rowCount, $offset) = $limit;
        $docs = array_slice($solrResponse['docs'], $offset ? $offset : 0, $rowCount);
      }
      else {
        $docs = $solrResponse['docs'];
      }

      foreach ($docs as $doc) {
        if ($doc['entity_type'] == 'civiFile') {
          if (isset($doc['entity_id'])) {
            $fileIds[] = $doc['entity_id'];
          }
          else {
          }
        }
      }
    }
    return $fileIds;
  }

  /**
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

  public function insertMatches($toTable, $matches) {
    if (empty($matches)) {
      return;
    }
    $insertContactSql = CRM_Utils_SQL_Insert::into($toTable)->rows($matches)->toSQL();
    CRM_Core_DAO::executeQuery($insertContactSql);
  }
}
