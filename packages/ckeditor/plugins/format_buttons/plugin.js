/*
* Youtube Embed Plugin
*
* @author Jonnas Fonini <contato@fonini.net>
* @version 1.0.10
*/
( function() {

	"use strict";
	var pluginName = "format_buttons";

	CKEDITOR.plugins.add(pluginName, {

		lang: 'en,nl',
		icons: 'h1,h2,h3,h4,h5,h6',
		hidpi: true,

		init: function( editor ) {

		var order = 0;
		// All buttons use the same code to register. So, to avoid
		// duplications, let's use this tool function.
		var addButtonCommand = function( buttonName, buttonLabel, commandName, styleDefiniton ) {
			// Disable the command if no definition is configured.
			if ( !styleDefiniton )
				return;

			var style = new CKEDITOR.style( styleDefiniton ),
				forms = contentForms[ commandName ];

			// Put the style as the most important form.
			forms.unshift( style );

			// Listen to contextual style activation.
			editor.attachStyleStateChange( style, function( state ) {
				!editor.readOnly && editor.getCommand( commandName ).setState( state );
			} );

			// Create the command that can be used to apply the style.
			editor.addCommand( commandName, new CKEDITOR.styleCommand( style, {
				contentForms: forms
			} ) );

			// Register the button, if the button plugin is loaded.
			if ( editor.ui.addButton ) {
				editor.ui.addButton( buttonName, {
					label: buttonLabel,
					command: commandName,
					toolbar: 'basicstyles,' + ( order += 10 )
				} );
			}
		};

		var contentForms = {
				h1: ['h1'],
				h2: ['h2'],
				h3: ['h3'],
				h4: ['h4'],
				h5: ['h5'],
				h6: ['h6'],
			},
			config = editor.config,
			lang = editor.lang.format_buttons;

			addButtonCommand( 'h1', lang.h1, 'h1', config.h1 );
			addButtonCommand( 'h2', lang.h2, 'h2', config.h2 );
			addButtonCommand( 'h3', lang.h3, 'h3', config.h3 );
			addButtonCommand( 'h4', lang.h4, 'h4', config.h4 );
			addButtonCommand( 'h5', lang.h5, 'h5', config.h5 );
			addButtonCommand( 'h6', lang.h6, 'h6', config.h6 );

		}

  });


})();


// Basic Styles.
CKEDITOR.config.h1 = { element: 'h1' };
CKEDITOR.config.h2 = { element: 'h2' };
CKEDITOR.config.h3 = { element: 'h3' };
CKEDITOR.config.h4 = { element: 'h4' };
CKEDITOR.config.h5 = { element: 'h5' };
CKEDITOR.config.h6 = { element: 'h6' };
