<?
use Applications\CSVParser;

class app extends Controller{
		private $AUTH_USER 	= '';
		private $AUTH_PW 	= '';

		function __construct(){
			parent::__construct();

			$this->authAccess();
		}


		private function authAccess(){
			if($_SERVER[PHP_AUTH_USER] !== $this->AUTH_USER && $_SERVER[PHP_AUTH_PW] !== $this->AUTH_PW){

				/*header("WWW-Authenticate: Basic realm=\"GOLDFISHING\"");
				header("HTTP\ 1.0 401 Unauthorized");

				echo "Sikertelen azonosítás. Illetéktelenek nem férhetnek hozzá a fájlokhoz!";
				exit;*/
			}
		}

		public function template()
		{
			echo 'ASD';
		}

		public function userimport()
		{
			$lista 		= array();
			$prepared 	= array();

			$csv 	= new CSVParser('src/json/users1.csv');
			$list 	= $csv->getResult();

			$csv2 	= new CSVParser('src/json/users2.csv');
			$list2 	= $csv2->getResult();

			$lista = array_merge($list, $list2);
			unset($list);
			unset($list2);

			foreach ($lista as $data)
			{

				if ($data['Státusz'] != 'Aktív') {
					continue;
				}

				$email = trim($data['E-mail cím']);

				if (!array_key_exists($email, $prepared))
				{
					$phone = trim($data['Telefon (pl. 72/123456)']);

					if ($phone == '') {
						$phone = trim($data['Mobil (pl. 30/1234567)']);
					}

					$inserted = $this->db->query("SELECT 1 FROM felhasznalok WHERE email = '".$email."';")->rowCount();

					if ( $inserted != 0 ) {
						//continue;
					}

					$prepared[$email] = array(
						'felhasznalok' 		=> array(
							'ID' 			=> $data['ID'],
							'email' 		=> $email,
							'nev' 			=> $data['Vezetéknév'].' '.$data['Keresztnév'],
							'jelszo' 		=> md5(uniqid()),
							'aktivalva' 	=> $data['Feliratkozás ideje'],
							'regisztralt' 	=> $data['Feliratkozás ideje'],
							'old_user' 		=> 1,
							'user_group' 	=> 'user'
						),
						'felhasznalo_adatok' => array(
							'szallitas_nev' 	=> $data['Vezetéknév'].' '.$data['Keresztnév'],
							'szallitas_irsz' 	=> $data['Irányítószám'],
							'szallitas_state' 	=> NULL,
							'szallitas_city' 	=> $data['Település'],
							'szallitas_uhsz' 	=> $data['Utca, házszám'],
							'szallitas_phone' 	=> $phone,
							'szamlazas_nev' 	=> $data['Vezetéknév'].' '.$data['Keresztnév'],
							'szamlazas_irsz' 	=> $data['Irányítószám'],
							'szamlazas_state' 	=> NULL,
							'szamlazas_city' 	=> $data['Település'],
							'szamlazas_uhsz' 	=> $data['Utca, házszám']
						)
					);
				}
			}

			unset($data);
			unset($lista);

			/* */
			if($prepared){
				$inserts_felh 			= array();
				$inserts_felh_head 		= array();
				$inserts_felh_d 		= array();
				$inserts_felh_d_head	= array('fiok_id', 'nev', 'ertek');

				foreach ( $prepared as $email => $data )
				{
					if ( !$inserts_felh_head )
					{
						foreach ($data['felhasznalok'] as $key => $value)
						{
							$inserts_felh_head[] = $key;
						}
					}

					$inserts_felh[] = $data['felhasznalok'];

					foreach ($data['felhasznalo_adatok'] as $key => $value)
					{
						$ins = array(
							'fiok_id' 	=> $data['felhasznalok']['ID'],
							'nev' 		=> $key,
							'ertek' 	=> $value
						);

						$inserts_felh_d[] = $ins;
					}
				}
			}
			/* */

			/* * /
			echo '<pre>';
			print_r($inserts_felh_d);
			/* */

			/* * /
			// Updater
			foreach ($inserts_felh_d as $data )
			{
				if( $data['nev'] == 'szallitas_phone') {
					if($data['ertek'] == '') continue;
					$up  = "UPDATE felhasznalo_adatok SET ertek = '".$data['ertek']."' WHERE fiok_id = ".$data['fiok_id'] . " and nev = 'szallitas_phone';";

					//$this->db->query($up);
				}
				//
			}
			/* */

			/* * /
			$this->db->multi_insert(
				'felhasznalok',
				$inserts_felh_head,
				$inserts_felh
			);
			/* */

			/* * /
			$this->db->multi_insert(
				'felhasznalo_adatok',
				$inserts_felh_d_head,
				$inserts_felh_d
			);
			/* */

			echo 'OK';
		}

		function clearadmin(){
			$timestamp 		= date('Y-m-d H:i:s');
			$last_timestamp = $_GET['last_orderhead_timestamp'];

			$show = true;

			if($show){
				header('Content-type: text/html');
				$xml = '<?xml version="2.0" encoding="UTF-8" standalone="yes" ?>';
				$xml .= "<!DOCTYPE ORDERS>\n";
			}

			switch($this->view->gets[2]){
				// Megrendelések
				case 'orders':

				$arg = array();
				$arg[excInProgress] = '1';
				$order = $this->admin->listAllOrder($arg);

				if(!$show){
					print_r($order);
				}

				if($show):
				$xml .= "<ORDERS>\n";
					foreach($order as $o):
					$szamlazasi_adat 	= json_decode($o[szamlazasi_keys],true);
					$szallitasi_adat 	= json_decode($o[szallitasi_keys],true);
					$verified 			= ($o[allapot] == 4) ? 1 : 0;
					$szallitas_ar 		= $o[szallitasi_koltseg];
					$kedvezmeny 		= ($o[kedvezmeny] > 0) ? ($o[kedvezmeny] * -1) : 0;
					$xml .= "<ORDER>\n";
						$xml .= "<ORDERHEAD_CODE>".$o[ID]."</ORDERHEAD_CODE>\n";
						$xml .= "<ORDERHEAD_TIMESTAMP>".$o[idopont]."</ORDERHEAD_TIMESTAMP>\n";

						$xml .= "<ORDERHEAD_PARTNER_CODE>".$o[userID]."</ORDERHEAD_PARTNER_CODE>\n";
						$xml .= "<ORDERHEAD_PARTNER_NAME>".$o[nev]."</ORDERHEAD_PARTNER_NAME>\n";
						$xml .= "<ORDERHEAD_PARTNER_ZIP>".$szamlazasi_adat[irsz]."</ORDERHEAD_PARTNER_ZIP>\n";
						$xml .= "<ORDERHEAD_PARTNER_CITY>".$szamlazasi_adat[city]."</ORDERHEAD_PARTNER_CITY>\n";
						$xml .= "<ORDERHEAD_PARTNER_ADDRESS>".$szamlazasi_adat[uhsz]."</ORDERHEAD_PARTNER_ADDRESS>\n";

						$xml .= "<ORDERHEAD_PARTNER_COUNTRY_INTERNATIONAL_CODE>HU</ORDERHEAD_PARTNER_COUNTRY_INTERNATIONAL_CODE>\n";

						$xml .= "<ORDERHEAD_PARTNER_LANG_CODE>HU</ORDERHEAD_PARTNER_LANG_CODE>\n";

						$xml .= "<ORDERHEAD_PARTNER_OTHER_DATA></ORDERHEAD_PARTNER_OTHER_DATA>\n";

						$xml .= "<ORDERHEAD_PARTNER_MAIL_IS_SAME>0</ORDERHEAD_PARTNER_MAIL_IS_SAME>\n";
						$xml .= "<ORDERHEAD_PARTNER_MAIL_NAME>".$szallitasi_adat[nev]."</ORDERHEAD_PARTNER_MAIL_NAME>\n";
						$xml .= "<ORDERHEAD_PARTNER_MAIL_ZIP>".$szallitasi_adat[irsz]."</ORDERHEAD_PARTNER_MAIL_ZIP>\n";
						$xml .= "<ORDERHEAD_PARTNER_MAIL_CITY>".$szallitasi_adat[city]."</ORDERHEAD_PARTNER_MAIL_CITY>\n";
						$xml .= "<ORDERHEAD_PARTNER_MAIL_ADDRESS>".$szallitasi_adat[uhsz]."</ORDERHEAD_PARTNER_MAIL_ADDRESS>\n";

						$xml .= "<ORDERHEAD_PARTNER_MAIL_COUNTRY_INTERNATIONAL_CODE>HU</ORDERHEAD_PARTNER_MAIL_COUNTRY_INTERNATIONAL_CODE>\n";

						$xml .= "<ORDERHEAD_PAYMENTMETHOD_CODE>".$o[fizetesMod]."</ORDERHEAD_PAYMENTMETHOD_CODE>\n";

						//$xml .= "<ORDERHEAD_DATE_SHIPPED>".date('Y-m-d')."</ORDERHEAD_DATE_SHIPPED>\n";
						//$xml .= "<ORDERHEAD_DATE_PAYMENT_DUE>".date('Y-m-d')."</ORDERHEAD_DATE_PAYMENT_DUE>\n";
						/*
						$xml .= "<ORDERHEAD_NO_VAT>1</ORDERHEAD_NO_VAT>\n";
						$xml .= "<ORDERHEAD_NOVATREASON_CODE>FAD</ORDERHEAD_NOVATREASON_CODE>\n";
						*/

						$xml .= "<ORDERHEAD_CURRENCY_ABBREVIATION>HUF</ORDERHEAD_CURRENCY_ABBREVIATION>\n";

						$xml .= "<ORDERHEAD_SUBJECT>GOLDFISHING.HU - ".$o[azonosito]."</ORDERHEAD_SUBJECT>\n";
						$xml .= "<ORDERHEAD_EXTRA_COMMENT></ORDERHEAD_EXTRA_COMMENT>\n";

						$xml .= "<ORDERHEAD_PAID>0</ORDERHEAD_PAID>\n";

						$xml .= "<ORDERHEAD_VERIFIED>".$verified."</ORDERHEAD_VERIFIED>\n";

						foreach($o[items][data] as $i):
							$ar = number_format($i[egysegAr]/1.27,2,".","");
						$xml .= "<ORDERITEM>\n";
							$xml .= "<ORDERITEM_CODE>".$i[ID]."</ORDERITEM_CODE>\n";

							$xml .= "<ORDERITEM_PRODUCT_CODE>".$i[termekID]."</ORDERITEM_PRODUCT_CODE>\n";
							//$xml .= "<ORDERITEM_STAT_NO>VTSZ 12345</ORDERITEM_STAT_NO>\n";
							//$xml .= "<ORDERITEM_PART_NO>abc123</ORDERITEM_PART_NO>\n";
							$xml .= "<ORDERITEM_NAME>".$i[termekNev]."</ORDERITEM_NAME>\n";
							//$xml .= "<ORDERITEM_NAME_TRANSLATION>Online Product 1</ORDERITEM_NAME_TRANSLATION>\n";
							$xml .= "<ORDERITEM_COMMENT></ORDERITEM_COMMENT>\n";
							$xml .= "<ORDERITEM_COMMENT_TRANSLATION></ORDERITEM_COMMENT_TRANSLATION>\n";
							$xml .= "<ORDERITEM_UNIT>db</ORDERITEM_UNIT>\n";
							$xml .= "<ORDERITEM_UNIT_TRANSLATION>pcs</ORDERITEM_UNIT_TRANSLATION>\n";
							$xml .= "<ORDERITEM_VAT_CODE>1</ORDERITEM_VAT_CODE>\n";
							$xml .= "<ORDERITEM_VAT_PERCENT>27</ORDERITEM_VAT_PERCENT>\n";
							$xml .= "<ORDERITEM_VAT_NAME>27 %</ORDERITEM_VAT_NAME>\n";

							$xml .= "<ORDERITEM_LIST_PRICE>".$ar."</ORDERITEM_LIST_PRICE>\n";
							$xml .= "<ORDERITEM_PRICE>".$ar."</ORDERITEM_PRICE>\n";
							$xml .= "<ORDERITEM_QTY>".$i[me]."</ORDERITEM_QTY>\n";

							$xml .= "<ORDERITEM_DISCOUNT_TYPE>0</ORDERITEM_DISCOUNT_TYPE>\n";
							$xml .= "<ORDERITEM_DISCOUNT_PERCENT></ORDERITEM_DISCOUNT_PERCENT>\n";
						$xml .= "</ORDERITEM>\n";
						endforeach;
						// Szállítási költség
							$szallitas_ar = number_format($szallitas_ar/1.27,2,".","");
							$xml .= "<ORDERITEM>\n";
								$xml .= "<ORDERITEM_CODE>".microtime()."</ORDERITEM_CODE>\n";

								$xml .= "<ORDERITEM_PRODUCT_CODE>100001</ORDERITEM_PRODUCT_CODE>\n";
								//$xml .= "<ORDERITEM_STAT_NO>VTSZ 12345</ORDERITEM_STAT_NO>\n";
								//$xml .= "<ORDERITEM_PART_NO>abc123</ORDERITEM_PART_NO>\n";
								$xml .= "<ORDERITEM_NAME>Szállítási költség</ORDERITEM_NAME>\n";
								//$xml .= "<ORDERITEM_NAME_TRANSLATION>Online Product 1</ORDERITEM_NAME_TRANSLATION>\n";
								$xml .= "<ORDERITEM_COMMENT></ORDERITEM_COMMENT>\n";
								$xml .= "<ORDERITEM_COMMENT_TRANSLATION></ORDERITEM_COMMENT_TRANSLATION>\n";
								$xml .= "<ORDERITEM_UNIT>db</ORDERITEM_UNIT>\n";
								$xml .= "<ORDERITEM_UNIT_TRANSLATION>pcs</ORDERITEM_UNIT_TRANSLATION>\n";
								$xml .= "<ORDERITEM_VAT_CODE>1</ORDERITEM_VAT_CODE>\n";
								$xml .= "<ORDERITEM_VAT_PERCENT>27</ORDERITEM_VAT_PERCENT>\n";
								$xml .= "<ORDERITEM_VAT_NAME>Szolgáltatás</ORDERITEM_VAT_NAME>\n";

								$xml .= "<ORDERITEM_LIST_PRICE>".$szallitas_ar."</ORDERITEM_LIST_PRICE>\n";
								$xml .= "<ORDERITEM_PRICE>".$szallitas_ar."</ORDERITEM_PRICE>\n";
								$xml .= "<ORDERITEM_QTY>1</ORDERITEM_QTY>\n";

								$xml .= "<ORDERITEM_DISCOUNT_TYPE>0</ORDERITEM_DISCOUNT_TYPE>\n";
								$xml .= "<ORDERITEM_DISCOUNT_PERCENT></ORDERITEM_DISCOUNT_PERCENT>\n";
							$xml .= "</ORDERITEM>\n";
						// Kedvezmény
							//$kedvezmeny = number_format($kedvezmeny/1.27,0,".","");
							$xml .= "<ORDERITEM>\n";
								$xml .= "<ORDERITEM_CODE>".microtime()."</ORDERITEM_CODE>\n";

								$xml .= "<ORDERITEM_PRODUCT_CODE>100002</ORDERITEM_PRODUCT_CODE>\n";
								//$xml .= "<ORDERITEM_STAT_NO>VTSZ 12345</ORDERITEM_STAT_NO>\n";
								//$xml .= "<ORDERITEM_PART_NO>abc123</ORDERITEM_PART_NO>\n";
								$xml .= "<ORDERITEM_NAME>Kedvezmény</ORDERITEM_NAME>\n";
								//$xml .= "<ORDERITEM_NAME_TRANSLATION>Online Product 1</ORDERITEM_NAME_TRANSLATION>\n";
								$xml .= "<ORDERITEM_COMMENT></ORDERITEM_COMMENT>\n";
								$xml .= "<ORDERITEM_COMMENT_TRANSLATION></ORDERITEM_COMMENT_TRANSLATION>\n";
								$xml .= "<ORDERITEM_UNIT>db</ORDERITEM_UNIT>\n";
								$xml .= "<ORDERITEM_UNIT_TRANSLATION>pcs</ORDERITEM_UNIT_TRANSLATION>\n";
								$xml .= "<ORDERITEM_VAT_CODE>1</ORDERITEM_VAT_CODE>\n";
								$xml .= "<ORDERITEM_VAT_PERCENT>0</ORDERITEM_VAT_PERCENT>\n";
								$xml .= "<ORDERITEM_VAT_NAME>Kedvezmény</ORDERITEM_VAT_NAME>\n";

								$xml .= "<ORDERITEM_LIST_PRICE>".$kedvezmeny."</ORDERITEM_LIST_PRICE>\n";
								$xml .= "<ORDERITEM_PRICE>".$kedvezmeny."</ORDERITEM_PRICE>\n";
								$xml .= "<ORDERITEM_QTY>1</ORDERITEM_QTY>\n";

								$xml .= "<ORDERITEM_DISCOUNT_TYPE>0</ORDERITEM_DISCOUNT_TYPE>\n";
								$xml .= "<ORDERITEM_DISCOUNT_PERCENT></ORDERITEM_DISCOUNT_PERCENT>\n";
							$xml .= "</ORDERITEM>\n";
					$xml .= "</ORDER>\n";
					endforeach;
					$xml .= "</ORDERS>\n";
				endif;
				break;
			}

			echo $xml;
		}

		function __destruct(){
			// RENDER OUTPUT
				//parent::bodyHead();					# HEADER
				//$this->view->render(__CLASS__);		# CONTENT
				//parent::__destruct();				# FOOTER
		}
	}

?>
