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
        defn="{$formEntity.parent_field_defn|json|escape}"
        />
    {foreach from=$group.fields item=field}
      <af-field
        name="{$field.key}"
        {if $field.defn}defn="{$field.defn|json|escape}"{/if}
      ></af-field>
    {/foreach}
  </fieldset>
</af-form>
