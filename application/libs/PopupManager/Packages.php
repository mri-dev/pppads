<?
namespace PopupManager;

class Packages
{
	private $db;
	private $arg;
  private $loaded_list = array();
  private $item = false;
  public $items = 0;

  const DBTABLE = 'packages';

	function __construct( $arg = array() )
	{
		$this->db 	= $arg[db];
		$this->arg 	= $arg;

		return $this;
	}

	public function getList( $arg = array() )
	{
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
			$this->loaded_list[] = $d;
		}

    if (isset($arg['id']))
		{
			$this->item = $this->loaded_list[0];
		}

		return $this->loaded_list;
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
