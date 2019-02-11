<ul class="crm-checkbox-list{if $sortable} crm-sortable{/if}">
{foreach from=$checkboxes item=checkbox}
    {if is_array($checkbox) && array_key_exists('html', $checkbox)}
    <li class="ui-state-default">
        {if $sortable}<span class="ui-icon ui-icon-arrowthick-2-n-s"></span>{/if}
        {$checkbox.html}
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
