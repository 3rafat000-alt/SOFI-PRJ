<?php

namespace Database\Seeders;

use App\Models\Integration;
use App\Models\IntegrationDoc;
use App\Models\IntegrationTemplate;

/**
 * Provides helper methods for creating Integration docs and templates.
 * Extracted to a trait so each feature seeder can use it independently.
 */
trait SeedsIntegrationContent
{
    protected function createDocs(Integration $integration, array $docs): void
    {
        foreach ($docs as $doc) {
            IntegrationDoc::create(array_merge(['integration_id' => $integration->id], $doc));
        }
    }

    protected function createTemplates(Integration $integration, array $templates): void
    {
        foreach ($templates as $template) {
            IntegrationTemplate::create(array_merge(['integration_id' => $integration->id], $template));
        }
    }
}
