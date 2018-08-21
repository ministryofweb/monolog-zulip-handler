<?php
declare(strict_types=1);

namespace MinistryOfWeb\Monolog\Handler;

use Monolog\Handler\MissingExtensionException;
use Monolog\Handler\SocketHandler;
use Monolog\Logger;

/**
 * Sends notifications through Zulip API
 *
 * @author Marcus Jaschen <mail@marcusjaschen.de>
 * @see    https://zulipchat.com/api/
 */
class ZulipHandler extends SocketHandler
{
    /**
     * Hostname of the Zulip instance, e.g. 'monolog.example.com'
     *
     * @var string
     */
    private $host;

    /**
     * Username of the bot, e.g. 'monolog-bot@monolog.example.com'
     *
     * @var string
     */
    private $login;

    /**
     * Zulip Bot API token
     *
     * @var string
     */
    private $token;

    /**
     * Zulip stream or username
     *
     * @var string
     */
    private $destination;

    /**
     * Message topic in Zulip
     *
     * @var string
     */
    private $topic;

    /**
     * @param string $host Hostname of the Zulip instance
     * @param string $login Zulip Bot username
     * @param string $token Zulip Bot API token
     * @param string $destination Zulip stream or username
     * @param string $topic Message topic in Zulip, optional
     * @param int $level The minimum logging level at which this handler will be triggered
     * @param bool $bubble Whether the messages that are handled can bubble up the stack or not
     *
     * @throws MissingExtensionException If no OpenSSL PHP extension configured
     */
    public function __construct(
        string $host,
        string $login,
        string $token,
        string $destination,
        string $topic = '',
        int $level = Logger::CRITICAL,
        bool $bubble = true
    ) {
        if (! \extension_loaded('openssl')) {
            throw new MissingExtensionException('The OpenSSL PHP extension is required to use the ZulipHandler');
        }

        parent::__construct('ssl://' . $host . ':443', $level, $bubble);

        $this->host        = $host;
        $this->login       = $login;
        $this->token       = $token;
        $this->destination = $destination;
        $this->topic       = $topic;
    }

    /**
     * {@inheritdoc}
     *
     * @param  array $record
     *
     * @return string
     */
    protected function generateDataStream($record): string
    {
        $content = $this->buildContent($record);

        return $this->buildHeader($content) . $content;
    }

    /**
     * Builds the body of API call
     *
     * @param  array $record
     *
     * @return string
     */
    private function buildContent($record): string
    {
        $dataArray = $this->prepareContentData($record);

        return http_build_query($dataArray);
    }

    /**
     * Prepares content data
     *
     * @param  array $record
     *
     * @return array
     */
    protected function prepareContentData($record): array
    {
        $data = [
            'type'    => 'stream',
            'to'      => $this->destination,
            'content' => $record['formatted'],
        ];

        if (\strlen($this->topic) > 0) {
            $data['subject'] = $this->topic;
        }

        return $data;
    }

    /**
     * Builds the header of the API Call
     *
     * @param  string $content
     *
     * @return string
     */
    private function buildHeader($content): string
    {
        $headers = [
            'POST /api/v1/messages HTTP/1.1',
            'Host: ' . $this->host,
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: ' . \strlen($content),
            'Authorization: Basic ' . base64_encode($this->login . ':' . $this->token),
            '',
        ];

        return implode("\r\n", $headers);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $record
     */
    protected function write(array $record)
    {
        parent::write($record);
        $this->closeSocket();
    }
}
