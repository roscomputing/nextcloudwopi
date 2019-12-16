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
	<p>
		<label><?php p($l->t('Latest discovery result: ')); ?></label>
		<span id="wopi_discovery_text"><?php p($_['text']); ?></span><br>
		<label><?php p($l->t('Latest discovery time: ')); ?></label>
		<span id="wopi_discovery_time"><?php empty($_['time']) ? p($l->t('never')) : p($l->l('datetime', $_['time'])); ?></span><br>
		<label><?php p($l->t('Next discovery time: ')); ?></label>
		<span id="wopi_discovery_ttl"><?php empty($_['ttl']) ? p($l->t('never')) : p($l->l('datetime', $_['ttl'])); ?></span><br>
		<label><?php p($l->t('Supported file extensions: ')); ?></label>
		<span id="wopi_discovery_extensions"><?php p($_['extensions']); ?></span><br>

	</p>
</div>

