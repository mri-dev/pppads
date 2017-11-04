<?
class Session {
	public static function set($key,$val){
		$_SESSION[$key] = $val;
	}
	
	public static function get($key){
		return $_SESSION[$key];
	}
	
	public static function init(){
		@session_start();
	}
	
	public static function kill($key){
		unset($_SESSION[$key]);
	}
	
	public static function killAll(){
		@session_destroy();
	}
	
}

?>