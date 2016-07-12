<?php
	if(!empty($error)){
		echo error::display($error);	
	}


    if(!empty($production)){
		echo "<table class=\"table curProdTable\">";
		echo "<tbody> \n";
		echo "<tr>
				<th>Name</th>
				<th> # </th>
				<th>".html::image('images/time.gif')."</th>
			  </tr>";

		foreach($production as $unit){
			$name	= $unit['name'];
			$time	= $unit['time'];
			$no		= $unit['quantity'];
			echo "<tr>
				  	<td>$name</td>
				  	<td>$no</td>
				  	<td>$time</td>
				  </tr>";
		}
		echo "</tbody>";
		echo "</table> <br />";
	}

	
	if(!empty($units) && !empty($totalCap)){
		$count = 0;
		echo form::open('recruitment/produce', array('id' => 'recrForm'));
		echo "<table class=\"table recrTable\">";
		echo "<tbody> \n";
		echo "<tr> <td colspan =\"3\"> $freeCap / $totalCap Plätze frei </td> </tr>";
		foreach($units as $unit){
			$count++;
			$class = ($count % 2 == 0) ? ' class = "even"' : '';
			
			$id 	= $unit['id'];
			$name	= $unit['name'];
			$gold	= html::image('images/gold.gif').number_format($unit['gold'], 0, ',', '.');
			$wood	= html::image('images/wood.gif').number_format($unit['wood'], 0, ',', '.');
			$stone	= html::image('images/stone.gif').number_format($unit['stone'], 0, ',', '.');
			$iron	= html::image('images/iron.gif').number_format($unit['iron'], 0, ',', '.');
            $ap     = html::image('images/ap.gif').number_format($unit['ap'], 0, ',', '.');
            $hp     = html::image('images/hp.gif').number_format($unit['hp'], 0, ',', '.');
            $cp     = html::image('images/cp.gif').number_format($unit['cp'], 0, ',', '.');
            $sp     = html::image('images/sp.gif').number_format($unit['sp'], 0, ',', '.');
			$time	= html::image('images/time.gif').$unit['time'];
			$no		= number_format($unit['quantity'], 0, ',', '.');
            $description = $unit['descr'];
            $link = "<a href=\"#unitDescr{$id}\" class=\"fbInline\">$name ({$no})</a>";
			
			echo "<tr{$class}>
					<td>
                        {$link} <br/>
                        ({$ap}, {$hp}, {$cp}, {$sp})
                        <div class=\"hidden\">
                            <div id=\"unitDescr{$id}\" class=\"description\">
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
						".form::input("quantity[$id]", 0)."
					</td>		
				  </tr> \n";		
		}
		echo "<tr> <td colspan =\"3\">".form::submit(array('id' => 'submit', 'name' => 'submit', 'class' => 'btn'), 'Ausbilden')."</td> </tr>";
		echo "</tbody>";
		echo "</table>";
		
		echo form::close();
	}
	
	
?>
