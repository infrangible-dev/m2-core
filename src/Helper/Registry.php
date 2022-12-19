<?php /** @noinspection PhpDeprecationInspection */

namespace Infrangible\Core\Helper;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Registry
{
    /** @var \Magento\Framework\Registry */
    protected $registry;

    /**
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(\Magento\Framework\Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param bool   $graceful
     * @param bool   $overwrite
     *
     * @return void
     */
    public function register(string $key, $value, bool $graceful = false, bool $overwrite = false)
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
     * @param string $key
     */
    public function unregister(string $key)
    {
        $this->registry->unregister($key);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function registry(string $key)
    {
        return $this->registry->registry($key);
    }
}
