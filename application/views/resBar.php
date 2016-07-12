<?php
$class = $unreadMessages ? 'msgOpen gotNewMsg' : 'msgOpen';
echo html::anchor("messages/getMessageList", html::image('images/email.png'), array("class"=> $class));
echo html::image('images/gold.gif', 'gold').number_format($gold, 0, ',', '.');
echo html::image('images/wood.gif', 'wood').number_format($wood, 0, ',', '.');
echo html::image('images/stone.gif', 'stone').number_format($stone, 0, ',', '.');
echo html::image('images/iron.gif', 'iron').number_format($iron, 0, ',', '.');
?>
