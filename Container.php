<?php

namespace Shuc324\Container;

use Closure;
use ArrayAccess;

class Container implements ArrayAccess
{

    protected $bindings;

    protected $instances;

    /**
     * 格式化
     * @param string $service
     * @return string
     */
    protected function normalize($service)
    {
        return is_string($service) ? ltrim($service, '\\') : $service;
    }

    /**
     * 获取具象
     * @param string $abstract 抽象
     * @return mixed
     */
    protected function getConcrete($abstract)
    {
        return !isset($this->bindings[$abstract]) ? $abstract : $this->bindings[$abstract]['concrete'];
    }

    /**
     * 是否是单例
     * @param string $abstract 抽象
     * @return bool
     */
    protected function isSingleton($abstract)
    {
        $abstract = $this->normalize($abstract);
        if (isset($this->instances[$abstract])) {
            return true;
        }
        if (!isset($this->bindings[$abstract]['singleton'])) {
            return false;
        }
        return $this->bindings[$abstract]['singleton'] === true;
    }

    /**
     * 是否可实例化
     * @param string|closure $concrete 具象
     * @param $abstract string 抽象
     * @return bool
     */
    protected function isBuildable($concrete, $abstract)
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * 生产实例
     * @param string|closure $abstract 抽象|闭包
     * @param array $parameters 参数
     * @return \stdClass
     */
    public function make($abstract, array $parameters = [])
    {
        $abstract = $this->normalize($abstract);
        // @todo 别名
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        $concrete = $this->getConcrete($abstract);
        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->make($concrete, $parameters);
        }
        if ($this->isSingleton($abstract)) {
            $this->instances[$abstract] = $object;
        }
        return $object;
    }

    public function build($concrete, array $parameters = [])
    {
        // @todo
        return new \stdClass();
    }

    /**
     * 绑定
     * @param string $abstract 抽象
     * @param Closure $concrete 具象
     * @param bool $singleton 是否单例
     */
    public function bind($abstract, Closure $concrete, $singleton = false)
    {
        $abstract = $this->normalize($abstract);
        $concrete = $this->normalize($concrete);

        $this->bindings[$abstract] = compact($concrete, $singleton);
    }

    public function offsetExists($key)
    {
        return isset($this->bindings[$this->normalize($key)]);
    }

    public function offsetGet($key)
    {
        return $this->make($key);
    }

    public function offsetSet($key, $value)
    {
        if (!$value instanceof Closure) {
            function () use ($value) {
                return $value;
            };
        } else {
            $this->bind($key, $value);
        }
    }

    public function offsetUnset($key)
    {
        $key = $this->normalize($key);
        unset($this->bindings[$key], $this->instances[$key]);
    }
    
    public function __get($key)
    {
        return $this[$key];
    }

    public function __set($key, $value)
    {
        $this[$key] = $value;
    }
}
