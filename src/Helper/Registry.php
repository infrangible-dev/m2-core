<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\Core\Helper;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Registry
{
    /** @var \Magento\Framework\Registry */
    protected $registry;

    public function __construct(\Magento\Framework\Registry $registry)
    {
        $this->registry = $registry;
    }

    public function register(string $key, $value, bool $graceful = false, bool $overwrite = false): void
    {
        if ($overwrite) {
            $previousValue = $this->registry($key);

            if ($previousValue !== null) {
                $this->unregister($key);
            }
        }

        $this->registry->register($key, $value, $graceful);
    }

    public function unregister(string $key): void
    {
        $this->registry->unregister($key);
    }

    public function registry(string $key)
    {
        return $this->registry->registry($key);
    }
}
