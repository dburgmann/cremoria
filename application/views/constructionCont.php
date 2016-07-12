	<?php
	if(!empty($error)){
		echo error::display($error);	
	}

	$count = 0;
	echo "<table class=\"table consTable\">";
	echo "<tbody> \n";
	foreach($buildings as $bld){
		$count++;
		$class = ($count % 2 == 0) ? ' class = "even"' : '';
		
		$id 	= $bld['id'];
		$name	= $bld['name'];
		$gold	= html::image('images/gold.gif').number_format($bld['gold'], 0, ',', '.');
		$wood	= html::image('images/wood.gif').number_format($bld['wood'], 0, ',', '.');
		$stone	= html::image('images/stone.gif').number_format($bld['stone'], 0, ',', '.');
		$iron	= html::image('images/iron.gif').number_format($bld['iron'], 0, ',', '.');
		$time	= html::image('images/time.gif').$bld['time'];
		$no		= number_format($bld['quantity'], 0, ',', '.');
        $description = $bld['descr'];
		
		if(isset($currentBld)){
			if($currentBld->id == $id){
				$build = "Restdauer: ".html::image('images/time.gif').$currentBld->time;
			}
			else{
				$build = '-';
			}
		}
		else{
			$build = html::anchor('construction/produce/'.$id, 'Bauen', array('class' => 'btn'));
		}
		$link = "<a href=\"#bldDescr{$id}\" class=\"fbInline\">$name ({$no})</a>";

		echo "<tr{$class}>
				<td>
                    {$link}
                    <div class=\"hidden\">
                        <div id=\"bldDescr{$id}\" class=\"description\">
                            {$description}
                        </div>
                    </div>
                </td>
				<td>
					<table class = \"expenses\">
						<tr>
							<td>$gold</td>
							<td> $wood </td>
							<td></td>
						</tr>
						<tr>
							<td> $stone </td>
							<td> $iron </td>
							<td> $time </td>
						</tr>
					</table>
				</td>
				<td>
					".$build."
				</td>		
			  </tr> \n";        
	}
	echo "</tbody>";
	echo "</table>";	
?>


