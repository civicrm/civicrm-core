<af-form ctrl="afform">
  <af-entity
    type="{$formEntity.type}"
    name="{$formEntity.name}"
    label="{$formEntity.label}"
    actions='{$formActions|@json_encode}'
    security="RBAC"
    url-autofill="{$urlAutofill}"
    />

  <fieldset af-fieldset="{$formEntity.name}" class="af-container">
    <af-field
        name="{$formEntity.parent_field}"
        defn='{$formEntity.parent_field_defn|@json_encode}'
        />
    <{$blockDirective} />
  </fieldset>
  <button class="af-button btn btn-primary" crm-icon="fa-save" ng-click="afform.submit()">Save</button>
</af-form>
