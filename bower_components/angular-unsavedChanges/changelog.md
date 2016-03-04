# Changelog

Versioning follows [http://semver.org/](http://semver.org/), ie: MAJOR.MINOR.PATCH. Major version 0 is initial development. Minor versions may be backwards incompatible.

### 0.1.0

**Features**

- Add `lazy-model` directive, and change `clear` buttons to `type="reset"` which allows for resetting the model to original values. Furthermore values are only persisted to model if user submits valid form.
- Only set pristine when clearing changes if form is valid. (https://github.com/facultymatt/angular-unsavedChanges/commit/26cd981397f3e1e637280e3778aa80708821dab4). The lazy-model form reset hook handles resetting the value. 
- Directive now removes onbeforeunload and route change listeners if no registered forms exist on the page. (https://github.com/facultymatt/angular-unsavedChanges/commit/58cad5401656bb806183d0a42c8b81bf1fbeeac6)

**Breaking Changes**

- Change getters and setters to user NJO (native javascript objects). This means that insated of setting `provider.setUseTranslateService(true)` you can natively set `provider.useTranslateService = true`. This may seem like semantics but if follows one of angulars core principals. 

### 0.0.3

**Tests**

- Add full set of unit and e2e tests

**Features**

- Add config option for custom messages
- Add support for uiRouter state change event via. config
- Add support for Angular Translate
- Add custom logging method for development

**Chores**

- Add module to bower. 

**Breaking Changes**

- Changed name from `mm.unsavedChanges` to `unsavedChanges`


### 0.0.2 and below

Offical changelog was not maintained for these versions.  
