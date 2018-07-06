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
{* Default template custom searches. This template is used automatically if templateFile() function not defined in
   custom search .php file. If you want a different layout, clone and customize this file and point to new file using
   templateFile() function.*}
<div class="crm-block crm-form-block crm-contact-custom-search-form-block">
<div class="crm-accordion-wrapper crm-custom_search_form-accordion {if $rows}collapsed{/if}">
    <div class="crm-accordion-header crm-master-accordion-header">
      {ts}Edit Search Criteria{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
        <table class="form-layout-compressed">
           <tr class="crm-contact-custom-search-contribSYBNT-form-block-min_amount_1">
               <td><label>{ts}Amount One: Min/Max{/ts}</label></td>
               <td>{$form.min_amount_1.html}</td>
               <td>{$form.max_amount_1.html}</td>
               <td>&nbsp;</td>
           </tr>
           <tr class="crm-contact-custom-search-contribSYBNT-form-block-inclusion_date_one">
               <td><label>Inclusion Date One: Start/End</label></td>
               <td>{$form.start_date_1.html}</td>
               <td>{$form.end_date_1.html}</td>
               <td>{$form.is_first_amount.html}&nbsp;{ts}First time donor only?{/ts}</td>
           </tr>
           <tr class="crm-contact-custom-search-contribSYBNT-form-block-min_amount_2">
               <td><label>{ts}Amount Two: Min/Max{/ts}</label></td>
               <td>{$form.min_amount_2.html}</td>
               <td>{$form.max_amount_2.html}</td>
               <td>&nbsp;</td>
           </tr>
           <tr class="crm-contact-custom-search-contribSYBNT-form-block-inclusion_date_two">
               <td><label>Inclusion Date Two: Start/End</label></td>
               <td>{$form.start_date_2.html}</td>
               <td>{$form.end_date_2.html}</td>
               <td>&nbsp;</td>
           </tr>
           <tr class="crm-contact-custom-search-contribSYBNT-form-block-exclude_min_amount">
               <td><label>Exclusion Amount: Min/Max</label></td>
               <td>{$form.exclude_min_amount.html}</td>
               <td>{$form.exclude_max_amount.html}</td>
               <td>&nbsp;</td>
           </tr>
           <tr class="crm-contact-custom-search-contribSYBNT-form-block-exclusion_date">
               <td><label>Exclusion Date: Start/End</label></td>
               <td>{$form.exclude_start_date.html}</td>
               <td>{$form.exclude_end_date.html}</td>
               <td>&nbsp;</td>
           </tr>
        </table>
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    </div><!-- /.crm-accordion-body -->
    </div><!-- /.crm-accordion-wrapper -->
    </div><!-- /.crm-form-block -->

{if $rowsEmpty || $rows}
<div class="crm-content-block">
{if $rowsEmpty}
    {include file="CRM/Contact/Form/Search/Custom/EmptyResults.tpl"}
{/if}

{if $summary}
    {$summary.summary}: {$summary.total}
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

        {* Include alpha pager if defined. *}
        {if $atoZ}
            {include file="CRM/common/pagerAToZ.tpl"}
        {/if}

        {strip}
        <table class="selector row-highlight" summary="{ts}Search results listings.{/ts}">
            <thead class="sticky">
                <th scope="col" title="Select All Rows">{$form.toggleSelect.html}</th>
                {foreach from=$columnHeaders item=header}
                    <th scope="col">
                        {if $header.sort}
                            {assign var='key' value=$header.sort}
                            {$sort->_response.$key.link}
                        {else}
                            {$header.name}
                        {/if}
                    </th>
                {/foreach}
                <th>&nbsp;</th>
            </thead>

            {counter start=0 skip=1 print=false}
            {foreach from=$rows item=row}
                <tr id='rowid{$row.contact_id}' class="{cycle values="odd-row,even-row"}">
                    {assign var=cbName value=$row.checkbox}
                    <td>{$form.$cbName.html}</td>
                    {foreach from=$columnHeaders item=header}
                        {assign var=fName value=$header.sort}
                        {if $fName eq 'sort_name'}
                            <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`&key=`$qfKey`"}">{$row.sort_name}</a></td>
                        {else}
                            <td>{$row.$fName}</td>
                        {/if}
                    {/foreach}
                    <td>{$row.action}</td>
                </tr>
            {/foreach}
        </table>
        {/strip}

        {include file="CRM/common/pager.tpl" location="bottom"}

        </p>
    {* END Actions/Results section *}
    </div>
    </div>
{/if}

</div>
{/if}
