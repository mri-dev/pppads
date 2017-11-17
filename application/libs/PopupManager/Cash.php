<?
namespace PopupManager;

class Cash
{
	private $db;
	private $arg;
  private $loaded_list = array();
  private $item = false;
  public $items = 0;
	private $current_index = 0;

  const DBTABLE = 'cash_log';

	function __construct( $arg = array() )
	{
		$this->db 	= $arg[db];
		$this->arg 	= $arg;

		return $this;
	}

	public function getList( $arg = array() )
	{
		// Adatmennyiség reset
		$this->items = 0;
		// Index reset
		$this->current_index = 0;


		$q = "SELECT
			d.*
		FROM ".self::DBTABLE." as d
		WHERE 1=1
		";

    if (isset($arg['id'])) {
      $q .= " and d.ID = ".(int)$arg['id'];
    }

    if (isset($arg['felh_id'])) {
      $q .= " and d.felh_id = ".(int)$arg['felh_id'];
    }

    $q .= " ORDER BY d.state ASC, d.idopont DESC";

		$list = $this->db->query($q);

		if ( $this->items = $list->rowCount() == 0)
		{
			return array();
		}

		foreach ($list->fetchAll(\PDO::FETCH_ASSOC) as $d)
		{
			$this->items = $this->items + 1;
			$this->loaded_list[] = $d;
		}

    if (isset($arg['id']))
		{
			$this->item = $this->loaded_list[0];
		}

		return $this;
	}

	public function walk()
	{
		$do = true;

		// Ha üres a lista, akkor el se kezdődik a ciklus
		if ( empty($this->loaded_list) || !$this->loaded_list ) {
			return false;
		}

		// Aktuális index megvizsgálása, hogy van-e benne adat
		$check = $this->loaded_list[ $this->current_index ];

		if (empty($check) || !$check) {
			return false;
		}

		// Adat behelyezése a konténerbe
		$this->item = $check;

		// Index növelése a következő index értékre
		$this->current_index = $this->current_index + 1;

		return $do;
	}

  public function topup( $userid, $amount = 0 )
  {
    $hashkey = md5(uniqid().microtime());
    $customprefix = 'WPP-U'.$userid.'-'.substr(date('Y'),-2);

    $last_cmid = $this->db->query("SELECT custom_id FROM ".self::DBTABLE." WHERE felh_id = {$userid} ORDER BY ID DESC LIMIT 0,1")->fetchColumn();
    $last_cmid = (int)str_replace($customprefix, '', $last_cmid);
    $last_cmid++;
    $last_cmid = str_pad((string)$last_cmid, 4, "0", STR_PAD_LEFT);
    $customid = $customprefix.$last_cmid;

    $this->db->insert(
      self::DBTABLE,
      array(
        'hashkey' => $hashkey,
        'custom_id' => $customid,
        'felh_id' => $userid,
        'tipus' => 'egyenleg',
        'direction' => 'in',
        'cash' => $amount
      )
    );

    return array($hashkey, $customid);
  }

  public function getTransDate()
  {
    return $this->item['idopont'];
  }

  public function getAcceptDate()
  {
    return $this->item['idopont_validate'];
  }

	public function getID()
	{
		return (int)$this->item['ID'];
	}

  public function status()
	{
		return (int)$this->item['state'];
	}

  public function price()
	{
		return (int)$this->item['cash'];
	}

  public function getCustomID()
	{
		return $this->item['custom_id'];
	}

  public function getHashkey()
  {
    return $this->item['hashkey'];
  }

	function __destruct()
	{
		$this->db 	= null;
		$this->arg 	= null;
	}
}
