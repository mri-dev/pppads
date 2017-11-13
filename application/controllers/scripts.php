<?

class scripts extends Controller{
		function __construct(){
			parent::__construct();

      header('Content-Type: application/javascript');

      $cache = 'scripts.cache';

      if ( file_exists($cache) && (filemtime($cache) > (time() - 60 * 60 )) )
      {
        $last_modified_time = filemtime($cache);
        $etag = md5_file($cache);
        header("Last-Modified: ".gmdate("D, d M Y H:i:s", $last_modified_time)." GMT");
        header("Etag: $etag");
        header('Cache-Control: public');

        if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time ||
            trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
            header("HTTP/1.1 304 Not Modified");
            exit;
        }

        $buffer = file_get_contents($cache);
        echo $buffer;
      }
      else
      {
        ob_start();
        include 'res/angular.js';
        include 'res/angular-cookies.js';
        include 'res/angular-sanitize.js';
        include 'res/angularjs-color-picker-min.js';
        include 'res/popup.receiver.js';
        $buffer = ob_get_clean();
        file_put_contents($cache, $buffer, \LOCK_EX);
        echo $buffer;
      }

      header("Cache-Control: max-age=3600");

		}

		function __destruct(){}
	}

?>
