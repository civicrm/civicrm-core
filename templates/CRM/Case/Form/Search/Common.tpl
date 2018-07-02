{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
*}
{if $notConfigured} {* Case types not present. Component is not configured for use. *}
  {include file="CRM/Case/Page/ConfigureError.tpl"}
{else}
  <tr>
    <td class="crm-case-common-form-block-case_id">
      {$form.case_id.label}<br />
      {$form.case_id.html}
    </td>
    <td class="crm-case-common-form-block-case_subject" colspan="2">
      {$form.case_subject.label}<br />
      {$form.case_subject.html}
    </td>
  </tr>

  <tr>
    <td>
      <label>{ts}Case Start Date{/ts}</label>
    </td>
    {include file="CRM/Core/DateRange.tpl" fieldName="case_from" from='_start_date_low' to='_start_date_high'}
  </tr>
  <tr>
    <td>
      <label>{ts}Case End Date{/ts}</label>
    </td>
    {include file="CRM/Core/DateRange.tpl" fieldName="case_to" from='_end_date_low' to='_end_date_high'}
  </tr>

  <tr id='case_search_form'>
    <td class="crm-case-common-form-block-case_type">
      {$form.case_type_id.label}<br />
      {$form.case_type_id.html}
    </td>

    <td class="crm-case-common-form-block-case_status_id">
      {$form.case_status_id.label}<br />
      {$form.case_status_id.html}
      {if $accessAllCases}
        <br />
        {$form.case_owner.html}
      {/if}
      {if $form.case_deleted}
        <br />
        {$form.case_deleted.html}
        {$form.case_deleted.label}
      {/if}
    </td>
    <td class="crm-case-common-form-block-case_tags">
      {if $form.case_tags.html}
        {$form.case_tags.label}<br />
        {$form.case_tags.html}
      {/if}
    </td>
  </tr>

  <tr>
    <td colspan="3">{include file="CRM/common/Tagset.tpl" tagsetType='case'}</td>
  </tr>

  {if $caseGroupTree}
    <tr>
      <td colspan="3">
        {include file="CRM/Custom/Form/Search.tpl" groupTree=$caseGroupTree showHideLinks=false}
      </td>
    </tr>
  {/if}

{/if}
