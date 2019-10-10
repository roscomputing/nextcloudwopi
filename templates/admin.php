<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */

script('wopi', 'admin');
style('wopi', 'admin');
?>

<div id="wopi" class="section">
	<h2><?php p($l->t('Office online integration')); ?></h2>

	<p>
		<label for="wopi_server_url"><?php p($l->t('Office server url')); ?></label>
		<input id="wopi_server_url" name="wopi_server_url" type="text" value="<?php p($_['server_url']); ?>" />
	</p>

</div>

