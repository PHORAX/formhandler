/*
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

define([
	'jquery'
], function ($) {
	function getSelectedUids(el) {
		var selected = new Array();
		if(typeof el !== 'undefined' && el.attr("data-uid") && el.attr("data-uid").length > 0) {
			selected.push(el.attr("data-uid"));
		} else {
			$('#formhandler-module .mark-row:checked').each(function() {
				selected.push($(this).attr("value"));
			});
		}
		return selected.join(",");
	}

	$(document).ready(function() {
		$("#formhandler-module .filterForm .select-all-pages").on("click", function() {
			$(this).closest('.input-group').find('INPUT.form-control').val("");
		});
		$("#formhandler-module A.select-all").on("click", function(e) {
			e.preventDefault();
			var allCheckboxes = $('#formhandler-module .mark-row');
			var activeCheckboxes = $('#formhandler-module .mark-row:checked');
			if(allCheckboxes.length === activeCheckboxes.length) {
				allCheckboxes.prop("checked", false);
			} else {
				allCheckboxes.prop("checked", true);
			}
		});
		$("#formhandler-module .process-selected-actions A.pdf").on("click", function(e) {
			e.preventDefault();
			var selectedUids = getSelectedUids();
			if(selectedUids.length > 0) {
				$('#process-selected-form-export INPUT.filetype').attr("value", "pdf");
				$('#process-selected-form-export INPUT.logDataUids').attr("value", selectedUids);
				$('#process-selected-form-export').submit();
			}
		});
		$("#formhandler-module .process-selected-actions A.csv").on("click", function(e) {
			e.preventDefault();
			var selectedUids = getSelectedUids();
			if(selectedUids.length > 0) {
				$('#process-selected-form-export INPUT.filetype').attr("value", "csv");
				$('#process-selected-form-export INPUT.logDataUids').attr("value", selectedUids);
				$('#process-selected-form-export').submit();
			}
		});
		$("#formhandler-module A.delete").on("click", function(e) {
			e.preventDefault();
			var infoElement = $(this).find('SPAN.delete-info');
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
						$('#process-selected-form-delete INPUT.logDataUids').attr("value", selectedUids);
						$('#process-selected-form-delete').submit();
					}
				});
			}
		});

		$(".table.select-fields A.select-all").on("click", function(e) {
			e.preventDefault();
			var table = $(this).closest("TABLE");
			var allCheckboxes = table.find('INPUT[type="checkbox"]');
			var activeCheckboxes = table.find('INPUT[type="checkbox"]:checked');
			if(allCheckboxes.length === activeCheckboxes.length) {
				allCheckboxes.prop("checked", false);
			} else {
				allCheckboxes.prop("checked", true);
			}
		});
	});
});

