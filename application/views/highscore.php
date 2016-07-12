<?php
if(!empty($error)){
	echo error::display($error);
}

echo form::open('movements/send',  array("class" => "movForm"));
echo '<table class="table hscTable sortableTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Punkte Gesamt </th>
                            <th>Gebäudepunkte</th>
                            <th>Forschungspunkte</th>
                            <th>Kampfpunkte</th>
                        </tr>
                    </thead>
                    <tbody>';

foreach ($users as $user) {
                echo "<tr>
                        <td>
                            {$user->username}
                        </td>
                        <td>
                            {$user->totalPts}
                        </td>
                        <td>
                            {$user->buildingPts}
                        </td>
                        <td>
                            {$user->researchPts}
                        </td>
                        <td>                        
                        </td>
                    </tr>";
}
echo '              </tbody>
                </table>
                <p>
                Zum Sortieren auf Spaltenköpfe klicken.
                </p>';
echo $pagination->render();

?>