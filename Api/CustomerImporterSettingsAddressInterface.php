<?php

namespace ClawRock\CustomerImporter\Api;

interface CustomerImporterSettingsAddressInterface
{
    public function getFieldsMap(): array;
    public function setFieldsMap(array $value): CustomerImporterSettingsAddressInterface;

    public function getDefaultValues(): array;
    public function setDefaultValues(array $value): CustomerImporterSettingsAddressInterface;

    public function getIsDefaultBillingAddress(): bool;
    public function setIsDefaultBillingAddress(bool $value): CustomerImporterSettingsAddressInterface;

    public function getIsDefaultShippingAddress(): bool;
    public function setIsDefaultShippingAddress(bool $value): CustomerImporterSettingsAddressInterface;

    public function getStateField(): ?string;
    public function setStateField(string $value): CustomerImporterSettingsAddressInterface;

    public function getIgnoreWithEmptyFields(): array;
    public function setIgnoreWithEmptyFields(array $value): CustomerImporterSettingsAddressInterface;
}
