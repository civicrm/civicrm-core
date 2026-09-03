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
    {foreach from=$group.fields item=field}
      <af-field
        {* Don't prepend the group name for multi-record groups because it will be the form entity itself *}
        name="{if !$group.is_multiple}{$group.name}.{/if}{$field.name}"
        {* Default field label includes the custom group name. Override that with just the field name *}
        defn="{ldelim}input_type: 'DisplayOnly', label: {$field.label|@json_encode|escape}{rdelim}"
      ></af-field>
    {/foreach}
  </fieldset>
</af-form>
