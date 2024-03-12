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
abstract class Ajax
    extends Action
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

    /**
     * @param Arrays $arrays
     * @param Json $json
     * @param Context $context
     * @param LoggerInterface $logging
     */
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

    /**
     * @param string $key
     * @param mixed $value
     */
    protected function addResponseValue(string $key, $value)
    {
        $keys = explode(':', $key);

        $this->responseValues = $this->arrays->addDeepValue($this->responseValues, $keys, $value);
    }

    /**
     * @param array $values
     */
    protected function addResponseValues(array $values)
    {
        foreach ($values as $key => $value) {
            $this->addResponseValue($key, $value);
        }
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    protected function getResponseValue(string $key)
    {
        if (isset ($this->responseValues[$key])) {
            return $this->responseValues[$key];
        }

        return null;
    }

    /**
     * Reset response values
     */
    protected function resetResponseValues()
    {
        $this->responseValues = [];
    }

    /**
     * @param int $responseCode
     */
    public function setResponseCode(int $responseCode)
    {
        $this->responseCode = $responseCode;
    }

    /**
     * @param string $message
     */
    protected function setSuccessResponse(string $message)
    {
        $this->responseResult = true;
        $this->responseValues = ['message' => $message];
    }

    /**
     * @param string $message
     */
    protected function setErrorResponse(string $message)
    {
        $this->responseResult = false;
        $this->responseValues = ['message' => $message];
    }

    /**
     * @param string $message
     */
    protected function setFatalResponse(string $message)
    {
        $this->setErrorResponse($message);
        $this->setResponseCode(500);
    }

    /**
     * Dispatch request
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     * @throws NotFoundException
     */
    public function dispatch(RequestInterface $request)
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

            $responseData =
                $this->arrays->mergeArrays(['success' => $this->responseResult], $this->responseValues);

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
