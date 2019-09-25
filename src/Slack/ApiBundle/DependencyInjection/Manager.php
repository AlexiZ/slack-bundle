<?php

namespace Slack\ApiBundle\DependencyInjection;

class Manager
{
    const CONTENT_TYPE_FORM = 'x-www-form-urlencoded';
    const SLACK_API_URL = 'https://slack.com/api/';

    /**
     * @var string
     */
    private $apiToken;
    /**
     * @var string
     */
    private $user;
    /**
     * @var string
     */
    private $channel;

    public function __construct(string $apiToken, string $userId, string $channel)
    {
        $this->apiToken = $apiToken;
        $this->user = $userId;
        $this->channel = $channel;
    }

    public function sendMessage($message)
    {
        if (!$this->validatePrerequisites()) {
            return false;
        }

        $url = self::SLACK_API_URL . 'chat.postMessage';

        $params = [
            'token' => $this->apiToken,
            'channel' => $this->channel,
            'text' => $message,
            'as_user' => true,
            'username' => $this->getUserInfo($this->user)['real_name_normalized'],
        ];

        $response = $this->callPostApi($url, $params, self::CONTENT_TYPE_FORM);

        if (is_array($response) && 'true' == $response['ok']) {
            return true;
        }

        return false;
    }

    private function getUserInfo(string $userId): array
    {
        $url = self::SLACK_API_URL . 'users.info';

        $params = [
            'token' => $this->apiToken,
            'user' => $userId,
        ];

        $response = $this->callGetApi($url, $params, self::CONTENT_TYPE_FORM);

        if (is_array($response) && 'true' == $response['ok']) {
            return $response['user']['profile'];
        }

        return [];
    }

    private function validatePrerequisites(): bool
    {
        if (!$this->doesUserExist()) {
            return false;
        }

        return true;
    }

    private function doesUserExist(): bool
    {
        $url = self::SLACK_API_URL . 'users.info';

        $params = [
            'token' => $this->apiToken,
            'user' => $this->user,
        ];

        $response = $this->callGetApi($url, $params, self::CONTENT_TYPE_FORM);

        if (is_array($response) && 'true' == $response['ok']) {
            return true;
        }

        return false;
    }

    private function callPostApi(string $url, array $data, string $contentType = 'json'): array
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