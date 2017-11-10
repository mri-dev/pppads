<?
class api extends Controller{

		function __construct(){
			parent::__construct();
			header("Access-Control-Allow-Origin: *");
		}

    function Pilot()
    {
      extract($_POST);

      $re = array(
        'call' => $type,
        'params' => $_POST,
        'error' => 0,
        'msg' => null,
        'data' 	=> array(),
      );
      $data = array();
      $current_domain_id = false;

      // Ha nincs kiválasztva domain ID, akkor a lista első eleme a mérvadó
      foreach ($this->MyDomains as $d) {
        if (!$current_domain_id && $d['active']) {
          $current_domain_id = $d['ID'];
          break;
        }
      }

      // Aktuálisan kiválasztott domain ID
      if ( isset($_COOKIE['edomid']) ) {
        $current_domain_id = (int)$_COOKIE['edomid'];
      }

      switch ($type)
      {
        case 'getCurrentDomain':

        break;
        case 'setCurrentDomain':
          setcookie('edomid', (int)$domain, time()+3600*24*7, '/');
        break;
        case 'loadDomains':
          $data['current_domain'] = $current_domain_id;
          $data['domains'] = $this->MyDomains;
        break;

        default:
          $re['error'] = 1;
          $re['msg'] = 'Hibás API hívás. Nincs ilyen funkció a listában: '.$type;
        break;
      }

      $re['data'] = $data;

      header('Content-Type: application/json');
      echo json_encode($re);
    }

		function __destruct(){}
	}

?>
