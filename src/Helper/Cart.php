<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use FeWeDev\Base\Arrays;
use Magento\Catalog\Model\Product\Option\Type\File;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;

class Cart
{
    /** @var Json */
    protected Json $serializer;

    /** @var Arrays */
    protected Arrays $arrays;

    /** @var RequestInterface */
    protected RequestInterface $request;

    /**
     * @param Json $serializer
     * @param Arrays $arrays
     * @param RequestInterface $request
     */
    public function __construct(Json $serializer, Arrays $arrays, RequestInterface $request)
    {
        $this->serializer = $serializer;
        $this->arrays = $arrays;
        $this->request = $request;
    }

    /**
     * Updates the custom options of the cart items with the options in the cart data and the uploaded files.
     *
     * @param \Magento\Checkout\Model\Cart $cart
     * @param array $cartData
     *
     * @phpstan-param array<int|string, mixed> $cartData item ID => item data with the options in "options"
     *
     * @return \Magento\Checkout\Model\Cart
     * @throws LocalizedException
     */
    public function addItemCustomOptions(
        \Magento\Checkout\Model\Cart $cart,
        array $cartData
    ): \Magento\Checkout\Model\Cart {
        foreach ($cartData as $itemId => $itemInfo) {
            $item = $cart->getQuote()->getItemById((int)$itemId);

            if (! $item instanceof Item) {
                continue;
            }

            $itemInfo = is_array($itemInfo) ? $itemInfo : [];
            $itemOptions = array_key_exists(
                'options',
                $itemInfo
            ) && is_array($itemInfo[ 'options' ]) ? $itemInfo[ 'options' ] : [];

            $fileNames = [];

            $requestParams = $this->request->getParams();

            foreach ($requestParams as $requestParamName => $requestParamValue) {
                if (preg_match(
                    sprintf(
                        '/cart_%s_options_([0-9]+)_file_action/',
                        $itemId
                    ),
                    (string)$requestParamName,
                    $matches
                )) {
                    $optionId = $matches[ 1 ];

                    $fileNames[ $optionId ] = $requestParamName;
                    $itemInfo[ $requestParamName ] = $requestParamValue;
                }
            }

            $buyRequestOption = $item->getOptionByCode('info_buyRequest');

            $buyRequestData = [];

            if ($buyRequestOption instanceof Option) {
                $buyRequestValue = $buyRequestOption->getValue();

                $buyRequestData = is_string($buyRequestValue) ? $this->serializer->unserialize($buyRequestValue) : [];
                $buyRequestData = is_array($buyRequestData) ? $buyRequestData : [];
            }

            $options = $buyRequestData[ 'options' ] ?? [];
            $options = is_array($options) ? $options : [];

            foreach ($itemOptions as $optionId => $optionValue) {
                $options[ $optionId ] = $optionValue;
            }

            $buyRequestData[ 'options' ] = $options;

            if ($buyRequestOption instanceof Option) {
                $buyRequestOption->setValue((string)$this->serializer->serialize($buyRequestData));
            }

            $optionsOption = $item->getOptionByCode('option_ids');
            $optionsOption = $optionsOption instanceof Option ? $optionsOption : null;

            $optionsOptionValue = $optionsOption?->getValue();
            $optionsOptionValue = is_string($optionsOptionValue) && $optionsOptionValue !== '' ? explode(
                ',',
                $optionsOptionValue
            ) : [];

            $product = $item->getProduct();
            $buyRequest = $item->getBuyRequest();

            foreach ($fileNames as $fileName) {
                $buyRequest->setData(
                    $fileName,
                    $this->request->getParam($fileName)
                );
            }

            $optionIds = [...array_keys($itemOptions), ...array_keys($fileNames)];

            $options = $buyRequest->getData('options');
            $options = is_array($options) ? $options : [];

            foreach ($optionIds as $optionId) {
                $option = $product->getOptionById((int)$optionId);

                if ($option === null) {
                    continue;
                }

                $group = $option->groupFactory($option->getType());

                if ($group instanceof File) {
                    $options[ 'files_prefix' ] = sprintf(
                        'cart_%d_',
                        $itemId
                    );
                }

                $group->setOption($option);
                $group->setProduct($product);
                $group->setData(
                    'request',
                    $buyRequest
                );
                $group->setData(
                    'process_mode',
                    AbstractType::PROCESS_MODE_FULL
                );

                $group->validateUserValue($options);

                $preparedValue = $group->prepareForCart();

                if ($preparedValue === null) {
                    $item->removeOption(
                        sprintf(
                            'option_%d',
                            $optionId
                        )
                    );

                    $optionIdKey = array_search(
                        $optionId,
                        $optionsOptionValue
                    );

                    if ($optionIdKey !== false) {
                        unset($optionsOptionValue[ $optionIdKey ]);
                    }
                } else {
                    $item->addOption(
                        [
                            'code'  => sprintf(
                                'option_%d',
                                $optionId
                            ),
                            'value' => $preparedValue
                        ]
                    );

                    $optionOption = $item->getOptionByCode(
                        sprintf(
                            'option_%d',
                            $optionId
                        )
                    );

                    if ($optionOption instanceof Option) {
                        $optionOption->setProduct($product);
                    }

                    if (! in_array(
                        $optionId,
                        $optionsOptionValue
                    )) {
                        $optionsOptionValue[] = $optionId;
                    }
                }
            }

            $optionsOptionValue = array_unique($optionsOptionValue);
            sort(
                $optionsOptionValue,
                SORT_NUMERIC
            );

            if ($optionsOption !== null) {
                $optionsOption->setValue(
                    implode(
                        ',',
                        $optionsOptionValue
                    )
                );
            } else {
                $item->addOption(
                    [
                        'code'  => 'option_ids',
                        'value' => implode(
                            ',',
                            $optionsOptionValue
                        )
                    ]
                );

                $optionsOption = $item->getOptionByCode('option_ids');

                if ($optionsOption instanceof Option) {
                    $optionsOption->setProduct($product);
                }
            }

            if ($buyRequestOption instanceof Option) {
                $buyRequestData = $buyRequest->getData();

                $buyRequestOption->setValue(
                    (string)$this->serializer->serialize(is_array($buyRequestData) ? $buyRequestData : [])
                );
            }
        }

        return $cart;
    }
}
