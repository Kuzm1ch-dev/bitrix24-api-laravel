<?php
declare(strict_types=1);

namespace Bitrix24Api\Batch;

use Bitrix24Api\ApiClient;
use Bitrix24Api\DTO\Pagination;
use Bitrix24Api\DTO\ResponseData;
use Bitrix24Api\DTO\Result;
use Bitrix24Api\DTO\Time;

class Batch
{
    protected const MAX_BATCH_PACKET_SIZE = 50;
    protected CommandCollection $commands;
    protected ApiClient $api;
    protected bool $halt;

    public function __construct(ApiClient $api, $halt = false)
    {
        $this->api = $api;
        $this->commands = new CommandCollection();
        $this->halt = $halt;
    }

    public function addCommand(string $method, array $params = [], ?string $commandName = null)
    {
        $this->commands->attach(new Command($method, $params, $commandName));
    }

    public function addStartListComman(string $method, array $params = [])
    {
        $params['start'] = $params['start'] ?? 0;
        $firstResult = $this->api->request($method, $params);
        $total = $firstResult->getResponseData()->getPagination()->getTotal();
        for ($i = 0; $i <= $total; $i += 50) {
            $params['start'] = $i;
            $this->addCommand($method, $params, 'get_' . $i);
        }
        return $this->getTraversable();
    }
    public function addNavListComman(string $method, array $params = [])
    {
        $firstResult = $this->api->request($method, $params);
        $total = $firstResult->getResponseData()->getPagination()->getTotal();
        for ($i = 1; $i <= ceil($total / 50); $i++) {
            $params['params']['NAV_PARAMS']['iNumPage'] = $i;
            $this->addCommand($method, $params, 'get_' . $i);
        }
        return $this->getTraversable();
    }

    /**
     * @return void
     * @todo: простой вызов
     */
    public function call(): \Generator
    {
        $apiCommands = $this->convertToApiCommands();
        $batchQueryCounter = 0;
        while (count($apiCommands)) {
            $batchQuery = array_splice($apiCommands, 0, self::MAX_BATCH_PACKET_SIZE);
            $batchResult = $this->api->request('batch', ['halt' => $this->halt, 'cmd' => $batchQuery]);
            $batchQueryCounter++;
            yield $batchResult;
        }
    }

    public function getTraversable(): \Generator
    {
        foreach ($this->call() as $batchItem => $batchResult) {
            $response = $batchResult->getResponseData();
            $resultDataItems = $response->getResult()->getResultData()['result'];
            $resultQueryTimeItems = $response->getResult()->getResultData()['result_time'];

            $resultNextItems = $response->getResult()->getResultData()['result_next'];
            $totalItems = $response->getResult()->getResultData()['result_total'];
            foreach ($resultDataItems as $singleQueryKey => $singleQueryResult) {
                if (!is_array($singleQueryResult)) {
                    $singleQueryResult = [$singleQueryResult];
                }
                if (!array_key_exists($singleQueryKey, $resultQueryTimeItems)) {
                    throw new \Exception(sprintf('query time with key %s not found', $singleQueryKey));
                }

                $nextItem = null;
                if ($resultNextItems !== null && array_key_exists($singleQueryKey, $resultNextItems)) {
                    $nextItem = $resultNextItems[$singleQueryKey];
                }

                $total = null;
                if ($totalItems !== null && count($totalItems) > 0 && isset($totalItems[$singleQueryKey])) {
                    $total = $totalItems[$singleQueryKey];
                }

                yield new ResponseData(
                    new Result($singleQueryResult),
                    Time::initFromResponse($resultQueryTimeItems[$singleQueryKey]),
                    new Pagination($nextItem, $total),
                    $this->commands->getByName($singleQueryKey)
                );
            }
        }
    }

    /**
     * @return array
     */
    private function convertToApiCommands(): array
    {
        $apiCommands = [];
        foreach ($this->commands as $itemCommand) {
            /**
             * @var Command $itemCommand
             */
            $apiCommands[$itemCommand->getName()] = sprintf(
                '%s?%s',
                $itemCommand->getMethod(),
                http_build_query($itemCommand->getParams())
            );
        }

        return $apiCommands;
    }
}
