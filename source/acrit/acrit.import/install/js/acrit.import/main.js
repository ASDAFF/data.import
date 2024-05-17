/**
 * Utilites
 */

// Call to server
function AcritImportServAction(action, params, success, error) {
	if (action) {
		$.post('/bitrix/admin/acrit.import_ajax.php', {
			'action': action,
			'sessid': BX.message('bitrix_sessid'),
			'params': params,
		}, function (data) {
			if (data.status == 'ok') {
				if (typeof success === 'function') {
					success(data);
				}
			} else {
				if (typeof error === 'function') {
					error(data);
				}
			}
		}, 'json');
	}
}


/**
 * Different functions
 */

function UpdateBitrixCloudMonitoring(addEmail) {
	BX.showWait('waitContainer');
	AcritImportServAction('monitor_add', {'add_email': addEmail}, function (data) {
		BX.closeWait('waitContainer');
		window.location = '/bitrix/admin/acrit.import_support.php';
	});
}


/**
 * Manual import start
 */

function AcritImportStartImportResetVars() {
	run_enabled = true;
	import_count = 0;
	import_success = 0;
	import_errors = 0;
	import_skip = 0;
	$('#start_import_result .start-import-result-all span').text(0);
	$('#start_import_result .start-import-result-good span').text(0);
	$('#start_import_result .start-import-result-bad span').text(0);
	$('#start_import_result .start-import-result-skip span').text(0);
	AcritImportStartImportProgress(1, 0);
	$('#start_import_errors').val('');
}

function AcritImportStartImportProgress(count, current) {
	$.post("/bitrix/admin/acrit.import_run_progress.php", {
		"count": count,
		"current": current
	}, function (data) {
		$("#start_import_progress").html(data);
	}, "html");
}

function AcritImportStartImport(next_item, imported_count) {
	if (!run_enabled) {
		return false;
	}
	$.post("/bitrix/admin/acrit.import_run.php", {
		"profile": acrit_import_profile_id,
		"next_item": next_item,
		"imported_count": imported_count,
	}, function (data) {
		if (data.errors.length > 0) {
			data.errors.forEach(function (item, i, arr) {
				AcritImportMessageAdd(item);
			});
		}
		AcritImportStartImportProgress(import_count, data.imported_count);
		import_success += data.report.success;
		import_errors += data.report.errors;
		import_skip += data.report.skip;
		$('#start_import_result .start-import-result-all span').text(data.imported_count);
		$('#start_import_result .start-import-result-good span').text(import_success);
		$('#start_import_result .start-import-result-bad span').text(import_errors);
		$('#start_import_result .start-import-result-skip span').text(import_skip);
		if (data.imported_count && data.imported_count < import_count) {
			AcritImportStartImport(data.next_item, data.imported_count);
		} else {
			AcritImportStartImportProgress(1, 1);
			$('#stop_import').addClass("adm-btn-disabled");
			$('#start_import').removeClass("adm-btn-disabled");
		}
	}, "json");
}

function AcritImportMessageAdd(message) {
	var text = $('#start_import_errors').val();
	text += message + "\n";
	$('#start_import_errors').val(text);
}


/**
 * JS actions
 */

$(function () {

	$("#step1 #profile_type").change(function () {
		$('[name=STEP_SHOW]').val(1);
		$('#import_form').submit();
	});

	$("#step1 #source_type").change(function () {
		$('[name=STEP_SHOW]').val(1);
		$('#import_form').submit();
	});

	$("#step2 .import-change-sbmt").change(function () {
		$('[name=STEP_SHOW]').val(2);
		$('[name=SAVE_TYPE]').val('general');
		$('#import_form').submit();
	});

	$('#step2 .field-params-link a').click(function () {
		var field_block = $(this).parent('.field-params-link').prev('.field-params');
		if (field_block.is(':visible')) {
			field_block.slideUp();
		} else {
			field_block.slideDown();
		}
		return false;
	});

	$('#step2 input[id$="_work_picture"]').change(function () {
		var add_block = $(this).parent().find('.fields-add-params');
		var check_field = $(this).parent().find('.hidden-workpicture-checked');
		if (add_block.is(':visible')) {
			check_field.val("N");
			add_block.slideUp();
		} else {
			add_block.slideDown();
			check_field.val("Y");
		}

	});

	$('#step2 input[id$="_num_round"]').change(function () {
		var add_block = $(this).parent().find('.fields-add-params');
		var check_field = $(this).parent().find('.hidden-numround-checked');
		if (add_block.is(':visible')) {
			add_block.slideUp();
			check_field.val("N");
		} else {
			add_block.slideDown();
			check_field.val("Y");
		}
	});

	$('#step2 .field-params-item .multiple .add').click(function () {
		var values = $(this).parents('.multiple').find('.values');
		var item_html = $('<div>').append(values.find('.value-item').last().clone()).html();
		var item_new = $(item_html);
		item_new.find('input').val('');
		item_new.find('.del').show();
		item_new.find('input').attr('name', item_new.find('input').attr('name').replace(/\[[0-9]*\]$/g, '') + '[]');
		values.append(item_new);
		return false;
	});

	$('body').on('click', '#step2 .field-params-item .multiple .del', function () {
		var item = $(this).parent('.value-item');
		item.remove();
		return false;
	});

	$('body').on('change', '#step2 .field-params-item .values input', function () {
		console.log($(this).val());
	});


	/**
	 * Import lock
	 */

	$('#acrit_imp_lock_reset a').click(function () {
		BX.showWait();
		AcritImportServAction('profile_lock_reset', {
			'id': acrit_import_profile_id,
		}, function (data) {
			BX.closeWait();
			$('#acrit_imp_lock_reset').hide();
		}, function (data) {
			BX.closeWait();
		});
		return false;
	});


	/**
	 * Schedule
	 */

	function AcritImportSheduleShow() {
		BX.showWait();
		AcritImportServAction('agents_list', {
			'profile_id': acrit_import_profile_id,
		}, function (data) {
			BX.closeWait();
			$('#acrit_imp_agents_list tbody .list-item').remove();
			var row_html = '';
			data.list.forEach(function (item) {
				row_html += '<tr class="adm-list-table-row list-item">\n' +
					'    <td class="adm-list-table-cell">' + item.ID + '</td>\n' +
					'    <td class="adm-list-table-cell">' + (item.AGENT_INTERVAL / 60) + item.INTERVAL_MIN_SUFFIX + ' </td>\n' +
					'    <td class="adm-list-table-cell">' + (item.LAST_EXEC !== null ? item.LAST_EXEC : '') + '</td>\n' +
					'    <td class="adm-list-table-cell">' + (item.NEXT_EXEC !== null ? item.NEXT_EXEC : '') + '</td>\n' +
					'    <td class="adm-list-table-cell"><a href="/bitrix/admin/agent_edit.php?ID=' + item.ID + '" target="_blank">' + BX.message('ACRIT_IMPORT_AGENTS_EDIT') + '</a></td>\n' +
					'    <td class="adm-list-table-cell"><a href="#" class="adm-btn acrit-imp-agent-del" data-id="' + item.ID + '">' + BX.message('ACRIT_IMPORT_AGENTS_DEL') + '</a></td>\n' +
					'</tr>';
			});
			$('#acrit_imp_agents_list tbody').prepend(row_html);
		}, function (data) {
			BX.closeWait();
		});
	}

	if ($('#acrit_imp_agents_list tbody').length) {
		AcritImportSheduleShow();
	}

	$('#acrit_imp_agent_add').click(function () {
		var period = $('#acrit_imp_agents_add [name=interval_min]').val();
		var start_time = $('#acrit_imp_agents_add [name=start_time]').val();
		BX.showWait();
		AcritImportServAction('agents_add', {
			'profile_id': acrit_import_profile_id,
			'period_min': period,
			'start_time': start_time,
		}, function (data) {
			BX.closeWait();
			AcritImportSheduleShow();
		}, function (data) {
			BX.closeWait();
		});
		return false;
	});

	$('body').on('click', '.acrit-imp-agent-del', function () {
		var id = $(this).data('id');
		if (confirm(BX.message('ACRIT_IMPORT_AGENTS_DEL_WARN'))) {
			BX.showWait();
			AcritImportServAction('agents_del', {
				'id': id,
			}, function (data) {
				BX.closeWait();
				AcritImportSheduleShow();
			}, function (data) {
				BX.closeWait();
			});
		}
		return false;
	});


	/**
	 * Manual import
	 */

	AcritImportStartImportResetVars();

	$("#start_import").click(function () {
		if (!$(this).hasClass("adm-btn-disabled")) {
			AcritImportStartImportResetVars();
			$('#start_import').addClass("adm-btn-disabled");
			$('#stop_import').removeClass("adm-btn-disabled");
			$('#start_import_result').show();
			$.post("/bitrix/admin/acrit.import_run_count.php", {
				"profile": acrit_import_profile_id,
			}, function (data) {
				import_count = data.count;
				if (data.errors.length > 0) {
					data.errors.forEach(function (item, i, arr) {
						AcritImportMessageAdd(item);
					});
					$('#stop_import').addClass("adm-btn-disabled");
					$('#start_import').removeClass("adm-btn-disabled");
				} else {
					AcritImportStartImportProgress(1, 0);
					AcritImportStartImport(0, 0);
				}
			}, "json");
		}
		return false;
	});

	$("#stop_import").click(function () {
		if (!$(this).hasClass("adm-btn-disabled")) {
			$('#stop_import').addClass("adm-btn-disabled");
			$('#start_import').removeClass("adm-btn-disabled");
			run_enabled = false;
		}
		return false;
	});

	if ($.fn.select2) {
		$('.acrit-import-store-fields').select2({
			width: '100%',
			language: {
				'noResults': function () {
					return loc_messages.ACRIT_IMPORT_STORE_FIELDS_NOTFOUND;
				}
			}
		});

		$('.select2-list').select2({
			width: '100%',
			language: {
				'noResults': function () {
					return loc_messages.ACRIT_IMPORT_STORE_FIELDS_NOTFOUND;
				}
			}
		});
	}
});
