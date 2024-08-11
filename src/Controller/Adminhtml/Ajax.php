<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\Core\Controller\Adminhtml;

use Exception;
use FeWeDev\Base\Arrays;
use FeWeDev\Base\Json;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Ajax extends Action
{
    /** @var Arrays */
    protected $arrays;

    /** @var Json */
    protected $json;

    /** @var LoggerInterface */
    protected $logging;

    /** @var int */
    private $responseCode = 200;

    /** @var bool */
    private $responseResult = true;

    /** @var array */
    private $responseValues = [];

    public function __construct(
        Arrays $arrays,
        Json $json,
        Context $context,
        LoggerInterface $logging
    ) {
        parent::__construct($context);

        $this->arrays = $arrays;
        $this->json = $json;

        $this->logging = $logging;
    }

    protected function addResponseValue(string $key, $value): void
    {
        $keys = explode(':', $key);

        $this->responseValues = $this->arrays->addDeepValue($this->responseValues, $keys, $value);
    }

    protected function addResponseValues(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->addResponseValue($key, $value);
        }
    }

    protected function getResponseValue(string $key)
    {
        if (isset($this->responseValues[ $key ])) {
            return $this->responseValues[ $key ];
        }

        return null;
    }

    protected function resetResponseValues(): void
    {
        $this->responseValues = [];
    }

    public function setResponseCode(int $responseCode): void
    {
        $this->responseCode = $responseCode;
    }

    protected function setSuccessResponse(string $message): void
    {
        $this->responseResult = true;
        $this->responseValues = ['message' => $message];
    }

    protected function setErrorResponse(string $message): void
    {
        $this->responseResult = false;
        $this->responseValues = ['message' => $message];
    }

    protected function setFatalResponse(string $message): void
    {
        $this->setErrorResponse($message);
        $this->setResponseCode(500);
    }

    /**
     * @throws NotFoundException
     */
    public function dispatch(RequestInterface $request): ResponseInterface
    {
        /** @var Http $request */
        $request = $this->getRequest();

        if ($request->isAjax()) {
            try {
                parent::dispatch($request);
            } catch (Exception $exception) {
                $this->logging->error($exception);
                $this->setErrorResponse($exception->getMessage());
            }

            $responseData = $this->arrays->mergeArrays(['success' => $this->responseResult], $this->responseValues);

            /** @var \Magento\Framework\App\Response\Http $response */
            $response = $this->getResponse();

            $response->setHttpResponseCode($this->responseCode);
            $response->setHeader('Content-type', 'application/json');

            $response->setBody($this->json->encode($responseData));

            return $response;
        } else {
            return parent::dispatch($request);
        }
    }
}
