<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use FeWeDev\Base\Variables;
use Magento\Framework\Model\AbstractModel;

class Models
{
    /** @var Variables */
    protected Variables $variables;

    /**
     * @param Variables $variables
     */
    public function __construct(Variables $variables)
    {
        $this->variables = $variables;
    }

    /**
     * Returns the codes of the attributes whose value differs from the originally loaded value.
     *
     * @param AbstractModel $object
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function getChangedAttributeCodes(AbstractModel $object): array
    {
        $oldData = $object->getOrigData();
        $newData = $object->getData();

        return is_array($oldData) && is_array($newData) ? $this->variables->getChangedData($oldData, $newData) : [];
    }
}
