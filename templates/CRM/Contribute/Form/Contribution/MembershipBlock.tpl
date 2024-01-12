{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div id="membership" class="crm-group membership-group">

</div>
{if $membershipBlock AND $is_quick_config}
    <div class="header-dark">
      {if $renewal_mode}
        {if array_key_exists('renewal_title', $membershipBlock) && $membershipBlock.renewal_title}
          {$membershipBlock.renewal_title}
        {else}
          {ts}Select a Membership Renewal Level{/ts}
        {/if}
      {else}
        {if array_key_exists('new_title', $membershipBlock) && $membershipBlock.new_title}
          {$membershipBlock.new_title}
        {else}
          {ts}Select a Membership Level{/ts}
        {/if}
      {/if}
    </div>

  {strip}
    <table id="membership-listings">
      {foreach from=$membershipTypes item=row}
        <tr valign="top">
          <td style="width: auto;">
                <span class="bold">{$row.name} &nbsp;
                </span><br />
            {$row.description} &nbsp;
          </td>

          <td style="width: auto;">
          </td>
        </tr>

      {/foreach}
      {if array_key_exists('auto_renew', $form)}
        <tr id="allow_auto_renew">
          <td style="width: auto;">{$form.auto_renew.html}</td>
          <td style="width: auto;">
            {$form.auto_renew.label}
          </td>
        </tr>
      {/if}
    </table>
  {/strip}
{/if}
