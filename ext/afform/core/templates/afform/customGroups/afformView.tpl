<af-form ctrl="afform">
  <af-entity
    type="{$formEntity.type}"
    name="{$formEntity.name}"
    label="{$formEntity.label}"
    actions='{ldelim}create: false, update: true{rdelim}'
    security="RBAC"
    url-autofill="1"
    />

  <fieldset af-fieldset="{$formEntity.name}" class="af-container">
    <af-field
        name="{$formEntity.parent_field}"
        defn='{$formEntity.parent_field_defn|@json_encode}'
        />
    {foreach from=$group.field_names item=field_name}
      {* for multiple record fields there is no need to prepend
      the group name because it  will be the form entity itself *}
      <af-field name="{if !$group.is_multiple}{$group.name}.{/if}{$field_name}" defn="{ldelim}input_type: 'DisplayOnly'{rdelim}" />
    {/foreach}
  </fieldset>
</af-form>
