{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Default template custom searches. This template is used automatically if templateFile() function not defined in
   custom search .php file. If you want a different layout, clone and customize this file and point to new file using
   templateFile() function.*}
<div class="crm-block crm-form-block crm-contact-custom-search-form-block">
<details class="crm-accordion-light crm-custom_search_form-accordion" {if $rows}{else}open{/if}>
    <summary>
      {ts}Edit Search Criteria{/ts}
    </summary>
    <div class="crm-accordion-body">
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
        <table class="form-layout-compressed">
           <tr><td class="label">{$form.distance.label}</td><td>{$form.distance.html|crmAddClass:four} {$form.prox_distance_unit.html}</td></tr>
           <tr><td class="label">{ts}FROM...{/ts}</td><td></td></tr>
           <tr><td class="label">{$form.street_address.label}</td><td>{$form.street_address.html}</td></tr>
           <tr><td class="label">{$form.city.label}</td><td>{$form.city.html}</td></tr>
           <tr><td class="label">{$form.postal_code.label}</td><td>{$form.postal_code.html}</td></tr>
           <tr><td class="label">{$form.country_id.label}</td><td>{$form.country_id.html}</td></tr>
           <tr><td class="label" style="white-space: nowrap;">{$form.state_province_id.label}</td><td>{$form.state_province_id.html}</td></tr>
           <tr><td class="label">{ts}OR enter lattitude and longitude if you already know it{/ts}.</td><td></td></tr>
           <tr><td class="label" style="white-space: nowrap;">{$form.geo_code_1.label}</td><td>{$form.geo_code_1.html}</td></tr>
           <tr><td class="label" style="white-space: nowrap;">{$form.geo_code_2.label}</td><td>{$form.geo_code_2.html}</td></tr>
           <tr><td class="label">{ts}AND ...{/ts}</td><td></td></tr>
           <tr><td class="label">{ts}Restrict results by ...{/ts}</td><td></td></tr>
           <tr><td class="label">{$form.group.label}</td><td>{$form.group.html}</td></tr>
           <tr><td class="label">{$form.tag.label}</td><td>{$form.tag.html}</td></tr>
        </table>
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    </div>
</details>
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
        <table class="selector row-highlight" summary="{ts escape='htmlattribute'}Search results listings.{/ts}">
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
                            <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`&key=`$qfKey`&context=custom"}">{$row.sort_name}</a></td>
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
