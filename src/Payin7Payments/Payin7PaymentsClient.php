<?php
/**
 * 2015-2016 Copyright (C) Payin7 S.L.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * DISCLAIMER
 *
 * Do not modify this file if you wish to upgrade the Payin7 module automatically in the future.
 *
 * @author    Payin7 S.L. <info@payin7.com>
 * @copyright 2015-2016 Payin7 S.L.
 * @license   http://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3 (GPL-3.0)
 */

namespace Payin7Payments;

use Guzzle\Common\Collection;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use Guzzle\Service\Client;
use Guzzle\Service\Description\ServiceDescription;
use InvalidArgumentException;
use Payin7Payments\Exception\Payin7APIException;
use Symfony\Component\EventDispatcher\Event;

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

    public function setConnectTimeout($timeout)
    {
        $this->setDefaultOption('connect_timeout', $timeout);
    }

    public function setTimeout($timeout)
    {
        $this->setDefaultOption('timeout', $timeout);
    }

    /**
     * @param Collection $config
     */
    protected function configure($config)
    {
        $this->setDefaultOption('headers', $config->get('headers'));

        $this->setDefaultOption('connect_timeout', $config->get('connect_timeout'));
        $this->setDefaultOption('timeout', $config->get('timeout'));

        $this->setDefaultOption(
            'auth',
            [
                $config->get('integration_id'),
                $config->get('integration_key'),
                'Basic'
            ]
        );

        $this->setDescription($this->getServiceDescriptionFromFile($config->get('service_description')));
        $this->setErrorHandler();
    }

    public function getServiceDescriptionFromFile($description_file)
    {
        if (!file_exists($description_file) || !is_readable($description_file)) {
            throw new InvalidArgumentException('Unable to read API definition schema');
        }

        return ServiceDescription::factory($description_file);
    }

    private function setErrorHandler()
    {
        $this->getEventDispatcher()->addListener(
            'request.error',
            function (Event $event) {
                // Stop other events from firing when you override 401 responses
                $event->stopPropagation();

                /** @var Response $response */
                $response = $event['response'];

                /** @var Request $request */
                $request = $event['request'];

                if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 600) {
                    $e = Payin7APIException::factory($request, $response);
                    /** @noinspection PhpUndefinedMethodInspection */
                    $request->setState(Request::STATE_ERROR, array('exception' => $e) + $event->toArray());
                    throw $e;
                }
            }
        );
    }

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
