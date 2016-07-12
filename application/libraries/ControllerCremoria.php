<?php
/**
 * Extended Template Controller for javascript & css used on every page
 */
abstract class ControllerCremoria extends Template_Controller{
    public $template = 'main';

    /**
     * Cosntructor
     */
	public function __construct(){
		parent::__construct();
		$this->template->js     = array('js/jquery', 'js/fancybox', 'js/main');
        $this->template->css    = array('css/fancybox', 'css/main');
	}
}
?>
