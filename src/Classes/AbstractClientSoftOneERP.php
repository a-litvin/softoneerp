<?php
/**
 * 2007-2021 BelVG
 *
 * * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://store.belvg.com/BelVG-LICENSE-COMMUNITY.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    BelVG
 * @copyright 2007-2021 BelVG
 * @license  https://store.belvg.com/BelVG-LICENSE-COMMUNITY.txt
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

namespace PrestaShop\Module\SoftOneERP\Classes;

use Symfony\Component\HttpClient\HttpClient;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

abstract class AbstractClientSoftOneERP
{
	/**
	 * HTTP Client
	 * @var \Symfony\Contracts\HttpClient\HttpClientInterface
	 */
	protected $client;

	/**
	 * Request Headers
	 * @var array
	 */
	protected $headers;

	/**
	 * Request Content
	 * @var string
	 */
	protected $content;

	/**
	 * Request clientID
	 * @var string
	 */
	protected $clientId;

	/**
	 * Request RunId
	 * @var int
	 */
	protected $runId;

	/**
	 * Request URL
	 * @var string
	 */
	protected $baseUrl;

	/**
	 * UserName API connection
	 * @var string
	 */
	protected $login;

	/**
	 * Password API connection
	 * @var string
	 */
	protected $password;

	/**
	 * AppID API connection
	 * @var int
	 */
	protected $appId;

	/**
	 * Company API connection
	 * @var int
	 */
	protected $company;

	/**
	 * Branch API connection
	 * @var int
	 */
	protected $branch;

	/**
	 * Module API connection (default 0)
	 * @var int
	 */
	protected $module;

	/**
	 * RefId API connection
	 * @var int
	 */
	protected $refId;

	/**
	 * Errors
	 * @var array
	 */
	protected $errors;

	/**
	 * Parameter entity save
	 * @var bool
	 */
	protected $onlyChanges = false;

    /**
     * Log filename
     * @var string
     */
    protected $log_filename = 'softoneerp_log_.txt';


	public static $default_values = [];

	/**
	 * ClientSoftOneERP constructor.
	 * @param $configuration
	 */
	public function __construct()
	{
		$configuration = SymfonyContainer::getInstance()->get("prestashop.module.softoneerp_block.form_provider")->getData();
		$this->client = HttpClient::create();
		$this->login = $configuration['softoneerp_block']['login'];
		$this->password = $configuration['softoneerp_block']['password'];
		$this->baseUrl = $configuration['softoneerp_block']['baseurl'];
		$this->appId = $configuration['softoneerp_block']['appid'];
		$this->company = $configuration['softoneerp_block']['company'];
		$this->branch = $configuration['softoneerp_block']['branch'];
		$this->module = $configuration['softoneerp_block']['module'];
		$this->refId = $configuration['softoneerp_block']['refid'];

	}

	/**
	 * Authorize API function
	 * @return false|mixed
	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
	 */
	public function authenticate()
	{
		$response = $this->client->request(
			'POST',
			$this->baseUrl,
			[
				'json' => [
					'service' => 'login',
					'username' => $this->login,
					'password' => $this->password,
					'appId' => $this->appId,
					'COMPANY' => $this->company,
					'BRANCH' => $this->branch,
					'MODULE' => $this->module,
					'REFID' => $this->refId,
				]
			]);

		$statusCode = $response->getStatusCode();

		if (200 !== $statusCode) {
            print_r($response);
			return false;
		}

		$this->headers = $response->getHeaders();

		$this->content = $response->getContent();

		$data = $this->toArray($this->content);

		if ($data['success'] == true) {
			$this->setClientId($data['clientID']);
		}

		return $data;
	}

	public abstract function getEntities();

	public abstract function save();

	/**
	 * Getting Errors
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Set requests Client ID
	 * @param $clientId
	 */
	public function setClientId($clientId)
	{
		$this->clientId = $clientId;
	}

	/**
	 * Get requests Client ID
	 * @return string
	 */
	public function getClientId()
	{
		return $this->clientId;
	}

	/**
	 * Set requests Run ID
	 * @param $runId
	 */
	public function setRunId($runId)
	{
		$this->runId = $runId;
	}

	/**
	 * Get requests Run ID
	 * @return int
	 */
	public function getRunId()
	{
		return $this->runId;
	}

	/**
	 * Convert request content in encoding windows-1253 to associative array
	 * @param $content
	 * @return mixed
	 */
	protected function toArray($content)
	{
		$content1 = iconv("windows-1253", "utf-8//IGNORE", $content);
		return json_decode($content1, true);
	}

	/**
	 * Confirmation POST request
	 * @param $savedIds
	 * @return false|mixed
	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
	 */
	public function confirmationRequest($savedIds)
	{		print_r($savedIds);

		$response = $this->client->request(
			'POST',
			$this->baseUrl . 'js/RDCJS/ConfirmationPost',
			[
				'body' => $this->generateBodyForConfirmation($savedIds)
			]);
		$statusCode = $response->getStatusCode();

		if (200 !== $statusCode) {
			return false;
		}

		$this->headers = $response->getHeaders();

		$this->content = $response->getContent();

		$data = $this->toArray($this->content);

		return $data;
	}

	/**
	 * Generate body for confirmation
	 * @param $array
	 * @return string
	 */
	protected function generateBodyForConfirmation($array)
	{
		$body = '{
			"clientID": "' . $this->getClientId() . '",
			"RunId": ' . $this->getRunId() . ',
			"ids": ';
		$res = '';
		foreach ($array as $id => $item) {
			if (!empty($res)) $res .= ',';
			$res .= '{"id":' . $item . '}';
		}
		return $body . '[' . $res . ']}';
	}

    protected function saveToLog(string $data)
    {
        $myFile = "/var/log/" . $this->log_filename;
        $fh = fopen($myFile, 'a');
        fwrite($fh, $data."\n\r ");
        fclose($fh);
    }
}