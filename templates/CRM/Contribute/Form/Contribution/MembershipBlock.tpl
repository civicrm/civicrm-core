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
    <div id="membership-listings">
      {foreach from=$membershipTypes item=row}
        <p>
          <strong>{$row.name}</strong>
          <br>{$row.description}
        </p>
      {/foreach}
    </div>
  {/strip}
{/if}
