<?php
namespace PopupManager;

use PopupManager\Creative;

class Creatives 
{
	private $db;
	private $arg;

	function __construct( $arg = array() )
	{
		$this->db 	= $arg[db];
		$this->arg 	= $arg;

		return $this;
	}

	public function getList()
	{
		$q = "SELECT
			c.ID
		FROM ".\PopupManager\Creative::DB_TABLE." as c 
		WHERE 1=1
		";

		$list = $this->db->query($q);

		if ( $list->rowCount() == 0) 
		{
			return array();
		}

		$a = array();

		foreach ($list->fetchAll(\PDO::FETCH_ASSOC) as $d) 
		{
			$a[] = (new Creative($this->arg))->load($d[ID]);
		}

		return $a;
	}

	public function create($post)
	{
		if (empty($post['name'])) throw new \Exception("Kérjük, hogy adja meg a megjelenés elnevezését.");			
		if (empty($post['check_url'])) throw new \Exception("Kérjük, hogy adja meg az aktivitás URL-t, ahol érvényes legyen a felugróablak.");

		$settings = array();

		$type_settings = $post['settings']['type'];
		unset($post['settings']['type']);

		foreach ($type_settings[$post['type']] as $key => $value)
		{
			$settings[$key] = $value;
		}

		foreach ($post['settings'] as $key => $value)
		{
			$settings[$key] = $value;
		}
		unset($post['settings']);
		
		$this->db->insert(
			\PopupManager\Creative::DB_TABLE,
			$post
		);
		$lastinsert =  $this->db->lastInsertId();

		foreach ($settings as $key => $value) {
			$this->saveSettings( $lastinsert, 'settings', $key, $value);
		}
	
		return $lastinsert;				
	}

	public function save($cid, $post)
	{
		if (empty($post['name'])) throw new \Exception("Kérjük, hogy adja meg a megjelenés elnevezését.");			
		if (empty($post['check_url'])) throw new \Exception("Kérjük, hogy adja meg az aktivitás URL-t, ahol érvényes legyen a felugróablak.");

		$settings = array();

		$type_settings = $post['settings']['type'];
		unset($post['settings']['type']);

		foreach ($type_settings[$post['type']] as $key => $value)
		{
			$settings[$key] = $value;
		}

		foreach ($post['settings'] as $key => $value)
		{
			$settings[$key] = $value;
		}
		unset($post['settings']);
		
		$this->db->update(
			\PopupManager\Creative::DB_TABLE,
			$post,
			"ID = ".$cid
		);

		foreach ($settings as $key => $value) {
			$this->saveSettings( $cid, 'settings', $key, $value);
		}
				
	}


	private function saveSettings( $cid, $group, $key, $value)
	{
		$check = $this->db->squery("SELECT 1 FROM ".\PopupManager\Creative::DB_TABLE_SETTINGS." WHERE creative_id = :cid and groupkey = :group and metakey = :key;", array('cid' => $cid, 'group' => $group, 'key' => $key));
	
		if ($check->rowCount() != 0) 
		{
			// Update
			$this->db->update(
				\PopupManager\Creative::DB_TABLE_SETTINGS,
				array(
					'metavalue' => trim($value)
				),
				sprintf("creative_id = %d and groupkey = '%s' and metakey = '%s'", $cid, $group, $key)
			);
		}
		else 
		{
			// Insert
			$this->db->insert(
				\PopupManager\Creative::DB_TABLE_SETTINGS,
				array(
					'creative_id' => $cid,
					'groupkey' => $group,
					'metakey' => $key,
					'metavalue' => trim($value)
				)
			);
		}
	}

	function __destruct()
	{
		$this->db 	= null;
		$this->arg 	= null;
	}		
}