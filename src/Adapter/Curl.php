<?php
/**
 * User: lleblanc42
 * Date: 08/02/2021
 * Time: 16:00
 */

namespace Cloudflare\API\Adapter;

use Cloudflare\API\Auth\Auth;

class Curl implements Adapter
{
	private $headers;
	private $baseURI;

    /**
     * @inheritDoc
     */
    public function __construct(Auth $auth, string $baseURI = null)
    {
        if ($baseURI === null) $this->baseURI = 'https://api.cloudflare.com/client/v4/';
		else $this->baseURI = $baseURI;

        $this->headers = $auth->getHeaders();
    }


    /**
     * @inheritDoc
     */
    public function get(string $uri, array $data = [], array $headers = [])
    {
        return $this->request('get', $uri, $data, $headers);
    }

    /**
     * @inheritDoc
     */
    public function post(string $uri, array $data = [], array $headers = [])
    {
        return $this->request('post', $uri, $data, $headers);
    }

    /**
     * @inheritDoc
     */
    public function put(string $uri, array $data = [], array $headers = [])
    {
        return $this->request('put', $uri, $data, $headers);
    }

    /**
     * @inheritDoc
     */
    public function patch(string $uri, array $data = [], array $headers = [])
    {
        return $this->request('patch', $uri, $data, $headers);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $uri, array $data = [], array $headers = [])
    {
        return $this->request('delete', $uri, $data, $headers);
    }

    public function request(string $method, string $uri, array $data = [], array $headers = [])
    {
        if (!in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) throw new \InvalidArgumentException('Request method must be get, post, put, patch, or delete');

		if (empty($uri)) throw new \InvalidArgumentException('Request uri must be set, empty given');

		if (!empty($headers)) $this->headers = array_merge($this->headers, $headers);

		$url = $this->baseURI . $uri;
		$ch = curl_init();

		switch ($method) {
			case 'put':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");

				if (!empty($data)) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

				break;
			case 'delete':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");

				if (!empty($data)) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

				break;
			case 'patch':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");

				if (!empty($data)) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

				break;
			case 'post':
				curl_setopt($ch, CURLOPT_POST, true);

				if (!empty($data)) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

				break;
			default:
				if (!empty($data)) $url = sprintf("%s?%s", $url, http_build_query($data));

				break;
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		$formatted_headers = array();

		foreach ($this->headers as $key => $value) {
			$formatted_headers[] = $key . ': ' . $value;
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $formatted_headers);

		$response = curl_exec($ch);

		curl_close($ch);

        if (strpos($uri, '/dns_records/export') === false) $this->checkError($response);

        return $response;
    }

    private function checkError($response)
    {
        $json = json_decode($response);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JSONException();
        }

        if (isset($json->errors) && count($json->errors) >= 1) {
            throw new ResponseException($json->errors[0]->message, $json->errors[0]->code);
        }

        if (isset($json->success) && !$json->success) {
            throw new ResponseException('Request was unsuccessful.');
        }
    }
}
