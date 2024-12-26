{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Template for "EventAggregate" custom search component. *}
{assign var="showBlock" value="'searchForm'"}
{assign var="hideBlock" value="'searchForm_show','searchForm_hide'"}
<div class="crm-block crm-form-block crm-search-form-block">
  <details class="crm-accordion-light crm-eventDetails_search-accordion" {if $rows}{else}open{/if}>
    <summary>
      {ts}Edit Search Criteria{/ts}
    </summary>
    <div class="crm-accordion-body">
      <div id="searchForm" class="crm-block crm-form-block crm-contact-custom-search-eventDetails-form-block">
          <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
            <table class="form-layout-compressed">
                {* Loop through all defined search criteria fields (defined in the buildForm() function). *}
                {foreach from=$elements item=element}
                    <tr class="crm-contact-custom-search-eventDetails-form-block-{$element}">
                        <td class="label">{$form.$element.label}</td>
                        <td>{$form.$element.html}</td>
                    </tr>
                {/foreach}
                <tr class="crm-contact-custom-search-eventDetails-form-block-event_type">
                    <td class="label">{ts}Event Type{/ts}</td>
                    <td>
                        <div class="listing-box">
                            {foreach from=$form.event_type_id item="event_val"}
                                <div class="{cycle values="odd-row,even-row"}">
                                    {$event_val.html}
                                </div>
                            {/foreach}
                        </div>
                        <div class="spacer"></div>
                    </td>
                </tr>
            </table>
          <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
      </div>
    </div>
  </details>

{if $rowsEmpty}
    {include file="CRM/Contact/Form/Search/Custom/EmptyResults.tpl"}
{/if}

{if $rows}
    {* Search request has returned 1 or more matching rows. Display results and collapse the search criteria fieldset. *}
    {assign var="showBlock" value="'searchForm_show'"}
    {assign var="hideBlock" value="'searchForm'"}

    <fieldset>
        {* The action task select and submit has been removed from this custom search because we're not dealing with contact records (so it won't work). *}

        {* This section displays the rows along and includes the paging controls *}
        <p>

        {include file="CRM/common/pager.tpl" location="top"}

        {include file="CRM/common/pagerAToZ.tpl"}

        {strip}
        <table summary="{ts}Search results listings.{/ts}">
            <thead class="sticky">
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
            </thead>

            {counter start=0 skip=1 print=false}
            {foreach from=$rows item=row}
                <tr id='rowid{$row.contact_id}' class="{cycle values="odd-row,even-row"}">

                    {foreach from=$columnHeaders item=header}
                        {assign var=fName value=$header.sort}
                        {if $fName eq 'sort_name'}
                            <td>{$row.sort_name}</a></td>
                        {elseif $fName eq 'payment_amount' || $fName eq 'fee' || $fName eq 'net_payment'}
                            <td>{$row.$fName|crmMoney}</td>
                        {elseif $fName eq 'participant_count'}
                            <td>{$row.$fName}</td>
                        {else}
                            <td>{$row.$fName}</td>
                        {/if}
                    {/foreach}
                </tr>
            {/foreach}

            {if $summary}
                <tr class="columnheader">
                    <td>&nbsp;</td>
                    <td>Totals &nbsp; &nbsp;</td>
                    <td>{$summary.participant_count}</td>
                    <td>{$summary.payment_amount|crmMoney}</td>
                    <td>{$summary.fee|crmMoney}</td>
                    <td colspan=2>{$summary.net_payment|crmMoney}</td>
               </tr>
            {/if}
        </table>
        {/strip}

        {include file="CRM/common/pager.tpl" location="bottom"}

        </p>
    </fieldset>
    {* END Actions/Results section *}
{/if}
</div>
