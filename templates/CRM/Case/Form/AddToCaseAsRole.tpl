<div><label for="assign_to">{ts}Assign To{/ts}:</label></div>
<div><input name="assign_to" data-api-entity="case" placeholder="{ts}- select case -{/ts}" class="huge" /></div>

<div>{$form.role_type.label}</div>
<div>{$form.role_type.html}</div><br />

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

{literal}
<script type="text/javascript">
    (function($, CRM) {
        $(function() {
            $('[name=assign_to], [name=role]', this)
                    .val('')
                    .crmEntityRef({create: false});
        });
    })(cj, CRM);
</script>
{/literal}
