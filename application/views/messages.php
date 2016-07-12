<?php
if($messages == false){
    $echo = "<div class=\"messages\"><p class=\"center\"> Du hast momentan keine Nachrichten </p>";
}
else{


$echo = "<div class=\"messages\">
             <h2>Nachrichten</h2>
             <table class=\"table msgTable\">
                <tr>
                    <th class=\"msgTitleCol\">Betreff</th>
                    <th class=\"msgSenderCol\">Absender</th>
                    <th class=\"msgTimeCol\">Empfangen</th>
                    <th class=\"msgDeleteCol\">Löschen</th>
                </tr>";

//Message List
foreach($messages as $message){
    //TODO: Username bestimmung sollte in Controller    
    $username = ($message->sender == 0) ?  'System': ORM::factory('user', $message->sender)->username;
    $class    = ($message->unread) ? 'unreadMsg' : '';
    //Generate Link for message deletion
    $delLink = "<a href=\"{$message->id}\" class=\"msgDelete\">".html::image('images/delete.png')."</a>";

    //Display Messages
    $echo .= "<tr class = \"$class\">
                <td>
                    <a href=\"\" onclick=\"expand('msg{$message->id}'); return false;\">
                        {$message->title}
                    </a>                    
                </td>
                <td>{$username}</td>
                <td>{$message->date} </td>
                <td>{$delLink} </td>                
              </tr>
              <tr id=\"msg{$message->id}\" class=\"hidden msgText\">
                <td colspan=\"2\">
                   {$message->text}
                </td>                
              </tr>";
}
$echo .="   </table>";
}


$echo .="<a href=\"\" class=\"btn center\" onclick=\"expand('msgForm'); return false;\">
               Nachricht verfassen
            </a>";

//Message Form
$echo .= "<div id=\"msgForm\" class=\"hidden\">";
$echo .= "<div id=\"msgInfo\" class=\"error hidden\"></div>";
$echo .= form::open("")                                                                 .' <br> ';
$echo .= form::label('receiver', 'An:')                                                 .' <br> ';
$echo .= form::input(array('id'=>'receiver', 'name'=>'receiver', 'maxlength'=>'20'))    .' <br> ';
$echo .= form::label('title', 'Betreff:')                                               .' <br> ';
$echo .= form::input(array('id'=>'title', 'name'=>'title', 'maxlength'=>'30'))          .' <br> ';
$echo .= form::label('text', 'Nachricht:')                                              .' <br> ';
$echo .= form::textarea(array('name'=>'text', 'id'=>'text', 'rows'=>'10', 'cols'=>'35', 'maxlength' =>'1500')).' <br> ';
$echo .= form::submit(array('name'=>'submit', 'id'=>'msgSend'), 'Abschicken');
$echo .= form::close();
$echo .="</div></div>";


//Echo as Json because of Ajax request
echo json_encode($echo);

?>
