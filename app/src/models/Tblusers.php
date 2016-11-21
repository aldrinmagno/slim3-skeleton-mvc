<?php
namespace App\Model;

use Aura\SqlQuery\QueryFactory;
use Slim\Container;

class Tblusers
{
	protected $table;
	protected $id;

	protected $pdo;
	protected $query_factory;

	protected $select;
	protected $insert;
	protected $update;
	protected $delete;

	public function __construct(Container $c)
	{
		$this->pdo = $c->get('db');
		$this->table = 'tblUsers'; // Table
		$this->id = '' // ID

		$this->query_factory = new QueryFactory('mysql');
	}

	public function findBy($select, $where, $bind)
	{
		$this->select = $this->query_factory->newSelect();
		$this->select
			->cols($select)
        	->where($where)
			->from($this->db);

		$sth = $this->select->getStatement();

		return $this->pdo->fetchOne($sth, $bind);
	}

    public function allFindBy($select, $where, $bind)
	{
        $this->select = $this->query_factory->newSelect();
		$this->select
			->cols($select)
    		->where($where)
			->from($this->db);

		$sth = $this->select->getStatement();

		return $this->pdo->fetchAll($sth, $bind);
	}

	public function count($where, $bind)
	{
		$this->select = $this->query_factory->newSelect();
		$this->select
			->cols(array('COUNT(*) AS cnt'))
			->where($where)
    		->from($this->db);

		$sth = $this->select->getStatement();

		return $this->pdo->fetchOne($sth, $bind);
	}

	public function add($values = array())
	{
		$this->insert = $this->query_factory->newInsert();
		$this->insert
			->into($this->db)
			->cols($values);

		$sth = $this->pdo->prepare($this->insert->getStatement());
		$sth->execute($this->insert->getBindValues());

        // get the last insert ID
        $name = $this->insert->getLastInsertIdName($this->id);
        $id = $this->pdo->lastInsertId($name);
        return $id;
	}

    public function edit($id, $values)
    {
        $this->update = $this->query_factory->newUpdate();
        $this->update
            ->table($this->db)
            ->cols($values)
            ->where($this->id . ' = ?', $id)
            ->bindValues($values);

        // prepare the statement
        $sth = $this->pdo->prepare($this->update->getStatement());

        // execute with bound values
        $sth->execute($this->update->getBindValues());

    //    $stm = $this->update->getStatement();
    //    $sth = $this->pdo->fetchAffected($stm, $bind);

        return $sth;
    }
}
