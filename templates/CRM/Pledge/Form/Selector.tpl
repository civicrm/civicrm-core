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
{if $context EQ 'Search'}
    {include file="CRM/common/pager.tpl" location="top"}
{/if}

{capture assign=iconURL}<img src="{$config->resourceBase}i/TreePlus.gif" alt="{ts}open section{/ts}"/>{/capture}
{ts 1=$iconURL}Click %1 to view pledge payments.{/ts}
{strip}
<table class="selector">
    <thead class="sticky">
        {if ! $single and $context eq 'Search' }
            <th scope="col" title="Select Rows">{$form.toggleSelect.html}</th>
        {/if}
        {if $single}
            <th></th>
        {/if}
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
        {cycle values="odd-row,even-row" assign=rowClass}
        <tr id='rowid{$row.pledge_id}' class='{$rowClass} {if $row.pledge_status_name eq 'Overdue' } status-overdue{/if}'>
            {if $context eq 'Search' }
                {assign var=cbName value=$row.checkbox}
                <td>{$form.$cbName.html}</td>
            {/if}
            <td>
                {if ! $single }
                    &nbsp;{$row.contact_type}<br/>
                {/if}
                <span id="{$row.pledge_id}_show">
                    <a href="#" onclick="cj('#paymentDetails{$row.pledge_id},#minus{$row.pledge_id}_hide,#{$row.pledge_id}_hide').show();
                        buildPaymentDetails('{$row.pledge_id}','{$row.contact_id}');
                        cj('#{$row.pledge_id}_show').hide();
                        return false;"><img src="{$config->resourceBase}i/TreePlus.gif" class="action-icon" alt="{ts}open section{/ts}"/></a>
                </span>
                <span id="minus{$row.pledge_id}_hide">
                    <a href="#" onclick="cj('#paymentDetails{$row.pledge_id},#{$row.pledge_id}_hide,#minus{$row.pledge_id}_hide').hide();
                            cj('#{$row.pledge_id}_show').show();
                            return false;"><img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="{ts}open section{/ts}"/></a>
                </span>
            </td>
            {if ! $single }
                <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.contact_id`"}">{$row.sort_name}</a></td>
            {/if}
            <td class="right">{$row.pledge_amount|crmMoney:$row.pledge_currency}</td>
            <td class="right">{$row.pledge_total_paid|crmMoney:$row.pledge_currency}</td>
            <td class="right">{$row.pledge_amount-$row.pledge_total_paid|crmMoney:$row.pledge_currency}</td>
            <td>{$row.pledge_financial_type}</td>
            <td>{$row.pledge_create_date|truncate:10:''|crmDate}</td>
            <td>{$row.pledge_next_pay_date|truncate:10:''|crmDate}</td>
            <td class="right">{$row.pledge_next_pay_amount|crmMoney:$row.pledge_currency}</td>
            <td>{$row.pledge_status}</td>
            <td>{$row.action|replace:'xx':$row.pledge_id}</td>
        </tr>
        <tr id="{$row.pledge_id}_hide" class='{$rowClass}'>
            <td style="border-right: none;"></td>
            <td colspan= {if $context EQ 'Search'} "10" {else} "8" {/if} class="enclosingNested" id="paymentDetails{$row.pledge_id}">&nbsp;</td>
        </tr>
        <script type="text/javascript">
            cj('#{$row.pledge_id}_hide').hide();
            cj('#minus{$row.pledge_id}_hide').hide();
        </script>
    {/foreach}

    {* Dashboard only lists 10 most recent pledges. *}
    {if $context EQ 'dashboard' and $limit and $pager->_totalItems GT $limit }
        <tr class="even-row">
            <td colspan="10"><a href="{crmURL p='civicrm/pledge/search' q='reset=1'}">&raquo; {ts}Find more pledges{/ts}... </a></td>
        </tr>
    {/if}

</table>
{/strip}

{if $context EQ 'Search'}
    <script type="text/javascript">
    {* this function is called to change the color of selected row(s) *}
    var fname = "{$form.formName}";
    on_load_init_checkboxes(fname);
 </script>
{/if}

{if $context EQ 'Search'}
    {include file="CRM/common/pager.tpl" location="bottom"}
{/if}

{* Build pledge payment details*}
{literal}
<script type="text/javascript">

    function buildPaymentDetails( pledgeId, contactId )
    {
        var dataUrl = {/literal}"{crmURL p='civicrm/pledge/payment' h=0 q="action=browse&snippet=4&context=`$context`&pledgeId="}"{literal} + pledgeId + '&cid=' + contactId;

        cj.ajax({
                url     : dataUrl,
                dataType: "html",
                timeout : 5000, //Time in milliseconds
                success : function( data ){
                            cj( '#paymentDetails' + pledgeId ).html( data );
                          },
                error   : function( XMLHttpRequest, textStatus, errorThrown ) {
                            console.error( 'Error: '+ textStatus );
                          }
             });
    }
</script>
{/literal}
