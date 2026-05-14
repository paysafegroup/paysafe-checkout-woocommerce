<?php

namespace Paysafe\PhpSdk\Result;

class PaysafeApiResult
{
    private array $data = [];

    private string $status;

    public function __construct(?array $data = null)
    {
        if (!is_array($data)) {
            $data = [];
        }

        $this->data = $data;

        $this->status = $this->data['status'] ?? '';
    }

    /**
     * Return the DTO data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Return the status of the DTO
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }
}