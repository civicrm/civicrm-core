{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Template for "Change Case Start Date" activities *}
   <div class="crm-block crm-form-block crm-case-changecasestartdate-form-block">
    <tr class="crm-case-changecasestartdate-form-block-current_start_date">
  <td class="label">{ts}Current Start Date{/ts}</td>
        <td>{$current_start_date|crmDate}</td>
    </tr>
    <tr class="crm-case-changecasestartdate-form-block-start_date">
        <td class="label">{$form.start_date.label}</td>
        <td>{$form.start_date.html}</td>
    </tr>
   </div>
