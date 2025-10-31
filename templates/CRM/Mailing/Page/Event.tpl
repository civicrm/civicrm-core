{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{include file="CRM/common/pager.tpl" location="top"}

{if $rows}
  {include file="CRM/common/jsortable.tpl"}
  {strip}
    <table id="mailing_event">
      <thead>
      <tr>
        {foreach from=$columnHeaders item=header}
          <th>
            {if $header.sort}
              {assign var='key' value=$header.sort}
              {$sort->_response.$key.link}
            {else}
              {$header.name}
            {/if}
          </th>
        {/foreach}
      </tr>
      </thead>
      {counter start=0 skip=1 print=false}
      {foreach from=$rows item=row}
        <tr class="{cycle values="odd-row,even-row"}">
          {foreach from=$row item=value}
            <td>{$value}</td>
          {/foreach}
        </tr>
      {/foreach}
    </table>
  {/strip}
{else}
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    &nbsp;
    {ts 1=$title}There are currently no %1.{/ts}
  </div>
{/if}

<div class="action-link">
  <a href="{$backUrl}"><i class="crm-i fa-chevron-left" role="img" aria-hidden="true"></i> {$backUrlTitle}</a>
</div>

{include file="CRM/common/pager.tpl" location="bottom"}

{if $pager and ( $pager->_totalPages > 1 )}
{literal}
  <script type="text/javascript">
    var totalPages = {/literal}{$pager->_totalPages}{literal};
    CRM.$(function($) {
      $("#crm-container .crm-pager button.crm-form-submit").click(function () {
        submitPagerData(this);
      });
    });

    function submitPagerData(el) {
      var urlParams = '';
      var jumpTo = cj(el).parent().children('input[type=text]').val();
      if (parseInt(jumpTo) == "Nan") {
        jumpTo = 1;
      }
      if (jumpTo > totalPages) {
        jumpTo = totalPages;
      }
      {/literal}
      {foreach from=$pager->_linkData item=val key=k}
        {if $k neq 'crmPID' && $k neq 'force' && $k neq 'q'}
        {literal}
        urlParams += '&{/literal}{$k}={$val}{literal}';
        {/literal}
        {/if}
      {/foreach}
      {literal}
      urlParams += '&crmPID=' + parseInt(jumpTo);
      var submitUrl = {/literal}'{crmURL p="civicrm/mailing/report/event" q="force=1" h=0}'{literal};
      document.location = submitUrl + urlParams;
    }
  </script>
{/literal}
{/if}
