<?php
echo form::open('overview/setTown', array('id' => 'townSelection'));
echo form::dropdown(array('name'=>'townId', 'onChange'=>'submit()'), $townSelect, 'standard');
echo form::close();

echo html::anchor('overview', 'Übersicht', array('class' => 'btn'));
echo html::anchor('construction', 'Gebäude', array('class' => 'btn'));
echo html::anchor('research', 'Forschung', array('class' => 'btn'));
echo html::anchor('recruitment', 'Einheiten', array('class' => 'btn'));
echo html::anchor('economy', 'Wirtschaft', array('class' => 'btn'));
echo html::anchor('map', 'Karte', array('class' => 'btn'));
echo html::anchor('movements', 'Armee', array('class' => 'btn'));
echo html::anchor('movements', 'Gilde', array('class' => 'btn'));
//echo html::anchor('movements', 'Nachrichten', array('class' => 'btn'));
echo html::anchor('highscore', 'Highscore', array('class' => 'btn'));
echo html::anchor('movements', 'Einstellungen', array('class' => 'btn'));
echo html::anchor('movements', 'Forum', array('class' => 'btn'));
echo html::anchor('movements', 'Hilfe', array('class' => 'btn'));
echo html::anchor('logout', 'Logout', array('class' => 'btn'));
echo '<br />';

echo html::anchor('TickEconomy/start', 'START TICK', array('target' => '_blank', 'class' => 'btn'));
echo html::anchor('TickMovement/start', 'START MOV. TICK', array('target' => '_blank', 'class' => 'btn'));
echo html::anchor('TickScore/start', 'START SCORE TICK', array('target' => '_blank', 'class' => 'btn'));
echo html::anchor('reset', 'RESET UNIVERSE', array('target' => '_blank', 'class' => 'btn'));
?>