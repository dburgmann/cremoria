<?php
    if(!empty($user->currentResearch)){
        $research = IniORM::factory('tech', $user->currentResearch)->name;
        $researchTime = "({$user->researchTime}T)";
    }
    else{
        $research = '-';
        $researchTime = '';
    }

    //Infos about Hero
    echo "<table class=\"table overviewTable\">
            <caption>Held </caption>
            <thead>
                <tr>
                    <th>Tätigkeit</th>
                    <th>Aktuelle Forschung</th>
                    <th>Punkte</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td> -</td>
                    <td> {$research} {$researchTime} </td>
                    <td> {$user->totalPts} </td>
                </tr>
            </tbody>
          </table>";


    //Infos about Towns
    echo "<table class=\"table overviewTable\">
            <caption>Städte </caption>
            <thead>
                <tr>
                    <th>Stadt</th>
                    <th>Gebäude im Bau</th>
                    <th># Einheiten in Ausbildung</th>
                </tr>
            </thead>
            <tbody>";

    foreach($towns as $town){

        if(!empty($town->currentBuilding)){
            $building = IniORM::factory('building', $town->currentBuilding)->name;
            $buildingTime = "({$town->buildingTime}T)";
        }
        else{
            $building = '-';
            $buildingTime = '';
        }
        
        $building = (!empty($town->currentBuilding)) ? IniORM::factory('building', $town->currentBuilding)->name : '-';

        echo "<tr>
                <td> {$town->name} ({$town->x}|{$town->y}) </td>
                <td> {$building} {$buildingTime} </td>
                <td> {$town->unitCapUsed}/{$town->unitCap} </td>
              </tr>";
    }


    echo "  </tbody>
          </table>";


    //Movements to town of user
    echo "<table class=\"table overviewTable\">
            <caption>Ankommende Bewegungen </caption>
            <thead>
                <tr>
                    <th>Start</th>
                    <th>Ziel</th>
                    <th>Restzeit</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>";

    foreach($movsTo as $to){        
        foreach($to['mov'] as $mov){
            $action = (isset($actions[$mov->action])) ? $actions[$mov->action] : null;
            echo "<tr>
                    <td> {$mov->startX} | {$mov->startY} </td>
                    <td> {$to['town']} ({$mov->destX}|{$mov->destY}) </td>
                    <td> {$mov->arrival} </td>
                    <td> {$action} </td>
                  </tr>";
        }
    }
    
    echo "  </tbody>";
    echo "</table>";



    //Movements from town of user
    echo "<table class=\"table overviewTable\">
            <caption>Abgehende Bewegungen </caption>
            <thead>
                <tr>
                    <th>Start</th>
                    <th>Ziel</th>
                    <th>Restzeit</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>";

    foreach($movsFrom as $from){        
        foreach($from['mov'] as $mov){
            $action = (isset($actions[$mov->action])) ? $actions[$mov->action] : null;
            echo "<tr>
                    <td> {$from['town']} ({$mov->startX}|{$mov->startY}) </td>
                    <td> {$mov->destX} | {$mov->destY} </td>
                    <td> {$mov->arrival} </td>
                    <td> {$action} </td>
                  </tr>";
        }
    }

    echo "  </tbody>";
    echo "</table>";

   
        




    
?>
