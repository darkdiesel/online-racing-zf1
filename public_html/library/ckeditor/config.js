/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';

	config.skin = 'moonocolor';
	
    config.extraPlugins = 'oembed';
    config.allowedContent = true;
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
