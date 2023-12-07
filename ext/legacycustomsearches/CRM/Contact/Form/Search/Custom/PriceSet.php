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

use Civi\Api4\PriceFieldValue;
use Civi\Api4\PriceSetEntity;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contact_Form_Search_Custom_PriceSet extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $eventID;
  protected $_aclFrom;
  protected $_aclWhere;
  protected $_tableName;
  public $_permissionedComponent;

  /**
   * Does the search class handle the prev next cache saving.
   *
   * This can be set to yes as long as a UI test works, and the deprecation
   * notice will disappear.
   *
   * @var bool
   */
  public $searchClassHandlesPrevNextCache = TRUE;

  /**
   * Class constructor.
   *
   * @param array $formValues
   */
  public function __construct(&$formValues) {
    parent::__construct($formValues);

    $this->eventID = (int) CRM_Utils_Array::value('event_id',
      $this->_formValues
    );

    $this->setColumns();

    $this->buildTempTable();
    $this->fillTable();

    // define component access permission needed
    $this->_permissionedComponent = 'CiviEvent';
  }

  public function buildTempTable(): void {
    $sql = 'id int unsigned NOT NULL AUTO_INCREMENT,
  contact_id int unsigned NOT NULL,
  participant_id int unsigned NOT NULL,
';

    foreach ($this->_columns as $fieldName) {
      if (in_array($fieldName, ['contact_id', 'participant_id', 'display_name'])) {
        continue;
      }
      $sql .= "{$fieldName} int default 0,\n";
    }

    $sql .= '
      PRIMARY KEY ( id ),
      UNIQUE INDEX unique_participant_id ( participant_id )';

    $this->_tableName = CRM_Utils_SQL_TempTable::build()->setCategory('priceset')->setMemory()->createWithColumns($sql)->getName();
  }

  public function fillTable(): void {
    $sql = "
REPLACE INTO {$this->_tableName}
( contact_id, participant_id )
SELECT c.id, p.id
FROM   civicrm_contact c,
       civicrm_participant p
WHERE  p.contact_id = c.id
  AND  p.is_test    = 0
  AND  p.event_id = %1
  AND  p.status_id NOT IN (4,11,12)
  AND  ( c.is_deleted = 0 OR c.is_deleted IS NULL )
";
    CRM_Core_DAO::executeQuery($sql, [1 => [$this->eventID, 'Positive']]);

    $sql = "
      SELECT c.id as contact_id,
        p.id as participant_id,
        l.price_field_value_id AS price_field_value_id,
        l.qty
      FROM civicrm_contact c
        INNER JOIN civicrm_participant p
          ON p.contact_id = c.id AND c.is_deleted = 0
        INNER JOIN civicrm_line_item l
          ON p.id = l.entity_id AND l.entity_table ='civicrm_participant'
        INNER JOIN civicrm_price_field_value cpfv
          ON cpfv.id = l.price_field_value_id AND cpfv.is_active = 1
        INNER JOIN civicrm_price_field cpf
          ON cpf.id = l.price_field_id AND cpf.is_active = 1
        INNER JOIN civicrm_price_set cps
          ON cps.id = cpf.price_set_id AND cps.is_active = 1
      WHERE  p.event_id = %1
      ORDER BY c.id, l.price_field_value_id;
    ";

    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$this->eventID, 'Positive']]);

    // first store all the information by option value id
    $rows = [];
    while ($dao->fetch()) {
      $participantID = $dao->participant_id;
      if (!isset($rows[$participantID])) {
        $rows[$participantID] = [];
      }

      $rows[$participantID][] = "price_field_{$dao->price_field_value_id} = {$dao->qty}";
    }

    foreach (array_keys($rows) as $participantID) {
      $values = implode(',', $rows[$participantID]);
      if ($values) {
        $sql = "
UPDATE {$this->_tableName}
SET $values
WHERE participant_id = $participantID;
";
        CRM_Core_DAO::executeQuery($sql);
      }
    }
  }

  /**
   *
   * @return Object
   */
  public function priceSetDAO() {

    // get all the events that have a price set associated with it
    $sql = "
SELECT e.id    as id,
       e.title as title,
       p.price_set_id as price_set_id
FROM   civicrm_event      e,
       civicrm_price_set_entity  p

WHERE  p.entity_table = 'civicrm_event'
AND    p.entity_id    = e.id
";
    return CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * @param CRM_Core_Form $form
   *
   * @throws Exception
   */
  public function buildForm(&$form) {
    $dao = $this->priceSetDAO();

    $event = [];
    while ($dao->fetch()) {
      $event[$dao->id] = $dao->title;
    }

    if (empty($event)) {
      CRM_Core_Error::statusBounce(ts('There are no events with Price Sets'));
    }

    $form->add('select',
      'event_id',
      ts('Event'),
      $event,
      TRUE
    );

    /**
     * You can define a custom title for the search form
     */
    $this->setTitle(ts('Price Set Export'));

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', ['event_id']);
  }

  /**
   * @throws \Civi\API\Exception\UnauthorizedException
   * @throws \CRM_Core_Exception
   */
  public function setColumns(): void {
    $this->_columns = [
      ts('Contact ID') => 'contact_id',
      ts('Participant ID') => 'participant_id',
      ts('Name') => 'display_name',
    ];

    // for the selected event, find the price set and all the columns associated with it.
    // create a column for each field and option group within it
    $priceSetEntity = PriceSetEntity::get()
      ->addWhere('entity_table', '=', 'civicrm_event')
      ->addWhere('entity_id', '=', $this->eventID)
      ->addSelect('price_set_id')
      ->execute()->first();
    $priceSetID = $priceSetEntity['price_set_id'];
    $priceFieldValues = PriceFieldValue::get()
      ->addWhere('price_field_id.price_set_id', '=', $priceSetID)
      ->addSelect('label', 'price_field_id.html_type', 'price_field_id.label')
      ->execute();

    foreach ($priceFieldValues as $value) {
      $columnHeader = $value['price_field_id.label'] ?? NULL;
      if ($value['price_field_id.html_type'] !== 'Text') {
        $columnHeader .= ' - ' . $value['label'];
      }
      $this->_columns[$columnHeader] = "price_field_{$value['id']}";
    }
  }

  /**
   * @return null
   */
  public function summary() {
    return NULL;
  }

  /**
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   *
   * @return string
   */
  public function all(
    $offset = 0, $rowcount = 0, $sort = NULL,
    $includeContactIDs = FALSE, $justIDs = FALSE
  ) {
    if ($justIDs) {
      $selectClause = "contact_a.id as contact_id";
    }
    else {
      $selectClause = "
contact_a.id             as contact_id  ,
contact_a.display_name   as display_name";

      foreach ($this->_columns as $dontCare => $fieldName) {
        if (in_array($fieldName, ['contact_id', 'display_name'])) {
          continue;
        }
        $selectClause .= ",\ntempTable.{$fieldName} as {$fieldName}";
      }
    }

    return $this->sql($selectClause,
      $offset, $rowcount, $sort,
      $includeContactIDs, NULL
    );
  }

  /**
   * @return string
   */
  public function from(): string {
    $this->buildACLClause('contact_a');
    $from = "
FROM       civicrm_contact contact_a
INNER JOIN {$this->_tableName} tempTable ON ( tempTable.contact_id = contact_a.id ) {$this->_aclFrom}
";
    return $from;
  }

  /**
   * @param bool $includeContactIDs
   *
   * @return string
   */
  public function where($includeContactIDs = FALSE) {
    $where = ' ( 1 ) ';
    if ($this->_aclWhere) {
      $where .= " AND {$this->_aclWhere} ";
    }
    return $where;
  }

  /**
   * @return string
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  /**
   * @param $row
   */
  public function alterRow(&$row) {
  }

  /**
   * @param $title
   */
  public function setTitle($title) {
    if (empty($title)) {
      $title = ts('Export Price Set Info for an Event');
    }
    parent::setTitle($title);
  }

  /**
   * @param string $tableAlias
   */
  public function buildACLClause($tableAlias = 'contact') {
    [$this->_aclFrom, $this->_aclWhere] = CRM_Contact_BAO_Contact_Permission::cacheClause($tableAlias);
  }

}
