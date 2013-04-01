/*******************************************************************************
Create Date : 26/03/2013
----------------------------------------------------------------------
Plugin name : limitcharcount
Version : 1.0
Author : igor Peshkov
Description : Displaying limit characters count for enter for CKEditor V.4
Update Date : 26/03/2013
********************************************************************************/
CKEDITOR.plugins.add('limitcharcount', {
    lang: ['en'],
    init: function (editor) {
        editor.ui.addButton('limitcharcount', {
                label: editor.lang.limitcharcount.button,
                command: 'limitcharcount',
                icon: this.path + 'images/icon.png'
            });
    }
});