(function($, _) {
  if (!CRM.Designer) CRM.Designer = {};

  /**
   * When rendering a template with Marionette.ItemView, the list of variables is determined by
   * serializeData(). The normal behavior is to map each property of this.model to a template
   * variable.
   *
   * This function extends that practice by exporting variables "_view", "_model", "_collection",
   * and "_options". This makes it easier for the template to, e.g., access computed properties of
   * a model (by calling "_model.getComputedProperty"), or to access constructor options (by
   * calling "_options.myoption").
   *
   * @return {*}
   */
  var extendedSerializeData = function() {
    var result = Marionette.ItemView.prototype.serializeData.apply(this);
    result._view = this;
    result._model = this.model;
    result._collection = this.collection;
    result._options = this.options;
    return result;
  };

  /**
   * Display a dialog window with an editable form for a UFGroupModel
   *
   * The implementation here is very "jQuery-style" and not "Backbone-style";
   * it's been extracted
   *
   * options:
   *  - model: CRM.UF.UFGroupModel
   */
  CRM.Designer.DesignerDialog = Backbone.Marionette.Layout.extend({
    serializeData: extendedSerializeData,
    template: '#designer_dialog_template',
    className: 'crm-designer-dialog',
    regions: {
      designerRegion: '.crm-designer'
    },
    /** @var bool whether this dialog is currently open */
    isDialogOpen: false,
    /** @var bool whether any changes have been made */
    isUfUnsaved: false,
    /** @var obj handle for the CRM.alert containing undo link */
    undoAlert: null,
    /** @var bool whether this dialog is being re-opened by the undo link */
    undoState: false,

    initialize: function(options) {
      CRM.designerApp.vent.on('ufUnsaved', this.onUfChanged, this);
      CRM.designerApp.vent.on('ufSaved', this.onUfSaved, this);
    },
    onClose: function() {
      this.undoAlert && this.undoAlert.close && this.undoAlert.close();
      CRM.designerApp.vent.off('ufUnsaved', this.onUfChanged, this);
    },
    onUfChanged: function(isUfUnsaved) {
      this.isUfUnsaved = isUfUnsaved;
    },
    onUfSaved: function() {
      CRM.designerApp.vent.off('ufUnsaved', this.onUfChanged, this);
      this.isUfUnsaved = false;
    },
    onRender: function() {
      var designerDialog = this;
      designerDialog.$el.dialog({
        autoOpen: true, // note: affects accordion height
        title: ts('Edit Profile'),
        modal: true,
        width: '75%',
        height: parseInt($(window).height() *.8, 10),
        minWidth: 500,
        minHeight: 600, // to allow dropping in big whitespace, coordinate with min-height of .crm-designer-fields
        open: function() {
          // Prevent conflicts with other onbeforeunload handlers
          designerDialog.oldOnBeforeUnload = window.onbeforeunload;
          // Warn of unsaved changes when navigating away from the page
          window.onbeforeunload = function() {
            if (designerDialog.isDialogOpen && designerDialog.isUfUnsaved) {
              return ts("Your profile has not been saved.");
            }
            if (designerDialog.oldOnBeforeUnload) {
              return designerDialog.oldOnBeforeUnload.apply(arguments);
            }
          };
          designerDialog.undoAlert && designerDialog.undoAlert.close && designerDialog.undoAlert.close();
          designerDialog.isDialogOpen = true;
          // Initialize new dialog if we are not re-opening unsaved changes
          if (designerDialog.undoState === false) {
            designerDialog.designerRegion && designerDialog.designerRegion.close && designerDialog.designerRegion.close();
            designerDialog.$el.block();
            designerDialog.options.findCreateUfGroupModel({
              onLoad: function(ufGroupModel) {
                designerDialog.model = ufGroupModel;
                var designerLayout = new CRM.Designer.DesignerLayout({
                  model: ufGroupModel,
                  el: '<div class="full-height"></div>'
                });
                designerDialog.$el.unblock();
                designerDialog.designerRegion.show(designerLayout);
                CRM.designerApp.vent.trigger('resize');
                designerDialog.isUfUnsaved = false;
              }
            });
          }
          designerDialog.undoState = false;
          // CRM-12188
          CRM.designerApp.DetachedProfiles = [];
        },
        close: function() {
          window.onbeforeunload = designerDialog.oldOnBeforeUnload;
          designerDialog.isDialogOpen = false;

          designerDialog.undoAlert && designerDialog.undoAlert.close && designerDialog.undoAlert.close();
          if (designerDialog.isUfUnsaved) {
            designerDialog.undoAlert = CRM.alert('<p>' + ts('%1 has not been saved.', {1: designerDialog.model.get('title')}) + '</p><a href="#" class="crm-undo">' + ts('Restore') + '</a>', ts('Unsaved Changes'), 'alert', {expires: 60000});
            $('.ui-notify-message a.crm-undo').button({icons: {primary: 'ui-icon-arrowreturnthick-1-w'}}).click(function(e) {
              e.preventDefault();
              designerDialog.undoState = true;
              designerDialog.$el.dialog('open');
            });
          }
          // CRM-12188
          CRM.designerApp.restorePreviewArea();
        },
        resize: function() {
          CRM.designerApp.vent.trigger('resize');
        }
      });
    }
  });

  /**
   * Display a complete form-editing UI, including canvas, palette, and
   * buttons.
   *
   * options:
   *  - model: CRM.UF.UFGroupModel
   */
  CRM.Designer.DesignerLayout = Backbone.Marionette.Layout.extend({
    serializeData: extendedSerializeData,
    template: '#designer_template',
    regions: {
      buttons: '.crm-designer-buttonset-region',
      palette: '.crm-designer-palette-region',
      form: '.crm-designer-form-region',
      fields: '.crm-designer-fields-region'
    },
    initialize: function() {
      CRM.designerApp.vent.on('resize', this.onResize, this);
    },
    onClose: function() {
      CRM.designerApp.vent.off('resize', this.onResize, this);
    },
    onRender: function() {
      this.buttons.show(new CRM.Designer.ToolbarView({
        model: this.model
      }));
      this.palette.show(new CRM.Designer.PaletteView({
        model: this.model
      }));
      this.form.show(new CRM.Designer.UFGroupView({
        model: this.model
      }));
      this.fields.show(new CRM.Designer.UFFieldCanvasView({
        model: this.model
      }));
    },
    onResize: function() {
      if (! this.hasResizedBefore) {
        this.hasResizedBefore = true;
        this.$('.crm-designer-toolbar').resizable({
          handles: 'w',
          maxWidth: 400,
          minWidth: 150,
          resize: function(event, ui) {
            $('.crm-designer-canvas').css('margin-right', (ui.size.width + 10) + 'px');
            $(this).css({left: '', height: ''});
          }
        }).css({left: '', height: ''});
      }
    }
  });

  /**
   * Display toolbar with working button
   *
   * options:
   *  - model: CRM.UF.UFGroupModel
   */
  CRM.Designer.ToolbarView = Backbone.Marionette.ItemView.extend({
    serializeData: extendedSerializeData,
    template: '#designer_buttons_template',
    previewMode: false,
    events: {
      'click .crm-designer-save': 'doSave',
      'click .crm-designer-preview': 'doPreview'
    },
    onRender: function() {
      this.$('.crm-designer-save').button({icons: {primary: 'ui-icon-check'}}).attr({
        disabled: 'disabled',
        style: 'opacity:.5; cursor:default;'
      });
      this.$('.crm-designer-preview').button({icons: {primary: 'ui-icon-search'}});
    },
    initialize: function(options) {
      CRM.designerApp.vent.on('ufUnsaved', this.onUfChanged, this);
    },
    onUfChanged: function(isUfUnsaved) {
      if (isUfUnsaved) {
        this.$('.crm-designer-save').removeAttr('style').prop('disabled', false);
      }
    },
    doSave: function(e) {
      e.preventDefault();
      var ufGroupModel = this.model;
      if (ufGroupModel.getRel('ufFieldCollection').hasDuplicates()) {
        CRM.alert(ts('Please correct errors before saving.'), '', 'alert');
        return;
      }
      var $dialog = this.$el.closest('.crm-designer-dialog'); // FIXME use events
      $dialog.block();
      var profile = ufGroupModel.toStrictJSON();
      profile["api.UFField.replace"] = {values: ufGroupModel.getRel('ufFieldCollection').toSortedJSON(), 'option.autoweight': 0};
      CRM.api('UFGroup', 'create', profile, {
        success: function(data) {
          $dialog.unblock();
          var error = false;
          if (data.is_error) {
            CRM.alert(data.error_message);
            error = true;
          }
          _.each(data.values, function(ufGroupResponse) {
            if (ufGroupResponse['api.UFField.replace'].is_error) {
              CRM.alert(ufGroupResponse['api.UFField.replace'].error_message);
              error = true;
            }
          });
          if (!error) {
            if (!ufGroupModel.get('id')) {
              ufGroupModel.set('id', data.id);
            }
            CRM.designerApp.vent.trigger('ufUnsaved', false);
            CRM.designerApp.vent.trigger('ufSaved');
            $dialog.dialog('close');
          }
        }
      });
    },
    doPreview: function(e) {
      e.preventDefault();
      this.previewMode = !this.previewMode;
      if (!this.previewMode) {
        $('.crm-designer-preview-canvas').html('');
        $('.crm-designer-canvas > *, .crm-designer-palette-region').show();
        $('.crm-designer-preview').button('option', {icons: {primary: 'ui-icon-search'}}).find('span').text(ts('Preview'));;
        return;
      }
      if (this.model.getRel('ufFieldCollection').hasDuplicates()) {
        CRM.alert(ts('Please correct errors before previewing.'), '', 'alert');
        return;
      }
      var $dialog = this.$el.closest('.crm-designer-dialog'); // FIXME use events
      $dialog.block();
      // CRM-12188
      CRM.designerApp.clearPreviewArea();
      $.post(CRM.url("civicrm/ajax/inline"), {
        'qfKey': CRM.profilePreviewKey,
        'class_name': 'CRM_UF_Form_Inline_Preview',
        'snippet': 1,
        'ufData': JSON.stringify({
          ufGroup: this.model.toStrictJSON(),
          ufFieldCollection: this.model.getRel('ufFieldCollection').toSortedJSON()
        })
      }).done(function(data) {
        $dialog.unblock();
        $('.crm-designer-canvas > *, .crm-designer-palette-region').hide();
        $('.crm-designer-preview-canvas').html(data).show().trigger('crmLoad').find(':input').prop('readOnly', true);
        $('.crm-designer-preview').button('option', {icons: {primary: 'ui-icon-pencil'}}).find('span').text(ts('Edit'));
      });
    }
  });

  /**
   * Display a selection of available fields
   *
   * options:
   *  - model: CRM.UF.UFGroupModel
   */
  CRM.Designer.PaletteView = Backbone.Marionette.ItemView.extend({
    serializeData: extendedSerializeData,
    template: '#palette_template',
    el: '<div class="full-height"></div>',
    openTreeNodes: [],
    events: {
      'keyup .crm-designer-palette-search input': 'doSearch',
      'change .crm-contact-types': 'doSetPaletteEntity',
      'click .crm-designer-palette-clear-search': 'clearSearch',
      'click .crm-designer-palette-toggle': 'toggleAll',
      'click .crm-designer-palette-add button': 'doNewCustomFieldDialog',
      'click #crm-designer-add-custom-set': 'doNewCustomSetDialog',
      'dblclick .crm-designer-palette-field': 'doAddToCanvas'
    },
    initialize: function() {
      this.model.getRel('ufFieldCollection')
        .on('add', this.toggleActive, this)
        .on('remove', this.toggleActive, this);
      this.model.getRel('paletteFieldCollection')
        .on('reset', this.render, this);
      CRM.designerApp.vent.on('resize', this.onResize, this);
    },
    onClose: function() {
      this.model.getRel('ufFieldCollection')
        .off('add', this.toggleActive, this)
        .off('remove', this.toggleActive, this);
      this.model.getRel('paletteFieldCollection')
        .off('reset', this.render, this);
      CRM.designerApp.vent.off('resize', this.onResize, this);
    },
    onRender: function() {
      var paletteView = this;

      // Prepare data for jstree
      var treeData = [];
      var paletteFieldsByEntitySection = this.model.getRel('paletteFieldCollection').getFieldsByEntitySection();

      paletteView.model.getRel('ufEntityCollection').each(function(ufEntityModel){
        _.each(ufEntityModel.getSections(), function(section, sectionKey){
          var defaultValue = paletteView.selectedContactType;
          if (!defaultValue) {
            defaultValue = paletteView.model.calculateContactEntityType();
          }

          // set selected option as default, since we are rebuilding palette
          paletteView.$('.crm-contact-types').val(defaultValue).prop('selected','selected');

          var entitySection = ufEntityModel.get('entity_name') + '-' + sectionKey;
          var items = [];
          if (paletteFieldsByEntitySection[entitySection]) {
            _.each(paletteFieldsByEntitySection[entitySection], function(paletteFieldModel, k) {
              items.push({data: paletteFieldModel.getLabel(), attr: {'class': 'crm-designer-palette-field', 'data-plm-cid': paletteFieldModel.cid}});
            });
          }
          if (section.is_addable) {
            items.push({data: ts('+ Add New Field'), attr: {'class': 'crm-designer-palette-add'}});
          }
          if (items.length > 0) {
            treeData.push({
              data: section.title,
              children: items,
              state: _.contains(paletteView.openTreeNodes, sectionKey) ? 'open' : 'closed',
              attr: {
                'class': 'crm-designer-palette-section',
                'data-section': sectionKey,
                'data-entity': ufEntityModel.get('entity_name')
              }
            });
          }
        })
      });

      this.$('.crm-designer-palette-tree').jstree({
        'json_data': {data: treeData},
        'search': {
          'case_insensitive' : true,
          'show_only_matches': true
        },
        themes: {
          "theme": 'classic',
          "dots": false,
          "icons": false,
          "url": CRM.config.resourceBase + 'packages/jquery/plugins/jstree/themes/classic/style.css'
        },
        'plugins': ['themes', 'json_data', 'ui', 'search']
      }).bind('loaded.jstree', function () {
        $('.crm-designer-palette-field', this).draggable({
          appendTo: '.crm-designer',
          zIndex: $(this.$el).zIndex() + 5000,
          helper: 'clone',
          connectToSortable: '.crm-designer-fields' // FIXME: tight canvas/palette coupling
        });
        paletteView.model.getRel('ufFieldCollection').each(function(ufFieldModel) {
          paletteView.toggleActive(ufFieldModel, paletteView.model.getRel('ufFieldCollection'))
        });
        paletteView.$('.crm-designer-palette-add a').replaceWith('<button>' + $('.crm-designer-palette-add a').first().text() + '</<button>');
        paletteView.$('.crm-designer-palette-tree > ul').append('<li><button id="crm-designer-add-custom-set">+ ' + ts('Add Set of Custom Fields') + '</button></li>');
        paletteView.$('.crm-designer-palette-tree button').button();
      }).bind("select_node.jstree", function (e, data) {
        $(this).jstree("toggle_node", data.rslt.obj);
        $(this).jstree("deselect_node", data.rslt.obj);
      });

      // FIXME: tight canvas/palette coupling
      this.$(".crm-designer-fields").droppable({
        activeClass: "ui-state-default",
        hoverClass: "ui-state-hover",
        accept: ":not(.ui-sortable-helper)"
      });

      this.onResize();
    },
    onResize: function() {
      var pos = this.$('.crm-designer-palette-tree').position();
      var div = this.$('.crm-designer-palette-tree').closest('.crm-container').height();
      this.$('.crm-designer-palette-tree').css({height: div - pos.top});
    },
    doSearch: function(e) {
      var str = $(e.target).val();
      this.$('.crm-designer-palette-clear-search').css('visibility', str ? 'visible' : 'hidden');
      this.$('.crm-designer-palette-tree').jstree("search", str);
    },
    doSetPaletteEntity: function(event) {
      this.selectedContactType = $('.crm-contact-types :selected').val();
      // loop through entity collection and remove non-valid entity section's
      var newUfEntityModels = [];
      this.model.getRel('ufEntityCollection').each(function(oldUfEntityModel){
        var values = oldUfEntityModel.toJSON();
        if (values.entity_name == 'contact_1') {
          values.entity_type = $('.crm-contact-types :selected').val();
        }
        newUfEntityModels.push(new CRM.UF.UFEntityModel(values));
      });
      this.model.getRel('ufEntityCollection').reset(newUfEntityModels);
    },
    doAddToCanvas: function(event) {
      var paletteFieldModel = this.model.getRel('paletteFieldCollection').get($(event.currentTarget).attr('data-plm-cid'));
      paletteFieldModel.addToUFCollection(this.model.getRel('ufFieldCollection'));
      event.stopPropagation();
    },
    doNewCustomFieldDialog: function(e) {
      e.preventDefault();
      var paletteView = this;
      var entityKey = $(e.currentTarget).closest('.crm-designer-palette-section').attr('data-entity');
      var sectionKey = $(e.currentTarget).closest('.crm-designer-palette-section').attr('data-section');
      var ufEntityModel = paletteView.model.getRel('ufEntityCollection').getByName(entityKey);
      var sections = ufEntityModel.getSections();
      var url = CRM.url('civicrm/admin/custom/group/field/add', {
        reset: 1,
        action: 'add',
        gid: sections[sectionKey].custom_group_id
      });
      CRM.loadForm(url).on('crmFormSuccess', function(e, data) {
        paletteView.doRefresh('custom_' + data.id);
      });
    },
    doNewCustomSetDialog: function(e) {
      e.preventDefault();
      var paletteView = this;
      var url = CRM.url('civicrm/admin/custom/group', 'action=add&reset=1');
      // Create custom field set and automatically go to next step (create fields) after save button is clicked.
      CRM.loadForm(url, {refreshAction: ['next']})
        .on('crmFormSuccess', function(e, data) {
          // When form switches to create custom field context, modify button behavior to only continue for "save and new"
          data.customField && ($(this).data('civiCrmSnippet').options.crmForm.refreshAction = ['next_new']);
          paletteView.doRefresh(data.customField ? 'custom_' + data.id : null);
        });
    },
    doRefresh: function(fieldToAdd) {
      var ufGroupModel = this.model;
      this.getOpenTreeNodes();
      CRM.Schema.reloadModels()
        .done(function(data){
          ufGroupModel.resetEntities();
          if (fieldToAdd) {
            var field = ufGroupModel.getRel('paletteFieldCollection').getFieldByName(null, fieldToAdd);
            field.addToUFCollection(ufGroupModel.getRel('ufFieldCollection'));
          }
        })
        .fail(function() {
          CRM.alert(ts('Failed to retrieve schema'), ts('Error'), 'error');
        });
    },
    clearSearch: function(e) {
      e.preventDefault();
      $('.crm-designer-palette-search input').val('').keyup();
    },
    toggleActive: function(ufFieldModel, ufFieldCollection, options) {
      var paletteFieldCollection = this.model.getRel('paletteFieldCollection');
      var paletteFieldModel = paletteFieldCollection.getFieldByName(ufFieldModel.get('entity_name'), ufFieldModel.get('field_name'));
      var isAddable = ufFieldCollection.isAddable(ufFieldModel);
      if (paletteFieldModel) {
        this.$('[data-plm-cid='+paletteFieldModel.cid+']').toggleClass('disabled', !isAddable);
      }
    },
    toggleAll: function(e) {
      if ($('.crm-designer-palette-search input').val() == '') {
        $('.crm-designer-palette-tree').jstree($(e.target).attr('rel'));
      }
      e.preventDefault();
    },
    getOpenTreeNodes: function() {
      var paletteView = this;
      this.openTreeNodes = [];
      this.$('.crm-designer-palette-section.jstree-open').each(function() {
        paletteView.openTreeNodes.push($(this).data('section'));
      })
    }
  });

  /**
   * Display all UFFieldModel objects in a UFGroupModel.
   *
   * options:
   *  - model: CRM.UF.UFGroupModel
   */
  CRM.Designer.UFFieldCanvasView = Backbone.Marionette.View.extend({
    initialize: function() {
      this.model.getRel('ufFieldCollection')
        .on('add', this.updatePlaceholder, this)
        .on('remove', this.updatePlaceholder, this)
        .on('add', this.addUFFieldView, this)
        .on('reset', this.render, this);
    },
    onClose: function() {
      this.model.getRel('ufFieldCollection')
        .off('add', this.updatePlaceholder, this)
        .off('remove', this.updatePlaceholder, this)
        .off('add', this.addUFFieldView, this)
        .off('reset', this.render, this);
    },
    render: function() {
      var ufFieldCanvasView = this;
      this.$el.html(_.template($('#field_canvas_view_template').html()));

      // BOTTOM: Setup field-level editing
      var $fields = this.$('.crm-designer-fields');
      this.updatePlaceholder();
      var ufFieldModels = this.model.getRel('ufFieldCollection').sortBy(function(ufFieldModel) {
        return parseInt(ufFieldModel.get('weight'));
      });
      _.each(ufFieldModels, function(ufFieldModel) {
        ufFieldCanvasView.addUFFieldView(ufFieldModel, ufFieldCanvasView.model.getRel('ufFieldCollection'), {skipWeights: true});
      });
      this.$(".crm-designer-fields").sortable({
        placeholder: 'crm-designer-row-placeholder',
        forcePlaceholderSize: true,
        receive: function(event, ui) {
          var paletteFieldModel = ufFieldCanvasView.model.getRel('paletteFieldCollection').get(ui.item.attr('data-plm-cid'));
          var ufFieldModel = paletteFieldModel.addToUFCollection(
            ufFieldCanvasView.model.getRel('ufFieldCollection'),
            {skipWeights: true}
          );
          if (null == ufFieldModel) {
            ufFieldCanvasView.$('.crm-designer-fields .ui-draggable').remove();
          } else {
            // Move from end to the 'dropped' position
            var ufFieldViewEl = ufFieldCanvasView.$('div[data-field-cid='+ufFieldModel.cid+']').parent();
            ufFieldCanvasView.$('.crm-designer-fields .ui-draggable').replaceWith(ufFieldViewEl);
          }
          // note: the sortable() update callback will call updateWeight
        },
        update: function() {
          ufFieldCanvasView.updateWeights();
        }
      });
    },
    /** Determine visual order of fields and set the model values for "weight" */
    updateWeights: function() {
      var ufFieldCanvasView = this;
      var weight = 1;
      var rows = this.$('.crm-designer-row').each(function(key, row) {
        if ($(row).hasClass('placeholder')) {
          return;
        }
        var ufFieldCid = $(row).attr('data-field-cid');
        var ufFieldModel = ufFieldCanvasView.model.getRel('ufFieldCollection').get(ufFieldCid);
        ufFieldModel.set('weight', weight);
        weight++;
      });
    },
    addUFFieldView: function(ufFieldModel, ufFieldCollection, options) {
      var paletteFieldModel = this.model.getRel('paletteFieldCollection').getFieldByName(ufFieldModel.get('entity_name'), ufFieldModel.get('field_name'));
      var ufFieldView = new CRM.Designer.UFFieldView({
        el: $("<div></div>"),
        model: ufFieldModel,
        paletteFieldModel: paletteFieldModel
      });
      ufFieldView.render();
      this.$('.crm-designer-fields').append(ufFieldView.$el);
      if (! (options && options.skipWeights)) {
        this.updateWeights();
      }
    },
    updatePlaceholder: function() {
      if (this.model.getRel('ufFieldCollection').isEmpty()) {
        this.$('.placeholder').css({display: 'block', border: '0 none', cursor: 'default'});
      } else {
        this.$('.placeholder').hide();
      }
    }
  });

  /**
   * options:
   * - model: CRM.UF.UFFieldModel
   * - paletteFieldModel: CRM.Designer.PaletteFieldModel
   */
  CRM.Designer.UFFieldView = Backbone.Marionette.Layout.extend({
    serializeData: extendedSerializeData,
    template: '#field_row_template',
    expanded: false,
    regions: {
      summary: '.crm-designer-field-summary',
      detail: '.crm-designer-field-detail'
    },
    events: {
      "click .crm-designer-action-settings": 'doToggleForm',
      "click button.crm-designer-edit-custom": 'doEditCustomField',
      "click .crm-designer-action-remove": 'doRemove'
    },
    modelEvents: {
      "destroy": 'remove',
      "change:is_duplicate": 'onChangeIsDuplicate'
    },
    onRender: function() {
      this.summary.show(new CRM.Designer.UFFieldSummaryView({
        model: this.model,
        fieldSchema: this.model.getFieldSchema(),
        paletteFieldModel: this.options.paletteFieldModel
      }));
      this.detail.show(new CRM.Designer.UFFieldDetailView({
        model: this.model,
        fieldSchema: this.model.getFieldSchema()
      }));
      this.onChangeIsDuplicate(this.model, this.model.get('is_duplicate'))
      if (!this.expanded) {
        this.detail.$el.hide();
      }
      var that = this;
      CRM.designerApp.vent.on('formOpened', function(event) {
        if (that.expanded && event != that.cid) {
          that.doToggleForm(false);
        }
      });
    },
    doToggleForm: function(event) {
      this.expanded = !this.expanded;
      if (this.expanded && event !== false) {
        CRM.designerApp.vent.trigger('formOpened', this.cid);
      }
      this.$el.toggleClass('crm-designer-open', this.expanded);
      var $detail = this.detail.$el;
      if (!this.expanded) {
        $detail.toggle('blind', 250);
        this.$('button.crm-designer-edit-custom').remove();
      }
      else {
        var $canvas = $('.crm-designer-canvas');
        var top = $canvas.offset().top;
        $detail.slideDown({
          duration: 250,
          step: function(num, effect) {
            // Scroll canvas to keep field details visible
            if (effect.prop == 'height') {
              if (effect.now + $detail.offset().top - top > $canvas.height() - 9) {
                $canvas.scrollTop($canvas.scrollTop() + effect.now + $detail.offset().top - top - $canvas.height() + 9);
              }
            }
          }
        });
        if (this.model.get('field_name').split('_')[0] == 'custom') {
          this.$('.crm-designer-field-summary > div').append('<button class="crm-designer-edit-custom">&raquo; ' + ts('Edit Custom Field') + '</button>');
          this.$('button.crm-designer-edit-custom').button().attr('title', ts('Edit global settings for this custom field.'));
        }
      }
    },
    doEditCustomField: function(e) {
      e.preventDefault();
      var url = CRM.url('civicrm/admin/custom/group/field/update', {
        action: 'update',
        reset: 1,
        id: this.model.get('field_name').split('_')[1]
      });
      var form1 = CRM.loadForm(url, {openInline: '.crm-custom-field-form-block-data_type a'})
        .on('crmFormLoad', function() {
          $(this).prepend('<div class="messages status"><div class="icon inform-icon"></div>' + ts('Note: This will modify the field system-wide, not just in this profile form.') + '</div>');
          var $link = $('.action-link a', this);
          if ($link.length) {
            $link.detach();
            var buttons = {};
            buttons[$link.text()] = function() {
              var form2 = CRM.loadForm($link.attr('href'), {
                dialog: {
                  width: '60%',
                  height: '70%'
                }
              });
            };
            $(this).dialog('option', 'buttons', buttons);
          }
        });
    },
    onChangeIsDuplicate: function(model, value, options) {
      this.$el.toggleClass('crm-designer-duplicate', value);
    },
    doRemove: function(event) {
      var that = this;
      this.$el.hide(250, function() {
        that.model.destroyLocal();
      });
    }
  });

  /**
   * options:
   * - model: CRM.UF.UFFieldModel
   * - fieldSchema: (Backbone.Form schema element)
   * - paletteFieldModel: CRM.Designer.PaletteFieldModel
   */
  CRM.Designer.UFFieldSummaryView = Backbone.Marionette.ItemView.extend({
    serializeData: extendedSerializeData,
    template: '#field_summary_template',
    modelEvents: {
      'change': 'render'
    },

    /**
     * Compose a printable string which describes the binding of this UFField to the data model
     * @return {String}
     */
    getBindingLabel: function() {
      var result = this.options.paletteFieldModel.getSection().title + ": " + this.options.paletteFieldModel.getLabel();
      if (this.options.fieldSchema.civiIsPhone) {
        result = result + '-' + CRM.PseudoConstant.phoneType[this.model.get('phone_type_id')];
      }
      if (this.options.fieldSchema.civiIsLocation) {
        var locType = this.model.get('location_type_id') ? CRM.PseudoConstant.locationType[this.model.get('location_type_id')] : ts('Primary');
        result = result + ' (' + locType + ')';
      }
      return result;
    },

    /**
     * Return a string marking if the field is required
     * @return {String}
     */
    getRequiredMarker: function() {
      if (this.model.get('is_required') == 1) {
        return ' <span class="crm-marker">*</span> ';
      }
      return '';
    },

    onRender: function() {
      this.$el.toggleClass('disabled', this.model.get('is_active') != 1);
      if (this.model.get("is_reserved") == 1) {
        this.$('.crm-designer-buttons').hide();
      }
    }
  });

  /**
   * options:
   * - model: CRM.UF.UFFieldModel
   * - fieldSchema: (Backbone.Form schema element)
   */
  CRM.Designer.UFFieldDetailView = Backbone.View.extend({
    initialize: function() {
      // FIXME: hide/display 'in_selector' if 'visibility' is one of the public options
      var fields = ['location_type_id', 'phone_type_id', 'label', 'is_multi_summary', 'is_required', 'is_view', 'visibility', 'in_selector', 'is_searchable', 'help_pre', 'help_post', 'is_active'];
      if (! this.options.fieldSchema.civiIsLocation) {
        fields = _.without(fields, 'location_type_id');
      }
      if (! this.options.fieldSchema.civiIsPhone) {
        fields = _.without(fields, 'phone_type_id');
      }
      if (!this.options.fieldSchema.civiIsMultiple) {
        fields = _.without(fields, 'is_multi_summary');
      }

      this.form = new Backbone.Form({
        model: this.model,
        fields: fields
      });
      this.form.on('change', this.onFormChange, this);
      this.model.on('change', this.onModelChange, this);
    },
    render: function() {
      this.$el.html(this.form.render().el);
      this.onFormChange();
    },
    onModelChange: function() {
      $.each(this.form.fields, function(i, field) {
        this.form.setValue(field.key, this.model.get(field.key));
      });
    },
    onFormChange: function() {
      this.form.commit();
      this.$('.field-is_multi_summary').toggle(this.options.fieldSchema.civiIsMultiple ? true : false);
      this.$('.field-in_selector').toggle(this.model.isInSelectorAllowed());

      if (!this.model.isInSelectorAllowed() && this.model.get('in_selector') != "0") {
        this.model.set('in_selector', "0");
        this.form.setValue('in_selector', "0");
        // TODO: It might be nicer if we didn't completely discard in_selector -- e.g.
        // if the value could be restored when the user isInSelectorAllowed becomes true
        // again. However, I haven't found a simple way to do this.
      }
    }
  });

  /**
   * options:
   * - model: CRM.UF.UFGroupModel
   */
  CRM.Designer.UFGroupView = Backbone.Marionette.Layout.extend({
    serializeData: extendedSerializeData,
    template: '#form_row_template',
    expanded: false,
    regions: {
      summary: '.crm-designer-form-summary',
      detail: '.crm-designer-form-detail'
    },
    events: {
      "click .crm-designer-action-settings": 'doToggleForm'
    },
    onRender: function() {
      this.summary.show(new CRM.Designer.UFGroupSummaryView({
        model: this.model
      }));
      this.detail.show(new CRM.Designer.UFGroupDetailView({
        model: this.model
      }));
      if (!this.expanded) {
        this.detail.$el.hide();
      }
      var that = this;
      CRM.designerApp.vent.on('formOpened', function(event) {
        if (that.expanded && event !== 0) {
          that.doToggleForm(false);
        }
      });
    },
    doToggleForm: function(event) {
      this.expanded = !this.expanded;
      if (this.expanded && event !== false) {
        CRM.designerApp.vent.trigger('formOpened', 0);
      }
      this.$el.toggleClass('crm-designer-open', this.expanded);
      this.detail.$el.toggle('blind', 250);
    }
  });

  /**
   * options:
   * - model: CRM.UF.UFGroupModel
   */
  CRM.Designer.UFGroupSummaryView = Backbone.Marionette.ItemView.extend({
    serializeData: extendedSerializeData,
    template: '#form_summary_template',
    modelEvents: {
      'change': 'render'
    },
    onRender: function() {
      this.$el.toggleClass('disabled', this.model.get('is_active') != 1);
      if (this.model.get("is_reserved") == 1) {
        this.$('.crm-designer-buttons').hide();
      }
    }
  });

  /**
   * options:
   * - model: CRM.UF.UFGroupModel
   */
  CRM.Designer.UFGroupDetailView = Backbone.View.extend({
    initialize: function() {
      this.form = new Backbone.Form({
        model: this.model,
        fields: ['title', 'help_pre', 'help_post', 'is_active']
      });
      this.form.on('change', this.form.commit, this.form);
    },
    render: function() {
      this.$el.html(this.form.render().el);
    }
  });

})(CRM.$, CRM._);
