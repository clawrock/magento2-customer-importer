<?php

namespace ClawRock\CustomerImporter\Model;

use ClawRock\CsvReader\Model\Exception\NotFoundException;
use ClawRock\CustomerImporter\Api\CustomerImporterInterface;
use ClawRock\CustomerImporter\Api\CustomerImporterSettingsAddressInterface;
use ClawRock\CustomerImporter\Api\CustomerImporterSettingsInterface;
use Magento\Directory\Model\Region;
use Magento\Customer\Model\Data\Customer as CustomerData;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Store\Model\Store;
use Magento\Customer\Model\Data\Address as AddressData;

class CustomerImporter implements CustomerImporterInterface
{
    const CUSTOMER_ADDRESS_TABLE = 'customer_address_entity';
    const CUSTOMER_ADDRESS_ATTRIBUTE_TABLE = 'customer_address_entity_';
    const CUSTOMER_TABLE = 'customer_entity';
    const CUSTOMER_ATTRIBUTE_TABLE = 'customer_entity_';
    const EAV_ATTRIBUTE_TABLE = 'eav_attribute';

    const ENTITY_ID = 'entity_id';
    const PARENT_ID = 'parent_id';

    const CUSTOMER_ENTITY_TYPE_ID = 1;
    const CUSTOMER_ADDRESS_ENTITY_TYPE_ID = 2;
    const ATTRIBUTE_FIELD_VALUE = 'value';

    const DATE_TARGET_FORMAT = 'Y-m-d H:i:s';

    const CUSTOMER_FIELD_IS_ACTIVE = 'is_active';
    const CUSTOMER_FIELD_FAILURES_NUM = 'failures_num';

    const BATCH_SIZE = 2000;

    /** @var \Magento\Framework\Filesystem\DirectoryList  */
    protected $directoryList;

    /** @var \ClawRock\CsvReader\Model\CsvReaderFactory  */
    protected $csvReaderFactory;

    /** @var \Magento\Directory\Model\ResourceModel\Region\CollectionFactory  */
    protected $regionCollectionFactory;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface  */
    protected $connection;

    /** @var CustomerImporterSettingsInterface */
    protected $customerImporterSettings;

    /** @var \ClawRock\CsvReader\Api\CsvReaderInterface */
    protected $customers;

    protected $regions;

    protected $attributes;

    protected $customersToSave = [];
    protected $customerAttributesToSave = [];
    protected $customerAddressesToSave = [];
    protected $customerAddressAttributesToSave = [];

    protected $currentCustomerId = null;
    protected $currentAddressId = null;

    protected $importStartTime = null;
    protected $importFinishTime = null;

    public function __construct(
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \ClawRock\CsvReader\Model\CsvReaderFactory $csvReaderFactory
    ) {
        $this->directoryList = $directoryList;
        $this->regionCollectionFactory = $regionCollectionFactory;
        $this->connection = $resourceConnection->getConnection();
        $this->csvReaderFactory = $csvReaderFactory;
    }

    public function import(CustomerImporterSettingsInterface $customerImporterSettings): void
    {
        $this->importStartTime = microtime(true);
        $this->customerImporterSettings = $customerImporterSettings;

        $this->connection->beginTransaction();

        $this->initCustomers($customerImporterSettings->getCustomersCsvPath());
        $this->createCustomers();

        $this->saveCustomers();
        $this->saveCustomerAttributes();
        $this->saveCustomerAddresses();
        $this->saveCustomerAddressAttributes();

        $this->connection->commit();
        $this->importFinishTime = microtime(true);
    }

    public function getImportTime(): float
    {
        return $this->importFinishTime - $this->importStartTime;
    }

    protected function initCustomers(string $customersCsvPath): void
    {
        if($this->customerImporterSettings->getIsCsvPathRelativeToMagentoRoot()) {
            $customersCsvPath = $this->directoryList->getRoot() . '/' . $customersCsvPath;
        }

        $this->customers = $this->csvReaderFactory->create($customersCsvPath);
    }

    protected function createCustomers(): void
    {
        foreach($this->customers->get() as $customer) {
            $this->createCustomer($customer);
        }
    }

    protected function createCustomer(array $data): void
    {
        if(!$this->shouldRowBeImported($data)) {
            return;
        }

        $customerId = $this->getNextCustomerId();

        $customer = $this->createDefaultCustomerFields($customerId, $data);

        $this->mapCustomerFields($customer, $data);
        $this->mapCustomerAttributes($customerId, $data);
        $this->createAddresses($customer, $data);

        $this->customersToSave[] = $customer;
    }

    protected function shouldRowBeImported(array $data): bool
    {
        foreach($this->customerImporterSettings->getIgnoreIfEmptyFields() as $field) {
            $value = $this->customers->getValue($data, $field);

            if(trim($value) == '') {
                return false;
            }
        }

        return true;
    }

    protected function createDefaultCustomerFields(int $id, array $data): array
    {
        return [
            static::ENTITY_ID => $id,
            CustomerData::WEBSITE_ID => $this->getStore()->getWebsiteId(),
            CustomerData::STORE_ID => $this->getStore()->getId(),
            CustomerData::CREATED_IN => $this->getStore()->getName(),
            CustomerData::UPDATED_AT => new \Zend_Db_Expr('NOW()'),
            CustomerData::GROUP_ID => $this->getCustomerGroupId($data),
            CustomerData::DISABLE_AUTO_GROUP_CHANGE => $this->getCustomerDisableAutoGroupChange($data),
            static::CUSTOMER_FIELD_IS_ACTIVE => $this->getCustomerIsActive($data),
            static::CUSTOMER_FIELD_FAILURES_NUM => $this->getCustomerFailuresNum($data)
        ];
    }

    protected function mapCustomerFields(array &$customer, array $data): void
    {
        foreach($this->customerImporterSettings->getCustomerFields() as $csvField => $modelField) {
            $value = $this->customers->getValue($data, $csvField);
            $customer[$modelField] = $this->formatValueIfNeeded($csvField, $value);
        }
    }

    protected function mapCustomerAttributes(int $customerId, array $data): void
    {
        foreach($this->customerImporterSettings->getCustomerAttributes() as $csvField => $attribute) {
            $value = $this->customers->getValue($data, $csvField);
            $value = $this->formatValueIfNeeded($csvField, $value);

            $this->addCustomerAttributeToSave($attribute, [
                AttributeInterface::ATTRIBUTE_ID => $this->getAttributeId($attribute, static::CUSTOMER_ENTITY_TYPE_ID),
                static::ATTRIBUTE_FIELD_VALUE => $value,
                static::ENTITY_ID => $customerId
            ]);
        }
    }

    protected function formatValueIfNeeded(string $csvField, $value)
    {
        if($format = $this->customerImporterSettings->getDateFormatForField($csvField)) {
            $value = date_create_from_format($format, $value)
                ->format(static::DATE_TARGET_FORMAT);
        }

        return $value;
    }

    protected function createAddresses(array &$customer, array $data): void
    {
        foreach ($this->customerImporterSettings->getAddresses() as $address) {
            $this->createAddress($customer, $data, $address);
        }
    }

    protected function createAddress(array &$customer, array $data, CustomerImporterSettingsAddressInterface $addressSettings): ?array
    {
        foreach($addressSettings->getIgnoreWithEmptyFields() as $csvField) {
            if(empty($this->customers->getValue($data, $csvField))) {
                return null;
            }
        }

        $address = [
            static::ENTITY_ID => $this->getNextAddressId(),
            static::PARENT_ID => $customer[static::ENTITY_ID]
        ];

        foreach($addressSettings->getFieldsMap() as $csvField => $modelField) {
            $address[$modelField] = $this->customers->getValue($data, $csvField);
        }

        foreach($addressSettings->getDefaultValues() as $modelField => $value) {
            $address[$modelField] = $value;
        }

        if($addressSettings->getIsDefaultBillingAddress()) {
            $customer[CustomerData::DEFAULT_BILLING] = $address[static::ENTITY_ID];
        }

        if($addressSettings->getIsDefaultShippingAddress()) {
            $customer[CustomerData::DEFAULT_SHIPPING] = $address[static::ENTITY_ID];
        }

        $this->addRegionToAddress($address, $data, $addressSettings);
        $this->customerAddressesToSave[] = $address;
        return $address;
    }

    protected function addRegionToAddress(array &$address, array $customerData, CustomerImporterSettingsAddressInterface $addressSettings)
    {
        $stateField = $addressSettings->getStateField();

        if(!$stateField) {
            $address[AddressData::REGION_ID] = null;
            $address[AddressData::REGION] = null;
            return;
        }

        $region = $this->getRegion($address[AddressData::COUNTRY_ID], $customerData[$stateField]);

        if($region) {
            $address[AddressData::REGION_ID] = $region->getRegionId();
            $address[AddressData::REGION] = $region->getName();
        } else {
            $address[AddressData::REGION_ID] = null;
            $address[AddressData::REGION] = null;
        }
    }

    protected function getStore(): Store
    {
        return $this->customerImporterSettings->getStore();
    }

    protected function getRegion(string $countryId, string $regionCode)
    {
        if(!$this->regions) {
            $regions = $this->regionCollectionFactory->create()->getItems();

            $this->regions = [];

            /** @var Region $region */
            foreach($regions as $region) {
                if(!isset($this->regions[$region->getCountryId()])) {
                    $this->regions[$region->getCountryId()] = [];
                }

                $this->regions[$region->getCountryId()][$region->getCode()] = $region;
            }
        }

        if(isset($this->regions[$countryId][$regionCode])) {
            return $this->regions[$countryId][$regionCode];
        }

        return null;
    }

    protected function getAttributeId(string $name, int $entityTypeId): int
    {
        if(!$this->attributes) {
            $this->fetchAttributes();
        }

        $key = $this->getAttrNameEntityKey($entityTypeId, $name);
        return $this->attributes[$key][AttributeInterface::ATTRIBUTE_ID];
    }

    protected function getAttributeTable(string $name, int $entityTypeId): string
    {
        if(!$this->attributes) {
            $this->fetchAttributes();
        }

        $key = $this->getAttrNameEntityKey($entityTypeId, $name);
        $attribute = $this->attributes[$key];

        switch ($entityTypeId) {
            case static::CUSTOMER_ENTITY_TYPE_ID:
                $table = static::CUSTOMER_ATTRIBUTE_TABLE;
                break;
            case static::CUSTOMER_ADDRESS_ENTITY_TYPE_ID:
                $table = static::CUSTOMER_ADDRESS_ATTRIBUTE_TABLE;
                break;
            default:
                throw new NotFoundException(sprintf('Attribute %s not found!', $name));
        }

        return $table . $attribute[AttributeInterface::BACKEND_TYPE];
    }

    protected function getAttrNameEntityKey(int $entityTypeId, string $name): string
    {
        return $entityTypeId. '|' . $name;
    }

    protected function fetchAttributes()
    {
        $select = $this->connection->select()
            ->from($this->connection->getTableName(static::EAV_ATTRIBUTE_TABLE))
            ->columns([
                AttributeInterface::ATTRIBUTE_ID,
                AttributeInterface::ATTRIBUTE_CODE,
                AttributeInterface::ENTITY_TYPE_ID,
                AttributeInterface::BACKEND_TYPE
            ])
            ->where(AttributeInterface::ENTITY_TYPE_ID . ' = ' . static::CUSTOMER_ENTITY_TYPE_ID)
            ->orWhere(AttributeInterface::ENTITY_TYPE_ID . ' = ' . static::CUSTOMER_ADDRESS_ENTITY_TYPE_ID);

        $attributes = $this->connection->fetchAll($select);

        $this->attributes = [];

        foreach($attributes as $attribute) {
            $attributeCodeWithEntityId = $attribute[AttributeInterface::ENTITY_TYPE_ID] . '|' . $attribute[AttributeInterface::ATTRIBUTE_CODE];
            $this->attributes[$attributeCodeWithEntityId] = $attribute;
        }
    }

    protected function getNextCustomerId(): int
    {
        if(!$this->currentCustomerId) {
            $this->currentCustomerId = $this->getTableLastId(static::CUSTOMER_TABLE);
        }

        return ++$this->currentCustomerId;
    }

    protected function getNextAddressId(): int
    {
        if(!$this->currentAddressId) {
            $this->currentAddressId = $this->getTableLastId(static::CUSTOMER_ADDRESS_TABLE);
        }

        return ++$this->currentAddressId;
    }

    protected function getTableLastId(string $table, string $column = self::ENTITY_ID): int
    {
        $select = $this->connection->select()
            ->from($this->connection->getTableName($table))
            ->columns($column)
            ->order([$column . ' DESC'])
            ->limit(1);

        $col = $this->connection->fetchCol($select);

        if(!empty($col[0])) {
            return $col[0];
        }

        return 0;
    }

    protected function saveCustomers(): void
    {
        $this->insertData(static::CUSTOMER_TABLE, $this->customersToSave);
    }

    protected function saveCustomerAttributes(): void
    {
        foreach($this->customerAttributesToSave as $table => &$data) {
            $this->insertData($table, $data);
        }
    }

    protected function saveCustomerAddresses(): void
    {
        $this->insertData(static::CUSTOMER_ADDRESS_TABLE, $this->customerAddressesToSave);
    }

    protected function saveCustomerAddressAttributes(): void
    {
        foreach($this->customerAddressAttributesToSave as $table => &$data) {
            $this->insertData($table, $data);
        }
    }

    protected function insertData(string $table, array &$data): void
    {
        $batch = [];
        $batchCount = 0;
        $table = $this->connection->getTableName($table);

        for($i = 0; $i < count($data); $i++, $batchCount++) {
            $batch[] = $data[$i];

            if($batchCount >= static::BATCH_SIZE) {
                $this->connection->insertMultiple($table, $batch);
                $batchCount = 0;
                $batch = [];
            }
        }

        if(count($batch) > 0) {
            $this->connection->insertMultiple($table, $batch);
        }
    }

    protected function getCustomerGroupId(array $customer): int
    {
        return $this->customerImporterSettings->getDefaultCustomerGroupId();
    }

    protected function getCustomerDisableAutoGroupChange(array $customer): int
    {
        return $this->customerImporterSettings->getDefaultCustomerDisableAutoGroupChange();
    }

    protected function getCustomerFailuresNum(array $customer): int
    {
        return $this->customerImporterSettings->getDefaultCustomerFailuresNum();
    }

    protected function getCustomerIsActive(array $customer): bool
    {
        return $this->customerImporterSettings->getDefaultCustomerIsActive();
    }

    protected function addCustomerAttributeToSave(string $attribute, array $data): void
    {
        $table = $this->getAttributeTable($attribute, static::CUSTOMER_ENTITY_TYPE_ID);

        if(!isset($this->customerAttributesToSave[$table])) {
            $this->customerAttributesToSave[$table] = [];
        }

        $this->customerAttributesToSave[$table][] = $data;
    }

    protected function addAddressAttributeToSave(string $attribute, array $data): void
    {
        $table = $this->getAttributeTable($attribute, static::CUSTOMER_ADDRESS_ENTITY_TYPE_ID);

        if(!isset($this->customerAddressAttributesToSave[$table])) {
            $this->customerAddressAttributesToSave[$table] = [];
        }

        $this->customerAddressAttributesToSave[$table][] = $data;
    }
}
