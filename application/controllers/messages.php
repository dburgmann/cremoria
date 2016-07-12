<?php
/**
 * Message Controller
 * controls all actions concerning messages, is only adressed via ajax
 */
class Messages_Controller extends ControllerIngame {

    public $template    = '';
    private $error      = '';

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->template->error = '';
    }


    /**
     * Generates a list of all messages for the active user
     */
    public function getMessageList() {        
        $view = new View();
        $userId = $this->user->id;
        $view->set_filename('messages');
        $view->messages = ORM::factory('message')->where('receiver', $userId)->find_all();
        if($view->messages->count() == 0){
            $view->messages = false;
        }
        else{
            //set to read TODO: find better method too much queries
            $updates = clone $view->messages;
            foreach($updates as $msg){
                $msg->unread = false;
                $msg->save();
            }
        }
        $view->render(true);
    }


    /**
     * Generates a message from post data and sends it
     */
    public function send() {
        //TODO: Spam Schutz

        $recName    = $this->input->post('receiver');
        $title      = $this->input->post('title');
        $text       = $this->input->post('text');
        $text       = wordwrap($text, 40, '<br />', true);
        $receiver   = ORM::factory('user')->where('username', $recName)->find();
        $receiver   = $receiver->id;

        $message    = ORM::factory('message');
        $message->receiver  = $receiver;
        $message->title     = $title;
        $message->text      = $text;
        $message->sender    = $this->user->id;
        $message->save();        
    }


    /**
     * Deletes the message specified by post data
     */
    public function delete(){
        $id = $this->input->get('id');
        $message  = ORM::factory('message')->where('receiver', $this->user->id)->delete($id);
        $this->auto_render = FALSE;
    }

}
?>