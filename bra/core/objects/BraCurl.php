<?php

namespace Bra\core\objects;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class BraCurl
{
	/**
	 * @var Client
	 */
	public $client;
	public $base_uri;

	public function __construct ($config = [], $req_type = '') {
		$config['headers'] = is_array($config['headers']) ? $config['headers'] : [];
		if ($req_type == 'ajax') {
			$config['headers'] = array_merge(
				[
					'Accept' => 'application/json, text/javascript, */*; q=0.01',
					'x-requested-with' => 'XMLHttpRequest'
				] ,
				$config['headers']
			);
		}
//		$config['verify'] = false;
		$this->client = new Client($config);
	}


	/**
	 * @param $url
	 * @param string $method
	 * @param array $data
	 * @return \Psr\Http\Message\StreamInterface  | \Psr\Http\Message\MessageInterface
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function fetch($url, $method = 'GET', $data = [])
	{
		$response = $this->client->request($method, $url, $data);
		return $response;
	}

	public function fetch_ansyc($url, $method = 'GET', $data = [])
	{
		$promise = $this->client->requestAsync($method, $url);
		$promise->then(
			function (ResponseInterface $res) {
				echo $res->getStatusCode() . "\n";
			},
			function (RequestException $e) {
				echo $e->getMessage() . "\n";
				echo $e->getRequest()->getMethod();
			}
		);
	}

	/**
	 * @param $url
	 * @param string $method
	 * @param array $data
	 * @return \Psr\Http\Message\StreamInterface | \Psr\Http\Message\MessageInterface
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function test_url($url, $method = 'GET', $data = [])
	{
		$response = $this->fetch($url, $method, $data);
		return $response;
	}

	public function get_content($url, $method = 'GET', $data = [], $format = true)
	{
		$response = $this->fetch($url, $method, $data);
		$content = $response->getBody();
		if ($format) {
			$content = json_decode($content, 1);
		}
		return $content;
	}

	public function get_api ($url, $method = 'GET', $form_data = [], $format = true) {
		$params = ['form_params' => $form_data];
		if($this->debug){
			$params['debug'] =  $this->debug;
		}

		$response = $this->client->request($method, $url, $params);

		$content = $response->getBody()->__toString();

		if ($format === true) {
			$content = json_decode($content, 1);
		}
		if ($format === "jsonp") {

		}
		return $content;
	}
}
