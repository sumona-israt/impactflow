<?php

namespace App\Services\Odoo;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Translates an ImpactFlow record into an Odoo field payload, config-driven
 * per config('odoo.mappings') rather than hardcoded per entity class — see
 * docs/odoo-integration.md §4.
 */
class OdooMappingService
{
    /**
     * @return array<string, mixed>
     */
    public function buildPayload(Model $entity): array
    {
        $fields = $this->mappingFor($entity::class)['fields'];

        $payload = [];
        foreach ($fields as $localAttribute => $odooField) {
            $payload[$odooField] = $entity->getAttribute($localAttribute);
        }

        return $payload;
    }

    public function odooModelFor(string $entityType): string
    {
        return $this->mappingFor($entityType)['model'];
    }

    public function slugFor(string $entityType): string
    {
        return $this->mappingFor($entityType)['slug'];
    }

    public function entityTypeForSlug(string $slug): string
    {
        foreach (config('odoo.mappings') as $entityType => $mapping) {
            if ($mapping['slug'] === $slug) {
                return $entityType;
            }
        }

        throw new RuntimeException("Unknown Odoo entity slug: {$slug}");
    }

    /**
     * @return array{slug: string, model: string, fields: array<string, string>}
     */
    private function mappingFor(string $entityType): array
    {
        $mapping = config("odoo.mappings.{$entityType}");

        if ($mapping === null) {
            throw new RuntimeException("No Odoo mapping configured for {$entityType}.");
        }

        return $mapping;
    }
}
