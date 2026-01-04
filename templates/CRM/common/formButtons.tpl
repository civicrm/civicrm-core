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
    {if array_key_exists('accessKey', $linkButton) && $linkButton.accessKey}
      {capture assign=accessKey}accesskey="{$linkButton.accessKey}"{/capture}
    {else}{assign var="accessKey" value=""}
    {/if}
    {if array_key_exists('icon', $linkButton) && $linkButton.icon}
      {capture assign=icon}<i class="crm-i {$linkButton.icon}" role="img" aria-hidden="true"></i> {/capture}
    {else}{assign var="icon" value=""}
    {/if}
    {if array_key_exists('ref', $linkButton) && $linkButton.ref}
      {capture assign=linkname}name="{$linkButton.ref}"{/capture}
    {else}{capture assign=linkname}{if array_key_exists('name', $linkButton)}name="{$linkButton.name}"{/if}{/capture}
    {/if}
    <a class="button{if array_key_exists('class', $linkButton)} {$linkButton.class}{/if}" {$linkname} href="{crmURL p=$linkButton.url q=$linkButton.qs}" {$accessKey} {if array_key_exists('extra', $linkButton)}{$linkButton.extra}>{/if}<span>{$icon nofilter}{$linkButton.title}</span></a>
  {/foreach}
{/if}
{if $form}
  {* This could be called from Membership View - which is a page not a form but uses it for the links above *}
  {foreach from=$form.buttons item=button key=key name=btns}
  {if $key|substring:0:4 EQ '_qf_'}
    {if $location}
      {$form.buttons.$key.html|crmReplace:id:"$key-$location"}
    {else}
      {$form.buttons.$key.html}
    {/if}
  {/if}
{/foreach}
{/if}
{/crmRegion}
