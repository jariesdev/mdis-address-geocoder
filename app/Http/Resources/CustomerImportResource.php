<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

class CustomerImportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $status = $this->resource->status;
        return [
            'id' => $this->resource->id,
            'file' => $this->resource->file,
            'table_name' => $this->resource->table_name,
            'status' => $status,
            'success_count' => $status === 'coordinate-searching' ? Cache::get("imports.{$this->resource->id}.success-counter", 0) : $this->resource->success_count,
            'search_elapse_count' => $status === 'coordinate-searching' ? Cache::get("imports.{$this->resource->id}.success-elapse-counter", 0) : 0,
            'batch_remaining' => $status === 'coordinate-searching' ? Cache::get("imports.{$this->resource->id}.coordinate-batch-search-remaining", 0) : 0,
            'total' => $status === 'importing' ? Cache::get("imports.{$this->resource->id}.record-counter", 0) : $this->resource->total,
            'created_at' => $this->resource->created_at,
            'csv_path' => $this->resource->csv_path,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
