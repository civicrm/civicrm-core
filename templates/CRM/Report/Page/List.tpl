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
    <fieldset>
        <legend>{ts}Template List{/ts}</legend>
        {if $list}
            {foreach from=$list item=rows key=report}
          <br>
                <div style="cursor:pointer;background-color:#F5F5F5" onclick="toggle_visibility('{$report}');">
              <table class="form-layout">
            <tr>
          <td><strong>{if $report}{$report}{else}Contact{/if} Reports</strong></td>
      </tr>
        </table>
          </div>
    <div id="{$report}" style="display:none;">
        <table style="border:0;">
            {foreach from=$rows item=row}
                      <tr style="border-bottom:1px solid #E3E9ED;background-color:{cycle values="#FFFFFF;,#F4F6F8;" name="$report"}">
                      <td style="color:#2F425C;width:200px;">
                             <a href="{$row.url}" style="text-decoration:none;display:block;" title="{$row.description}">
                             <img alt="report" src="{$config->resourceBase}i/report.gif"/>&nbsp;&nbsp;
                    <strong>{$row.title}</strong>
            </a>
               {if $row.instanceUrl}
          <div align="right">
              <a href="{$row.instanceUrl}">{ts}Instance(s){/ts}</a>
          </div>
            {/if}
              </td>
        <td style="cursor:help;width:350px;">
            {$row.description}
        </td>
          </tr>
            {/foreach}
                    </table>
                </div>
      {/foreach}
        {else}
            <div class="messages status no-popup">
                <div class="icon inform-icon"></div>&nbsp; {ts}There are currently no Reports.{/ts}
            </div>
        {/if}
    </fieldset>
{/strip}
{literal}
<script type="text/javascript">
    function toggle_visibility(id) {
  var e = document.getElementById(id);
  if (e.style.display == 'block') {
      e.style.display = 'none';
  } else {
      e.style.display = 'block';
  }
    }
</script>
{/literal}
