<?php

namespace service;

use Doctrine\DBAL\Connection as db;
use Predis\Client as Redis;
use Monolog\Logger;

/*
                              Table "xdb.events"
 Column  |            Type             |              Modifiers
---------+-----------------------------+--------------------------------------
 ts      | timestamp without time zone | default timezone('utc'::text, now())
 id      | character varying(255)      | not null
 version | integer                     | not null
 data    | jsonb                       |
 ip      | character varying(60)       |
 type    | character varying(60)       | not null
Indexes:
    "events_pkey" PRIMARY KEY, btree (id, type, version)


SQL:

create table if not exists xdb.events (
	ts timestamp without time zone default timezone('utc'::text, now()),
	id varchar(255),
	type varchar(60),
	version int,
	data jsonb,
	ip varchar(60)
);

alter table xdb.events add primary key (id, version, type);
*/

class xdb
{
	private $ip;
	private $db;
	private $redis;
	private $monolog;

	public function __construct(db $db, Redis $redis, Logger $monolog)
	{
		$this->db = $db;
		$this->redis = $redis;
		$this->monolog = $monolog;

		if (php_sapi_name() == 'cli')
		{
			$this->ip = '';
		}
		else if (isset($_SERVER['HTTP_CLIENT_IP']))
		{
			$this->ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		else if (isset($_SERVER['HTTP_X_FORWARDE‌​D_FOR']))
		{
			$this->ip = $_SERVER['HTTP_X_FORWARDE‌​D_FOR'];
		}
		else
		{
			$this->ip = $_SERVER['REMOTE_ADDR'];
		}
	}

	/*
	 *
	 */

	public function set(string $type, string $id, array $data)
	{
		$version = $this->db->fetchColumn('select max(version)
			from xdb.events
			where type = ? and id = ?', [$type, $id]);

		$version = $version ? $version + 1 : 1;

		$data['version'] = $version;

		$json = json_encode($data);

		$insert = [
			'id'			=> $id,
			'version'		=> $version,
			'type'			=> $type,
			'data'			=> $json,
			'ip'			=> $this->ip,
		];

		try
		{
			$this->db->insert('xdb.events', $insert);

			$this->redis->set('xdb_' . $type . '_' . $id, $json);
		}
		catch(Exception $e)
		{
			$this->monolog->error('xdb: ' . $e->getMessage());
			throw $e;
			exit;
		}
	}

	/*
	 * @return string (json format)
	 */

	public function get_json(string $type, string $id, int $version = 0)
	{

		if ($version === 0)
		{
			$key = 'xdb_' . $type . '_' . $id;

			$json = $this->redis->get($key);

			if ($json)
			{
				return $json;
			}

			$json = $this->db->fetchColumn('select e1.data from xdb.events e1
				where e1.type = ?
					and e1.id = ?
					and e1.version =
					(select max(e2.version) from xdb.events e2
						where e1.id = e2.id and e1.type = e2.type)', [$type, $id]);

			if ($json)
			{
				$this->redis->set($key, $json);

				return $json;
			}
			else
			{
				return '{}';
			}
		}

		$json = $this->db->fetchColumn('select e1.data from xdb.events e1
			where e1.id = ? and e1.version = ?', [$id, $version]);

		return '{}';
	}

	/*
	 * @return array
	 */

	public function get(string $type, string $id, int $version = 0)
	{
		return json_decode($this->get_json($type, $id, $version), true);
	}

	/**
	 * @return boolean
	 */

	public function exists(string $type, string $id)
	{
		$key = 'xdb_' . $type . '_' . $id;

		$json = $this->redis->get($key);

		if ($json)
		{
			return true;
		}

		$id = $this->db->fetchColumn('select id from xdb.events e1
			where e1.type = ? and e1.id = ?', [$type, $id]);

		if ($id)
		{
			return true;
		}

		return false;
	}

	/*
	 * @return array (ids)
	 */

	public function search(string $type, array $ary)
	{
		$sql_where = ['type = ?'];
		$sql_param = [$type];

		foreach ($ary as $key => $val)
		{
			$sql_where[] = ' data->>\'' . $key . '\' = ? ';
			$sql_param[] = $val;
		}

		$sql_where = count($sql_where) ? ' where ' . implode(' and ', $sql_where) : '';

		$fetch = $this->db->fetchAssoc('select distinct id
			from xdb.events' . $sql_where, $sql_param);

		$ids = [];

		if (!$fetch)
		{
			return [];
		}

		foreach ($fetch as $f)
		{
			$ids[] = $f['id'];
		}

		return $ids;
	}


	/*
	 * @return array
	 */
/*
	public function get_by(array $ary)
	{
		$sql_where = [];
		$sql_param = [];

		foreach ($ary as $key => $val)
		{
			$sql_where[] = ' data->>\'' . $key . '\' = ? ';
			$sql_param[] = $val;
		}

		$sql_where = count($sql_where) ? ' and ' . implode(' and ', $sql_where) : '';

		$fetch = $this->db->fetchAssoc('select e1.data from xdb.events e1
			where e1.version =
				(select max(e2.version) from xdb.events e2
					where e1.id = e2.id)' . $sql_where, $sql_param);

		return $fetch;
	}
*/
}

