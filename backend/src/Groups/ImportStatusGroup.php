<?php

declare(strict_types=1);

namespace App\Groups;

use App\Components\GroupInterface;

final readonly class ImportStatusGroup implements GroupInterface
{
    /**
     * @param array{status:string,total:int,processed:int,failed:int,progress_percent:int,errors:list<array{row:int,message:string}>} $products
     * @param array{pending:int,processing:int,completed:int,failed:int,total:int} $images
     */
    public function __construct(
        public string $jobId,
        public string $status,
        public string $stage,
        public array $products,
        public array $images,
    ) {
    }

    public function toArray(): array
    {
        return [
            'job_id' => $this->jobId,
            'status' => $this->status,
            'stage' => $this->stage,
            'products' => $this->products,
            'images' => $this->images,
        ];
    }
}
