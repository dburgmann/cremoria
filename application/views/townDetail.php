<?php
echo "<div class=\"box detailBox\">";
echo "Name: ".$town->name;
echo "<br> Besiter: ".$user->username;
echo "<br> Koordinaten: [{$town->x}|{$town->y}]";
echo html::anchor("movements/index/{$town->x}/{$town->y}/", "Armee schicken", array('class' => 'btn'));
//echo html::anchor("message", "Nachricht schicken", array('class' => 'btn'));
echo "</div>";


?>