{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
        <dt><img src="{$config->resourceBase}i/Inform.gif" alt="{ts escape='htmlattribute'}status{/ts}"/></dt>
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
