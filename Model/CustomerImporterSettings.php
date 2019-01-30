<?php

namespace ClawRock\CustomerImporter\Model;

use ClawRock\CustomerImporter\Api\CustomerImporterSettingsAddressInterface;
use ClawRock\CustomerImporter\Api\CustomerImporterSettingsInterface;

class CustomerImporterSettings implements CustomerImporterSettingsInterface
{
    /** @var \Magento\Store\Model\StoreManagerInterface  */
    protected $storeManager;

    protected $customersCsvPath;
    protected $store;
    protected $isCsvPathRelativeToMagentoRoot = true;
    protected $defaultCustomerGroupId = 1;
    protected $defaultCustomerDisableAutoGroupChange = 0;
    protected $defaultCustomerIsActive = true;
    protected $defaultCustomerFailuresNum = 0;
    protected $dateFormatForField = [];
    protected $customerFields = [];
    protected $customerAttributes = [];
    protected $addresses = [];
    protected $ignoreIfEmptyFields = [];

    public function __construct(\Magento\Store\Model\StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    public function getCustomersCsvPath(): string
    {
        return $this->customersCsvPath;
    }

    public function setCustomersCsvPath(string $value): CustomerImporterSettingsInterface
    {
        $this->customersCsvPath = $value;
        return $this;
    }

    public function getStore(): \Magento\Store\Model\Store
    {
        if($this->store) {
            return $this->store;
        }

        return $this->storeManager->getStore();
    }

    public function setStore(\Magento\Store\Model\Store $value): CustomerImporterSettingsInterface
    {
        $this->store = $value;
        return $this;
    }

    public function getIsCsvPathRelativeToMagentoRoot(): bool
    {
        return $this->isCsvPathRelativeToMagentoRoot;
    }

    public function setIsCsvPathRelativeToMagentoRoot(bool $value): CustomerImporterSettingsInterface
    {
        $this->isCsvPathRelativeToMagentoRoot = $value;
        return $this;
    }

    public function getDefaultCustomerGroupId(): int
    {
        return $this->defaultCustomerGroupId;
    }

    public function setDefaultCustomerGroupId(int $value): CustomerImporterSettingsInterface
    {
        $this->defaultCustomerGroupId = $value;
        return $this;
    }

    public function getDefaultCustomerDisableAutoGroupChange(): int
    {
        return $this->defaultCustomerDisableAutoGroupChange;
    }

    public function setDefaultCustomerDisableAutoGroupChange(int $value): CustomerImporterSettingsInterface
    {
        $this->defaultCustomerDisableAutoGroupChange = $value;
        return $this;
    }

    public function getDefaultCustomerIsActive(): int
    {
        return $this->defaultCustomerIsActive;
    }

    public function setDefaultCustomerIsActive(int $value): CustomerImporterSettingsInterface
    {
        $this->defaultCustomerIsActive = $value;
        return $this;
    }

    public function getDefaultCustomerFailuresNum(): int
    {
        return $this->defaultCustomerFailuresNum;
    }

    public function setDefaultCustomerFailuresNum(int $value): CustomerImporterSettingsInterface
    {
        $this->defaultCustomerFailuresNum = $value;
        return $this;
    }

    public function getDateFormatForField(string $field): ?string
    {
        return $this->dateFormatForField[$field] ?? null;
    }

    public function setDateFormatForField(string $field, string $format): CustomerImporterSettingsInterface
    {
        $this->dateFormatForField[$field] = $format;
        return $this;
    }

    public function getCustomerFields(): array
    {
        return $this->customerFields;
    }

    public function addCustomerField(string $csvField, string $modelField): CustomerImporterSettingsInterface
    {
        $this->customerFields[$csvField] = $modelField;
        return $this;
    }

    public function getCustomerAttributes(): array
    {
        return $this->customerAttributes;
    }

    public function addCustomerAttribute(string $csvField, string $attribute): CustomerImporterSettingsInterface
    {
        $this->customerAttributes[$csvField] = $attribute;
        return $this;
    }

    public function addAddress(CustomerImporterSettingsAddressInterface $address): CustomerImporterSettingsInterface
    {
        $this->addresses[] = $address;
        return $this;
    }

    public function getAddresses(): array
    {
        return $this->addresses;
    }

    public function getIgnoreIfEmptyFields(): array
    {
        return $this->ignoreIfEmptyFields;
    }

    public function setIgnoreIfEmptyFields(array $value): CustomerImporterSettingsInterface
    {
        $this->ignoreIfEmptyFields = $value;
        return $this;
    }
}
