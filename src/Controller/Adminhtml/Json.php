<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\Core\Controller\Adminhtml;

use FeWeDev\Base\Arrays;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Json extends Action
{
    /** @var Arrays */
    protected $arrays;

    /** @var \FeWeDev\Base\Json */
    protected $json;

    /** @var LoggerInterface */
    protected $logging;

    /** @var int */
    private $responseCode = 200;

    /** @var array */
    private $responseValues = [];

    public function __construct(
        Arrays $arrays,
        \FeWeDev\Base\Json $json,
        Context $context,
        LoggerInterface $logging
    ) {
        parent::__construct($context);

        $this->arrays = $arrays;
        $this->json = $json;
        $this->logging = $logging;
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
            } catch (\Exception $exception) {
                $this->handleException($exception);
            }

            return $this->processResponse($this->getResponseValues());
        } else {
            return parent::dispatch($request);
        }
    }

    protected function handleException(\Exception $exception): void
    {
        $this->logging->error($exception);

        $this->setResponseCode(500);
    }

    protected function processResponse(array $responseData): HttpInterface
    {
        /** @var \Magento\Framework\App\Response\Http $response */
        $response = $this->getResponse();

        $response->setHttpResponseCode($this->getResponseCode());
        $response->setHeader(
            'Content-type',
            'application/json'
        );

        $response->setBody($this->json->encode($responseData));

        return $response;
    }

    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    public function setResponseCode(int $responseCode): void
    {
        $this->responseCode = $responseCode;
    }

    public function getResponseValues(): array
    {
        return $this->responseValues;
    }

    protected function getResponseValue(string $key)
    {
        if (isset($this->responseValues[ $key ])) {
            return $this->responseValues[ $key ];
        }

        return null;
    }

    public function setResponseValues(array $responseValues): void
    {
        $this->responseValues = $responseValues;
    }

    protected function addResponseValues(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->addResponseValue(
                $key,
                $value
            );
        }
    }

    protected function addResponseValue(string $key, $value): void
    {
        $keys = explode(
            ':',
            $key
        );

        $this->responseValues = $this->arrays->addDeepValue(
            $this->responseValues,
            $keys,
            $value
        );
    }

    protected function resetResponseValues()
    {
        $this->responseValues = [];
    }
}
