<?php

declare(strict_types=1);

namespace Infrangible\Core\Helper;

use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Model\AbstractModel;
use Psr\Log\LoggerInterface;
use Zend_Db_Statement_Interface;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Database
    extends AbstractHelper
{
    /** @var LoggerInterface */
    protected $logging;

    /** @var ResourceConnection */
    protected $resourceConnection;

    /** @var TransactionFactory */
    protected $transactionFactory;

    /** @var bool */
    private $queryLogging;

    /**
     * @param Context            $context
     * @param ResourceConnection $resourceConnection
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        Context $context,
        ResourceConnection $resourceConnection,
        TransactionFactory $transactionFactory)
    {
        parent::__construct($context);

        $this->logging = $context->getLogger();
        $this->resourceConnection = $resourceConnection;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @return AdapterInterface
     */
    public function getDefaultConnection(): AdapterInterface
    {
        return $this->resourceConnection->getConnection();
    }

    /**
     * @param string $resourceName
     *
     * @return AdapterInterface
     */
    public function getConnection(string $resourceName): AdapterInterface
    {
        return $this->resourceConnection->getConnection($resourceName);
    }

    /**
     * @param Select                $select
     * @param AdapterInterface|null $specialAdapter
     * @param bool                  $allowLogging
     *
     * @return array
     */
    public function fetchAll(Select $select, AdapterInterface $specialAdapter = null, bool $allowLogging = true): array
    {
        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(sprintf('Executing query: %s', $select->assemble()));
        }

        $result = ! is_null($specialAdapter) ? $specialAdapter->fetchAll($select) :
            $this->getDefaultConnection()->fetchAll($select);

        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(sprintf('Query result: %s', trim(print_r($result, true))));
        }

        return $result;
    }

    /**
     * @param Select                $select
     * @param AdapterInterface|null $specialAdapter
     * @param bool                  $allowLogging
     *
     * @return array
     */
    public function fetchAssoc(
        Select $select,
        AdapterInterface $specialAdapter = null,
        bool $allowLogging = true): array
    {
        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(sprintf('Executing query: %s', $select->assemble()));
        }

        $result = ! is_null($specialAdapter) ? $specialAdapter->fetchAssoc($select) :
            $this->getDefaultConnection()->fetchAssoc($select);

        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(sprintf('Query result: %s', trim(print_r($result, true))));
        }

        return $result;
    }

    /**
     * @param Select                $select
     * @param AdapterInterface|null $specialAdapter
     * @param bool                  $allowLogging
     *
     * @return array
     */
    public function fetchPairs(
        Select $select,
        AdapterInterface $specialAdapter = null,
        bool $allowLogging = true): array
    {
        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(sprintf('Executing query: %s', $select->assemble()));
        }

        $result = ! is_null($specialAdapter) ? $specialAdapter->fetchPairs($select) :
            $this->getDefaultConnection()->fetchPairs($select);

        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(sprintf('Query result: %s', trim(print_r($result, true))));
        }

        return $result;
    }

    /**
     * @param Select                $select
     * @param AdapterInterface|null $specialAdapter
     * @param bool                  $allowLogging
     *
     * @return string
     */
    public function fetchOne(Select $select, AdapterInterface $specialAdapter = null, bool $allowLogging = true): string
    {
        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(sprintf('Executing query: %s', $select->assemble()));
        }

        $result = ! is_null($specialAdapter) ? $specialAdapter->fetchOne($select) :
            $this->getDefaultConnection()->fetchOne($select);

        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(sprintf('Query result: %s', trim(print_r($result, true))));
        }

        return $result;
    }

    /**
     * @param Select                $select
     * @param AdapterInterface|null $specialAdapter
     * @param bool                  $allowLogging
     *
     * @return array
     */
    public function fetchRow(Select $select, AdapterInterface $specialAdapter = null, bool $allowLogging = true): array
    {
        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(sprintf('Executing query: %s', $select->assemble()));
        }

        $result = ! is_null($specialAdapter) ? $specialAdapter->fetchRow($select) :
            $this->getDefaultConnection()->fetchRow($select);

        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(sprintf('Query result: %s', trim(print_r($result, true))));
        }

        return $result;
    }

    /**
     * @param Select                $select
     * @param AdapterInterface|null $specialAdapter
     * @param bool                  $allowLogging
     *
     * @return array
     */
    public function fetchCol(Select $select, AdapterInterface $specialAdapter = null, bool $allowLogging = true): array
    {
        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(sprintf('Executing query: %s', $select->assemble()));
        }

        $result = ! is_null($specialAdapter) ? $specialAdapter->fetchCol($select) :
            $this->getDefaultConnection()->fetchCol($select);

        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(sprintf('Query result: %s', trim(print_r($result, true))));
        }

        return $result;
    }

    /**
     * @param string $modelEntity
     * @param string $connectionName
     *
     * @return string
     */
    public function getTableName(
        string $modelEntity,
        string $connectionName = ResourceConnection::DEFAULT_CONNECTION): string
    {
        return $this->resourceConnection->getTableName($modelEntity, $connectionName);
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param array            $createTableData
     * @param bool             $test
     * @param bool             $allowLogging
     *
     * @return array
     * @throws Exception
     */
    public function saveCreateTableData(
        AdapterInterface $dbAdapter,
        array $createTableData,
        bool $test = false,
        bool $allowLogging = true): array
    {
        $createdIds = [];

        foreach ($createTableData as $tableName => $dbEntries) {
            $createdIds[ $tableName ] = [];

            foreach ($dbEntries as $key => $tableData) {
                if ($allowLogging && $this->isQueryLogging()) {
                    $this->logging->debug(sprintf('Inserting data into table: %s with values: %s', $tableName,
                        trim(print_r($dbEntries, true))));
                }

                if ( ! $test) {
                    try {
                        $dbAdapter->insert($tableName, $tableData);

                        /** @var Mysql $dbAdapter */
                        $createdIds[ $tableName ][ $key ] = $dbAdapter->lastInsertId($tableName);
                    } catch (Exception $exception) {
                        $this->logging->error(sprintf('Could not insert data into table: %s because: %s using values: %s',
                            $tableName, $exception->getMessage(), trim(print_r($tableData, true))));

                        throw new Exception(sprintf('Could not insert data into table: %s because: %s', $tableName,
                            $exception->getMessage()), 0, $exception);
                    }
                } else {
                    $createdIds[ $tableName ][ $key ] = rand(10000000, 99999999);
                }

                if ($allowLogging && $this->isQueryLogging()) {
                    $this->logging->debug(sprintf('Created entry in table: %s with id: %s', $tableName,
                        array_key_exists($key, $createdIds[ $tableName ]) ? $createdIds[ $tableName ][ $key ] : null));
                }
            }
        }

        return $createdIds;
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param array            $singleAttributeTableData
     * @param array            $eavAttributeTableData
     * @param bool             $test
     * @param bool             $allowLogging
     *
     * @return void
     * @throws Exception
     */
    public function saveUpdateTableData(
        AdapterInterface $dbAdapter,
        array $singleAttributeTableData,
        array $eavAttributeTableData,
        bool $test = false,
        bool $allowLogging = true)
    {
        $this->saveSingleAttributeTableData($dbAdapter, $singleAttributeTableData, $test, $allowLogging);
        $this->saveEavAttributeTableData($dbAdapter, $eavAttributeTableData, $test, $allowLogging);
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param array            $singleAttributeTableData
     * @param bool             $test
     * @param bool             $allowLogging
     *
     * @return void
     * @throws Exception
     */
    private function saveSingleAttributeTableData(
        AdapterInterface $dbAdapter,
        array $singleAttributeTableData,
        bool $test = false,
        bool $allowLogging = true)
    {
        foreach ($singleAttributeTableData as $tableName => $attributeEntries) {
            foreach ($attributeEntries as $attributeName => $dbEntries) {
                if ($allowLogging && $this->isQueryLogging()) {
                    $this->logging->debug(sprintf('Updating single attribute: %s in table: %s with values: %s',
                        $attributeName, $tableName, trim(print_r($dbEntries, true))));
                }

                if ( ! $test) {
                    try {
                        $dbAdapter->insertOnDuplicate($tableName, $dbEntries, [$attributeName]);
                    } catch (Exception $exception) {
                        $this->logging->error(sprintf('Could not update data in table: %s because: %s using values: %s',
                            $tableName, $exception->getMessage(), trim(print_r($dbEntries, true))));

                        throw new Exception(sprintf('Could not update data in table: %s because: %s', $tableName,
                            $exception->getMessage()), 0, $exception);
                    }
                }
            }
        }
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param array            $eavAttributeTableData
     * @param bool             $test
     * @param bool             $allowLogging
     *
     * @return void
     * @throws Exception
     */
    private function saveEavAttributeTableData(
        AdapterInterface $dbAdapter,
        array $eavAttributeTableData,
        bool $test = false,
        bool $allowLogging = true)
    {
        foreach ($eavAttributeTableData as $tableName => $dbEntries) {
            if ($allowLogging && $this->isQueryLogging()) {
                $this->logging->debug(sprintf('Updating eav attribute in table: %s with values: %s', $tableName,
                    trim(print_r($dbEntries, true))));
            }

            if ( ! $test) {
                try {
                    $dbAdapter->insertOnDuplicate($tableName, $dbEntries, ['value']);
                } catch (Exception $exception) {
                    $this->logging->error(sprintf('Could not update data in table: %s because: %s using values: %s',
                        $tableName, $exception->getMessage(), trim(print_r($dbEntries, true))));

                    throw new Exception(sprintf('Could not update data in table: %s because: %s', $tableName,
                        $exception->getMessage()), 0, $exception);
                }
            }
        }
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param string           $tableName
     * @param array            $tableData
     * @param bool             $checkDuplicate
     * @param bool             $test
     * @param bool             $allowLogging
     *
     * @return int
     * @throws Exception
     */
    public function createTableData(
        AdapterInterface $dbAdapter,
        string $tableName,
        array $tableData,
        bool $checkDuplicate = false,
        bool $test = false,
        bool $allowLogging = true): int
    {
        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(sprintf('Inserting data into table: %s with values: %s', $tableName,
                trim(print_r($tableData, true))));
        }

        if ( ! $test) {
            try {
                if ($checkDuplicate) {
                    $dbAdapter->insertOnDuplicate($tableName, $tableData);
                } else {
                    $dbAdapter->insert($tableName, $tableData);
                }

                /** @var Mysql $dbAdapter */
                return (int)$dbAdapter->lastInsertId($tableName);
            } catch (Exception $exception) {
                $this->logging->error(sprintf('Could not insert data into table: %s because: %s using values: %s',
                    $tableName, $exception->getMessage(), trim(print_r($tableData, true))));

                throw new Exception(sprintf('Could not insert data into table: %s because: %s', $tableName,
                    $exception->getMessage()), 0, $exception);
            }
        } else {
            return rand(10000000, 99999999);
        }
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param string           $tableName
     * @param array            $tableData
     * @param mixed            $where
     * @param bool             $test
     * @param bool             $allowLogging
     *
     * @return void
     * @throws Exception
     */
    public function updateTableData(
        AdapterInterface $dbAdapter,
        string $tableName,
        array $tableData,
        $where = null,
        bool $test = false,
        bool $allowLogging = true)
    {
        if ($allowLogging && $this->isQueryLogging()) {
            if (empty($where)) {
                $this->logging->debug(sprintf('Updating data in table: %s with values: %s', $tableName,
                    trim(print_r($tableData, true))));
            } else {
                $this->logging->debug(sprintf('Updating data in table: %s with values: %s where: %s', $tableName,
                    trim(print_r($tableData, true)), trim(print_r($where, true))));
            }
        }

        if ( ! $test) {
            try {
                if (empty($where)) {
                    $dbAdapter->insertOnDuplicate($tableName, $tableData);
                } else {
                    $dbAdapter->update($tableName, $tableData, $where);
                }
            } catch (Exception $exception) {
                $this->logging->error(sprintf('Could not update data in table: %s because: %s using values: %s',
                    $tableName, $exception->getMessage(), trim(print_r($tableData, true))));

                throw new Exception(sprintf('Could not update data in table: %s because: %s', $tableName,
                    $exception->getMessage()), 0, $exception);
            }
        }
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param string           $tableName
     * @param mixed            $where
     * @param bool             $test
     * @param bool             $allowLogging
     *
     * @return void
     * @throws Exception
     */
    public function deleteTableData(
        AdapterInterface $dbAdapter,
        string $tableName,
        $where = null,
        bool $test = false,
        bool $allowLogging = true)
    {
        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(sprintf('Deleting data in table: %s where: %s', $tableName,
                trim(print_r($where, true))));
        }

        if ( ! $test) {
            try {
                $dbAdapter->delete($tableName, $where);
            } catch (Exception $exception) {
                $this->logging->error(sprintf('Could not delete data in table: %s because: %s', $tableName,
                    $exception->getMessage()));

                throw new Exception(sprintf('Could not delete data in table: %s because: %s', $tableName,
                    $exception->getMessage()), 0, $exception);
            }
        }
    }

    /**
     * Used in processes which take longer than the database timeout
     */
    public function databaseKeepAlive()
    {
        $dbAdapter = $this->getDefaultConnection();

        // keep-alive for database
        $select = $dbAdapter->select()->from('admin_user_session')->where('1');

        $dbAdapter->fetchOne($select);
    }

    /**
     * @return bool
     */
    public function isQueryLogging(): bool
    {
        if ($this->queryLogging === null) {
            $this->queryLogging = $this->scopeConfig->isSetFlag('dev/log/query_logging');
        }

        return $this->queryLogging;
    }

    /**
     * @param AdapterInterface $dbAdapter
     * @param Select           $select
     * @param bool             $allowLogging
     *
     * @return Zend_Db_Statement_Interface
     */
    public function query(
        AdapterInterface $dbAdapter,
        Select $select,
        bool $allowLogging = true): Zend_Db_Statement_Interface
    {
        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(sprintf('Executing query: %s', $select->assemble()));
        }

        return $dbAdapter->query($select);
    }

    /**
     * @param mixed       $name
     * @param mixed       $cols
     * @param string|null $schema
     *
     * @return Select
     */
    public function select($name, $cols = '*', string $schema = null): Select
    {
        return $this->getDefaultConnection()->select()->from($name, $cols, $schema);
    }

    /**
     * @param AbstractModel[] $objects
     *
     * @throws Exception
     */
    public function saveObjectsInTransaction(array $objects)
    {
        $transaction = $this->transactionFactory->create();

        foreach ($objects as $object) {
            if ($object instanceof AbstractModel) {
                $transaction->addObject($object);
            } else {
                $this->logging->error('Tried to save non-object in transaction');
            }
        }

        $transaction->save();
    }
}
