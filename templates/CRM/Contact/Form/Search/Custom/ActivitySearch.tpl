{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{* Template for "Sample" custom search component. *}
<div class="crm-form-block crm-search-form-block">
<div class="crm-accordion-wrapper crm-activity_search-accordion {if $rows}collapsed{/if}">
 <div class="crm-accordion-header crm-master-accordion-header">
   {ts}Edit Search Criteria{/ts}
</div><!-- /.crm-accordion-header -->
<div class="crm-accordion-body">
<div id="searchForm" class="crm-block crm-form-block crm-contact-custom-search-activity-search-form-block">
      <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
        <table class="form-layout-compressed">
            {* Loop through all defined search criteria fields (defined in the buildForm() function). *}
            {foreach from=$elements item=element}
                <tr class="crm-contact-custom-search-activity-search-form-block-{$element}">
                    <td class="label">{$form.$element.label}</td>
                    <td>
                        {if $element eq 'start_date' OR $element eq 'end_date'}
                            {include file="CRM/common/jcalendar.tpl" elementName=$element}
                        {else}
                            {$form.$element.html}
                        {/if}
                    </td>
                </tr>
            {/foreach}
        </table>
      <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
</div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
</div><!-- /.crm-form-block -->

{if $rowsEmpty || $rows}

<div class="crm-content-block">
    {if $rowsEmpty}
  <div class="crm-results-block crm-results-block-empty">
    {include file="CRM/Contact/Form/Search/Custom/EmptyResults.tpl"}
    </div>
{/if}

{if $rows}
  <div class="crm-results-block">
    {* Search request has returned 1 or more matching rows. Display results and collapse the search criteria fieldset. *}

    {* This section handles form elements for action task select and submit *}
  <div class="crm-search-tasks">
    {include file="CRM/Contact/Form/Search/ResultTasks.tpl"}
  </div>
    {* This section displays the rows along and includes the paging controls *}
    <div class="crm-search-results">

    {include file="CRM/common/pager.tpl" location="top"}

    {include file="CRM/common/pagerAToZ.tpl"}

    {strip}
    <table summary="{ts}Search results listings.{/ts}">
        <thead class="sticky">
            <th scope="col" title="Select All Rows">{$form.toggleSelect.html}</th>
            {foreach from=$columnHeaders item=header}
                {if ($header.sort eq 'activity_id') or ($header.sort eq 'activity_type_id') or ($header.sort eq 'case_id') }
                {elseif ($header.sort eq 'sort_name') or ($header.sort eq 'activity_status') or ($header.sort eq 'activity_type') or ($header.sort eq 'activity_subject') or ($header.sort eq 'source_contact') or ($header.SORT eq 'activity_date') or ($header.name eq null) }
                    <th scope="col">
                        {if $header.sort}
                            {assign var='key' value=$header.sort}
                            {$sort->_response.$key.link}
                        {else}
                            {$header.name}
                        {/if}
                    </th>
                {/if}
                </th>
            {/foreach}
            <th>&nbsp;</th>
        </thead>

        {counter start=0 skip=1 print=false}
        {foreach from=$rows item=row}
            <tr id='rowid{counter}' class="{cycle values="odd-row,even-row"}">
                {assign var=cbName value=$row.checkbox}
                <td>{$form.$cbName.html}</td>
                {foreach from=$columnHeaders item=header}
                    {if ($header.sort eq 'sort_name') or ($header.sort eq 'activity_status') or ($header.sort eq 'activity_type') or ($header.sort eq 'activity_subject') or ($header.sort eq 'source_contact') or ($header.SORT eq 'activity_date') or ($header.name eq null) }
                        {assign var=fName value=$header.sort}
                        {if $fName eq 'sort_name'}
                            <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`&key=`$qfKey`"}">{$row.sort_name}</a></td>
                        {elseif $fName eq 'activity_subject'}
                            <td>
                                {if $row.case_id }
                                    <a href="{crmURL p='civicrm/case/activity/view' q="reset=1&aid=`$row.activity_id`&cid=`$row.contact_id`&caseID=`$row.case_id`"}" title="{ts}View activity details{/ts}">
                                {else}
                                    <a href="{crmURL p='civicrm/contact/view/activity' q="atype=`$row.activity_type_id`&action=view&reset=1&id=`$row.activity_id`&cid=`$row.contact_id`"}" title="{ts}View activity details{/ts}">
                                {/if}
                                {if isset($row.activity_subject) AND $row.activity_subject NEQ 'NULL'}{$row.activity_subject}{else}{ts}(no subject){/ts}{/if}</a>
                            </td>
                        {elseif ($fName eq 'activity_id') or ($fName eq 'activity_type_id') or ($fName eq 'case_id')}
                        {else}
                            <td>{$row.$fName}</td>
                        {/if}
                    {/if}
                {/foreach}
                <td>{$row.action}</td>
            </tr>
        {/foreach}
    </table>
    {/strip}

{include file="CRM/common/pager.tpl" location="bottom"}


    </div>
    {* END Actions/Results section *}
  </div>
{/if}
</div>
{/if}
