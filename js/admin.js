$('#wopi_server_url').change(function() {
	var val = $(this).val();
	function isUrlValid(userInput) {
		var res = userInput.match(/^(?:http(s)?:\/\/)?[\w.-]+(?:\.[\w\.-]+)+[\w\-\._~:/?#[\]@!\$&'\(\)\*\+,;=.]+$/g);
		return res != null;
	}
	if (!!val && !isUrlValid(val))
	{
		OC.dialogs.alert(t('wopi', 'Please provide valid url'), t('wopi', 'Invalid url'));
		return;
	}
	var r = {
		url:val
	};
	$.ajax({
		method: "POST",
		url: OC.generateUrl('/apps/wopi/admin/seturl'),
		data: JSON.stringify(r),
		contentType: "application/json; charset=utf-8",
	}).done(function(data) {
		$('#wopi_discovery_text').text(data.text);
		var time = new Date();
		time.setTime(data.time * 1000);
		$('#wopi_discovery_time').text(time.toLocaleString());
		var ttl = new Date();
		ttl.setTime(data.ttl * 1000);
		$('#wopi_discovery_ttl').text(ttl.toLocaleString());
		$('#wopi_discovery_extensions').text(data.extensions);

	}).fail(function () {
			alert( t('wopi', 'Unknown error') );
	});
});