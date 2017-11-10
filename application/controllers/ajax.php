<?
use PortalManager\Template;
use ProductManager\Products;
use Applications\Lookbooks;
use PopupManager\Creative;
use PopupManager\CreativeScreens;

class ajax extends Controller{
		private $votepool_db_prefix = 'poll_';

		function __construct(){
			parent::__construct();

			header("Access-Control-Allow-Origin: *");
		}

		function post(){
			extract($_POST);

			switch($type)
			{
				case 'logPopupClick':

					$this->db->insert(
						'popup_clicks',
						array(
							'creative_id' => $creative,
							'screen_id' => $screen,
							'session_id' => $sessionid,
							'closed' => $closed
						)
					);
				break;
				case 'getPopupScreenVariables':

					$ret['data'] 	= false;
					$ret['show'] 	= false;
					$data 			= array();
					$go 			= true;

					if (isset($url)) {
						$ret['url'] = $url;
					}

					$creative = (new Creative(array('db'=> $this->db)))->loadByURI( $url );
					$creative_settings = $creative->getSettings();

					// Időpont megvizsgálása, hogy mikor látta a creatívot utoljára
					$last_view_delay = $creative->getSessionLastViewAsSec($sessionid);
					$ret['last_view_diff_insec'] = $last_view_delay;

					// Megjelenés korlátozás
					$viewed_numbers = (int)$creative->getSessionViewedNumbers($sessionid);
					$ret['viewed_numbers'] = $viewed_numbers;

					// Időeltelés vizsgálat
					if ( $last_view_delay <= $creative_settings[view_sec_btw] )
					{
						$go = false;
					}

					// Megjelenés vizsgálat
					if ( $viewed_numbers >= $creative_settings[view_max] )
					{
						$go = false;
					}

					if ($go && $creative->hasData())
					{
						$cr = array();

						$cr['id'] 		= $creative->getID();
						$cr['type'] 	= $creative->getType();
						$cr['settings'] = $creative_settings;

						$data['creative'] = $cr;

						$screen = (new CreativeScreens($cr['id'], array('db' => $this->db)))->loadForAction($sessionid);

						$data['screen'] 		= $screen;
						$data['screen_loaded'] 	= $screen[id];

						if (empty($screen)) {
							$data = array();
						}
					}


					if (!empty($data))
					{
						$ret['data'] 	= $data;
						$ret['success'] = 1;
						$ret['show'] 	= true;
					}

					echo json_encode( $ret );
				break;

				case 'logPopupScreenshow':
					$ret[post] = $_POST;
					$creative = (new Creative(array('db'=> $this->db)))->load( $creative );
					$creative->logShow( $sessionid, $screen );
				break;

				case 'setOrderArchived':
					$this->db->query("UPDATE orders SET archivalt = 1 WHERE ID = ".$id);
				break;
				case 'getPopupScreenTemplateStrings':
					$ret = array(
						'error' => false,
						'data' 	=> false
					);
					$q 	= "SELECT metakey, metavalue FROM ".\PopupManager\CreativeScreen::DB_SETTINGS_TABLE." WHERE groupkey = 'template' and campaign_id = $screen_id;";
					$qq = $this->db->query( $q );

					$data = $qq->fetchAll(\PDO::FETCH_ASSOC);

					$bdata = array();

					foreach ($data as $d) {
						$value = trim($d['metavalue']);
						$value = rtrim($value ,'"');
						$value = ltrim($value ,'"');

						$bdata[$d['metakey']] = $value;
					}

					$ret['data'] = $bdata;

					echo json_encode( $ret );
				break;
				case 'savePopupScreenDatasets':

					$temp = array( 'settings', 'screen', 'content', 'interacion', 'links' );

					foreach ( $temp as $key ) {
						$iq = "SELECT ID FROM ".\PopupManager\CreativeScreen::DB_SETTINGS_TABLE." WHERE groupkey = 'template' and metakey = '$key' and creative_id = $container_id and campaign_id = $screen_id;";

						$check = $this->db->query( $iq )->rowCount();

						if ($check > 0 )
						{ // Update
							$this->db->update(
								\PopupManager\CreativeScreen::DB_SETTINGS_TABLE,
								array(
									"metavalue" 	=> json_encode($_POST[$key], JSON_UNESCAPED_UNICODE),
									"lastupdate" 	=> NOW
								),
								"groupkey = 'template' and metakey = '".$key."' and creative_id = ".$container_id." and campaign_id = ".$screen_id
							);
						}
						else
						{
							$ins 	= array();
							$ins[] 	= array(
								$container_id,
								$screen_id,
								'template',
								$key,
								json_encode($_POST[$key], JSON_UNESCAPED_UNICODE),
								NOW
							);

							$i = $this->db->multi_insert(
								\PopupManager\CreativeScreen::DB_SETTINGS_TABLE,
								array('creative_id', 'campaign_id', 'groupkey', 'metakey', 'metavalue', 'lastupdate'),
								$ins,
								array(
									'debug' => false
								)
							);
						}
					}
				break;
				/**
				 * CASADA ÜZLET LOGÓ FELTÖLTÉS
				 *
				 * FORM
				 * @param string $type* Az ajax post feldolgozó type elágazása
				 * @param string $path* A feltöltendő fájl elérési útja
				 * @param string $name Meghatározott fájlnév legyen
				 *
				 * @uses jquery.form.js | http://jquery.malsup.com/form/#options-object
				 *
				 * */
				case 'uploadPlaceLogo':
					$ret 	= array(
						'success' 	=> false,
						'msg' 		=> 'Nem sikerült feltölteni a fájlt. Próbálja meg újra vagy értesítse a program fejlesztőjét a hibáról, ha folyamatosan fent áll.'
					);

					$path = rtrim($path,'/');

					$source = $_FILES['file']['tmp_name'];
					$ext 	= strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

					if( isset($name) )
					{
						$newFileName = $name.'.'.$ext;
					} else {
						$newFileName = microtime(true).'.'.$ext;
					}

					$dest 	= $path.'/'.$newFileName;
					$upload = move_uploaded_file($source,$dest);

					if ($upload) {
						$ret['success'] 	= true;
						$ret['msg'] 		= 'A fájl sikeresen feltöltve!';
						$ret['name'] 		= $newFileName;
						$ret['file'] 		= $dest;
					}

					echo json_encode($ret);
				break;

				/**
				 * CASADA PONT FELTÖLTÖTT LOGÓ MENTÉSE
				 *
				 * @param string $placeID Az üzlet ID-ja
				 * @param string $src A feltöltött kép elérési útja
				 * */
				case 'savePlaceLogoURL':
					$bind = array();

					$bind['logo'] 	= $src;
					$bind['id'] 	= (int)$placeID;

					$this->db->squery("UPDATE casada_shops SET logo = :logo WHERE ID = :id;", $bind);
				break;

				case 'searchUsers':
					$re = array(
						'error' => 0,
						'data' 	=> array()
					);

					$qry = $this->db->query("SELECT ID, nev, email FROM ".\PortalManager\Users::TABLE_NAME." WHERE nev like '%$search%' or email like '%$search%'");

					$re['data'] = $qry->fetchAll(\PDO::FETCH_ASSOC);
					$re['num'] 	= $qry->rowCount();

					echo json_encode( $re );
				break;

				case 'template':
					parse_str($arg, $arg_export);
					$temp = new Template( VIEW . 'templates/' );
					echo $temp->get( $key, $arg_export );
				break;
				case 'lookbook_remove_container':
					$lookbook = new Lookbooks ( array( 'db' => $this->db ) );
					$lookbook->removeContainer( $id );
				break;
				case 'search_product_for_lookbook':

					$srckey 	= $search;
					$srchashs 	= explode(" ", $srckey);
					$this->out( 'search_hashs', $srchashs );

					$temp 		= new Template( VIEW . 'templates/' );
					$filters 	= array();

					$arg 	= array(
						'filters' 	=> $filters,
						'search' 	=> $srchashs,
						'limit' 	=> 999,
						'page' 		=> 1
					);

					$products = (new Products( array( 'db' => $this->db ) ))->prepareList( $arg );
					$this->out( 'products', $products );
					$this->out( 'product_list', $products->getList() );
					$this->out( 'book', $book );
					$this->out( 'position', $position );
					$this->out( 'container', $container );

					echo $temp->get( 'lookbook_search_product', array( 'view' => $this->view) );

				break;
				case 'removeProductConnects':
					$products 	= new Products( array( 'db' => $this->db ) );
					$products->disconnectProducts( $idfrom, $idto );
				break;
				case 'addProductConnects':
					$products 	= new Products( array( 'db' => $this->db ) );
					$products->connectProducts( $idfrom, $idto );
				break;
				case 'loadProducts':
					$json = array();
					$json['arg']['by'] = $by;
					$json['arg']['val'] = $val;
					$json['arg']['template'] = $template;
					$json['arg']['mode'] = $mode;
					$json['arg']['fromid'] = $fromid;
					$json['result'] = null;
					$json['info']['results'] = 0;

					if ( empty($val) ) {
						return $json;
					}

					$temp 		= new Template( VIEW . 'templates/' );
					$products 	= new Products( array( 'db' => $this->db ) );
					$product 	= $products->get( $fromid );

					$exc_id_list = array();
					$exc_id_list[] = $fromid;

					if( $product['related_products_ids'] )
					foreach ( $product['related_products_ids'] as $id) {
						$exc_id_list[] = $id;
					}

					$arg = array(
						'admin' => true,
						'limit' => -1,
						'filters' => array(
							'nev' => $val
						),
						'except' => array(
							'ID' => $exc_id_list
						)
					);
					$products_list = $products->prepareList( $arg )->getList();

					$json['info']['results'] = $products->getItemNumbers();
					$data = array(
						'list' 	=> $products_list,
						'id' 	=> $fromid,
						'mode' 	=> 'add',
						'howdo' => $howdo
					);

					$json['result'] = $temp->get( 'products_'.$template, $data);

					echo json_encode($json);
				break;
				case 'uzenetek':
					switch($mode){
						case 'toggleArchive':
							$this->db->query("UPDATE uzenetek SET archivalva = '".$val."' WHERE ID = ".$id);
						break;
					}
				break;
				case 'userChangeActions':
					switch($mode){
						case 'engedelyezve':
							$this->db->query("UPDATE felhasznalok SET engedelyezve = '".$val."' WHERE ID = ".$id);
						break;
					}
				break;
				case 'changeSettings':
					$this->db->query("UPDATE beallitasok SET bErtek = '".$val."' WHERE bKulcs = '".$key."'");
				break;
				case 'couponsChangeActions':
					switch($mode){
						case 'io':
							$v = ($val == '1') ? 1 : 0;
							$this->db->query("UPDATE ".\PortalManager\Coupons::DB_TABLE." SET active = $v WHERE coupon_code = '$code'");
						break;
					}
				break;
				case 'documentChangeActions':
					switch($mode){
						case 'io':
							$v = ($val == '1') ? 1 : 0;
							$this->db->query("UPDATE shop_documents SET lathato = $v WHERE ID = '$id'");
						break;
						case 'szaktanacsado':
							$v = ($val == '1') ? 1 : 0;
							$this->db->query("UPDATE shop_documents SET szaktanacsado_only = $v WHERE ID = '$id'");
						break;
					}
				break;
				case 'termekChangeActions':
					switch($mode){
						case 'putInKategoria';
							$param = array();
							$param[autoRemove] = true;
							$this->AdminUser->putTermekInListingKategoria($tid, $mid, $gyid, $param);
						break;
						case 'cikkszam':
							$val = ($val == '') ? 'NULL' : "'".$val."'";
							$this->db->query("UPDATE shop_termekek SET cikkszam = $val WHERE ID = $id");
						break;
						case 'raktar_keszlet':
							$val = ($val == '') ? 0 : (int)$val;
							$this->db->query("UPDATE shop_termekek SET raktar_keszlet = $val WHERE ID = $id");
						break;
						case 'kategoria':
							$this->db->query("UPDATE shop_termekek SET termek_kategoria = $val WHERE ID = $id");
						break;
						case 'marka':
							$this->db->query("UPDATE shop_termekek SET marka = $val WHERE ID = $id");
						break;
						case 'szin':
							$this->db->query("UPDATE shop_termekek SET szin = '$val' WHERE ID = $id");
						break;
						case 'meret':
							$this->db->query("UPDATE shop_termekek SET meret = '$val' WHERE ID = $id");
						break;
						case 'netto_ar':
							$nt = round($val);
							$br = round($val*1.27);

							$this->db->query("UPDATE shop_termekek SET netto_ar = $nt, brutto_ar = $br WHERE ID = $id");
						break;
						case 'brutto_ar':
							$nt = round($val/1.27);
							$br = round($val);

							$this->db->query("UPDATE shop_termekek SET netto_ar = $nt, brutto_ar = $br WHERE ID = $id");
						break;
						case 'akcios_netto_ar':
							$nt = round($val);
							$br = round($val*1.27);

							$this->db->query("UPDATE shop_termekek SET akcios_netto_ar = $nt, akcios_brutto_ar = $br WHERE ID = $id");

							if ($nt == 0 || $br == 0) {
								$this->db->query("UPDATE shop_termekek SET akcios = 0 WHERE ID = $id");
							}
						break;
						case 'akcios_brutto_ar':
							$nt = round($val/1.27);
							$br = round($val);

							$this->db->query("UPDATE shop_termekek SET akcios_netto_ar = $nt, akcios_brutto_ar = $br WHERE ID = $id");

							if ($nt == 0 || $br == 0) {
								$this->db->query("UPDATE shop_termekek SET akcios = 0 WHERE ID = $id");
							}
						break;
						case 'egyedi_ar':
						  	if($val != '' && $val > 0){
								$ear = round($val);
							}else{
								$ear = 'null';
							}

							$this->db->query("UPDATE shop_termekek SET egyedi_ar = $ear WHERE ID = $id");
						break;
						case 'akcios_egyedi_brutto_ar':
						  	if($val != '' && $val > 0){
								$ear = round($val);
							}else{
								$ear = 'null';
							}

							$this->db->query("UPDATE shop_termekek SET akcios_egyedi_brutto_ar = $ear WHERE ID = $id");
						break;
						case 'szallitasi_ido':
							$this->db->query("UPDATE shop_termekek SET szallitasID = $val WHERE ID = $id");
						break;
						case 'allapot':
							$this->db->query("UPDATE shop_termekek SET keszletID = $val WHERE ID = $id");
						break;
						case 'showHideTermek':
							$v = ($val == '1') ? 1 : 0;
							$this->db->query("UPDATE shop_termekek SET lathato = $v WHERE ID = $id");

							if( $v == 0){
								$this->AdminUser->removeProductFromCart( $id );
							}

						break;
						case 'changePrimaryProduct':
							$v = ($val == '1') ? 1 : 0;
							$this->db->query("UPDATE shop_termekek SET fotermek = $v WHERE ID = $id");

						break;
						case 'changeTermekKep':
							$this->db->query("UPDATE shop_termekek SET profil_kep = '$i' WHERE ID = $id");
						break;
						case 'delTermekImg':
							$this->AdminUser->delTermekImage($tid, $i);
						break;
					}
				break;
				case 'casadashopChangeActions':
					switch($mode){
						case 'IO';
							$v = ($val == '1') ? 1 : 0;
							$this->db->query("UPDATE ".\PortalManager\CasadaShop::DB_TABLE." SET geo_show = $v WHERE ID = $id");
						break;
					}

				break;

			}
		}

		function get(){
			extract($_POST);

			$sub_page = '';

			switch($type){
				case 'checkCouponCodeUsage':
					$re = array(
						'error' => 0,
						'data' 	=> array()
					);



					echo json_encode( $re );
				break;



				case 'loadAddNewItemsOnOrder':
					$this->view->allapotok = $this->AdminUser->getMegrendeltTermekAllapotok();
				break;
				// Értesítő adatok összeállítása
				case 'getNotification':
					$re = array(
						'error' => 0,
						'data' 	=> array()
					);

					echo json_encode( $re );
				break;
				case 'loadTrafficAdder':
					$this->view->kulcsok 	= $this->traffic->getTipusKulcsok();
					$this->view->key 		= $key;
				break;
				case 'loadCheckKat':
					$this->view->modszerek 	= $this->AdminUser->getModszerek();
					$this->view->gyujtok 	= $this->AdminUser->getGyujtoKategoriak();
					$this->view->kat 		= $this->AdminUser->getKategoriakWhereTermekIn($id);

					$modszer_gyujtok = array();
					$modszer = array();

					foreach($this->view->kat as $k){
						if(!is_null($k[modszerID]) && !is_null($k[gyujtoID])){
							$modszer_gyujtok[] = $k[modszerID].'_'.$k[gyujtoID];
						}

						if(!is_null($k[modszerID]) && is_null($k[gyujtoID])){
							$modszer[] = $k[modszerID];
						}
					}

					$this->view->mod_gyujt 	= $modszer_gyujtok;
					$this->view->modsz 		= $modszer;
					$this->view->tid 		= $id;

				break;

				case 'loadGyujtok':
					$this->view->gyujtok = $this->AdminUser->getGyujtoKategoriaSub($modszerId);
				break;
				case 'loadModszerek':
					$this->view->index = $i;
					// Módszerek
					$this->view->modszerek = $this->AdminUser->getModszerek();
				break;
				case 'loadTermkatParameters':
					$this->view->parameterek = $this->AdminUser->getParameterOnTermekKategoria($katid);
				break;
				case 'loadCreateTermkatParameters':
					$this->view->parameterek = $this->AdminUser->getParameterOnTermekKategoria($katid);
				break;
				case 'nagykerListaActions':
					switch( $fnc ) {
						case 'checkItems':
							$sub_page = $fnc;

							$q = "
							SELECT 		x.*,
										t.netto_ar as old_netto_ar,
										t.brutto_ar as old_brutto_ar,
										t.akcios_netto_ar as old_akcios_netto_ar,
										t.akcios_brutto_ar as old_akcios_brutto_ar,
										t.ID as termek_id,
										t.egyedi_ar as old_egyedi_ar
							FROM nagyker_xls_termekek as x
							LEFT OUTER JOIN nagyker as n ON n.ID = x.nagyker_id
							LEFT OUTER JOIN shop_termekek as t ON t.nagyker_kod = x.nagyker_kod
							WHERE
								x.list_id = $id and
								t.marka IN (SELECT ID FROM shop_markak WHERE nagyker_id = x.nagyker_id)
							ORDER BY t.netto_ar DESC
							";

							$arg[multi] = 1;

							extract( $this->db->q( $q, $arg ));

							$this->view->data 	= $data;
							$this->view->id 	= $id;

						break;
						case 'showListItems':
							$sub_page = $fnc;

							$q = "
							SELECT 		x.*,
										n.nagyker_nev
							FROM nagyker_uploaded_xls as x
							LEFT OUTER JOIN nagyker as n ON n.ID = x.nagyker_id
							WHERE x.ID = $id
							";

							//$arg[multi] = 1;

							extract( $this->db->q( $q, $arg ));

							$csv = CSVParser::GET( $data[file_path] );

							$this->view->data = $data;

							$this->view->csv = $csv;

						break;
						case 'updateProductPrice':
							$sub_page 	= $fnc;
							$sdata 		= array();
							$updated_num= 0;
							$akcio 		= '';
							$egyedi_ar 	= 'NULL';
							$netto 		= 'netto_ar';
							$brutto 	= 'brutto_ar';

							parse_str($data, $sdata);

							$change = $sdata[priceUpdate];

							$update_key = $this->db->query("SELECT update_key FROM nagyker_updated_termekek GROUP BY update_key ORDER BY update_key DESC LIMIT 0,1")->fetch(PDO::FETCH_COLUMN);
							$update_key = (int)$update_key + 1;


						/*	echo '<pre>';
								print_r( $change );
							echo '</pre>';


							return false; */

							foreach( $change as $uid => $c ){
								$updated_num++;

								// Update product
								if( $c[akcios_netto] == 0 || $c[akcios_brutto] == 0 ){
									$akcio = ', akcios_netto_ar = 0, akcios_brutto_ar = 0, akcios = 0';
								}else{
									$akcio = ', akcios_netto_ar = '.$c[akcios_netto].', akcios_brutto_ar = '.$c[akcios_brutto].', akcios = 1';
								}

								if( $c[netto] != '' ) $netto = $c[netto]; else $netto = 'netto_ar';
								if( $c[brutto] != '' ) $brutto = $c[brutto]; else $brutto = 'brutto_ar';

								$egyedi_ar = ( $c[egyedi_ar] != 0 ) ? $c[egyedi_ar]  : 'NULL';

								$update = "UPDATE shop_termekek SET netto_ar = $netto, brutto_ar = $brutto, egyedi_ar = $egyedi_ar $akcio WHERE ID = $uid;";

								// Log change
								$netto_old = ( is_null($c[netto_old])) ? 0 : $c[netto_old];
								$brutto_old = ( is_null($c[brutto_old])) ? 0 : $c[brutto_old];
								$netto 	= ( is_null($c[netto])) ? 0 : $c[netto];
								$brutto = ( is_null($c[brutto])) ? 0 : $c[brutto];

								$this->db->insert(
									'nagyker_updated_termekek',
									array_combine(
									array('termek_id', 'update_key', 'netto_old', 'netto', 'brutto_old', 'brutto','akcios_netto_old', 'akcios_netto', 'akcios_brutto_old', 'akcios_brutto', 'egyedi_ar_old', 'egyedi_ar'),
									array($uid, $update_key, $netto_old, $netto, $brutto_old, $brutto, $c[akcios_netto_old], $c[akcios_netto], $c[akcios_brutto_old], $c[akcios_brutto], $c[egyedi_ar_old], $c[egyedi_ar])
									));

								//echo $update . '<br>';
								$this->db->query( $update );

							}

							// Flag updated state
							$this->db->update(
								'nagyker_uploaded_xls',
								array(
									'refreshed_at' => NOW
								),
								'ID = '.$list_id
							);

							$this->view->has_updated = ( $updated_num == 0 ) ? false : true;

							echo '<meta http-equiv="refresh" content="5">';

						break;
						case 'deleteList':
							$q = "
							SELECT 		*
							FROM nagyker_uploaded_xls
							WHERE ID = $id
							";

							//$arg[multi] = 1;

							extract( $this->db->q( $q, $arg ));

							// Remove file
							unlink('../src/'.$data[file_path]);

							// Delete registered list record
							$this->db->query("DELETE FROM nagyker_uploaded_xls WHERE ID = $id");

							// Clear temp products
							$this->db->query("DELETE FROM nagyker_xls_termekek WHERE list_id = $id");

							echo '<meta http-equiv="refresh" content="0">';
						break;
						case 'importToDatabase':
							$q = "
							SELECT 		*
							FROM nagyker_uploaded_xls
							WHERE ID = $id
							";

							//$arg[multi] = 1;

							extract( $this->db->q( $q, $arg ));

							// Clear before import
								$this->db->query( "DELETE FROM nagyker_xls_termekek WHERE nagyker_id = {$data['nagyker_id']} and list_id = {$id}" );

							// Import to database from csv
								$csv = CSVParser::GET( $data[file_path] );

								$insert_data = array();

								foreach ( $csv->data as $h => $d ) {
									$netto 			= (int)trim(str_replace(' ','',$d['nt']));
									$brutto 		= (int)trim(str_replace(' ','',$d['br']));
									$netto_akcios 	= (int)trim(str_replace(' ','',$d['nt_a']));
									$brutto_akcios 	= (int)trim(str_replace(' ','',$d['br_a']));
									$egyedi_ar 		= (int)trim(str_replace(' ','',$d['ea']));

									if($netto !== 0 && $d['kod'] != ''){
										$insert_data[] = array(
											'nagyker_id'	=> $data[nagyker_id],
											'list_id' 		=> $id,
											'nagyker_kod'	=> $d['kod'],
											'termek_nev'	=> addslashes($d[nev]),
											'netto_ar' 		=> $netto,
											'brutto_ar' 	=> $brutto,
											'akcios_netto_ar' 	=> $netto_akcios,
											'akcios_brutto_ar' 	=> $brutto_akcios,
											'egyedi_ar' 		=> $egyedi_ar
										);
									}
								}

								$insd = $this->db->multi_insert(
									'nagyker_xls_termekek',
									array( 'nagyker_id', 'list_id', 'nagyker_kod', 'termek_nev', 'netto_ar', 'brutto_ar', 'akcios_netto_ar', 'akcios_brutto_ar', 'egyedi_ar'),
									$insert_data,
									array( 'debug' => false )
								);


							// Flag timestamp on list database
								$this->db->update(
									'nagyker_uploaded_xls',
									array(
										'imported_at' => NOW
									),
									"ID = $id"
								);

							echo '<meta http-equiv="refresh" content="0">';
						break;
						default: break;
					}
				break;
			}

			$sub_page = ( $sub_page != '' ) ? '_'.$sub_page : '';

			$this->view->render(__CLASS__.'/'.__FUNCTION__.'/'.$type.$sub_page, true);
		}

		function traffic(){
			extract($_POST);
			switch($action){
				case 'add':
					try{
						$options = $_POST;
						$re = $this->traffic->add($options);
						echo '<span style="color:green;">'.$re.'</span>';
					}catch(Exception $e){
						echo '<span style="color:red;">Hiba történt: '.$e->getMessage().'</span>';
					}
				break;
			}
		}

		function __destruct(){
		}
	}

?>
