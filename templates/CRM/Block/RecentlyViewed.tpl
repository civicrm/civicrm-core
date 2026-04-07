{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Displays recently viewed objects (contacts and other objects like groups, notes, etc. *}
<div id="crm-recently-viewed" class="left crm-container">
  <ul>
    {foreach from=$recentlyViewed item=item}
      <li class="crm-recently-viewed">
        <div class="crm-recentview-item">
          <a href="{$item.url}" title="{$item.title|escape:'html'}">
            {if $item.image_url}
              <span class="icon crm-icon {if $item.subtype}{$item.subtype}{else}{$item.type}{/if}-icon" style="background: url('{$item.image_url}')"></span>
            {else}
              <i class="crm-i fa-fw {$item.icon}" role="img" aria-hidden="true"></i>
            {/if}
            {if $item.isDeleted}<del>{/if}{$item.title}{if $item.isDeleted}</del>{/if}
          </a>
        </div>
        <div class="crm-recentview-wrapper">
          <a href='{$item.url}' class="crm-actions-view">{ts}View{/ts}</a>
          {if $item.edit_url}<a href='{$item.edit_url}' class="crm-actions-edit">{ts}Edit{/ts}</a>{/if}
          {if $item.delete_url}<a href='{$item.delete_url}' class="crm-actions-delete">{ts}Delete{/ts}</a>{/if}
        </div>
      </li>
    {/foreach}
  </ul>
</div>
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      if ($('#crm-recently-viewed').offset().left > 150) {
        $('#crm-recently-viewed').removeClass('left').addClass('right');
      }
    });
  </script>
{/literal}
