TYPO3.jQuery(function() {

	function getSelectedUids(el) {
		var selected = new Array();
		if(typeof el !== 'undefined' && el.attr("data-uid") && el.attr("data-uid").length > 0) {
			selected.push(el.attr("data-uid"));
		} else {
			TYPO3.jQuery('#formhandler-module .mark-row:checked').each(function() {
				selected.push(TYPO3.jQuery(this).attr("value"));
			});
		}
		return selected.join(",");
	}
	TYPO3.jQuery("#formhandler-module .filterForm .select-all-pages").on("click", function() {
		TYPO3.jQuery(this).closest('.input-group').find('INPUT.form-control').val("");
	});
	TYPO3.jQuery("#formhandler-module A.select-all").on("click", function(e) {
		e.preventDefault();
		var allCheckboxes = TYPO3.jQuery('#formhandler-module .mark-row');
		var activeCheckboxes = TYPO3.jQuery('#formhandler-module .mark-row:checked');
		if(allCheckboxes.length === activeCheckboxes.length) {
			allCheckboxes.prop("checked", false);
		} else {
			allCheckboxes.prop("checked", true);
		}
	});
	TYPO3.jQuery("#formhandler-module .process-selected-actions A.pdf").on("click", function(e) {
		e.preventDefault();
		var selectedUids = getSelectedUids();
		if(selectedUids.length > 0) {
			TYPO3.jQuery('#process-selected-form-export INPUT.filetype').attr("value", "pdf");
			TYPO3.jQuery('#process-selected-form-export INPUT.logDataUids').attr("value", selectedUids);
			TYPO3.jQuery('#process-selected-form-export').submit();
		}
	});
	TYPO3.jQuery("#formhandler-module .process-selected-actions A.csv").on("click", function(e) {
		e.preventDefault();
		var selectedUids = getSelectedUids();
		if(selectedUids.length > 0) {
			TYPO3.jQuery('#process-selected-form-export INPUT.filetype').attr("value", "csv");
			TYPO3.jQuery('#process-selected-form-export INPUT.logDataUids').attr("value", selectedUids);
			TYPO3.jQuery('#process-selected-form-export').submit();
		}
	});
	TYPO3.jQuery("#formhandler-module A.delete").on("click", function(e) {
		e.preventDefault();
		var infoElement = TYPO3.jQuery(this).find('SPAN.delete-info');
		var selectedUids = getSelectedUids(infoElement);
		if(selectedUids.length > 0) {
			var modal = top.TYPO3.Modal.confirm(infoElement.data('title'), infoElement.data('message'), top.TYPO3.Severity.warning, [
				{
					text: infoElement.data('button-close-text') || TYPO3.lang['button.cancel'] || 'Cancel',
					active: true,
					name: 'cancel'
				},
				{
					text: infoElement.data('button-ok-text') || TYPO3.lang['button.delete'] || 'Delete',
					btnClass: 'btn-warning',
					name: 'delete'
				}
			]);
			modal.on('button.clicked', function(e) {
				if (e.target.name === 'cancel') {
					top.TYPO3.Modal.dismiss();
				} else if (e.target.name === 'delete') {
					top.TYPO3.Modal.dismiss();
					TYPO3.jQuery('#process-selected-form-delete INPUT.logDataUids').attr("value", selectedUids);
					TYPO3.jQuery('#process-selected-form-delete').submit();
				}
			});
		}
	});

	TYPO3.jQuery(".table.select-fields A.select-all").on("click", function(e) {
		e.preventDefault();
		var table = TYPO3.jQuery(this).closest("TABLE");
		var allCheckboxes = table.find('INPUT[type="checkbox"]');
		var activeCheckboxes = table.find('INPUT[type="checkbox"]:checked');
		if(allCheckboxes.length === activeCheckboxes.length) {
			allCheckboxes.prop("checked", false);
		} else {
			allCheckboxes.prop("checked", true);
		}
	});
});
