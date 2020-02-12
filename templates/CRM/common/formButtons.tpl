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
    {if $linkButton.accesskey}
      {capture assign=accessKey}accesskey="{$linkButton.accessKey}"{/capture}
    {else}{assign var="accessKey" value=""}
    {/if}
    {if $linkButton.icon}
      {capture assign=icon}<i class="crm-i {$linkButton.icon}"></i> {/capture}
    {else}{assign var="icon" value=""}
    {/if}
    {if $linkButton.ref}
      {capture assign=linkname}name="{$linkButton.ref}"{/capture}
    {else}{capture assign=linkname}name="{$linkButton.name}"{/capture}
    {/if}
    <a class="button" {$linkname} href="{crmURL p=$linkButton.url q=$linkButton.qs}" {$accessKey} {$linkButton.extra}><span>{$icon}{$linkButton.title}</span></a>
  {/foreach}
{/if}

{* Loops through $form.buttons.html array and assigns separate spans with classes to allow theming by button and name.
 * crmBtnType grabs type keyword from button name (e.g. 'upload', 'next', 'back', 'cancel') so types of buttons can be styled differently via css.
 *}
{foreach from=$form.buttons item=button key=key name=btns}
  {if $key|substring:0:4 EQ '_qf_'}
    {if $location}
      {assign var='html' value=$form.buttons.$key.html|crmReplace:id:"$key-$location"}
    {else}
      {assign var='html' value=$form.buttons.$key.html}
    {/if}
    {crmGetAttribute html=$html attr='crm-icon' assign='icon'}
    {capture assign=iconPrefix}{$icon|truncate:3:"":true}{/capture}
    {if $icon && $iconPrefix eq 'fa-'}
      {assign var='buttonClass' value=' crm-i-button'}
      {capture assign=iconDisp}<i class="crm-i {$icon}"></i>{/capture}
    {elseif $icon}
      {assign var='buttonClass' value=' crm-icon-button'}
      {capture assign=iconDisp}<span class="crm-button-icon ui-icon-{$icon}"> </span>{/capture}
    {/if}
    {crmGetAttribute html=$html attr='disabled' assign='disabled'}
    <span class="crm-button crm-button-type-{$key|crmBtnType} crm-button{$key}{$buttonClass}{if $disabled} crm-button-disabled{/if}"{if $buttonStyle} style="{$buttonStyle}"{/if}>
      {$iconDisp}
      {$html}
    </span>
  {/if}
{/foreach}
{/crmRegion}
