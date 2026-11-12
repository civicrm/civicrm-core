{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* template adding custom field instances. *}
<div class="crm-label">
  {$instance.field_title}
</div>
<div class="crm-content crm-custom_data">
  {* should be escaped / purified by CRM_Core_BAO_CustomField::displayValue *}
  {$instance.field_value}
</div>
