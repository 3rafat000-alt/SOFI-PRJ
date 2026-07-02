<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'workspace_id' => ['required', 'string', 'uuid', 'exists:workspaces,id'],
            'url' => ['required', 'string', 'url', 'max:500'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string', 'in:task.created,task.updated,task.deleted,time_entry.created,time_entry.updated,project.created,project.updated,project.deleted,comment.created,member.joined'],
            'secret' => ['nullable', 'string', 'min:16', 'max:255'],
        ];
    }
}
