// https://civicrm.org/licensing
(function($, _) {
  function getInstance(item) {
    var name = $(item).attr("name"),
      id = $(item).attr("id");
    if (name && CKEDITOR.instances[name]) {
      return CKEDITOR.instances[name];
    }
    if (id && CKEDITOR.instances[id]) {
      return CKEDITOR.instances[id];
    }
  }
  CRM.wysiwyg.supportsFileUploads =  true;
  CRM.wysiwyg.create = function(item) {
    var editor,
      browseUrl = CRM.config.userFrameworkResourceURL + "packages/kcfinder/browse.php?cms=civicrm",
      uploadUrl = CRM.config.userFrameworkResourceURL + "packages/kcfinder/upload.php?cms=civicrm";
    if ($(item).length) {
      editor = CKEDITOR.replace($(item)[0], {
        filebrowserBrowseUrl: browseUrl + '&type=files',
        filebrowserImageBrowseUrl: browseUrl + '&type=images',
        filebrowserFlashBrowseUrl: browseUrl + '&type=flash',
        filebrowserUploadUrl: uploadUrl + '&type=files',
        filebrowserImageUploadUrl: uploadUrl + '&type=images',
        filebrowserFlashUploadUrl: uploadUrl + '&type=flash',
        customConfig: CRM.config.CKEditorCustomConfig
      });
    }
    if (editor) {
      editor.on('focus', function() {
        $(item).trigger('focus');
      });
      editor.on('blur', function() {
        editor.updateElement();
        $(item).trigger("blur");
        $(item).trigger("change");
      });
      editor.on('insertText', function() {
        $(item).trigger("keypress");
      });
      editor.on('pasteState', function() {
        $(item).trigger("paste");
      });
      // Hide CiviCRM menubar when editor is fullscreen
      editor.on('maximize', function (e) {
        $('#civicrm-menu').toggle(e.data === 2);
      });
    }
  };
  CRM.wysiwyg.destroy = function(item) {
    var editor = getInstance(item);
    if (editor) {
      editor.destroy();
    }
  };
  CRM.wysiwyg.updateElement = function(item) {
    var editor = getInstance(item);
    if (editor) {
      editor.updateElement();
    }
  };
  CRM.wysiwyg.getVal = function(item) {
    var editor = getInstance(item);
    if (editor) {
      return editor.getData();
    } else {
      return $(item).val();
    }
  };
  CRM.wysiwyg.setVal = function(item, val) {
    var editor = getInstance(item);
    if (editor) {
      return editor.setData(val);
    } else {
      return $(item).val(val);
    }
  };
  CRM.wysiwyg.insert = function(item, text) {
    var editor = getInstance(item);
    if (editor) {
      editor.insertText(text);
    } else {
      CRM.wysiwyg._insertIntoTextarea(item, text);
    }
  };
  CRM.wysiwyg.focus = function(item) {
    var editor = getInstance(item);
    if (editor) {
      editor.focus();
    } else {
      $(item).focus();
    }
  };

})(CRM.$, CRM._);
