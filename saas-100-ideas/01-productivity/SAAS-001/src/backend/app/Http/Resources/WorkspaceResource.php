<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Workspace */
class WorkspaceResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = null;
        if ($request->user()) {
            $member = $request->user()->workspaces()
                ->where('workspace_id', $this->id)
                ->first();
            $role = $member?->pivot?->role;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?? $this->getAttribute('description'),
            'logo_url' => null,
            'role' => $role ?? ($this->owner_id === $request->user()?->id ? 'owner' : null),
            'member_count' => $this->whenCounted('members', fn () => $this->members_count),
            'project_count' => $this->whenCounted('projects', fn () => $this->projects_count),
            'plan' => $this->plan,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
