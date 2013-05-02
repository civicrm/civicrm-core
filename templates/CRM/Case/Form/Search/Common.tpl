{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
  <td><label>{ts}Case Start Date{/ts}</label></td>{include file="CRM/Core/DateRange.tpl" fieldName="case_from" from='_start_date_low' to='_start_date_high'}
</tr>
<tr>
  <td><label>{ts}Case End Date{/ts}</label></td>{include file="CRM/Core/DateRange.tpl" fieldName="case_to" from='_end_date_low' to='_end_date_high'}
</tr>

<tr id='case_search_form'>
  <td colspan="2" class="crm-case-common-form-block-case_type" width="25%">
    <label>{ts}Case Type{/ts}</label><br />
    <div class="listing-box">
      {foreach from=$form.case_type_id item="case_type_id_val"}
        <div class="{cycle values='odd-row,even-row'}">
          {$case_type_id_val.html}
        </div>
      {/foreach}
    </div><br />
  </td>

  <td class="crm-case-common-form-block-case_status_id" width="25%">
    <label>{ts}Status{/ts}</label><br />
    <div class="listing-box">
      {foreach from=$form.case_status_id item="case_status_id_val"}
        <div class="{cycle values='odd-row,even-row'}">
          {$case_status_id_val.html}
        </div>
      {/foreach}
    </div>
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
  {if $form.case_tags}
    <td class="crm-case-common-form-block-case_tags">
      <label>{ts}Case Tag(s){/ts}</label>
      <div id="Tag" class="listing-box">
        {foreach from=$form.case_tags item="tag_val"}
          <div class="{cycle values='odd-row,even-row'}">
            {$tag_val.html}
          </div>
        {/foreach}
    </td>
  {/if}
</tr>

<tr><td colspan="3">{include file="CRM/common/Tag.tpl" tagsetType='case'}</td></tr>

  {if $caseGroupTree}
  <tr>
    <td colspan="4">
    {include file="CRM/Custom/Form/Search.tpl" groupTree=$caseGroupTree showHideLinks=false}
    </td>
  </tr>
  {/if}

{/if}

