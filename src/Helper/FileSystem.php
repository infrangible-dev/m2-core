<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class FileSystem
{
    /** @var \Magento\Framework\Filesystem */
    protected $filesystem;

    public function __construct(\Magento\Framework\Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function getMediaPath(?string $path = null): string
    {
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

        return $mediaDirectory->getAbsolutePath($path);
    }

    public function getVarPath(?string $path = null): string
    {
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);

        return $mediaDirectory->getAbsolutePath($path);
    }
}
