// https://civicrm.org/licensing
(function($, _) {
  function getInstance(item) {
    var name = $(item).attr("name");
    var id = $(item).attr("id");
    if (name && CKEDITOR.instances[name]) {
      return CKEDITOR.instances[name];
    }
    if (id && CKEDITOR.instances[id]) {
      return CKEDITOR.instances[id];
    }
  }
  CRM.wysiwyg.supportsFileUploads =  true;
  CRM.wysiwyg.create =  function(item) {
    var browseUrl = CRM.config.userFrameworkResourceURL + "packages/kcfinder/browse.php";
    var uploadUrl = CRM.config.userFrameworkResourceURL + "packages/kcfinder/upload.php";
    var editor = CKEDITOR.replace($(item)[0]);
    if (editor) {
      editor.config.filebrowserBrowseUrl = browseUrl+'?cms=civicrm&type=files';
      editor.config.filebrowserImageBrowseUrl = browseUrl+'?cms=civicrm&type=images';
      editor.config.filebrowserFlashBrowseUrl = browseUrl+'?cms=civicrm&type=flash';
      editor.config.filebrowserUploadUrl = uploadUrl+'?cms=civicrm&type=files';
      editor.config.filebrowserImageUploadUrl = uploadUrl+'?cms=civicrm&type=images';
      editor.config.filebrowserFlashUploadUrl = uploadUrl+'?cms=civicrm&type=flash';
      editor.on('blur', function() {
        editor.updateElement();
        $(item).trigger("blur");
      });
      editor.on('insertText', function() {
        $(item).trigger("keypress");
      });
      editor.on('pasteState', function() {
        $(item).trigger("paste");
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
      CRM.wysiwyg.insertIntoTextarea(item, text);
    }
  };
  CRM.wysiwyg.focus = function(item) {
    var editor = getInstance(item);
    if (editor) {
      editor.focus();
    }
  };

})(CRM.$, CRM._);
