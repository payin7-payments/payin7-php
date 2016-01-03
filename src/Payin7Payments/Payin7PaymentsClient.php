<?php
namespace Payin7Payments;

use Guzzle\Common\Collection;
use Guzzle\Service\Client;
use Guzzle\Service\Description\ServiceDescription;
use InvalidArgumentException;

class Payin7PaymentsClient extends Client
{
    /** @var string */
    const DEFAULT_CONTENT_TYPE = 'application/json';

    /** @var string */
    const DEFAULT_ACCEPT_HEADER = 'application/json';

    /** @var string */
    const USER_AGENT = 'payin7-php/1.0.0';

    /** @var int */
    const DEFAULT_CONNECT_TIMEOUT = 60;

    /** @var int */
    const DEFAULT_TIMEOUT = 60;

    /**
     * @var array
     */
    private static $required_configs = [
        'integration_id',
        'integration_key'
    ];

    /**
     * @param array $config
     * @return Payin7PaymentsClient
     */
    public static function getInstance($config = [])
    {
        $client = new self();

        $config = Collection::fromConfig($config, $client->getDefaultConfig(), static::$required_configs);

        $client->configure($config);
        $client->setUserAgent(self::USER_AGENT, true);

        return $client;
    }

    /**
     * @param Collection $config
     */
    protected function configure($config)
    {
        $this->setDefaultOption('headers', $config->get('headers'));

        // TODO: Verify if these two values are being passed correctly!
        $this->setDefaultOption('connect_timeout', $config->get('connect_timeout'));
        $this->setDefaultOption('timeout', $config->get('timeout'));

        $this->setDescription($this->getServiceDescriptionFromFile($config->get('service_description')));
        //$this->setErrorHandler();
    }

    public function getServiceDescriptionFromFile($description_file)
    {
        if (!file_exists($description_file) || !is_readable($description_file)) {
            throw new InvalidArgumentException('Unable to read API definition schema');
        }

        return ServiceDescription::factory($description_file);
    }

    /*private function setErrorHandler()
    {
        $this->getEventDispatcher()->addListener(
            'request.error',
            function (Event $event) {
                // Stop other events from firing when you override 401 responses
                $event->stopPropagation();

                if ($event['response']->getStatusCode() >= 400 && $event['response']->getStatusCode() < 600) {
                    $e = IntercomException::factory($event['request'], $event['response']);
                    $event['request']->setState(Request::STATE_ERROR, array('exception' => $e) + $event->toArray());
                    throw $e;
                }
            }
        );
    }*/

    public static function getDefaultConfig()
    {
        return [
            'service_description' => __DIR__ . '/Service/config/payin7.json',
            'headers' => [
                'Content-Type' => self::DEFAULT_CONTENT_TYPE,
                'Accept' => self::DEFAULT_ACCEPT_HEADER,
                'User-Agent' => self::USER_AGENT
            ],
            'connect_timeout' => self::DEFAULT_CONNECT_TIMEOUT,
            'timeout' => self::DEFAULT_TIMEOUT
        ];
    }
}
