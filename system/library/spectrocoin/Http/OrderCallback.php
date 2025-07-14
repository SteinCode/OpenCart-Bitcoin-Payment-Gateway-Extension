<?php

namespace Opencart\Catalog\Controller\Extension\Spectrocoin\Payment\Http;

if (!defined('DIR_APPLICATION')) {
    die('Access denied.');
}

require_once DIR_EXTENSION . 'spectrocoin/system/library/spectrocoin/SCConfig.php';

use Opencart\Catalog\Controller\Extension\Spectrocoin\Payment\SCUtils;
use InvalidArgumentException;

class OrderCallback
{
     private ?string $uuid;
    private ?string $merchantApiId;

    public function __construct(?string $uuid, ?string $merchantApiId)
    {
        $this->uuid = isset($uuid) ? SCUtils::sanitize_text_field((string)$uuid) : null;
        $this->merchantApiId = isset($merchantApiId) ? SCUtils::sanitize_text_field((string)$merchantApiId) : null;

        $validation_result = $this->validate();
        if (is_array($validation_result)) {
            $errorMessage = 'Invalid order callback. Failed fields: ' . implode(', ', $validation_result);
            throw new InvalidArgumentException($errorMessage);
        }
    }

    /**
     * Validate the input data.
     *
     * @return bool|array True if validation passes, otherwise an array of error messages.
     */
    private function validate(): bool|array
    {
        $errors = [];

        if (empty($this->getUuid())) {
            $errors[] = 'Uuid is empty';
        }

        if (empty($this->getmerchantApiId())) {
            $errors[] = 'merchantApiId is empty';
        }

        return empty($errors) ? true : $errors;
    }

    public function getUuid()
    {
        return $this->uuid;
    }
    public function getmerchantApiId()
    {
        return $this->merchantApiId;
    }

}