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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
use Civi\Api4\Membership;

/**
 * class to parse membership csv files
 */
class CRM_Member_Import_Parser_Membership extends CRM_Import_Parser {

  /**
   * Array of metadata for all available fields.
   *
   * @var array
   */
  protected $fieldMetadata = [];

  protected string $baseEntity = 'Membership';

  /**
   * Has this parser been fixed to expect `getMappedRow` to break it up
   * by entity yet? This is a transitional property to allow the classes
   * to be fixed up individually.
   *
   * @var bool
   */
  protected $isUpdatedForEntityRowParsing = TRUE;

  /**
   * Array of successfully imported membership id's
   *
   * @var array
   */
  protected $_newMemberships;

  /**
   * Separator being used
   * @var string
   */
  protected $_separator;

  /**
   * Get information about the provided job.
   *  - name
   *  - id (generally the same as name)
   *  - label
   *
   *  e.g. ['activity_import' => ['id' => 'activity_import', 'label' => ts('Activity Import'), 'name' => 'activity_import']]
   *
   * @return array
   */
  public static function getUserJobInfo(): array {
    return [
      'membership_import' => [
        'id' => 'membership_import',
        'name' => 'membership_import',
        'label' => ts('Membership Import'),
        'entity' => 'Membership',
        'url' => 'civicrm/import/membership',

      ],
    ];
  }

  /**
   * Get a list of entities this import supports.
   *
   * @return array
   */
  public function getImportEntities() : array {
    return [
      'Membership' => ['text' => ts('Membership Fields'), 'is_contact' => FALSE],
      'Contact' => ['text' => ts('Contact Fields'), 'is_contact' => TRUE],
    ];
  }

  /**
   * Validate the values.
   *
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @throws \CRM_Core_Exception
   */
  public function validateValues(array $values): void {
    $params = $this->getMappedRow($values);
    $errors = [];
    foreach ($params as $key => $value) {
      $errors = array_merge($this->getInvalidValues($value, $key), $errors);
    }
    $this->validateRequiredFields($this->getRequiredFields(), $params['Membership']);

    //To check whether start date or join date is provided
    if (empty($params['Membership']['id']) && empty($params['Membership']['start_date']) && empty($params['Membership']['join_date'])) {
      $errors[] = 'Membership Start Date is required to create a memberships.';
    }
    //fix for CRM-2219 Update Membership
    if ($this->isUpdateExisting() && !empty($params['Membership']['is_override']) && empty($params['Membership']['status_id'])) {
      $errors[] = 'Required parameter missing: Status';
    }
    if ($errors) {
      throw new CRM_Core_Exception('Invalid value for field(s) : ' . implode(',', $errors));
    }
  }

  /**
   * Get the required fields.
   *
   * @return array
   */
  public function getRequiredFields(): array {
    return [[$this->getRequiredFieldsForMatch(), $this->getRequiredFieldsForCreate()]];
  }

  /**
   * Get required fields to create a membership.
   *
   * @return array
   */
  public function getRequiredFieldsForCreate(): array {
    return ['membership_type_id'];
  }

  /**
   * Get required fields to match a membership.
   *
   * @return array
   */
  public function getRequiredFieldsForMatch(): array {
    return [['id']];
  }

  /**
   * Handle the values in import mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return int
   *   the result of this processing - which is ignored
   */
  public function import(array $values) {
    $rowNumber = (int) ($values[array_key_last($values)]);
    try {
      $params = $this->getMappedRow($values);
      $this->removeEmptyValues($params);
      $membershipParams = $params['Membership'];
      $contactParams = $params['Contact'] ?? [];

      $existingMembership = [];
      if (!empty($membershipParams['id'])) {
        $existingMembership = $this->checkEntityExists('Membership', $membershipParams['id']);
        $membershipParams['contact_id'] = !empty($membershipParams['contact_id']) ? (int) $membershipParams['contact_id'] : $existingMembership['contact_id'];
      }
      $membershipParams['contact_id'] = $this->getContactID($contactParams, $membershipParams['contact_id'] ?? $contactParams['id'] ?? NULL, 'Contact', $this->getDedupeRulesForEntity('Contact'));
      $formatted = $formatValues = $membershipParams;
      // don't add to recent items, CRM-4399
      $formatted['skipRecentView'] = TRUE;

      $startDate = $membershipParams['start_date'] ?? $existingMembership['start_date'] ?? NULL;
      // Assign join date equal to start date if join date is not provided.
      $joinDate = $membershipParams['join_date'] ?? $existingMembership['join_date'] ?? $startDate;
      $endDate = $membershipParams['end_date'] ?? $existingMembership['end_date'] ?? NULL;
      $membershipTypeID = $membershipParams['membership_type_id'] ?? $existingMembership['membership_type_id'];
      $isOverride = $membershipParams['is_override'] ?? $existingMembership['is_override'] ?? FALSE;

      //to calculate dates
      $calcDates = CRM_Member_BAO_MembershipType::getDatesForMembershipType($membershipTypeID,
        $joinDate,
        $startDate,
        $endDate
      );
      $this->formattedDates($calcDates, $formatted);
      //end of date calculation part

      //fix for CRM-3570, exclude the statuses those having is_admin = 1
      //now user can import is_admin if is override is true.
      $excludeIsAdmin = FALSE;
      if (!$isOverride) {
        $formatted['exclude_is_admin'] = $excludeIsAdmin = TRUE;
      }
      $calcStatus = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($startDate,
        $endDate,
        $joinDate,
        'now',
        $excludeIsAdmin,
        $membershipTypeID,
        $formatted
      );
      if (empty($formatted['status_id'])) {
        $formatted['status_id'] = $calcStatus['id'] ?? NULL;
      }
      elseif (!$isOverride) {
        if (empty($calcStatus)) {
          throw new CRM_Core_Exception('Status in import row (' . ($formatValues['status_id'] ?? '') . ') does not match calculated status based on your configured Membership Status Rules. Record was not imported.', CRM_Import_Parser::ERROR);
        }
        if ($formatted['status_id'] != $calcStatus['id']) {
          //Status Hold" is either NOT mapped or is FALSE
          throw new CRM_Core_Exception('Status in import row (' . ($formatValues['status_id'] ?? '') . ') does not match calculated status based on your configured Membership Status Rules (' . $calcStatus['name'] . '). Record was not imported.', CRM_Import_Parser::ERROR);
        }
      }

      $newMembership = Membership::save()
        ->addRecord($formatted)
        ->execute()->first();
      $this->setImportStatus($rowNumber, 'IMPORTED', '', $newMembership['id']);
      return CRM_Import_Parser::VALID;

    }
    catch (CRM_Core_Exception $e) {
      $this->setImportStatus($rowNumber, 'ERROR', $e->getMessage());
      return CRM_Import_Parser::ERROR;
    }
  }

  /**
   *  to calculate join, start and end dates
   *
   * @param array $calcDates
   *   Array of dates returned by getDatesForMembershipType().
   *
   * @param $formatted
   *
   */
  public function formattedDates($calcDates, &$formatted) {
    $dates = [
      'join_date',
      'start_date',
      'end_date',
    ];

    foreach ($dates as $d) {
      if (isset($formatted[$d]) &&
        !CRM_Utils_System::isNull($formatted[$d])
      ) {
        $formatted[$d] = CRM_Utils_Date::isoToMysql($formatted[$d]);
      }
      elseif (isset($calcDates[$d])) {
        $formatted[$d] = CRM_Utils_Date::isoToMysql($calcDates[$d]);
      }
    }
  }

  /**
   * Set field metadata.
   */
  protected function setFieldMetadata(): void {
    if (empty($this->importableFieldsMetadata)) {
      $metadata = $this->getImportableFields($this->getContactType());
      // We are consolidating on `importableFieldsMetadata` - but both still used.
      $this->importableFieldsMetadata = $this->fieldMetadata = $metadata;
    }
  }

  /**
   * @param string $contactType
   *
   * @return array|mixed
   * @throws \CRM_Core_Exception
   */
  protected function getImportableFields(string $contactType = 'Individual'): array {
    $fields = Civi::cache('fields')->get('membership_importable_fields' . $contactType);
    if (!$fields) {
      $fields = ['' => ['title' => '- ' . ts('do not import') . ' -']]
        + (array) Membership::getFields()
          ->addWhere('readonly', '=', FALSE)
          ->addWhere('usage', 'CONTAINS', 'import')
          ->setAction('save')
          ->addOrderBy('title')
          ->execute()->indexBy('name');

      $contactFields = $this->getContactFields($this->getContactType());
      $fields['contact_id'] = $contactFields['id'];
      $fields['contact_id']['match_rule'] = '*';
      $fields['contact_id']['entity'] = 'Contact';
      $fields['contact_id']['html']['label'] = $fields['contact_id']['title'];
      $fields['contact_id']['title'] .= ' ' . ts('(match to contact)');
      unset($contactFields['id']);
      $fields += $contactFields;
      Civi::cache('fields')->set('membership_importable_fields' . $contactType, $fields);
    }
    return $fields;
  }

}
