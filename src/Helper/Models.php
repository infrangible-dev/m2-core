<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use FeWeDev\Base\Variables;
use Magento\Framework\Model\AbstractModel;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Models
{
    /** @var Variables */
    protected $variables;

    public function __construct(Variables $variables)
    {
        $this->variables = $variables;
    }

    public function getChangedAttributeCodes(AbstractModel $object): array
    {
        $oldData = $object->getOrigData();
        $newData = $object->getData();

        return is_array($oldData) && is_array($newData) ? $this->variables->getChangedData($oldData, $newData) : [];
    }
}
