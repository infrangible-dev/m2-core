<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Infrangible\Core\Controller\Adminhtml;

use Magento\Framework\App\Response\HttpInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class Ajax extends Json
{
    /** @var bool */
    private $responseResult = true;

    protected function setSuccessResponse(string $message): void
    {
        $this->responseResult = true;
        $this->addResponseValue(
            'message',
            $message
        );
    }

    protected function setErrorResponse(string $message): void
    {
        $this->responseResult = false;
        $this->addResponseValue(
            'message',
            $message
        );
    }

    protected function setFatalResponse(string $message): void
    {
        $this->setErrorResponse($message);
        $this->setResponseCode(500);
    }

    protected function handleException(\Exception $exception): void
    {
        $this->logging->error($exception);
        $this->setErrorResponse($exception->getMessage());
    }

    protected function processResponse(array $responseData): HttpInterface
    {
        $responseData = $this->arrays->mergeArrays(
            ['success' => $this->responseResult],
            $responseData
        );

        return parent::processResponse($responseData);
    }
}
