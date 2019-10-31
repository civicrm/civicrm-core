# Progression of sketches of canvas content

```html
<script class="afform-meta-1234abcd" type="text/json"> {
  "entities": [
    "parent": {
      "title": "Parent",
      "type": "Individual"
    },
    "spouse": {
      "title": "Spouse".
      "type": "Individual"
    }
  ]
}
</script>
  
<div afform-entity-ctrl="afform-meta-1234abcd">
  <af-address contact-id="entities.parent.id" label="Address" ng-if="" ng-classes="{foo: name == 'Frank'}" />
  <af-field entity="entities.parent" entity-type="contact" field="first_name" label="First Name"/>
  <af-field entity="entities.parent" entity-type="contact" field="last_name" label="Last Name"/>
</div>
```


-------------

```html
<div afform-entity-ctrl="myEntities">
  <afform-entity type="Individual" name="parent" title="Parent" />
  <afform-entity type="Individual" name="spouse" title="Spouse" />
  
  <af-name entity="myEntities.parent" label="Address" />
  <af-address entity="myEntities.parent" label="Address" />
  <af-field entity="myEntities.parent" entity-type="contact" field="first_name" label="First Name"/>
  <af-field entity="myEntities.parent" entity-type="contact" field="last_name" label="Last Name"/>
</div>
```

------------------

```html
<div afform-entity-ctrl="myEntities">

  <af-entity type="Individual" name="parent" label="Parent">
    <af-name label="Name" />
    <af-email label="Email" />
  </af-entity>
  
  <af-entity type="Individual" name="spouse" af-title="Spouse">
    <af-name label="Spouse Name" />
    <af-email label="Spouse Email" />
  </af-entity>

  <af-entity type="Individual" name="parent" af-title="Parent">
    <af-address label="Address" />
  </af-entity>

</div>
```

---------

```html
<!-- 1. s/entity/model-->
<af-form>

  <af-fieldset type="Individual" model="parent" label="Parent">
    <af-name label="Name" />
    <af-email label="Email" />
  </div>

  <af-fieldset type="Individual" model="spouse" af-title="Spouse">
    <af-name label="Spouse Name" />
    <af-email label="Spouse Email" />
  </div>

  <af-fieldset type="Individual" model="parent" af-title="Parent">
    <af-address label="Address" />
  </div>

</af-form>
```

--------------

```html
<af-form ctrl="modelListCtrl">
  <af-entity
    type="Individual"
    name="parent"
    label="Parent"
    api4-params="{where: ['id','=', routeParams.cid]}"
  />
  <af-entity
    type="Individual"
    name="spouse"
    label="Spouse"
    contact-relationship="['Spouse of', 'parent']"
  />
  <!-- "parent" and "spouse" should be exported as variables in this scope -->

  <div af-fieldset="parent">
    <af-name label="Name" />
    <af-email label="Email" />
  </div>

  <div af-fieldset="spouse">
    <af-name label="Spouse Name" />
    <af-email label="Spouse Email" only-primary="true" />
  </div>

  <p ng-if="spouse.display_name.contains('Thor')">
    Your spouse should go to the gym.
  </p>

  <div af-fieldset="parent">
    <af-address label="Address" />
  </div>

  <!-- General elements: FIELDSET, UL, BUTTON, P, H1 should work anywhere -->
  <button ng-model="modelListCtrl.submit()">Submit</button>

</af-form>
```

------

```html
<!-- afform/Blocks/Email.html -->
<!-- input: options.parent.id -->
<!-- Decision: These blocks are written in straight AngularJS rather than Afform -->
<!--<af-form>-->
  <!--<af-entity -->
    <!--type="Email"-->
    <!--name="email"-->
    <!--label="Emails"-->
    <!--api4-params="{where: ['contact_id', '=', options.parent.id]}"-->
  <!--/>-->
  <!--<div af-fieldset="email">-->
    <!---->
  <!--</div>-->
<!--</af-form>-->

```

------

```html
<af-form ctrl="modelListCtrl">
  <af-entity
    type="Individual"
    name="parent"
    label="Parent"
    api4-params="{where: ['id','=', routeParams.cid]}"
  />
  <af-entity
    type="Individual"
    name="spouse"
    label="Spouse"
    contact-relationship="['Spouse of', 'parent']"
  />
  <!-- "parent" and "spouse" should be exported as variables in this scope -->

  <crm-ui-tab-set>
    <crm-ui-tab title="About You">
      <div af-fieldset="parent">
        <af-std-contact-name label="Name" />
        <af-std-contact-email label="Email" />
        <af-field name="do_not_email" field-type="checkbox" field-default="1" />
      </div>
    </crm-ui-tab>
    <crm-ui-tab title="Spouse">
      <div af-fieldset="spouse">
        <af-std-contact-name label="Spouse Name" />
        <af-std-contact-email label="Spouse Email" only-primary="true" />
        <af-field name="do_not_email" field-type="checkbox" field-default="1" />
      </div>
    </crm-ui-tab>
  </crm-ui-tab-set>

  <p ng-if="spouse.display_name.contains('Thor')">
    Your spouse should go to the gym.
  </p>

  <div af-fieldset="parent">
    <af-block-contact-address label="Address" />
  </div>

  <!-- General elements: FIELDSET, UL, BUTTON, P, H1 should work anywhere -->
  <button ng-model="modelListCtrl.submit()">Submit</button>

</af-form>
```
