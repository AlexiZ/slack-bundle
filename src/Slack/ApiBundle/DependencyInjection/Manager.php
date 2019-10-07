<?php

namespace Slack\ApiBundle\DependencyInjection;

use Symfony\Component\HttpFoundation\Request;

class Manager
{
    const CONTENT_TYPE_FORM = 'x-www-form-urlencoded';
    const SLACK_API_URL = 'https://slack.com/api/';
    const STATE_KEY = 'oauthslackstate';
    const PARAMETER_NAME_TOKEN = 'token';
    const PARAMETER_NAME_CHANNEL = 'channel';
    const PARAMETER_NAME_USER = 'user';

    public static $nameValues = [
        self::PARAMETER_NAME_TOKEN,
        self::PARAMETER_NAME_CHANNEL,
        self::PARAMETER_NAME_USER,
    ];

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    public function __construct(string $clientId,  string $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function authorize(Request $request)
    {
        if (Manager::STATE_KEY !== $request->query->get('state') || !$request->query->has('code')) {
            return [];
        }

        return $this->getCredentials($request->query->get('code'));
    }

    private function getCredentials(string $code): array
    {
        $url = self::SLACK_API_URL . 'oauth.access';
        $params = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'single_channel' => true,
        ];

        $response = $this->callPostApi($url, $params, self::CONTENT_TYPE_FORM);

        if (is_array($response) && 'true' == $response['ok']) {
            return [
                self::PARAMETER_NAME_TOKEN => $response['access_token'],
                self::PARAMETER_NAME_CHANNEL => $response['incoming_webhook']['channel_id'],
            ];
        }

        return [];
    }

    public function sendMessage(string $message, array $parameters)
    {
        if (!$this->doesUserExist($parameters[self::PARAMETER_NAME_USER], $parameters[self::PARAMETER_NAME_TOKEN])) {
            return false;
        }

        $url = self::SLACK_API_URL . 'chat.postMessage';

        $params = [
            self::PARAMETER_NAME_TOKEN => $parameters[self::PARAMETER_NAME_TOKEN],
            self::PARAMETER_NAME_CHANNEL => $parameters[self::PARAMETER_NAME_CHANNEL],
            'text' => $message,
            'as_user' => true,
            'username' => $this->getUserInfo($parameters[self::PARAMETER_NAME_USER], $parameters[self::PARAMETER_NAME_TOKEN])['real_name_normalized'],
        ];

        $response = $this->callPostApi($url, $params, self::CONTENT_TYPE_FORM);

        if (is_array($response) && 'true' == $response['ok']) {
            return true;
        }

        return false;
    }

    private function getUserInfo(string $userId, string $token): array
    {
        $url = self::SLACK_API_URL . 'users.info';

        $params = [
            'token' => $token,
            'user' => $userId,
        ];

        $response = $this->callGetApi($url, $params, self::CONTENT_TYPE_FORM);

        if (is_array($response) && 'true' == $response['ok']) {
            return $response['user']['profile'];
        }

        return [];
    }

    private function doesUserExist(string $userId, string $token): bool
    {
        $url = self::SLACK_API_URL . 'users.info';

        $params = [
            'token' => $token,
            'user' => $userId,
        ];

        $response = $this->callGetApi($url, $params, self::CONTENT_TYPE_FORM);

        if (is_array($response) && 'true' == $response['ok']) {
            return true;
        }

        return false;
    }

    public function callPostApi(string $url, array $data, string $contentType = 'json'): array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/' . $contentType . ', charset=utf8'
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // $output contains the output string
        $output = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch);

        return json_decode($output, true);
    }

    private function callGetApi(string $url, array $parameters = [], string $contentType = 'x-www-form-urlencoded'): array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url . (!empty($parameters) ? '?' . http_build_query($parameters) : ''));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/' . $contentType . ', charset=utf8'
        ]);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // $output contains the output string
        $output = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch);

        return json_decode($output, true);
    }
}