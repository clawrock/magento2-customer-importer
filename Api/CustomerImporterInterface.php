<?php

namespace ClawRock\CustomerImporter\Api;

interface CustomerImporterInterface
{
    public function import(CustomerImporterSettingsInterface $customerImporterSettings): void;
    public function getImportTime(): float;
}
