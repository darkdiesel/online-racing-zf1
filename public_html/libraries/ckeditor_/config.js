/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function(config) {
	// Define changes to default configuration here.
	// For the complete reference:
	// http://docs.ckeditor.com/#!/api/CKEDITOR.config
    // config.language = 'fr';
    // config.uiColor = '#AADC6E';

    // The toolbar groups arrangement, optimized for two toolbar rows.
    /*config.toolbar_chat = [
        ['Source', 'Cut', 'Copy', 'Smiley'],
    ];*/

	// The toolbar groups arrangement, optimized for two toolbar rows.
	config.toolbarGroups = [
		{ name: 'clipboard',   groups: [ 'clipboard', 'undo' ] },
		{ name: 'editing',     groups: [ 'find', 'selection', 'spellchecker' ] },
		{ name: 'links' },
		{ name: 'insert' },
		{ name: 'forms' },
		{ name: 'tools' },
		{ name: 'document',	   groups: [ 'mode', 'document', 'doctools' ] },
		{ name: 'others' },
		'/',
		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
		{ name: 'paragraph',   groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ] },
		{ name: 'styles' },
		{ name: 'colors' },
		{ name: 'about' }


	];

	//config.toolbar = 'chat';



	// Remove some buttons, provided by the standard plugins, which we don't
	// need to have in the Standard(s) toolbar.
	config.removeButtons = 'Underline,Subscript,Superscript';
	
	// Se the most common block elements.
	config.format_tags = 'p;h1;h2;h3;pre';

	// Make dialogs simpler.
	config.removeDialogTabs = 'image:advanced;link:advanced';

    //config.removePlugins = 'iframe';
    config.extraPlugins = 'oembed';
    config.oembed_ShowIframePreview = true;

    //config for wordcount plugin
    config.wordcount = {
        showWordCount: true,
        showCharCount: true
    };

    //config for smiley plugin
    config.smiley_columns = 6;
    config.smiley_path = 'http://online-racing.net/img/smileys/';
    config.smiley_images =
            [
                'blum1.gif', 'biggrin.gif', 'smile.gif', 'sad.gif'
            ];
    config.smiley_descriptions =
            [
                'blum', 'biggrin', 'smiley', 'sad'
            ];
};
