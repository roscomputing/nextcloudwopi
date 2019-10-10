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
	OCP.AppConfig.setValue('wopi', 'serverUrl', val);
});