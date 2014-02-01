jQuery(document).ready(function(e) {
	var resource_splitter = "::";

	jQuery("#admin-resource-add, #admin-resource-edit").on('keyup', 'input#module, input#controller', setName);

	function setName() {
		var module = jQuery("#admin-resource-add, #admin-resource-edit").find('input#module').val();
		var controller = jQuery("#admin-resource-add, #admin-resource-edit").find('input#controller').val();

		jQuery("#admin-resource-add, #admin-resource-edit").find('input#name').val(module + folderSplitter(controller));
	}

	function folderSplitter(name) {
		if (name !== "" || name == 'underfined') {
			return resource_splitter + name;
		} else {
			return "";
		}
	}
});