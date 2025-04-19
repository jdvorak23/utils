<?php

namespace Cesys\Utils;

trait LazyProperty
{
	private array $lazyProperties = [];

	/**
	 * @param callable $factory
	 * @param string|null $name
	 * @return mixed
	 */
	protected function &getLazyProperty(callable $factory, ?string $name = null)
	{
		$name = $name ?? Strings::nameFromGetter(debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function']);
		if (array_key_exists($name, $this->lazyProperties)) {
			return $this->lazyProperties[$name];
		}
		$this->lazyProperties[$name] = $factory();
		return $this->lazyProperties[$name];
	}

	/**
	 * @param mixed $value
	 * @param string|null $name
	 * @return void
	 */
	protected function setLazyProperty($value, ?string $name = null): void
	{
		$name = $name ?? Strings::nameFromSetter(debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function']);
		$this->lazyProperties[$name] = $value;
	}

	protected function unsetLazyProperty(string $name): void
	{
		unset($this->lazyProperties[$name]);
	}

	protected function issetLazyProperty(string $name): bool
	{
		return isset($this->lazyProperties[$name]);
	}

	protected function isDeclaredLazyProperty(string $name): bool
	{
		return array_key_exists($name, $this->lazyProperties);
	}
}