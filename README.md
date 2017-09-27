# HTTP

This is a very simple cURL wrapper for PHP.



## Usage

### Configuration

> \$http = new HTTP( \[**array** $options\] );

##### Options

| Parameter | Type       | Description                              |
| --------- | ---------- | ---------------------------------------- |
| userAgent | **string** | User agent string.                       |
| timeout   | **int**    | Value in seconds the HTTP will time out. |
| referer   | **string** | A HTTP referral.                         |
| headers   | **array**  | Any custom headers.                      |
| username  | **string** | Username for basic HTTP authentication.  |
| password  | **string** | Password for basic HTTP authentication.  |



```php
// Include core HTTP library
require_once 'class.HTTP.php';

// Initialize HTTP object
$http = new HTTP([
  	// User agent for Google Chrome
	'userAgent'	=> 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
  
  	// Request timed out in 60 seconds
  	'timeout'	=> 60,
  
  	// Simulate we clicked from https://www.google.com
  	'referer'	=> 'https://www.google.com',
]);
```



### Get Logs

Gets logs for debugging purpose.

> **array** \$http->getLogs( );

```php
$logs = $http->getLogs();

echo '<pre>';
print_r($logs);
echo '</pre>';
```



### GET Request

Sends a HTTP GET request.

> **array** \$http->get( **string** \$url );

```php
// Visit to https://www.reddit.com
$response = $http->get('https://www.reddit.com');

if ($response) {
  	echo $response['header'];
    echo $response['body'];
}
```



### POST Request

Sends a HTTP POST request.

>  **array** \$http->post( **string** \$url, \[**array** \$fields\] );

```php
// Simulate a form post for login at Reddit website
$response = $http->post('https://www.reddit.com/api/login/', [
	'op'		=> 'login-main',
	'user'		=> 'test',
	'passwd'	=> 'Gmxn3$dcSD',
  	'api_type'	=> 'json',
]);

if ($response) {
  	echo $response['header'];
    echo $response['body'];
}
```



### Download

Downloads a file

> **array** \$http->download( **string** \$url, **string** \$path );

```php
$response = $http->download('https://github.com/seikan/HTTP/archive/master.zip', '/var/www/');

if ($response) {
    echo 'File "'.$response['file'].'" with size '.$response['size'].' bytes has been downloads.';
}
```



### Upload

Uploads a file.

> **array** \$http->upload( **string** \$url, \[**array** $fields\], **array** \$files );

```php
$response = $http->upload('https://www.example.com/upload', [], [
	'attachment'	=> '/var/www/example.zip',
]);
```



### Using Proxy Server

Uses HTTP proxy server.

> \$http->useProxy( **string** \$host, **string** \$port, **string** \$username, **string** \$password );

```php
$http->useProxy('127.0.0.1', 3128, 'proxy_user', 'proxy_password');

$response = $http->get('https://www.whatismyip.com/');
```


