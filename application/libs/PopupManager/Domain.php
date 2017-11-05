<?
namespace PopupManager;

class Domain
{
	private $db;
	private $arg;
  private $loaded_list = array();
  private $item = false;
  public $items = 0;

  const DBTABLE = 'domains';

	function __construct( $arg = array() )
	{
		$this->db 	= $arg[db];
		$this->arg 	= $arg;

		return $this;
	}

	public function getList( $arg = array() )
	{
		$q = "SELECT
			d.ID,
      d.domain,
      d.active
		FROM ".self::DBTABLE." as d
		WHERE 1=1
		";

    if (isset($arg['id'])) {
      $q .= " and d.ID = ".(int)$arg['id'];
    }

    if (isset($arg['userid'])) {
      $q .= " and d.userid = ".(int)$arg['userid'];
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


	function __destruct()
	{
		$this->db 	= null;
		$this->arg 	= null;
	}
}
