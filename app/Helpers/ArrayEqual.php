<?php

namespace App\Helpers;

use App\Models\PaymentHistory;

class ArrayEqual
{

    protected $list = [];
    protected $sizeEach;
    private $count = 0;
    private $current = [];

    /**
     * Create a new job instance.
     */
    public function __construct($size)
    {
        $this->sizeEach = $size;
    }

    public function push($value)
    {
        if ($this->count >= $this->sizeEach) {
            $this->list[] = $this->current;
            $this->current = [];
            $this->count = 0;
        }

        $this->addToArray($value);
    }

    private function addToArray($value)
    {
        $this->current[] = $value;
        $this->count += 1;
    }

    public function getList()
    {
        if ($this->count > 0)
            $this->list[] = $this->current;

        return $this->list;
    }
}
