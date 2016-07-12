<?php
if(!empty($error)){
	echo error::display($error);
}

if(!empty($units)){
echo form::open('movements/generateMovement',  array("class" => "movForm"));
echo '<table class="table movTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Ap</th>
                <th>Dp</th>
                <th>Cp</th>
                <th>Sp</th>
                <th>Anzahl</th>
                <th>Ausgewählt</th>
            </tr>
        </thead>
        <tbody>';

foreach ($units as $unit) {
    $no		= number_format($unit->quantity, 0, ',', '.');
    echo "<tr>
            <td>{$unit->name}</td>
            <td>{$unit->ap}</td>
            <td>{$unit->hp}</td>
            <td>{$unit->cp}</td>
            <td>{$unit->sp}</td>
            <td>{$no}</td>
            <td>".form::input("units[{$unit->id}]", 0)."</td>
          </tr>";
}
echo '      <tr id="totalRow" class="hidden bold">
                <td>Gesamt: </td>
                <td id="totalAp"></td>
                <td id="totalHp"></td>
                <td id="totalCp"></td>
                <td id="totalSp"></td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
      </table>
      <a class="btn centerBlock" id="selectArmy">Auswählen</a>';

echo '<div id="movSettings" class="hidden">';
echo "<div class=\"movCargo \">";
echo "<p> Fracht:</p>";
echo "<span class=\"cargo\">";
echo "Gold: <br />";
echo form::input('gold', 0, 'class="cargoInput"');
echo "</span>";

echo "<span class=\"cargo\">";
echo "Holz: <br />";
echo form::input('wood', 0, 'class="cargoInput"');
echo "</span>";

echo "<span class=\"cargo\">";
echo "Stein: <br />";
echo form::input('stone', 0, 'class="cargoInput"');
echo "</span>";

echo "<span class=\"cargo\">";
echo "Eisen: <br />";
echo form::input('iron', 0, 'class="cargoInput"');
echo "</span>";

echo "<br class=\"clear\" />";
echo "<p id=\"cargoDisplay\">Genutzte Kapazität: <span id=\"usedCap\">0</span>/<span id=\"maxCap\">0</span></p>";
echo "</div>";

echo "<div class=\"movCoords \">";
echo "<p> Zielkoordinaten:</p>";
echo form::input("destX", $destX);
echo form::input("destY", $destY);
echo "</div>";

echo form::input("startX", $startX, 'class="hidden"');
echo form::input("startY", $startY, 'class="hidden"');




echo "<p>Reisedauer: <span id=\"movTime\">0</span>T<p>";

$selection = Kohana::config('crmGame.movementTypes');
echo form::dropdown("action", $selection, 0);
echo form::submit("submit", "Schicken");
echo '</div>';
echo form::close();
}
?>