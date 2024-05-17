$(document).ready(function() {
	$('.select-search').select2({ 
		language: "ru",
	}); 
	$(document).on('select2:open', function(e) {
		window.setTimeout(function () {
			document.querySelector('input.select2-search__field').focus();
		}, 0);
	});
});