<div class="crm-label" id="tagLink">
  <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$contactId&selectedChild=tag"}"
     title="{ts escape='htmlattribute'}Edit Tags{/ts}">{$tagsLabel|escape}</a>
</div>
<div class="crm-content" id="tags">
  {foreach from=$contactTag item=tagName key=tagId}
    <span class="crm-tag-item" {if !empty($allTags.$tagId.color)}style="background-color: {$allTags.$tagId.color}; color: {$allTags.$tagId.color|colorContrast};"{/if} title="{$allTags.$tagId.description|escape}">
      {$tagName|escape}
    </span>
  {/foreach}
</div>
