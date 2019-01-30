<?php

namespace ClawRock\CustomerImporter\Model;

use ClawRock\CustomerImporter\Api\CustomerImporterSettingsAddressInterface;

class CustomerImporterSettingsAddress implements CustomerImporterSettingsAddressInterface
{
    protected $fieldsMap = [];
    protected $defaultValues = [];
    protected $isDefaultBillingAddress = false;
    protected $isDefaultShippingAddress = false;
    protected $stateField;
    protected $ignoreWithEmptyFields = [];

    public function getFieldsMap(): array
    {
        return $this->fieldsMap;
    }

    public function setFieldsMap(array $value): CustomerImporterSettingsAddressInterface
    {
        $this->fieldsMap = $value;
        return $this;
    }

    public function getDefaultValues(): array
    {
        return $this->defaultValues;
    }

    public function setDefaultValues(array $value): CustomerImporterSettingsAddressInterface
    {
        $this->defaultValues = $value;
        return $this;
    }

    public function getIsDefaultBillingAddress(): bool
    {
        return $this->isDefaultBillingAddress;
    }

    public function setIsDefaultBillingAddress(bool $value): CustomerImporterSettingsAddressInterface
    {
        $this->isDefaultBillingAddress = $value;
        return $this;
    }

    public function getIsDefaultShippingAddress(): bool
    {
        return $this->isDefaultShippingAddress;
    }

    public function setIsDefaultShippingAddress(bool $value): CustomerImporterSettingsAddressInterface
    {
        $this->isDefaultShippingAddress = $value;
        return $this;
    }

    public function getStateField(): ?string
    {
        return $this->stateField;
    }

    public function setStateField(string $value): CustomerImporterSettingsAddressInterface
    {
        $this->stateField = $value;
        return $this;
    }

    public function getIgnoreWithEmptyFields(): array
    {
        return $this->ignoreWithEmptyFields;
    }

    public function setIgnoreWithEmptyFields(array $value): CustomerImporterSettingsAddressInterface
    {
        $this->ignoreWithEmptyFields = $value;
        return $this;
    }
}
