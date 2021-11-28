{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{crmRegion name='form-buttons'}
{* Loops through $linkButtons and assigns html "a" (link) buttons to the template. Used for additional entity functions such as "Move to Case" or "Renew Membership" *}
{if $linkButtons}
  {foreach from=$linkButtons item=linkButton}
    {if $linkButton.accessKey}
      {capture assign=accessKey}accesskey="{$linkButton.accessKey}"{/capture}
    {else}{assign var="accessKey" value=""}
    {/if}
    {if $linkButton.icon}
      {capture assign=icon}<i class="crm-i {$linkButton.icon}" aria-hidden="true"></i> {/capture}
    {else}{assign var="icon" value=""}
    {/if}
    {if $linkButton.ref}
      {capture assign=linkname}name="{$linkButton.ref}"{/capture}
    {else}{capture assign=linkname}name="{$linkButton.name}"{/capture}
    {/if}
    <a class="button" {$linkname} href="{crmURL p=$linkButton.url q=$linkButton.qs}" {$accessKey} {$linkButton.extra}><span>{$icon}{$linkButton.title}</span></a>
  {/foreach}
{/if}

{foreach from=$form.buttons item=button key=key name=btns}
  {if $key|substring:0:4 EQ '_qf_'}
    {if !empty($location)}
      {$form.buttons.$key.html|crmReplace:id:"$key-$location"}
    {else}
      {$form.buttons.$key.html}
    {/if}
  {/if}
{/foreach}
{/crmRegion}
