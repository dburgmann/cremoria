<?php
/**
 * Helper for error displaying
 */
class error{
    /**
     * Returns a String containing the Errormessage
     * @param <string> $error
     * @return <string>
     */
	public static function display($error){
		$error = kohana::lang("errors.{$error}");
		return "<div class=\"error\"> $error </div>";
	}
}