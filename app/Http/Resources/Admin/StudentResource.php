<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $subscription = $this->subscriptions->first();

        return [
            'id' => $this->id,

            'first_name' => $this->first_name,

            'last_name' => $this->last_name,

            'name' => trim(
                $this->first_name . ' ' . $this->last_name
            ),

            'phone' => $this->phone,

            'parent_phone' => $this->parent_phone,

            'grade' => $this->grade ? [
                'id' => $this->grade->id,
                'name' => $this->grade->name,
            ] : null,

            'registered_at' =>
                $this->registered_at?->toDateString(),

            'subscription' => $subscription ? [
                'id' => $subscription->id,

                'status' => $subscription->status,

                'approval_status' =>
                    $subscription->approval_status,

                'start_date' =>
                    $subscription->start_date?->toDateString(),

                'end_date' =>
                    $subscription->end_date?->toDateString(),

                'approved_at' =>
                    $subscription->approved_at?->toISOString(),
            ] : null,

            'subscription_code' =>
                $subscription?->subscriptionCode ? [
                    'id' =>
                        $subscription->subscriptionCode->id,

                    'code' =>
                        $subscription->subscriptionCode->code,

                    'approval_status' =>
                        $subscription->subscriptionCode->approval_status,

                    'expires_at' =>
                        $subscription->subscriptionCode->expires_at
                            ?->toDateString(),
                ] : null,

            'created_at' =>
                $this->created_at?->toISOString(),

            'updated_at' =>
                $this->updated_at?->toISOString(),
        ];
    }
}