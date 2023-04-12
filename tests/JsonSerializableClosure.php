<?php
/* ===========================================================================
 * Copyright (c) 2019-2021 Zindex Software
 *
 * Licensed under the MIT License
 * =========================================================================== */

namespace Opis\Closure\Test;

use Opis\Closure\ClosureScope;
use Opis\Closure\SerializableClosure;

class JsonSerializableClosure extends SerializableClosure
{
    public function __serialize()
    {
        if ($this->scope === null) {
            $this->scope = new ClosureScope();
            $this->scope->toserialize++;
        }

        $this->scope->serializations++;

        $scope = $object = null;
        $reflector = $this->getReflector();

        if ($reflector->isBindingRequired()){
            $object = $reflector->getClosureThis();
            static::wrapClosures($object, $this->scope);
            if ($scope = $reflector->getClosureScopeClass()) {
                $scope = $scope->name;
            }
        } else {
            if ($scope = $reflector->getClosureScopeClass()) {
                $scope = $scope->name;
            }
        }

        $this->reference = spl_object_hash($this->closure);

        $this->scope[$this->closure] = $this;

        $use = $this->transformUseVariables($reflector->getUseVariables());
        $code = $reflector->getCode();

        $this->mapByReference($use);

        $ret = array(
            'use' => $use,
            'function' => $code,
            'scope' => $scope,
            'this' => $object,
            'self' => $this->reference,
        );

        if (static::$securityProvider !== null && $this->scope->serializations === 1) {
            $ser = \serialize($ret);
            $ret = static::$securityProvider->sign($ser);
        }

        if (!--$this->scope->serializations && !--$this->scope->toserialize) {
            $this->scope = null;
        }

        return [json_encode($ret)];
    }

    public function __unserialize($data)
    {
        if (is_array($data) && count($data) === 1 && isset($data[0])) {
            $data = $data[0];
        }

        parent::__unserialize(json_decode($data, true));
    }

    public function unserialize($data)
    {
        $json = \unserialize($data);

        $this->__unserialize($json);
    }
}
