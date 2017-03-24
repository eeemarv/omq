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

	public function set(string $id, array $data = [])
	{
		$version = $this->db->fetchColumn('select max(version)
			from xdb.events
			where id = ?', [$id]);

		$version = $version ? $version + 1 : 1;

		$data['version'] = $version;
		$data['id'] = $id;

		$insert = [
			'id'			=> $id,
			'version'		=> $version,
			'data'			=> json_encode($data),
			'ip'			=> $this->ip,
		];

		try
		{
			$this->db->insert('xdb.events', $insert);

			$this->redis->hmset('cwv_xdb_' . $id, $data);
			$this->redis->hmset('cwv_xdb_' . $version . '_' . $id, $data);
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
			$data = $this->redis->hmget('cwv_xdb_' . $id);

			if (!$data)
			{
				$data = $this->db->fetchColumn('select e1.data from xdb.events e1
					where e1.id = ? and e1.version =
						(select max(e2.version) from xdb.events e2
							where e1.id = e2.id', [$id]);

				if ($data)
				{
					$data = json_decode($data, true);

					$this->redis->hmset('cwv_xdb_' . $id, $data);

					return $data;
				}
				else
				{
					return [];
				}
			}

		}
		else
		{
			$data = $this->redis->hmget('cwv_xdb_' . $version . '_' . $id);

			if (!$data)
			{
				$data = $this->db->fetchColumn('select e1.data from xdb.events e1
					where e1.id = ? and e1.version = ?', [$id, $version]);

				if ($data)
				{
					$data = json_decode($data, true);

					$this->redis->hmset('cwv_xdb_' . $version . '_' . $id, $data);

					return $data;
				}
				else
				{
					return [];
				}
			}
		}

		return $data;
	}
}

