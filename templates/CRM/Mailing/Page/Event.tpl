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
{include file="CRM/common/pager.tpl" location="top"}

{if $rows }
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
        <div class="icon inform-icon"></div>&nbsp;
        {ts 1=$title}There are currently no %1.{/ts}
    </div>    
{/if}  

        <div class="action-link">
        <a href="{crmURL p='civicrm/mailing/report' q="mid=`$mailing_id`&reset=1"}" >&raquo; {ts}Back to Report{/ts}</a>
        </div>

{include file="CRM/common/pager.tpl" location="bottom"}

{if $pager and ( $pager->_totalPages > 1 )}
{literal}
<script type="text/javascript">
  var totalPages = {/literal}{$pager->_totalPages}{literal};
  cj( function ( ) {
    cj("#crm-container .crm-pager input.form-submit").click( function( ) {
      submitPagerData( this );
    }); 
  });
 
  function submitPagerData( el ) {
      var urlParams= '';
      var jumpTo   = cj(el).parent( ).children('input[type=text]').val( );
      if ( parseInt(jumpTo)== "Nan" ) jumpTo = 1;
      if ( jumpTo > totalPages ) jumpTo = totalPages;
      {/literal} 
      {foreach from=$pager->_linkData item=val key=k }
      {if $k neq 'crmPID' && $k neq 'force' && $k neq 'q' } 
      {literal}
        urlParams += '&{/literal}{$k}={$val}{literal}';
      {/literal}
      {/if}
      {/foreach}
      {literal}
      urlParams += '&crmPID='+parseInt(jumpTo);
      var submitUrl = {/literal}'{crmURL p="civicrm/mailing/report/event" q="force=1" h=0 }'{literal};
      document.location = submitUrl+urlParams;
  }
</script>
{/literal}
{/if}