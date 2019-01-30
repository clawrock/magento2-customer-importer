<?php

namespace ClawRock\CustomerImporter\Api;

interface CustomerImporterSettingsInterface
{
    public function getCustomersCsvPath(): string;
    public function setCustomersCsvPath(string $value): CustomerImporterSettingsInterface;

    public function getStore(): \Magento\Store\Model\Store;
    public function setStore(\Magento\Store\Model\Store $value): CustomerImporterSettingsInterface;

    public function getIsCsvPathRelativeToMagentoRoot(): bool;
    public function setIsCsvPathRelativeToMagentoRoot(bool $value): CustomerImporterSettingsInterface;

    public function getDefaultCustomerGroupId(): int;
    public function setDefaultCustomerGroupId(int $value): CustomerImporterSettingsInterface;

    public function getDefaultCustomerDisableAutoGroupChange(): int;
    public function setDefaultCustomerDisableAutoGroupChange(int $value): CustomerImporterSettingsInterface;

    public function getDefaultCustomerIsActive(): int;
    public function setDefaultCustomerIsActive(int $value): CustomerImporterSettingsInterface;

    public function getDefaultCustomerFailuresNum(): int;
    public function setDefaultCustomerFailuresNum(int $value): CustomerImporterSettingsInterface;

    public function getDateFormatForField(string $field): ?string;
    public function setDateFormatForField(string $field, string $format): CustomerImporterSettingsInterface;

    public function getCustomerFields(): array;
    public function addCustomerField(string $csvField, string $modelField): CustomerImporterSettingsInterface;

    public function getCustomerAttributes(): array;
    public function addCustomerAttribute(string $csvField, string $attribute): CustomerImporterSettingsInterface;

    public function addAddress(CustomerImporterSettingsAddressInterface $address): CustomerImporterSettingsInterface;
    public function getAddresses(): array;

    public function getIgnoreIfEmptyFields(): array;
    public function setIgnoreIfEmptyFields(array $value): CustomerImporterSettingsInterface;
}
