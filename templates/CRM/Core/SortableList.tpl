{*
  This included template loops through $list and enders an unordered list which
  can be sortable when $sortable is set to true.
  $list             An array of list of items containing the key 'html' which will be rendered inbetween <li>s
  $sortable         Should the list be sortable?
  $jsUpdateHandler  Name of a JS function to be called everytime the list is rearranged
*}
<ul class="crm-list{if $sortable} crm-sortable{/if}">
{foreach from=$list item=listItem}
  {if is_array($listItem) && array_key_exists('html', $listItem)}
  <li class="ui-state-default">
    {if $sortable}<span class="ui-icon ui-icon-arrowthick-2-n-s"></span>{/if}
    {$listItem.html}
  </li>
  {/if}
{/foreach}
</ul>
{literal}
<script type="text/javascript">
CRM.$(function($) {
  $('.crm-sortable').sortable({
    {/literal}{if isset($jsUpdateHandler)}update: {$jsUpdateHandler}{/if}{literal},
  });
  $('.crm-sortable').disableSelection();
});
</script>
{/literal}
