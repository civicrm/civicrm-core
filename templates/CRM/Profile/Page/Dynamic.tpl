{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if ! empty( $row )}
{* wrap in crm-container div so crm styles are used *}
    {if $overlayProfile}
        {include file="CRM/Profile/Page/Overlay.tpl"}
    {else}
        <div id="crm-container" class="crm-container" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
            <div class="crm-profile-name-{$ufGroupName}">
            {crmRegion name="profile-view-`$ufGroupName`"}
            {foreach from=$profileFields item=field key=rowName}
              <div id="row-{$rowName}" class="crm-section {$rowName}-section">
                <div class="label">
                    {$field.label}
                </div>
                 <div class="content">
                    {$field.value}
                 </div>
                 <div class="clear"></div>
              </div>
            {/foreach}
            {/crmRegion}
            </div>
        </div>
    {/if}
{/if}
{* fields array is not empty *}
