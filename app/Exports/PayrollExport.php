<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class PayrollExport implements FromCollection
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
    public function collection(): Collection
    {
        return collect($this->data);
    }

}
