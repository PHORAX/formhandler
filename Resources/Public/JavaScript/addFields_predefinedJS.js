/*
 * Adds onchange listener on the drop down menu "predefined".
 * If the event is fired and old value was ".default", then empty some fields.
 *
 * $Id: addFields_predefinedJS.js 20791 2009-05-28 07:53:36Z reinhardfuehricht $
 */

Event.observe(window, 'load', function() {

	var requiredFieldsDefault = 'firstname, lastname, email';
	var langFileDefault = 'EXT:formhandler/Examples/Default/lang.xml';
	var templateFileDefault = 'EXT:formhandler/Examples/Default/template.html';

	var templateFileName = 'data[tt_content][' + uid + '][pi_flexform][data][sDEF][lDEF][template_file][vDEF]_list';
	var langFileName = 'data[tt_content][' + uid + '][pi_flexform][data][sDEF][lDEF][lang_file][vDEF]_list';
	var predefinedName = 'data[tt_content][' + uid + '][pi_flexform][data][sDEF][lDEF][predefined][vDEF]';
	var requiredFieldsName = 'data[tt_content][' + uid + '][pi_flexform][data][sMISC][lDEF][required_fields][vDEF]_hr';
	var templateFileHiddenName = 'data[tt_content][' + uid + '][pi_flexform][data][sDEF][lDEF][template_file][vDEF]';
	var langFileHiddenName = 'data[tt_content][' + uid + '][pi_flexform][data][sDEF][lDEF][lang_file][vDEF]';
	var requiredFieldsHiddenName = 'data[tt_content][' + uid + '][pi_flexform][data][sMISC][lDEF][required_fields][vDEF]';
	// Initializes variables
	var templateFile, langFile, predefined, requiredFields, templateFileHidden, langFileHidden, requiredFieldsHidden;

	// Searches <select> reference
	var elements = $$('#' + flexformBoxId + ' select'); // this code has been added for compatibility reasons
	if (elements.length == 0) {
		elements = $$(flexformBoxId + ' select');
	}
	elements.each(function(element){
		switch(element.readAttribute('name')) {
			case templateFileName :
				templateFile = element;
				break;
			case langFileName :
				langFile = element;
				break;
			case predefinedName :
				predefined = element;
				break;
			default:
				break;
		}
	});

	// Searches <input> reference
	elements = $$('#' + flexformBoxId + ' input');
	if (elements.length == 0) {
		elements = $$(flexformBoxId + ' input');
	}
	elements.each(function(element){
		switch(element.readAttribute('name')) {
			case requiredFieldsName :
				requiredFields = element;
				break;
			case templateFileHiddenName :
				templateFileHidden = element;
				break;
			case langFileHiddenName :
				langFileHidden = element;
				break;
			case requiredFieldsHiddenName :
				requiredFieldsHidden = element;
				break;
			default:
				break;
		}
	});

	// Tries to set default value. Handy for newbie user
	if (newRecord) {
		var options = predefined.options;
		for (var index = 0; index < options.length; index ++) {
			if (options[index].value == 'default.') {
				predefined.value = 'default.';
				requiredFields.value = requiredFieldsHidden.value = requiredFieldsDefault;
				templateFileHidden.value = templateFileDefault;
				langFileHidden.value = langFileDefault;
				$(templateFile).insert('<option value="' + templateFileDefault + '">' + templateFileDefault + '</option>');
				$(langFile).insert('<option value="' + langFileDefault + '">' + langFileDefault + '</option>');
				break;
			}
		}
	}

	// Handles the even change
	Event.observe(predefined, 'change', function(){
		if (this.value != 'default.') {
			if (typeof(templateFile.options[0]) != 'undefined' && templateFile.options[0].value == templateFileDefault) {
				templateFile.removeChild(templateFile.options[0]);
				templateFileHidden.value = '';
			}
			if (typeof(langFile.options[0]) != 'undefined' && langFile.options[0].value == langFileDefault) {
				langFile.removeChild(langFile.options[0]);
				langFileHidden.value = '';
			}
			if (requiredFields.value == requiredFieldsDefault) {
				requiredFields.value = '';
				requiredFieldsHidden.value = '';
			}
		}
	});
});
