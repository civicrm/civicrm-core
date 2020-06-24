{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* upgradeCleanup.tpl: Display page for special cleanup scripts related to Upgrade.*}

<div style="margin-top: 3em; padding: 1em; background-color: #0C0; border: 1px #070 solid; color: white; font-weight: bold">
  {if $preMessage}
    <p>{$preMessage}</p>
  {/if}
  {if $rows}
  <div class="upgrade-success">
    <table>
      <tr>
        {foreach from=$columnHeaders item=header}
          <th>{$header}</th>
        {/foreach}
      </tr>
      {foreach from=$rows item=row}
        <tr>
            {foreach from=$row item=cell}
              <td>{$cell}</td>
            {/foreach}
        </tr>
      {/foreach}
    </table>
  </div>
  {/if}
  {if $postMessage}
    <p>{$postMessage}</p>
  {/if}
</div>
