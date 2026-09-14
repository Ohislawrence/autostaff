<?php

namespace Nomdal;

class ApiResponse
{
    public $success;
    public $status;
    public $message;
    public $data;

    public function __construct(bool $success, int $status = 0, string $message = '', $data = null)
    {
        $this->success = $success;
        $this->status = $status;
        $this->message = $message;
        $this->data = $data;
    }
}
