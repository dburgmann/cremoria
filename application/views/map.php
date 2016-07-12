<?php


echo "<table class=\"table mapTable\">";
echo "<tr><td colspan=\"13\" class=\"mapControls mcVertical\">".html::anchor("map/show/{$buttons['top']['x']}/{$buttons['top']['y']}", '-')."</td></tr>";
echo "<tr>";
echo "<td rowspan =\"10\" class=\"mapControls mcHorizontal\">".html::anchor("map/show/{$buttons['left']['x']}/{$buttons['left']['y']}", '-')."</td>";
echo "<th></th>";
for($x = $startX; $x < $endX; $x++){
	echo "<th> $x </th>";
}
echo "<td rowspan =\"10\" class=\"mapControls mcHorizontal\">".html::anchor("map/show/{$buttons['right']['x']}/{$buttons['right']['y']}", '+')."</td>";
echo "</tr>";


for($y = $startY; $y < $endY; $y++){
	echo "<tr>";
	echo "<th> $y </th>";	
	for($x = $startX; $x < $endX; $x++){
		if(isset($map[$x][$y])){
			$town = $map[$x][$y];
			$data = html::anchor("details/town/{$town->id}", $town->name);
		}
		else{
			$data = "";
		}
		
		echo "<td class=\"mapCell\"> $data </td>";
	}
	echo "</tr>";
}
echo "<tr><td colspan=\"13\" class=\"mapControls mcVertical\">".html::anchor("map/show/{$buttons['bottom']['x']}/{$buttons['bottom']['y']}", '+')."</td></tr>";
echo "</table>";

echo form::open('map/jump', array("class" => "mapForm"));
echo form::input('x', 'x');
echo form::input('y', 'y');
echo form::submit('submit', 'Jump');
echo form::close();

?>