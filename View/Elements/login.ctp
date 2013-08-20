<?php
echo $this->Form->create('User');
echo $this->Form->input('provider', array('value' => 'OpenID'));
echo $this->Form->input('openid_identifier', array('value' => 'https://www.google.com/accounts/o8/id'));
echo $this->Form->end('Submit');
?>