jQuery(document).ready(function(e) {
    jQuery(".form-resource").on('keyup', 'input#module, input#controller, input#action', setName);

    function setName() {
	var module = jQuery(".form-resource").find('input#module').val();
	var controller = jQuery(".form-resource").find('input#controller').val();
	var action = jQuery(".form-resource").find('input#action').val();

	jQuery(".form-resource").find('input#name').val(module + folderSplitter(controller) + folderSplitter(action));
    }

    function folderSplitter(name) {
	if (name !== "" || name == 'underfined') {
	    return "/" + name;
	} else {
	    return "";
	}
    }
});