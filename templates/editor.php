<?php 
script('wopi', 'editor');
style('wopi', 'editor');

?>

<form id="office_form" name="office_form" target="office_frame" action="<?php p($_['url']) ?>" method="post">
        <input name="access_token" value="<?php p($_['token']) ?>" type="hidden" />
        <input name="access_token_ttl" value="<?php p($_['token_ttl']) ?>" type="hidden" />
</form>