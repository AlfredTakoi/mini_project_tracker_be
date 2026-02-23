<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'weight' => $this->weight,
            'dependencies' => TaskResource::collection($this->whenLoaded('dependencies')),
            'dependency_ids' => $this->whenLoaded('dependencies', function () {
                return $this->dependencies->pluck('id');
            }),
        ];
    }
}
