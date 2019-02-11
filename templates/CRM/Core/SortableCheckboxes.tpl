<ul class="crm-checkbox-list{if $sortable} crm-sortable{/if}">
{foreach from=$checkboxes item=checkbox}
    {if is_array($checkbox) && array_key_exists('html', $checkbox)}
    <li class="ui-state-default">
        <span class="ui-icon ui-icon-arrowthick-2-n-s"></span>
        {$checkbox.html}
    </li>
    {/if}
{/foreach}
</ul>
{literal}
<script type="text/javascript">
CRM.$(function($) {
    $('.crm-sortable').sortable({
        update: function( event, ui ) {
            var checkboxes = $(event.target).find('input'),
                sortedCheckboxes = [];
            for (var i = 0; i < checkboxes.length; i++) {
                sortedCheckboxes.push(checkboxes[i].name.split(/[\[\]]/)[1]);
            }
            {/literal}{if isset($jsUpdateHandler)}{$jsUpdateHandler}(sortedCheckboxes);{/if}{literal}
        }
    });
    $('.crm-sortable').disableSelection();
});
</script>
{/literal}
