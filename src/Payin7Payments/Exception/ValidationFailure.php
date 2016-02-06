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

namespace Payin7Payments\Exception;

use JsonSerializable;
use Serializable;

class ValidationFailure implements Serializable, JsonSerializable
{
    protected $name;
    protected $message;
    protected $extra_data;

    public function __construct($name, $failure_message, array $extra_data = null)
    {
        $this->name = $name;
        $this->message = $failure_message;
        $this->extra_data = $extra_data;
    }

    public function getExtraData()
    {
        return $this->extra_data;
    }

    public function setExtraData(array $data = null)
    {
        $this->extra_data = $data;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getMessage()
    {
        return $this->message;
    }

    #pragma mark - Serializable

    public function serialize()
    {
        return serialize(array(
            'name' => $this->name,
            'message' => $this->message,
            'extra_data' => $this->extra_data
        ));
    }

    public function unserialize($serialized)
    {
        $serialized = unserialize($serialized);
        $this->message = isset($serialized['message']) ? $serialized['message'] : null;
        $this->name = isset($serialized['name']) ? $serialized['name'] : null;
        $this->extra_data = isset($serialized['extra_data']) ? $serialized['extra_data'] : null;
    }

    #pragma mark - JsonSerializable

    public function jsonSerialize()
    {
        return json_encode(array(
            'name' => $this->name,
            'message' => $this->message,
            'extra_data' => $this->extra_data
        ));
    }
}
