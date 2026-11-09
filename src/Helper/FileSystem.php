<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class FileSystem
{
    /**
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        protected \Magento\Framework\Filesystem $filesystem
    ) {
    }

    /**
     * Returns the absolute path of the media directory, or of the given path within it.
     *
     * @param string|null $path
     *
     * @return string
     */
    public function getMediaPath(?string $path = null): string
    {
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

        return $mediaDirectory->getAbsolutePath($path);
    }

    /**
     * Returns the absolute path of the var directory, or of the given path within it.
     *
     * @param string|null $path
     *
     * @return string
     */
    public function getVarPath(?string $path = null): string
    {
        $varDirectory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);

        return $varDirectory->getAbsolutePath($path);
    }
}
