<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        
        return [
            'id' => $this->id,
            'cat_id' => $this->cat_id,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'created' => $this->created_at->toDateTimeString(),
            'modified' => $this->updated_at->toDateTimeString(),
        ];
    }
}
