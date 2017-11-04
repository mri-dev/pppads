<?
namespace PortalManager;

class Traffic
{
	const DB_TABLE 				= "forgalom";
	const DB_TABLE_TYPE_KEY 	= "forgalom_tipus_kulcsok";
	
	
	const TYPE_TRAFFIC_INCOME 	= 1;
	const TYPE_TRAFFIC_OUTGO 	= -1;
	const ADDTYPE_BUY 	= "income_by_buy";
	const ADDTYPE_BUY_OUTGO	= "outgo_by_buy";

	private $db = null;
	
	private $forgalom_tipusok 		= array();
	private $forgalom_tipusok_txt 	= array();
	private $alape_tipus_kulcsok 	= array('income','outgo','income_by_buy','outgo_by_buy');
	
	function __construct( $arg = array() ){
		$this->db = $arg[db];
		$this->loadTypes();
	}
	function isDefaultTypeKey($val){
		if(in_array($val,$this->alape_tipus_kulcsok))
			return true;
		else
			return false;
	}
	private function loadTypes(){
		// Default keys
		$types = array(
			'outgo_by_buy' => 'Megrendelt termékek nagykerára.',
			'income_by_buy' => 'Bevétel megrendelésből!',
			'income' 		=> 'Egyéb bevétel',
			'outgo' 		=> 'Egyéb kiadás'
		);
		// User defined keys
		$load = $this->db->query("SELECT * FROM ".self::DB_TABLE_TYPE_KEY)->fetchAll(\PDO::FETCH_ASSOC);
			foreach($load as $uk){
				$types[$uk[kulcs]] = $uk[nev];
			}
		
		foreach($types as $tk => $tv){
			$this->forgalom_tipusok[] 			= $tk;
			$this->forgalom_tipusok_txt[$tk] 	= $tv;
		}
	}
	function delTipusKulcs($kulcs){
		$this->db->query("DELETE FROM ".self::DB_TABLE_TYPE_KEY." WHERE kulcs = '$kulcs'");
	}
	function saveTipusKulcs($post){
		extract($post);
		
		if($megnevezes == '')
			throw new \Exception('Alapértelmezett megnevezes megadása kötelező!');
		if($forgalom_tipus == '')
			throw new \Exception('Forgalom típus kiválasasztása kötelező!');
		if($key == '')
			throw new \Exception('Típus kulcs megadása kötelező!');
				
		$this->db->update(self::DB_TABLE_TYPE_KEY,
			array(
				'nev' 	=> $megnevezes,
				'kulcs' => $traffic_prefix.$key
			),
		"kulcs = '$id'"
		);
	}
	
	function addTipusKulcs($post){
		extract($post);
		
		if($megnevezes == '')
			throw new \Exception('Alapértelmezett megnevezes megadása kötelező!');
		if($forgalom_tipus == '')
			throw new \Exception('Forgalom típus kiválasasztása kötelező!');
		if($key == '')
			throw new \Exception('Típus kulcs megadása kötelező!');
		
		if($this->checkTipusKulcsExists($traffic_prefix.$key)){
			throw new \Exception('Ez a típus kulcs már használatban van!');
		}
		
		
		$this->db->insert(
			self::DB_TABLE_TYPE_KEY,
			array(
				'kulcs' => $traffic_prefix.$key,
				'nev' => trim($megnevezes)
			)
		);
		
	}
	
	function getTipusKulcsAdat($type_kulcs){
		$q = $this->db->query("SELECT * FROM ".self::DB_TABLE_TYPE_KEY." WHERE kulcs = '$type_kulcs';");
		
		return $q->fetch(\PDO::FETCH_ASSOC);
	}
	
	private function checkTipusKulcsExists($key){
		$check = $this->db->query("SELECT ID FROM ".self::DB_TABLE_TYPE_KEY." WHERE kulcs = '$key'");
		
		if($check->rowCount() == 0){
			return false;
		}else{
			return true;
		}
	}
	function getTrafficType($key){
		if(strpos($key,'income') === 0){
			return self::TYPE_TRAFFIC_INCOME;
		}else if(strpos($key,'outgo') === 0){
			return self::TYPE_TRAFFIC_OUTGO;
		}
	}

	function getTipusKulcsok(){
		$keys = array();
		
		foreach($this->forgalom_tipusok as $k){
			$arr = array();
				$arr[key] 			= $k;
				$arr[comment] 		= $this->forgalom_tipusok_txt[$k];
				$arr[isDefault] 	= $this->isDefaultTypeKey($k);
				$arr[trafficType] 	= $this->getTrafficType($k);
			$keys[] = $arr;
		}
		
		return $keys;
	}

	function calcTrafficInfo(){
		$datum 	= date('Y-m');
		$re 	= array();
		// Havi forgalom számolása
		$havi 	= "SELECT sum(bevetel) as bevetel, sum(kiadas) as kiadas FROM `forgalom` WHERE datum LIKE '$datum%'";	
		$q 		= $this->db->query($havi)->fetch(\PDO::FETCH_ASSOC);
			$re[latest][in] 	= $q[bevetel];
			$re[latest][out] 	= $q[kiadas];
			
		// Teljes forgalom számolása
		$all 	= "SELECT sum(bevetel) as bevetel, sum(kiadas) as kiadas FROM `forgalom`";	
		$q 		= $this->db->query($all)->fetch(\PDO::FETCH_ASSOC);
			$re[all][in] 	= $q[bevetel];
			$re[all][out] 	= $q[kiadas];
			
		return $re;
	}

	function getAll($arg = array()){
		$traffic = array('income'=>0, 'outgo' => 0);
		$q = "SELECT 
			t.*
		FROM ".self::DB_TABLE." as t
		WHERE t.ID IS NOT NULL		
		";
		
			// FILTERS
			if($arg[filters][forgalom]){
				if($arg[filters][forgalom] == 'bevetel'){
					$q .= " and t.bevetel > 0";
				}else{
					$q .= " and t.kiadas > 0";
				}
			}
			if($arg[filters][megnevezes]){
				$q .= " and t.megnevezes LIKE '%".$arg[filters][megnevezes]."%'";
			}
			if($arg[filters][itemid]){
				$q .= " and t.item_id = '".$arg[filters][itemid]."'";
			}
			if($arg[filters][tipus]){
				$q .= " and t.tipus_kulcs = '".$arg[filters][tipus]."'";
			}
			if($arg[filters][dateFrom]){
				$q .= " and t.idopont >= '".$arg[filters][dateFrom]."'";
			}
			if($arg[filters][dateTo]){
				$q .= " and t.idopont <= '".$arg[filters][dateTo]."'";
			}
		
		// ORDER 
		$q .= " ORDER BY t.idopont DESC";
		
		$arg[multi] = "1";
		extract($this->db->q($q, $arg));
		
		// Calc traffic
		$dateFrom 	= $arg[dateFrom];
		$dateTo 	= $arg[dateTo];
		$tr = "SELECT sum(bevetel) as bevetel, sum(kiadas) as kiadas FROM `forgalom` WHERE ID IS NOT NULL ";
			if($dateFrom || $dateTo){
				if($dateFrom){
					$tr .= " and idopont >= '$dateFrom' ";
				}
				if($dateTo){
					$tr .= " and idopont <= '$dateTo' ";
				}
			}
			$trd = $this->db->query($tr)->fetch(\PDO::FETCH_ASSOC);
			$traffic[income] 	= $trd[bevetel];
			$traffic[outgo] 	= $trd[kiadas];
		
		$ret[traffic] = $traffic;
		
		return $ret;
	}
	
	function add($options = array()){
		extract($options);
		
		if($type == '') throw new \Exception("Forgalom típus meghatározása szükséges!");
		
		if(!in_array($type,$this->forgalom_tipusok)) throw new \Exception("Forgalom típus ismeretlen!");
		
		if($type == 'income' || $type == 'outgo'){
			if($text == '') throw new \Exception("Forgalom megjegyzése kötelező!");
		}
		
		if($type == self::ADDTYPE_BUY && $item_id == '') throw new \Exception("Kapcsolódó tranzakció ID hiányzik, vásárlási forgalom után!");
		
		if($bevetel == 0 && $kiadas == 0) throw new \Exception("Forgalom nem bejegyezhető! Bevétel: 0 Ft; Kiadás: 0 Ft");
		
		if($type == 'income_by_buy'){
			$check = $this->db->query("SELECT ID FROM ".self::DB_TABLE." WHERE tipus_kulcs = '$type' and item_id = '$item_id'");
			
			if($check->rowCount() != 0){
				throw new \Exception("Ezután a megrendelés után már be lett jegyezve a forgalom!");
			}
		}
		
		////
		$datum 		= ($date == '') ? date('Y-m-d') : $date;
		$megnevezes = ($text == '') ? $this->forgalom_tipusok_txt[$type] : $text;
		$bevetel 	= (is_null($bevetel)) ? 0 : (int)$bevetel; 
		$kiadas 	= (is_null($kiadas)) ? 0 : (int)$kiadas;
		
		$this->db->insert(self::DB_TABLE,
			array(
				'megnevezes' => $megnevezes,
				'tipus_kulcs' => $type,
				'item_id' => $item_id,
				'bevetel' => $bevetel,
				'kiadas' => $kiadas,
				'datum' => $datum,
				'idopont' => $datum)
		);
		
		return "Forgalom bejegyezve!";
	}

	public function __destruct()
	{
		$this->db = null;
	}
	
}

?>