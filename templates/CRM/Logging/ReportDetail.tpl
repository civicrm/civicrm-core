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
<div class="crm-block crm-content-block crm-report-form-block">
  {if $rows}
    {if $raw}
      <div class="status">
        <dl>
          <dt><div class="icon inform-icon"></div></dt>
          <dd>
            {ts}WARNING: Are you sure you want to revert the below changes?{/ts}
          </dd>
        </dl>
      </div>
    {/if}
    <p>{ts 1=$whom_url 2=$whom_name 3=$who_url 4=$who_name 5=$log_date}Change to <a href='%1'>%2</a> made by <a href='%3'>%4</a> on %5:{/ts}</p>
    {if $layout eq 'overlay'}
      {include file="CRM/Report/Form/Layout/Overlay.tpl"}
    {else}
      {include file="CRM/Report/Form/Layout/Table.tpl"}
    {/if}
  {else}
    <div class='messages status'>
        <div class='icon inform-icon'></div>&nbsp; {ts}This report can not be displayed because there are no entries in the logging tables yet.{/ts}
    </div>
  {/if}
  {if $layout neq 'overlay'}
  <div class="action-link">
      <a href="{$backURL}"   class="button"><span><div class="icon back-icon"></div>{ts}Back to Logging Summary{/ts}</span></a>
      <a href="{$revertURL}" class="button" onclick="return confirm('{$revertConfirm}');"><span><div class="icon delete-icon"></div>{ts}Revert These Changes{/ts}</span></a>
  </div>
  {/if}
</div>
