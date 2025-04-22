{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This file provides the template for inline editing of custom data attached to a block
 - ie email & in the future phone, maybe im, website & openID although those
 may not rise to the top of anyone's to-do *}
{if array_key_exists($blockId, $customFields)}
  {foreach item='custom_field' from=$customFields.$blockId key='custom_field_name'}
    <tr class="crm-block-entity-{$entity}-{$blockId} {if $blockId gt $actualBlockCount}hiddenElement{/if}">
      <td colspan="5">{$form.$entity.$blockId.$custom_field_name.label}</td>
    </tr>
    <tr class="crm-block-entity-{$entity}-{$blockId} {if $blockId gt $actualBlockCount}hiddenElement{/if}">
      <td colspan="5">{$form.$entity.$blockId.$custom_field_name.html}</td>
    </tr>
  {/foreach}
{/if}

