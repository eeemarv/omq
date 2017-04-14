<?php

namespace service;

use Doctrine\DBAL\Connection as db;
use Predis\Client as Redis;
use Monolog\Logger;

/*
                            Table "xdb.events"
   Column    |            Type             |              Modifiers
-------------+-----------------------------+--------------------------------------
 ts          | timestamp without time zone | default timezone('utc'::text, now())
 id          | character varying(255)      | not null
 version     | integer                     | not null
 data        | jsonb                       |
 ip          | character varying(255)      |
Indexes:
    "events_pkey" PRIMARY KEY, btree (id, version)

SQL:

create table if not exists xdb.events (
	ts timestamp without time zone default timezone('utc'::text, now()),
	id varchar(255),
	version int,
	data jsonb,
	ip varchar(60)
);

alter table xdb.events add primary key (id, version);
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

	public function set(string $id, array $data)
	{
		$version = $this->db->fetchColumn('select max(version)
			from xdb.events
			where id = ?', [$id]);

		$version = $version ? $version + 1 : 1;

		$data['version'] = $version;
		$data['id'] = $id;

		$json = json_encode($data);

		$insert = [
			'id'			=> $id,
			'version'		=> $version,
			'data'			=> $json,
			'ip'			=> $this->ip,
		];

		try
		{
			$this->db->insert('xdb.events', $insert);

			$this->redis->set('cwv_xdb_' . $id, $json);

			$key = 'cwv_xdb_' . $version . '_' . $id;

			$this->redis->set($key, $json);
			$this->redis->expire($key, 86400);
		}
		catch(Exception $e)
		{
			$this->monolog->error('Database xdb: ' . $e->getMessage());
			throw $e;
			exit;
		}
	}

	/*
	 *
	 */

	public function get(string $id, int $version = 0)
	{

		if ($version === 0)
		{
			$key = 'cwv_xdb_' . $id;

			$json = $this->redis->get($key);

			if ($json)
			{
				return $json;
			}

			$json = $this->db->fetchColumn('select e1.data from xdb.events e1
				where e1.id = ? and e1.version =
					(select max(e2.version) from xdb.events e2
						where e1.id = e2.id)', [$id]);

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

		$key = 'cwv_xdb_' . $version . '_' . $id;

		$json = $this->redis->get($key);

		if ($json)
		{
			return $json;
		}

		$json = $this->db->fetchColumn('select e1.data from xdb.events e1
			where e1.id = ? and e1.version = ?', [$id, $version]);

		if ($json)
		{
			$this->redis->set($key, $json);
			$this->redis->expire($key, 86400);

			return $json;
		}

		return '{}';
	}

	/*
	 *
	 */

	public function search(array $ary)
	{
		$sql_where = [];
		$sql_param = [];

		foreach ($ary as $key => $val)
		{
			$sql_where[] = ' data->>\'' . $key . '\' = ? ';
			$sql_param[] = $val;
		}

		$sql_where = count($sql_where) ? ' where ' . implode(' and ', $sql_where) : '';

		$fetch = $this->db->fetchAssoc('select distinct id
			from xdb.events' . $sql_where, $sql_param);

		$ids = [];

		foreach ($fetch as $f)
		{
			$ids[] = $f['id'];
		}

		return $ids;
	}
}

