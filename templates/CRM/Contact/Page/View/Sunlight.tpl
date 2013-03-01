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
<div id="electedOfficals" class="view-content">
    <p></p>
    <div class="bold">{ts}Elected Officals:{/ts} {$displayName}</div>
    <div class="form-item">
     {if $rowCount > 0 }
       <table>
       <tr class="columnheader">
          <th>{ts}Image{/ts}</th>
          <th>{ts}Name{/ts}</th>
          <th>{ts}Party{/ts}</th>
          <th>{ts}Address{/ts}</th>
          <th>{ts}Phone{/ts}</th>
          <th>{ts}Email{/ts}</th>
       </tr>
       {foreach from=$rows item=row}
         <tr class="{cycle values="odd-row,even-row"}">
            <td><a href="{$row.url}"><img src="{$row.image_url}"></a></td>
            <td>{$row.title} {$row.first_name} {$row.last_name}</td>
            <td>{$row.party}</td>
            <td>{$row.address}</td>
            <td>{$row.phone}</td>
            <td>{$row.email}</td>
         </tr>
       {/foreach}
       </table>
     {else}
     <div class="messages status no-popup">
     <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"> &nbsp;
      {ts}No data available for this contact. Please check city/state/zipcode{/ts}
     </div>
     {/if}
    </div>
 </p>
</div>
