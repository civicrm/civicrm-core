{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
<div id="name" class="section-hidden section-hidden-border form-item">
    <p>
        <label>{$displayName}</label>
    </p>
</div>

<div id="groupContact">
 <p>
    <div class="form-item">
    {if $groupCount > 0 }
       <table>
       <tr class="columnheader"><th>{ts}Group Listings{/ts}</th><th>{ts}In Date{/ts}</th><th>{ts}Out Date{/ts}</th><th></th></tr>
       {foreach from=$groupContact item=row}
         <tr class="{cycle values="odd-row,even-row"}">
            <td> {$row.name}</td>
            <td>{$row.in_date|crmDate}</td>
            <td>{$row.out_date|crmDate}</td>
      <td><a href="#">{ts}View{/ts}</a></td>
         </tr>
       {/foreach}
       </table>
     {else}
     <div class="messages status no-popup">
     <div class="icon inform-icon"></div> &nbsp;
      {ts}This contact does not belong to any groups.{/ts}
     </div>
     {/if}
    </div>
 </p>
  <span class="float-right">
   <a href="#">{ts}Add this contact to one or more groups...{/ts}</a>
  </span>

</div>
