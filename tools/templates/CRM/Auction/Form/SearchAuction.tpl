{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
<div class="form-item">
 <fieldset><legend>{ts}Find Auctions{/ts}</legend>
  <table class="form-layout">
    <tr>
        <td class="label">{$form.title.label}</td>
        <td>{$form.title.html|crmReplace:class:twenty}
             <div class="description font-italic">
                    {ts}Complete OR partial Auction name.{/ts}
             </div>
             <div style="height: auto; vertical-align: bottom">{$form.auctionsByDates.html}</div>
        </td>
        <td class="left" rowspan="2">&nbsp;{$form.buttons.html}</td>  
    </tr>
    <tr>
       <td></td>
       <td>
       <table class="form-layout-compressed" id="id_fromToDates">
         <tr>
           <td>{$form.start_date.label}</td>
           <td>&nbsp;{$form.start_date.html}&nbsp;
            &nbsp;{include file="CRM/common/calendar/desc.tpl" trigger=trigger_search_member_1}
            {include file="CRM/common/calendar/body.tpl" dateVar=start_date startDate=startYear endDate=endYear offset=5 trigger=trigger_search_member_1}
            </td>
          </tr>
          <tr>
            <td>{$form.end_date.label}</td>
            <td>&nbsp;{$form.end_date.html}&nbsp;
             &nbsp;{include file="CRM/common/calendar/desc.tpl" trigger=trigger_search_member_2}
             {include file="CRM/common/calendar/body.tpl" dateVar=end_date startDate=startYear endDate=endYear offset=5 trigger=trigger_search_member_2}
            </td> 
          </tr>
       </table> 
       </td>
    </tr>  
  </table>
</fieldset>
</div>

{include file="CRM/common/showHide.tpl"}

{literal} 
<script type="text/javascript">
if ( document.getElementsByName('auctionsByDates')[1].checked ) {
  cj('#id_fromToDates').show();
}
</script>
{/literal} 
