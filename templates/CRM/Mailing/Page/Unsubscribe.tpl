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
{if $confirm}
    <div class="messages status no-popup">
          <div class="icon inform-icon"></div>&nbsp;
          <label>{$display_name} ({$email})</label> {ts}has been successfully removed from the specified mailing list(s).{/ts}
    </div>
{else}
    <div>
    <form action="{$confirmURL}" method="post">
      {if $groupExist}
        <div class="messages status no-popup">
          {ts 1=$display_name 2=$email} %1 (%2){/ts}<br/>
          {ts}Are you sure you want to be removed from the mailing list(s) shown below:{/ts}<br/>
        </div>
        <table class="selector" style="width: auto; margin-top: 20px;">
            {counter start=0 skip=1 print=false}
            {foreach from=$groups item=group}
            <tr class="{cycle values="odd-row,even-row"}">
             <td><strong>{$group.title}</strong></td>
             <td>&nbsp;&nbsp;{$group.description}&nbsp;</td>
            </tr>
            {/foreach}  
        </table>
        <div class="crm-submit-buttons">
            <span class="crm-button crm-button-type-save"><input type="submit" name="_qf_unsubscribe_next" value="{ts}Unsubscribe{/ts}" class="form-submit" /></span> &nbsp;&nbsp;&nbsp;
            <span class="crm-button crm-button-type-cancel"><input type="submit" name="_qf_unsubscribe_cancel" value="{ts}Cancel{/ts}" class="form-submit" /></span>
        </div>
      {else}
        <div class="messages status no-popup">
          {ts 1=$display_name 2=$email} %1 (%2){/ts}<br/>
          {ts}Sorry you are not currently on this mailing list. Perhaps you have already unsubscribed.{/ts}<br/>
        </div>
      {/if}
    </form>
    </div>
{/if}