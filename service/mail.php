<?php

namespace service;

use Predis\Client as Redis;

class mail
{
	private $redis;

	public function __construct(Redis $redis)
	{
		$this->redis = $redis;
	}

	public function queue(array $data)
	{
		$this->redis->lpush('omv_mail_queue_low_priority', json_encode($data));
		return $this;
	}

	public function queue_priority(array $data)
	{
		$this->redis->lpush('omv_mail_queue_high_priority', json_encode($data));
		return $this;
	}
}

