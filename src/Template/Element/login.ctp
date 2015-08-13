<?php
echo $this->Flash->render('auth');

echo $this->Form->create();
echo $this->Form->input(
    'provider',
    [
        'type' => 'select',
        'options' => ['Google' => 'Google', 'Facebook' => 'Facebook']
    ]
);

echo $this->Form->button('Login');
echo $this->Form->end();
?>
