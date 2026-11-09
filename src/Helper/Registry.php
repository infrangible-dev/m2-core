<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

class Registry
{
    /**
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        protected \Magento\Framework\Registry $registry
    ) {
    }

    /**
     * Registers the value under the key in the Magento registry.
     *
     * The registry is deprecated by Magento, but still used by its core. With graceful, an existing key is kept
     * silently; with overwrite, an existing value is replaced.
     *
     * @param string $key
     * @param mixed $value
     * @param bool $graceful
     * @param bool $overwrite
     *
     * @return void
     */
    public function register(string $key, mixed $value, bool $graceful = false, bool $overwrite = false): void
    {
        if ($overwrite) {
            $previousValue = $this->registry($key);

            if ($previousValue !== null) {
                $this->unregister($key);
            }
        }

        $this->registry->register($key, $value, $graceful);
    }

    /**
     * Removes the key from the Magento registry.
     *
     * @param string $key
     *
     * @return void
     */
    public function unregister(string $key): void
    {
        $this->registry->unregister($key);
    }

    /**
     * Returns the value registered under the key, or null if there is none.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function registry(string $key): mixed
    {
        return $this->registry->registry($key);
    }
}
