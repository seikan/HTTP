<?php

/**
 * HTTP: A very simple cURL wrapper.
 *
 * Copyright (c) 2017 Sei Kan
 *
 * Distributed under the terms of the MIT License.
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright  2017 Sei Kan <seikan.dev@gmail.com>
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * @see       https://github.com/seikan/HTTP
 */
class HTTP
{
	/**
	 * cURL object.
	 *
	 * @var object
	 */
	protected $http;

	/**
	 * Collection of errors.
	 *
	 * @var array
	 */
	private $logs = [];

	/**
	 * Collection of cookies.
	 *
	 * @var array
	 */
	private $cookies = [];

	/**
	 * Initialize cURL object.
	 *
	 * @param array $options
	 */
	public function __construct($options = [])
	{
		$this->http = curl_init();

		curl_setopt($this->http, CURLOPT_FAILONERROR, true);
		curl_setopt($this->http, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->http, CURLOPT_AUTOREFERER, true);
		curl_setopt($this->http, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->http, CURLOPT_HEADER, true);
		curl_setopt($this->http, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->http, CURLOPT_ENCODING, 'gzip, deflate');
		curl_setopt($this->http, CURLOPT_HTTP_VERSION, '1.1');

		if (isset($options['userAgent'])) {
			curl_setopt($this->http, CURLOPT_USERAGENT, $options['userAgent']);
		}

		if (isset($options['timeout'])) {
			curl_setopt($this->http, CURLOPT_TIMEOUT, $options['timeout']);
		}

		if (isset($options['referer'])) {
			curl_setopt($this->http, CURLOPT_REFERER, $options['referer']);
		}

		if (isset($options['headers'])) {
			curl_setopt($this->http, CURLOPT_HTTPHEADER, $options['headers']);
		}

		if (isset($options['username']) && isset($options['password'])) {
			curl_setopt($this->http, CURLOPT_USERPWD, $options['username'] . ':' . $options['password']);
		}
	}

	/**
	 * Destroy PDO object.
	 */
	public function __destruct()
	{
		curl_close($this->http);
	}

	/**
	 * Get logs for debugging purpose.
	 *
	 * @return array
	 */
	public function getLogs()
	{
		return $this->logs;
	}

	/**
	 * Send a GET request.
	 *
	 * @param string $url
	 *
	 * @return array|null
	 */
	public function get($url)
	{
		curl_setopt($this->http, CURLOPT_URL, $url);
		curl_setopt($this->http, CURLOPT_HTTPGET, true);

		$this->logs[] = 'GET ' . $url;

		$response = curl_exec($this->http);

		if (!curl_errno($this->http)) {
			$code = curl_getinfo($this->http, CURLINFO_HTTP_CODE);
			$size = curl_getinfo($this->http, CURLINFO_HEADER_SIZE);

			$this->logs[] = '[STATUS] ' . $code;
			$headers = $this->parseHeader(substr($response, 0, $size));

			return [
				'header' => $headers,
				'body'   => substr($response, $size),
			];
		}

		$this->logs[] = '[ERROR] [' . curl_errno($this->http) . '] ' . curl_error($this->http);

		return null;
	}

	/**
	 * Send a POST request.
	 *
	 * @param string $url
	 * @param array  $fields
	 *
	 * @return array|null
	 */
	public function post($url, $fields = [])
	{
		curl_setopt($this->http, CURLOPT_URL, $url);
		curl_setopt($this->http, CURLOPT_POST, true);

		$queries = (!empty($fields)) ? http_build_query($fields) : '';

		if ($queries) {
			curl_setopt($this->http, CURLOPT_POSTFIELDS, $queries);
		}

		$this->logs[] = 'POST ' . $url . (($queries) ? (' ' . $queries) : '');

		$response = curl_exec($this->http);

		if (!curl_errno($this->http)) {
			$code = curl_getinfo($this->http, CURLINFO_HTTP_CODE);
			$size = curl_getinfo($this->http, CURLINFO_HEADER_SIZE);

			$this->logs[] = '[STATUS] ' . $code;
			$headers = $this->parseHeader(substr($response, 0, $size));

			return [
				'header' => $headers,
				'body'   => substr($response, $size),
			];
		}

		$this->logs[] = '[ERROR] [' . curl_errno($this->http) . '] ' . curl_error($this->http);

		return null;
	}

	/**
	 * Download a file to local.
	 *
	 * @param string $url
	 * @param string $path
	 *
	 * @return array|false
	 */
	public function download($url, $path)
	{
		curl_setopt($this->http, CURLOPT_HEADER, false);
		curl_setopt($this->http, CURLOPT_URL, $url);
		curl_setopt($this->http, CURLOPT_HTTPGET, true);

		if (is_dir($path)) {
			$path = $path . DIRECTORY_SEPARATOR . md5(microtime());
		}

		$buffer = tmpfile();
		curl_setopt($this->http, CURLOPT_WRITEHEADER, $buffer);

		if (($fp = fopen($path, 'w')) === false) {
			$this->logs[] = '[ERROR] Directory is not writable.';

			return false;
		}

		curl_setopt($this->http, CURLOPT_FILE, $fp);

		$this->logs[] = 'GET ' . $url;

		$response = curl_exec($this->http);

		curl_setopt($this->http, CURLOPT_HEADER, 1);

		fclose($fp);

		if (!curl_errno($this->http)) {
			$code = curl_getinfo($this->http, CURLINFO_HTTP_CODE);
			$this->logs[] = '[STATUS] ' . $code;

			rewind($buffer);
			$headers = $this->parseHeader(stream_get_contents($buffer));
			fclose($buffer);

			if (isset($headers['Content-disposition'])) {
				if (preg_match('/filename="([^"]+)/', $headers['Content-disposition'], $matches)) {
					rename($path, dirname($path) . DIRECTORY_SEPARATOR . $matches[1]);
					$path = dirname($path) . DIRECTORY_SEPARATOR . $matches[1];
				}
			}

			return [
				'file' => $path,
				'size' => filesize($path),
			];
		}

		$this->logs[] = '[ERROR] [' . curl_errno($this->http) . '] ' . curl_error($this->http);

		return false;
	}

	/**
	 * Upload file.
	 *
	 * @param string $url
	 * @param array  $fields
	 * @param array  $files
	 *
	 * @return array|null
	 */
	public function upload($url, $fields = [], $files = [])
	{
		curl_setopt($this->http, CURLOPT_URL, $url);
		curl_setopt($this->http, CURLOPT_POST, 1);

		if (!empty($files)) {
			foreach ($files as $key => $file) {
				if (!file_exists($file)) {
					continue;
				}

				$fields[$key] = '@' . (((strpos(PHP_OS, 'WIN') !== false)) ? str_replace('/', '\\\\', $file) : $file);
			}
		}

		$queries = (!empty($fields)) ? http_build_query($fields) : '';

		if ($queries) {
			curl_setopt($this->http, CURLOPT_POSTFIELDS, $queries);
		}

		$this->logs[] = 'POST ' . $url . (($queries) ? (' ' . $queries) : '');

		$response = curl_exec($this->http);

		if (!curl_errno($this->http)) {
			$code = curl_getinfo($this->http, CURLINFO_HTTP_CODE);
			$size = curl_getinfo($this->http, CURLINFO_HEADER_SIZE);

			$this->logs[] = '[STATUS] ' . $code;
			$headers = $this->parseHeader(substr($response, 0, $size));

			return [
				'header' => $headers,
				'body'   => substr($response, $size),
			];
		}

		$this->logs[] = '[ERROR] [' . curl_errno($this->http) . '] ' . curl_error($this->http);

		return null;
	}

	/**
	 * Use a HTTP proxy.
	 *
	 * @param string $host
	 * @param string $port
	 * @param string $username
	 * @param string $password
	 */
	public function useProxy($host, $port, $username, $password)
	{
		curl_setopt($this->http, CURLOPT_PROXY, $host . ':' . $port);
		curl_setopt($this->http, CURLOPT_PROXYUSERPWD, $username . ':' . $password);
	}

	/**
	 * Process headers received from HTTP request.
	 *
	 * @param string $raw
	 *
	 * @return string
	 */
	private function parseHeader($raw)
	{
		$headers = [];

		$rows = explode("\r\n", $raw);

		foreach ($rows as $row) {
			if (strpos($row, ':') === false) {
				continue;
			}

			list($key, $value) = explode(':', $row, 2);
			$headers[$key] = trim($value);

			if ($key == 'Set-Cookie') {
				$parts = explode(';', trim($value));

				foreach ($parts as $part) {
					parse_str($part, $chunks);
					$this->cookies = array_merge($this->cookies, $chunks);
				}
			}
		}

		curl_setopt($this->http, CURLOPT_HTTPHEADER, [
			'Cookie: ' . http_build_query($this->cookies, '', '; '),
		]);

		return $headers;
	}
}
