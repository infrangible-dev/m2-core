<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\Core\Helper;

use Exception;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
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

class Database extends AbstractHelper
{
    /** @var LoggerInterface */
    protected LoggerInterface $logging;

    /** @var ResourceConnection */
    protected ResourceConnection $resourceConnection;

    /** @var TransactionFactory */
    protected TransactionFactory $transactionFactory;

    /** @var bool|null */
    private ?bool $queryLogging = null;

    /**
     * @param Context $context
     * @param ResourceConnection $resourceConnection
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        Context $context,
        ResourceConnection $resourceConnection,
        TransactionFactory $transactionFactory
    ) {
        parent::__construct($context);

        $this->logging = $context->getLogger();
        $this->resourceConnection = $resourceConnection;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * Returns the default database connection.
     *
     * @return AdapterInterface
     */
    public function getDefaultConnection(): AdapterInterface
    {
        return $this->resourceConnection->getConnection();
    }

    /**
     * Returns the database connection of the resource.
     *
     * @param string $resourceName
     *
     * @return AdapterInterface
     */
    public function getConnection(string $resourceName): AdapterInterface
    {
        return $this->resourceConnection->getConnection($resourceName);
    }

    /**
     * Fetches all rows of the query, with the default connection or the given one.
     *
     * @param Select $select
     * @param AdapterInterface|null $specialAdapter
     * @param bool $allowLogging
     *
     * @return array
     * @phpstan-return array<int, array<string, mixed>>
     */
    public function fetchAll(Select $select, ?AdapterInterface $specialAdapter = null, bool $allowLogging = true): array
    {
        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(
                sprintf(
                    'Executing query: %s',
                    $select->assemble()
                )
            );
        }

        $result = $specialAdapter !== null ? $specialAdapter->fetchAll($select) :
            $this->getDefaultConnection()->fetchAll($select);

        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(
                sprintf(
                    'Query result: %s',
                    $this->formatLogValue($result)
                )
            );
        }

        return $result;
    }

    /**
     * Fetches all rows of the query as array with the value of the first column as key.
     *
     * @param Select $select
     * @param AdapterInterface|null $specialAdapter
     * @param bool $allowLogging
     *
     * @return array
     * @phpstan-return array<int|string, array<string, mixed>>
     */
    public function fetchAssoc(
        Select $select,
        ?AdapterInterface $specialAdapter = null,
        bool $allowLogging = true
    ): array {
        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(
                sprintf(
                    'Executing query: %s',
                    $select->assemble()
                )
            );
        }

        $result = $specialAdapter !== null ? $specialAdapter->fetchAssoc($select) :
            $this->getDefaultConnection()->fetchAssoc($select);

        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(
                sprintf(
                    'Query result: %s',
                    $this->formatLogValue($result)
                )
            );
        }

        return $result;
    }

    /**
     * Fetches the query result as array with the first column as key and the second column as value.
     *
     * @param Select $select
     * @param AdapterInterface|null $specialAdapter
     * @param bool $allowLogging
     *
     * @return array
     * @phpstan-return array<int|string, mixed>
     */
    public function fetchPairs(
        Select $select,
        ?AdapterInterface $specialAdapter = null,
        bool $allowLogging = true
    ): array {
        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(
                sprintf(
                    'Executing query: %s',
                    $select->assemble()
                )
            );
        }

        $result = $specialAdapter !== null ? $specialAdapter->fetchPairs($select) :
            $this->getDefaultConnection()->fetchPairs($select);

        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(
                sprintf(
                    'Query result: %s',
                    $this->formatLogValue($result)
                )
            );
        }

        return $result;
    }

    /**
     * Fetches the value of the first column of the first row, or null if there is no row.
     *
     * @param Select $select
     * @param AdapterInterface|null $specialAdapter
     * @param bool $allowLogging
     *
     * @return string|null
     */
    public function fetchOne(
        Select $select,
        ?AdapterInterface $specialAdapter = null,
        bool $allowLogging = true
    ): ?string {
        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(
                sprintf(
                    'Executing query: %s',
                    $select->assemble()
                )
            );
        }

        /** @var mixed $result */
        $result = $specialAdapter !== null ? $specialAdapter->fetchOne($select) :
            $this->getDefaultConnection()->fetchOne($select);

        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(
                sprintf(
                    'Query result: %s',
                    $this->formatLogValue($result)
                )
            );
        }

        // fetchOne() returns false if there is no row
        return is_scalar($result) && ! is_bool($result) ? (string)$result : null;
    }

    /**
     * Fetches the first row of the query, or null if there is no row.
     *
     * @param Select $select
     * @param AdapterInterface|null $specialAdapter
     * @param bool $allowLogging
     *
     * @return array|null
     * @phpstan-return array<string, mixed>|null
     */
    public function fetchRow(
        Select $select,
        ?AdapterInterface $specialAdapter = null,
        bool $allowLogging = true
    ): ?array {
        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(
                sprintf(
                    'Executing query: %s',
                    $select->assemble()
                )
            );
        }

        $result = $specialAdapter !== null ? $specialAdapter->fetchRow($select) :
            $this->getDefaultConnection()->fetchRow($select);

        if (! is_array($result)) {
            $result = null;
        }

        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(
                sprintf(
                    'Query result: %s',
                    $this->formatLogValue($result)
                )
            );
        }

        return $result;
    }

    /**
     * Fetches the values of the first column of all rows.
     *
     * @param Select $select
     * @param AdapterInterface|null $specialAdapter
     * @param bool $allowLogging
     *
     * @return array
     * @phpstan-return array<int, mixed>
     */
    public function fetchCol(Select $select, ?AdapterInterface $specialAdapter = null, bool $allowLogging = true): array
    {
        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(
                sprintf(
                    'Executing query: %s',
                    $select->assemble()
                )
            );
        }

        $result = $specialAdapter !== null ? $specialAdapter->fetchCol($select) :
            $this->getDefaultConnection()->fetchCol($select);

        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(
                sprintf(
                    'Query result: %s',
                    $this->formatLogValue($result)
                )
            );
        }

        return $result;
    }

    /**
     * Returns the name of the table including the table prefix of the installation.
     *
     * @param string $modelEntity
     * @param string $connectionName
     *
     * @return string
     */
    public function getTableName(
        string $modelEntity,
        string $connectionName = ResourceConnection::DEFAULT_CONNECTION
    ): string {
        return $this->resourceConnection->getTableName(
            $modelEntity,
            $connectionName
        );
    }

    /**
     * Inserts the rows into their tables and returns the ids of the new rows.
     *
     * In test mode, nothing is written and random ids are returned.
     *
     * @param AdapterInterface $dbAdapter
     * @param array $createTableData table name => row key => row data
     * @param bool $test
     * @param bool $allowLogging
     *
     * @phpstan-param array<string, array<int|string, array<string, mixed>>> $createTableData
     *
     * @return array table name => row key => id
     * @phpstan-return array<string, array<int|string, int|string>>
     * @throws CouldNotSaveException
     */
    public function saveCreateTableData(
        AdapterInterface $dbAdapter,
        array $createTableData,
        bool $test = false,
        bool $allowLogging = true
    ): array {
        $createdIds = [];

        foreach ($createTableData as $tableName => $dbEntries) {
            $createdIds[ $tableName ] = [];

            foreach ($dbEntries as $key => $tableData) {
                if ($allowLogging && $this->isQueryLogging()) {
                    $this->logging->debug(
                        sprintf(
                            'Inserting data into table: %s with values: %s',
                            $tableName,
                            $this->formatLogValue($dbEntries)
                        )
                    );
                }

                if (! $test) {
                    try {
                        $dbAdapter->insert(
                            $tableName,
                            $tableData
                        );

                        /** @var Mysql $dbAdapter */
                        $createdIds[ $tableName ][ $key ] = $dbAdapter->lastInsertId($tableName);
                    } catch (Exception $exception) {
                        $this->logging->error(
                            sprintf(
                                'Could not insert data into table: %s because: %s using values: %s',
                                $tableName,
                                $exception->getMessage(),
                                $this->formatLogValue($tableData)
                            )
                        );

                        throw new CouldNotSaveException(
                            __(
                                'Could not insert data into table: %1 because: %2',
                                $tableName,
                                $exception->getMessage()
                            ),
                            $exception
                        );
                    }
                } else {
                    $createdIds[ $tableName ][ $key ] = rand(
                        10000000,
                        99999999
                    );
                }

                if ($allowLogging && $this->isQueryLogging()) {
                    $this->logging->debug(
                        sprintf(
                            'Created entry in table: %s with id: %s',
                            $tableName,
                            array_key_exists(
                                $key,
                                $createdIds[ $tableName ]
                            ) ? $createdIds[ $tableName ][ $key ] : null
                        )
                    );
                }
            }
        }

        return $createdIds;
    }

    /**
     * Writes the rows of single attributes and EAV attribute values with insert on duplicate update.
     *
     * For single attributes only the attribute column is updated, for EAV attribute values only the value column.
     *
     * @param AdapterInterface $dbAdapter
     * @param array $singleAttributeTableData table name => attribute name => rows
     * @param array $eavAttributeTableData table name => rows
     * @param bool $test
     * @param bool $allowLogging
     *
     * @phpstan-param array<string, array<string, array<int|string, mixed>>> $singleAttributeTableData
     * @phpstan-param array<string, array<int|string, mixed>> $eavAttributeTableData
     *
     * @return void
     * @throws CouldNotSaveException
     */
    public function saveUpdateTableData(
        AdapterInterface $dbAdapter,
        array $singleAttributeTableData,
        array $eavAttributeTableData,
        bool $test = false,
        bool $allowLogging = true
    ): void {
        $this->saveSingleAttributeTableData(
            $dbAdapter,
            $singleAttributeTableData,
            $test,
            $allowLogging
        );
        $this->saveEavAttributeTableData(
            $dbAdapter,
            $eavAttributeTableData,
            $test,
            $allowLogging
        );
    }

    /**
     * Writes the rows with insert on duplicate update of the attribute column.
     *
     * @param AdapterInterface $dbAdapter
     * @param array $singleAttributeTableData table name => attribute name => rows
     * @param bool $test
     * @param bool $allowLogging
     *
     * @phpstan-param array<string, array<string, array<int|string, mixed>>> $singleAttributeTableData
     *
     * @return void
     * @throws CouldNotSaveException
     */
    private function saveSingleAttributeTableData(
        AdapterInterface $dbAdapter,
        array $singleAttributeTableData,
        bool $test = false,
        bool $allowLogging = true
    ): void {
        foreach ($singleAttributeTableData as $tableName => $attributeEntries) {
            foreach ($attributeEntries as $attributeName => $dbEntries) {
                if ($allowLogging && $this->isQueryLogging()) {
                    $this->logging->debug(
                        sprintf(
                            'Updating single attribute: %s in table: %s with values: %s',
                            $attributeName,
                            $tableName,
                            $this->formatLogValue($dbEntries)
                        )
                    );
                }

                if (! $test) {
                    try {
                        $dbAdapter->insertOnDuplicate(
                            $tableName,
                            $dbEntries,
                            [$attributeName]
                        );
                    } catch (Exception $exception) {
                        $this->logging->error(
                            sprintf(
                                'Could not update data in table: %s because: %s using values: %s',
                                $tableName,
                                $exception->getMessage(),
                                $this->formatLogValue($dbEntries)
                            )
                        );

                        throw new CouldNotSaveException(
                            __('Could not update data in table: %1 because: %2', $tableName, $exception->getMessage()),
                            $exception
                        );
                    }
                }
            }
        }
    }

    /**
     * Writes the EAV attribute values with insert on duplicate update of the value column.
     *
     * @param AdapterInterface $dbAdapter
     * @param array $eavAttributeTableData table name => rows
     * @param bool $test
     * @param bool $allowLogging
     *
     * @phpstan-param array<string, array<int|string, mixed>> $eavAttributeTableData
     *
     * @return void
     * @throws CouldNotSaveException
     */
    private function saveEavAttributeTableData(
        AdapterInterface $dbAdapter,
        array $eavAttributeTableData,
        bool $test = false,
        bool $allowLogging = true
    ): void {
        foreach ($eavAttributeTableData as $tableName => $dbEntries) {
            if ($allowLogging && $this->isQueryLogging()) {
                $this->logging->debug(
                    sprintf(
                        'Updating eav attribute in table: %s with values: %s',
                        $tableName,
                        $this->formatLogValue($dbEntries)
                    )
                );
            }

            if (! $test) {
                try {
                    $dbAdapter->insertOnDuplicate(
                        $tableName,
                        $dbEntries,
                        ['value']
                    );
                } catch (Exception $exception) {
                    $this->logging->error(
                        sprintf(
                            'Could not update data in table: %s because: %s using values: %s',
                            $tableName,
                            $exception->getMessage(),
                            $this->formatLogValue($dbEntries)
                        )
                    );

                    throw new CouldNotSaveException(
                        __('Could not update data in table: %1 because: %2', $tableName, $exception->getMessage()),
                        $exception
                    );
                }
            }
        }
    }

    /**
     * Inserts the row (with check duplicate: insert on duplicate update) and returns the id of the new row.
     *
     * In test mode, nothing is written and a random id is returned.
     *
     * @param AdapterInterface $dbAdapter
     * @param string $tableName
     * @param array $tableData
     * @param bool $checkDuplicate
     * @param bool $test
     * @param bool $allowLogging
     *
     * @phpstan-param array<string, mixed> $tableData
     *
     * @return int
     * @throws CouldNotSaveException
     */
    public function createTableData(
        AdapterInterface $dbAdapter,
        string $tableName,
        array $tableData,
        bool $checkDuplicate = false,
        bool $test = false,
        bool $allowLogging = true
    ): int {
        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(
                sprintf(
                    'Inserting data into table: %s with values: %s',
                    $tableName,
                    $this->formatLogValue($tableData)
                )
            );
        }

        if (! $test) {
            try {
                if ($checkDuplicate) {
                    $dbAdapter->insertOnDuplicate(
                        $tableName,
                        $tableData
                    );
                } else {
                    $dbAdapter->insert(
                        $tableName,
                        $tableData
                    );
                }

                /** @var Mysql $dbAdapter */
                return (int)$dbAdapter->lastInsertId($tableName);
            } catch (Exception $exception) {
                $this->logging->error(
                    sprintf(
                        'Could not insert data into table: %s because: %s using values: %s',
                        $tableName,
                        $exception->getMessage(),
                        $this->formatLogValue($tableData)
                    )
                );

                throw new CouldNotSaveException(
                    __('Could not insert data into table: %1 because: %2', $tableName, $exception->getMessage()),
                    $exception
                );
            }
        } else {
            return rand(
                10000000,
                99999999
            );
        }
    }

    /**
     * Updates the rows matching the where condition.
     *
     * Without condition, the row is written with insert on duplicate update.
     *
     * @param AdapterInterface $dbAdapter
     * @param string $tableName
     * @param array $tableData
     * @param mixed $where condition as accepted by AdapterInterface::update()
     * @param bool $test
     * @param bool $allowLogging
     *
     * @phpstan-param array<string, mixed> $tableData
     *
     * @return void
     * @throws CouldNotSaveException
     */
    public function updateTableData(
        AdapterInterface $dbAdapter,
        string $tableName,
        array $tableData,
        mixed $where = null,
        bool $test = false,
        bool $allowLogging = true
    ): void {
        if ($allowLogging && $this->isQueryLogging()) {
            if (empty($where)) {
                $this->logging->debug(
                    sprintf(
                        'Updating data in table: %s with values: %s',
                        $tableName,
                        $this->formatLogValue($tableData)
                    )
                );
            } else {
                $this->logging->debug(
                    sprintf(
                        'Updating data in table: %s with values: %s where: %s',
                        $tableName,
                        $this->formatLogValue($tableData),
                        $this->formatLogValue($where)
                    )
                );
            }
        }

        if (! $test) {
            try {
                if (empty($where)) {
                    $dbAdapter->insertOnDuplicate(
                        $tableName,
                        $tableData
                    );
                } else {
                    $dbAdapter->update(
                        $tableName,
                        $tableData,
                        $where
                    );
                }
            } catch (Exception $exception) {
                $this->logging->error(
                    sprintf(
                        'Could not update data in table: %s because: %s using values: %s',
                        $tableName,
                        $exception->getMessage(),
                        $this->formatLogValue($tableData)
                    )
                );

                throw new CouldNotSaveException(
                    __('Could not update data in table: %1 because: %2', $tableName, $exception->getMessage()),
                    $exception
                );
            }
        }
    }

    /**
     * Deletes the rows matching the where condition (all rows without condition).
     *
     * @param AdapterInterface $dbAdapter
     * @param string $tableName
     * @param mixed $where condition as accepted by AdapterInterface::delete()
     * @param bool $test
     * @param bool $allowLogging
     *
     * @return void
     * @throws CouldNotDeleteException
     */
    public function deleteTableData(
        AdapterInterface $dbAdapter,
        string $tableName,
        mixed $where = null,
        bool $test = false,
        bool $allowLogging = true
    ): void {
        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(
                sprintf(
                    'Deleting data in table: %s where: %s',
                    $tableName,
                    $this->formatLogValue($where)
                )
            );
        }

        if (! $test) {
            try {
                $dbAdapter->delete(
                    $tableName,
                    $where
                );
            } catch (Exception $exception) {
                $this->logging->error(
                    sprintf(
                        'Could not delete data in table: %s because: %s',
                        $tableName,
                        $exception->getMessage()
                    )
                );

                throw new CouldNotDeleteException(
                    __('Could not delete data in table: %1 because: %2', $tableName, $exception->getMessage()),
                    $exception
                );
            }
        }
    }

    /**
     * Runs a simple query to keep the connection open in processes which take longer than the database timeout.
     *
     * @return void
     */
    public function databaseKeepAlive(): void
    {
        $dbAdapter = $this->getDefaultConnection();

        // keep-alive for the database
        $select = $dbAdapter->select()->from('admin_user_session')->where('1');

        $dbAdapter->fetchOne($select);
    }

    /**
     * Checks whether query logging is enabled (dev/log/query_logging).
     *
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
     * Executes the query and returns the statement.
     *
     * @param AdapterInterface $dbAdapter
     * @param Select $select
     * @param bool $allowLogging
     *
     * @return Zend_Db_Statement_Interface
     */
    public function query(
        AdapterInterface $dbAdapter,
        Select $select,
        bool $allowLogging = true
    ): Zend_Db_Statement_Interface {
        if ($allowLogging && $this->isQueryLogging()) {
            $this->logging->debug(
                sprintf(
                    'Executing query: %s',
                    $select->assemble()
                )
            );
        }

        return $dbAdapter->query($select);
    }

    /**
     * Creates a select of the default connection from the table.
     *
     * @param mixed $name table name, or array alias => table name, as accepted by Select::from()
     * @param mixed $cols
     * @param string|null $schema
     *
     * @return Select
     */
    public function select(mixed $name, mixed $cols = '*', ?string $schema = null): Select
    {
        return $this->getDefaultConnection()->select()->from(
            $name,
            $cols,
            $schema
        );
    }

    /**
     * Saves the models in one transaction; values that are no models are skipped and logged.
     *
     * @param array $objects
     *
     * @phpstan-param array<int|string, mixed> $objects
     *
     * @return void
     * @throws Exception
     */
    public function saveObjectsInTransaction(array $objects): void
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

    /**
     * Returns the value as JSON for log messages.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function formatLogValue(mixed $value): string
    {
        return (string)json_encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
        );
    }
}
