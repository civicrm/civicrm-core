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
namespace Civi\Api4;

/**
 * ImportTemplateField entity - stores field mappings for import templates.
 *
 * @see \Civi\Api4\UFGroup
 * @orderBy column_number
 * @groupWeightsBy user_job_id
 * @since 5.83
 * @package Civi\Api4
 */
class ImportTemplateField extends Generic\DAOEntity {
  use Generic\Traits\SortableEntity;

}
