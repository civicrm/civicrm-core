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
{strip}
<p>
{if $rows}
    <table>
    <tr class="columnheader">
        <th>{ts}Saved Search{/ts}</th>
        <th>{ts}Description{/ts}</th>
        <th>{ts}Criteria{/ts}</th>
        <th></th>
    </tr>

    {foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"}">
        <td>{$row.name}</td>
        <td>{$row.description}</td>
        <td><ul>
            {foreach from=$row.query_detail item=criteria}
                <li>{$criteria}</li>
            {/foreach}
            </ul>
        </td>
        <td>{$row.action}</td>
    </tr>
    {/foreach}
    </table>
{else}
    <div class="messages status no-popup">
      <dl>
        <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/></dt>
        <dd>
            {ts}There are currently no Saved Searches. To create a Saved search:{/ts}
            <p>
            <ul>
            {capture assign=crmURLsearch}{crmURL p='civicrm/contact/search/basic' q='reset=1'}{/capture}
            {capture assign=crmURLadvanced}{crmURL p='civicrm/contact/search/advanced' q='reset=1'}{/capture}
            <li>{ts 1=$crmURLsearch 2=$crmURLadvanced}Use <a href='%1'>Find</a> or <a href='%2'>Advanced Search</a> form to enter search criteria{/ts}</li>
            <li>{ts}Run and refine the search criteria as necessary{/ts}</li>
            <li>{ts}Select 'New Saved Search' from the '- more actions -' drop-down menu and click 'Go'{/ts}</li>
            <li>{ts}Enter a name and description for your Saved Search{/ts}</li>
            </ul>
            </p>
        </dd>
      </dl>
    </div>
{/if}
</p>
{/strip}
