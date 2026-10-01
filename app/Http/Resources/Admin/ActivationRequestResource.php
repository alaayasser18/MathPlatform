<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivationRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'student' => [
                'id' => $this->student?->id,

                'name' => trim(
                    ($this->student?->first_name ?? '') . ' ' .
                    ($this->student?->last_name ?? '')
                ),

                'phone' => $this->student?->phone,

                'parent_phone' => $this->student?->parent_phone,

                'grade' => $this->student?->grade?->name,
            ],

            'subscription' => [
                'id' => $this->id,

                'status' => $this->status,

                'approval_status' => $this->approval_status,
            ],

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}