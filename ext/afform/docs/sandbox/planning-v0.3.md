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
  <af-address contact-id="entities.parent.id" af-label="Address" ng-if="" ng-classes="{foo: name == 'Frank'}" />
  <af-field entity="entities.parent" entity-type="contact" field="first_name" af-label="First Name"/>
  <af-field entity="entities.parent" entity-type="contact" field="last_name" af-label="Last Name"/>
</div>
```


-------------

```html
<div afform-entity-ctrl="myEntities">
  <afform-entity type="Individual" name="parent" title="Parent" />
  <afform-entity type="Individual" name="spouse" title="Spouse" />
  
  <af-name entity="myEntities.parent" af-label="Address" />
  <af-address entity="myEntities.parent" af-label="Address" />
  <af-field entity="myEntities.parent" entity-type="contact" field="first_name" af-label="First Name"/>
  <af-field entity="myEntities.parent" entity-type="contact" field="last_name" af-label="Last Name"/>
</div>
```

------------------

```html
<div afform-entity-ctrl="myEntities">

  <af-entity af-type="Individual" af-name="parent" af-label="Parent">
    <af-name af-label="Name" />
    <af-email af-label="Email" />
  </af-entity>
  
  <af-entity af-type="Individual" af-name="spouse" af-title="Spouse">
    <af-name af-label="Spouse Name" />
    <af-email af-label="Spouse Email" />
  </af-entity>

  <af-entity af-type="Individual" af-name="parent" af-title="Parent">
    <af-address af-label="Address" />
  </af-entity>

</div>
```

---------

```html
<!-- 1. s/entity/model-->
<af-model-list>

  <af-model af-type="Individual" af-name="parent" af-label="Parent">
    <af-name af-label="Name" />
    <af-email af-label="Email" />
  </af-model>

  <af-model af-type="Individual" af-name="spouse" af-title="Spouse">
    <af-name af-label="Spouse Name" />
    <af-email af-label="Spouse Email" />
  </af-model>

  <af-model af-type="Individual" af-name="parent" af-title="Parent">
    <af-address af-label="Address" />
  </af-model>

</af-model-list>
```

--------------

```html
<af-model-list ctrl="modelListCtrl">
  <af-model-prop
    af-type="Individual"
    af-name="parent"
    af-label="Parent"
    af-api4-params="{where: ['id','=', routeParams.cid]}"
  />
  <af-model-prop
    af-type="Individual"
    af-name="spouse"
    af-label="Spouse"
    af-contact-relationship="['Spouse of', 'parent']"
  />
  <!-- "parent" and "spouse" should be exported as variables in this scope -->

  <af-model af-name="parent">
    <af-name af-label="Name" />
    <af-email af-label="Email" />
  </af-model>

  <af-model af-name="spouse">
    <af-name af-label="Spouse Name" />
    <af-email af-label="Spouse Email" only-primary="true" />
  </af-model>

  <p ng-if="spouse.display_name.contains('Thor')">
    Your spouse should go to the gym.
  </p>

  <af-model af-name="parent">
    <af-address af-label="Address" />
  </af-model>

  <!-- General elements: FIELDSET, UL, BUTTON, P, H1 should work anywhere -->
  <button ng-model="modelListCtrl.submit()">Submit</button>

</af-model-list>
```

------

```html
<!-- afform/Blocks/Email.html -->
<!-- input: options.parent.id -->
<!-- Decision: These blocks are written in straight AngularJS rather than Afform -->
<!--<af-model-list>-->
  <!--<af-model-prop -->
    <!--af-type="Email"-->
    <!--af-name="email"-->
    <!--af-label="Emails"-->
    <!--af-api4-params="{where: ['contact_id', '=', options.parent.id]}"-->
  <!--/>-->
  <!--<af-model af-name="email">-->
    <!---->
  <!--</af-model>-->
<!--</af-model-list>-->

```

------

```html
<af-model-list ctrl="modelListCtrl">
  <af-model-prop
    af-type="Individual"
    af-name="parent"
    af-label="Parent"
    af-api4-params="{where: ['id','=', routeParams.cid]}"
  />
  <af-model-prop
    af-type="Individual"
    af-name="spouse"
    af-label="Spouse"
    af-contact-relationship="['Spouse of', 'parent']"
  />
  <!-- "parent" and "spouse" should be exported as variables in this scope -->

  <crm-ui-tab-set>
    <crm-ui-tab title="About You">
      <af-model af-name="parent">
        <af-std-contact-name af-label="Name" />
        <af-std-contact-email af-label="Email" />
        <af-field field-name="do_not_email" field-type="checkbox" field-default="1" />
      </af-model>
    </crm-ui-tab>
    <crm-ui-tab title="Spouse">
      <af-model af-name="spouse">
        <af-std-contact-name af-label="Spouse Name" />
        <af-std-contact-email af-label="Spouse Email" only-primary="true" />
        <af-field field-name="do_not_email" field-type="checkbox" field-default="1" />
      </af-model>
    </crm-ui-tab>
  </crm-ui-tab-set>

  <p ng-if="spouse.display_name.contains('Thor')">
    Your spouse should go to the gym.
  </p>

  <af-model af-name="parent">
    <af-block-contact-address af-label="Address" />
  </af-model>

  <!-- General elements: FIELDSET, UL, BUTTON, P, H1 should work anywhere -->
  <button ng-model="modelListCtrl.submit()">Submit</button>

</af-model-list>
```
