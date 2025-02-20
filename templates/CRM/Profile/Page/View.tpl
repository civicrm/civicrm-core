{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* If you want a custom profile view, you can access field labels and values in $profileFields_N array - where N is profile ID. *}
{* EXAMPLES *}{* $profileFields_1.last_name.label *}{* $profileFields_1.last_name.value *}

{if $overlayProfile}
    {foreach from=$profileGroups item=group}
        <div class="crm-summary-group">
           {$group.content}
        </div>
    {/foreach}
{else}
    {foreach from=$profileGroups item=group}
        <h2>{$group.title}</h2>
        <div id="profilewrap{$groupID}" class="crm-profile-view">
           {$group.content}
        </div>
    {/foreach}
    <div class="action-link">
        {* dev/core#4808 profile listings are being phased out, but extensions can still set this *}
        {if $listingURL}
            <a href="{$listingURL}"><i class="crm-i fa-chevron-left" aria-hidden="true"></i> {ts}Back to Listings{/ts}</a>&nbsp;&nbsp;&nbsp;&nbsp;
        {/if}
        {if $mapURL}
            <a href="{$mapURL}"><i class="crm-i fa-map-marker" aria-hidden="true"></i> {ts}Map Primary Address{/ts}</a>
        {/if}
    </div>
{/if}
{literal}
     <script type='text/javascript'>
          function contactImagePopUp (url, width, height) {
             newWindow = window.open( url,'name', 'width='+width+', height='+height );
          }
     </script>
{/literal}
