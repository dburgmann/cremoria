<?php
/**
 * Highscore Controller
 * Determines Highscore Data
 */
class Highscore_Controller extends ControllerIngame {

    public  $template = 'main';
    private $error = '';

    private $pagination;
    private $users;


    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->template->content = new View('highscore');
        $this->template->content->error = '';
        $this->template->js[] = 'js/tablesorter';
        $this->template->js[] = 'js/highscore';
    }


    /**
     * Highscore display
     */
    public function index() {
        parent::index();
        $pagination   = new Pagination();
        $users        = ORM::factory('user');
        $users        = $users->find_all();
        //$users        = ORM::factory('user')->findAll($pagination->sql_limit, $pagination->sql_offset);

        //TODO: Pagination nutzen

        $this->template->content->pagination   = $pagination;
        $this->template->content->users        = $users;
    }
}
?>