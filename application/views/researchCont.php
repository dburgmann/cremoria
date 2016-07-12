<?php
	if(!empty($error)){
		echo error::display($error);	
	}

	if(!empty($techs)){
		$eco 	= '';
		$war 	= '';
		$misc 	= '';
        $rep    = '';

		foreach($techs as $tech){
			$id 	= $tech['id'];
			$name	= $tech['name'];
			$gold	= html::image('images/gold.gif').'<br>'.number_format($tech['gold'], 0, ',', '.');
			$wood	= html::image('images/wood.gif').'<br>'.number_format($tech['wood'], 0, ',', '.');
			$stone	= html::image('images/stone.gif').'<br>'.number_format($tech['stone'], 0, ',', '.');
			$iron	= html::image('images/iron.gif').'<br>'.number_format($tech['iron'], 0, ',', '.');
			$time	= html::image('images/time.gif').'<br>'.$tech['time'];
			$type	= $tech['type'];
			$no		= $tech['quantity'];
            $class  = '';
            $description = $tech['descr'];

            //Add Level to repeatable techs
            if($type == 'repeatable'){
                $name .= " [{$no}]";
            }
            $link = "<a href=\"#rscDescr{$id}\" class=\"fbInline\">$name</a>";

            //Check if Tech is the Tech current in research
			if(isset($currentTech)){
				if($currentTech->id == $id){
					$research   = "Restdauer: ".html::image('images/time.gif').$currentTech->time;
                    $class      ="rscCurrent";
				}
				else{
					$research   = '';
				}
			}
			else{
				$research = html::anchor('research/produce/'.$id, 'Forschen', array('class' => 'btn'));
			}

			//Check if Tech is already researched
			if($no > 0 && $type != 'repeatable'){
				$html = "<tr class = \"rscFinished\"><td> $name </td></tr> \n";
			}else{
				$html = "<tr>
						  	<td>
								<table class = \"expenses  $class\">
									<tr>
                                        <th colspan=\"3\">
                                            {$link}
                                            <div class=\"hidden\">
                                                <div id=\"rscDescr{$id}\" class=\"description\">
                                                    {$description}
                                                </div>
                                            </div>
                                        </th>
                                    </tr>
									<tr>
										<td> $gold </td>
										<td> $wood </td>
										<td></td>
									</tr>
									<tr>
										<td> $stone </td>
										<td> $iron </td>
										<td> $time </td>
									</tr>
									<tr> <td colspan=\"3\">$research</td></tr>
								</table>
							</td> 
						</tr> \n";
			}
            //Choose Display Place according to type
			switch($type){
				case 'economy':
					$eco.= $html;
					break;
				case 'war':
					$war.= $html;
					break;
				case 'misc':
					$misc.= $html;
					break;
                case 'repeatable':
                    $rep.= $html;
			}
		
		}
		if(!empty($eco)){	
			echo "<table class=\"table rscTable\">
				  <tbody>
				  $eco
				  </tbody>
				  </table>";
		}
		
		if(!empty($war)){	
			echo "<table class=\"table rscTable\">
				  <tbody>
				  $war
				  </tbody>
				  </table>";
		}
			  
		if(!empty($misc)){			  
			echo "<table class=\"table rscTable\">
				  <tbody>
			  	  $misc
			  	  </tbody>
			  	  </table>";
		}
        
        if(!empty($rep)){			  
			echo "<table class=\"table rscTable\">
				  <tbody>
			  	  $rep
			  	  </tbody>
			  	  </table>";
		}

		echo "<br style=\"clear:both\" />";
	}
?>