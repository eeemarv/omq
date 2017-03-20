<?php

namespace service;

use Aws\S3\S3Client;
use Monolog\Logger;

class s3
{
	private $monolog;
	private $img_bucket;
	private $client;

	private $img_types = [
		'jpg'	=> 'image/jpeg',
		'jpeg'	=> 'image/jpeg',
		'png'	=> 'image/png',
		'gif'	=> 'image/gif',
	];

	/**
	 *
	 */

	public function __construct(Logger $monolog)
	{
		$this->monolog = $monolog;

		$this->img_bucket = 'https://' . getenv('S3_IMG');

		$this->client = S3Client::factory([
			'signature'	=> 'v4',
			'region'	=> 'eu-central-1',
			'version'	=> '2006-03-01',
		]);
	}

	/*
	 *
	 */

	public function img_exists(string $filename)
	{
		return $this->client->doesObjectExist($this->img_bucket, $filename);
	}

	/*
	 *
	 */

	public function img_upload(string $filename, string $tmpfile)
	{
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

		$content_type = $this->img_types[$ext] ?? false;

		if (!$content_type)
		{
			return 'No valid file-extension.';
		}

		try {

			$this->client->upload($this->img_bucket, $filename, fopen($tmpfile, 'rb'), 'public-read', [
				'params'	=> [
					'CacheControl'	=> 'public, max-age=31536000',
					'ContentType'	=> $content_type,
				],
			]);

			return;
		}
		catch(Exception $e)
		{
			return 'Uploading img failed: ' . $e->getMessage();
		}
	}

	/**
	 *
	 */

	public function img_copy(string $source, string $destination)
	{
		return $this->copy($this->img_bucket, $source, $destination);
	}

	public function copy(string $bucket, string $source, string $destination)
	{
		try
		{
			$result = $this->client->getObject([
				'Bucket' => $bucket,
				'Key'    => $source,
			]);

			$this->client->copyObject([
				'Bucket'		=> $bucket,
				'CopySource'	=> $bucket . '/' . $source,
				'Key'			=> $destination,
				'ACL'			=> 'public-read',
				'CacheControl'	=> 'public, max-age=31536000',
				'ContentType'	=> $result['ContentType'],
			]);
		}
		catch (Exception $e)
		{
			return 'Copy failed: ' . $e->getMessage();
		}
	}

	/*
	 *
	 */

	public function img_del(string $filename)
	{
		return $this->del($this->img_bucket, $filename);
	}

	public function del(string $bucket, string $filename)
	{
		try
		{
			$this->client->deleteObject([
				'Bucket'	=> $bucket,
				'Key'		=> $filename,
			]);
		}
		catch (Exception $e)
		{
			return 'Delete failed: ' . $e->getMessage();
		}
	}

	/*
	 *
	 */

	public function img_list(string $marker = '0')
	{
		return $this->bucket_list($this->img_bucket, $marker);
	}

	public function bucket_list(string $bucket, $marker = '0')
	{
		$params = ['Bucket'	=> $bucket];

		if ($marker)
		{
			$params['Marker'] = $marker;
		}

		try
		{
			$objects = $this->client->getIterator('ListObjects', $params);

			return $objects;
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}
	}

	/**
	 *
	 */

	public function find_img(string $marker = '0')
	{
		$params = [
			'Bucket'	=> $this->img_bucket,
			'Marker'	=> $marker,
			'MaxKeys'	=> 1,
		];

		try
		{
			return $this->client->getIterator('ListObjects', $params)->current();
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}
	}
}
