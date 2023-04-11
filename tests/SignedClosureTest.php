<?php
/* ===========================================================================
 * Copyright (c) 2018-2021 Zindex Software
 *
 * Licensed under the MIT License
 * =========================================================================== */

namespace Opis\Closure\Test;

use Opis\Closure\SerializableClosure;

class SignedClosureTest extends ClosureTest
{
    public function testSecureClosureIntegrityFail()
    {
        if (method_exists($this, 'expectException')) {
            $this->expectException('\Opis\Closure\SecurityException');
        } else {
            $this->setExpectedException('\Opis\Closure\SecurityException');
        }

        $closure = function(){
            /*x*/
        };

        SerializableClosure::setSecretKey('secret');

        $value = serialize(new SerializableClosure($closure));
        $value = str_replace('*x*', '*y*', $value);
        unserialize($value);
    }

    public function testJsonSecureClosureIntegrityFail()
    {
        if (method_exists($this, 'expectException')) {
            $this->expectException('\Opis\Closure\SecurityException');
        } else {
            $this->setExpectedException('\Opis\Closure\SecurityException');
        }

        $closure = function(){
            /*x*/
        };

        SerializableClosure::setSecretKey('secret');

        $value = serialize(new JsonSerializableClosure($closure));
        $value = str_replace('*x*', '*y*', $value);
        unserialize($value);
    }

    public function testUnsecuredClosureWithSecurityProvider()
    {
        if (method_exists($this, 'expectException')) {
            $this->expectException('\Opis\Closure\SecurityException');
        } else {
            $this->setExpectedException('\Opis\Closure\SecurityException');
        }

        SerializableClosure::removeSecurityProvider();

        $closure = function(){
            /*x*/
        };

        $value = serialize(new SerializableClosure($closure));
        SerializableClosure::setSecretKey('secret');
        unserialize($value);
    }

    public function testJsonUnsecuredClosureWithSecurityProvider()
    {
        if (method_exists($this, 'expectException')) {
            $this->expectException('\Opis\Closure\SecurityException');
        } else {
            $this->setExpectedException('\Opis\Closure\SecurityException');
        }

        SerializableClosure::removeSecurityProvider();

        $closure = function(){
            /*x*/
        };

        $value = serialize(new JsonSerializableClosure($closure));
        SerializableClosure::setSecretKey('secret');
        unserialize($value);
    }

    public function testSecuredClosureWithoutSecuriyProvider()
    {
        SerializableClosure::setSecretKey('secret');

        $closure = function(){
            return true;
        };

        $value = serialize(new SerializableClosure($closure));
        SerializableClosure::removeSecurityProvider();
        $closure = unserialize($value)->getClosure();
        $this->assertTrue($closure());
    }

    public function testSecurityProviderPersistsAfterNestedSerialization()
    {
        SerializableClosure::setSecretKey('secret');

        $a = array();
        $x = null;
        $b = function() use(&$x){
            return $x;
        };
        $c = function($i) use (&$a) {
            $f = $a[$i];
            return $f();
        };
        $a[] = $b;
        $a[] = $c;
        $x = $c;

        $secProvider = SerializableClosure::getSecurityProvider();
        $this->s($c);
        $this->assertSame($secProvider, SerializableClosure::getSecurityProvider());
    }

    public function testSecurityProviderPersistsAfterFailedNestedSerialization()
    {
        SerializableClosure::setSecretKey('secret');

        $a = array();
        $x = null;
        $b = function() use(&$x){
            return $x;
        };
        $c = function($i) use (&$a) {
            $f = $a[$i];
            return $f();
        };
        $a[] = $b;
        $a[] = $c;
        $x = $c;

        $secProvider = SerializableClosure::getSecurityProvider();
        $value = serialize(new SerializableClosure($c));
        $value = str_replace('$a[$i]', '$a[$z]', $value);
        try {
            $u = unserialize($value);
        } catch (\Exception $e) {
            $caught = true;
        }
        $this->assertTrue(isset($caught));
        $this->assertSame($secProvider, SerializableClosure::getSecurityProvider());
    }

    public function testJsonSecuredClosureWithoutSecuriyProvider()
    {
        SerializableClosure::setSecretKey('secret');

        $closure = function(){
            return true;
        };

        $value = serialize(new SerializableClosure($closure));
        SerializableClosure::removeSecurityProvider();
        $closure = unserialize($value)->getClosure();
        $this->assertTrue($closure());
    }

    public function testInvalidSecuredClosureWithoutSecuriyProvider()
    {
        if (method_exists($this, 'expectException')) {
            $this->expectException('\Opis\Closure\SecurityException');
        } else {
            $this->setExpectedException('\Opis\Closure\SecurityException');
        }

        SerializableClosure::setSecretKey('secret');
        $closure = function(){
            /*x*/
        };

        $value = serialize(new SerializableClosure($closure));
        $value = str_replace('closure', 'cl0sure', $value);
        SerializableClosure::removeSecurityProvider();
        unserialize($value);
    }

    public function testInvalidJsonSecuredClosureWithoutSecuriyProvider()
    {
        if (method_exists($this, 'expectException')) {
            $this->expectException('\Opis\Closure\SecurityException');
        } else {
            $this->setExpectedException('\Opis\Closure\SecurityException');
        }

        SerializableClosure::setSecretKey('secret');
        $closure = function(){
            /*x*/
        };

        $value = serialize(new JsonSerializableClosure($closure));
        $value = str_replace('hash', 'ha5h', $value);
        SerializableClosure::removeSecurityProvider();
        unserialize($value);
    }

    public function testMixedEncodings()
    {
        $a = iconv('utf-8', 'utf-16', "Düsseldorf");
        $b = mb_convert_encoding("Düsseldorf", 'ISO-8859-1');

        $closure = function() use($a, $b) {
            return [$a, $b];
        };

        SerializableClosure::setSecretKey('secret');

        $value = serialize(new SerializableClosure($closure));
        $u = unserialize($value)->getClosure();
        $r = $u();

        $this->assertEquals($a, $r[0]);
        $this->assertEquals($b, $r[1]);
    }
}
