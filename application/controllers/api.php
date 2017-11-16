<?

use PopupManager\Packages;

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
				/**
				* DOMAINS & WEBSITES
				**/
        case 'getCurrentDomain':

        break;
        case 'setCurrentDomain':
          setcookie('edomid', (int)$domain, time()+3600*24*7, '/');
        break;
        case 'loadDomains':
          $data['current_domain'] = $current_domain_id;
          $data['domains'] = $this->MyDomains;
        break;

				/**
				* PACKAGES
				**/
				case 'loadPackages':
					$this->initPackagesClass();
					$this->Packages->getList();

					$packages = array();
					while ($this->Packages->walk())
					{
						$p = $this->Packages;
						$packages[] = array(
							'ID' => $p->getID(),
							'name' => $p->getName(),
							'isdemo' => $p->isDemo(),
							'domains' => $p->avaiableDomains(),
							'freeviews' => $p->packageViews(true),
							'viewprice' => $p->viewPrice(),
							'price' => $p->price(true),
							'raw_price' => $p->price(),
							'subs' => $p->hasSubscribing(),
							'subsgroups' => $p->hasSubscriberGrouping(),
							'promotext' => $p->getPromoText(),
							'promotextcolor' => $p->getPromoTextColor(),
							'colorcode' => $p->getColorCode()
						);
					}
					unset($p);

					$data['packages'] = $packages;
					$current_package =  $this->Packages->getList(array('id' => (int)$this->view->user['data']['package']));

					$data['current_package'] = array(
						'ID' => $current_package->getID(),
						'name' => $current_package->getName(),
						'isdemo' => $current_package->isDemo(),
						'domains' => $current_package->avaiableDomains(),
						'freeviews' => $current_package->packageViews(),
						'viewprice' => $current_package->viewPrice(),
						'price' => $current_package->price(),
						'demo_expire_at' => $this->view->user['data']['demo_expired']
					);
					$data['user'] = $this->view->user;

					unset($packages);
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

		public function template()
		{
			$key = $this->gets[2];

			switch ($key)
			{
				case 'confirm_package_order':
					$this->view->render('templates/api/'.$key);
				break;
			}

		}

		/*************************************
		**************************************/

		private function initPackagesClass()
		{
			$this->Packages = new Packages(array(
				'db' => $this->db,
				'settings' => $this->settings
			));
		}

		function __destruct(){}
	}

?>
