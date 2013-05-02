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
