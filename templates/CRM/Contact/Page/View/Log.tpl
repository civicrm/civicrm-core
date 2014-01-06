{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
<div id="changeLog" class="view-content">
   <p></p>
   <div class="bold">{ts}Change Log:{/ts} {$displayName}</div>
   {if $useLogging}
     <br />
     <div id='instance_data'><div class="crm-loading-element"></div></div>
   {else}
    <div class="form-item">
     {if $logCount > 0 }
       <table>
       <tr class="columnheader"><th>{ts}Changed By{/ts}</th><th>{ts}Change Date{/ts}</th></tr>
       {foreach from=$log item=row}
         <tr class="{cycle values="odd-row,even-row"}">
            <td> {$row.image}&nbsp;<a href="{crmURL p='civicrm/contact/view' q="action=view&reset=1&cid=`$row.id`"}">{$row.name}</a></td>
            <td>{$row.date|crmDate}</td>
         </tr>
       {/foreach}
       </table>
     {else}
     <div class="messages status no-popup">
      <div class="icon inform-icon"></div> &nbsp;
      {ts}No modifications have been logged for this contact.{/ts}
     </div>
     {/if}
    </div>
   {/if}
 </p>
</div>

{if $useLogging}
{literal}
  <script type="text/javascript">
  CRM.reloadChangeLogTab = function() {
    cj('#changeLog #instance_data').load({/literal}"{$instanceUrl}"{literal});
  };
  cj(function () {
    CRM.reloadChangeLogTab();

    cj('#changeLog').on('click', '.report-pager .crm-pager-nav a', function(e) {
      cj('#changeLog #instance_data').block().load(this.href + '&snippet=4&section=2');
      return false;
    });

    cj('#changeLog').on('click', 'input[name="PagerBottomButton"], input[name="PagerTopButton"]', function(e) {
      var url  = cj('#changeLog #instance_data .report-pager .crm-pager-nav a:first').attr('href') + '&snippet=4&section=2';
      cj('#changeLog #instance_data').block().load(url + '&crmPID=' + cj(this).siblings('input[type=text]').val());
      return false;
    });
  });

  </script>
{/literal}
{/if}
