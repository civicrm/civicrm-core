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
{* Template for "EventAggregate" custom search component. *}
{assign var="showBlock" value="'searchForm'"}
{assign var="hideBlock" value="'searchForm_show','searchForm_hide'"}
<div class="crm-block crm-form-block crm-search-form-block">
<div id="searchForm_show" class="form-item">
    <a href="#" onclick="cj('#searchForm_show').hide(); cj('#searchForm').show(); return false;"><img src="{$config->resourceBase}i/TreePlus.gif" class="action-icon" alt="{ts}open section{/ts}" /></a>
    <label>{ts}Edit Search Criteria{/ts}</label>
</div>

<div id="searchForm" class="crm-block crm-form-block crm-contact-custom-search-eventDetails-form-block">
    <fieldset>
        <legend><span id="searchForm_hide"><a href="#" onclick="cj('#searchForm').hide(); cj('#searchForm_show').show(); return false;"><img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="{ts}close section{/ts}" /></a></span>{ts}Search Criteria{/ts}</legend>
      <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
        <table class="form-layout-compressed">
            {* Loop through all defined search criteria fields (defined in the buildForm() function). *}
            {foreach from=$elements item=element}
                <tr class="crm-contact-custom-search-eventDetails-form-block-{$element}">
                    <td class="label">{$form.$element.label}</td>
                    {if $element eq 'start_date'}
                        <td>{include file="CRM/common/jcalendar.tpl" elementName=start_date}</td>
                    {elseif $element eq 'end_date'}
                        <td>{include file="CRM/common/jcalendar.tpl" elementName=end_date}</td>
                    {else}
                        <td>{$form.$element.html}</td>
                    {/if}
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
    </fieldset>
</div>

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

<script type="text/javascript">
    var showBlock = new Array({$showBlock});
    var hideBlock = new Array({$hideBlock});

    {* hide and display the appropriate blocks *}
    on_load_init_blocks( showBlock, hideBlock );
</script>
</div>
