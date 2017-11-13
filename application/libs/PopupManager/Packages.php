<?
namespace PopupManager;

class Packages
{
	private $db;
	private $arg;
  private $loaded_list = array();
  private $item = false;
  public $items = 0;
	private $current_index = 0;

  const DBTABLE = 'packages';

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

    if (isset($arg['demo'])) {
      $q .= " and d.demo = 1 ";
    }

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

	public function avaiableDomains()
	{
		return (int)$this->item['domains'];
	}

	public function packageViews( $formated = false )
	{
		if ($formated) {
			return number_format($this->item['freeviews'], 0,"", " ");
		} else {
			return (int)$this->item['freeviews'];
		}
	}

	public function viewPrice()
	{
		return (float)$this->item['viewprice'];
	}

	public function price( $formated = false )
	{
		if ($formated) {
			return number_format($this->item['packageprice'], 0, "", " ");
		} else {
			return (float)$this->item['packageprice'];
		}
	}

	public function getID()
	{
		return (int)$this->item['ID'];
	}

  public function getName()
  {
    return $this->item['name'];
  }

  public function isDemo()
  {
    return ($this->item['demo'] == 1) ? true : false;
  }

	function __destruct()
	{
		$this->db 	= null;
		$this->arg 	= null;
	}
}
