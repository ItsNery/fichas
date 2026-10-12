<?php

namespace App\Services;

use App\Models\Macrorregion;
use App\Models\Microrregion;
use App\Models\UnidadGeografica;
use Illuminate\Support\Collection;

class GeographicUnitService
{
    public function forSelection(string $level, ?int $regionId = null): ?UnidadGeografica
    {
        return match ($level) {
            'estatal' => $this->state(),
            'macrorregion' => $regionId ? $this->macrorregion($regionId) : null,
            'microrregion' => $regionId ? $this->microrregion($regionId) : null,
            default => null,
        };
    }

    public function forImport(string $level, string $key): ?UnidadGeografica
    {
        return match ($level) {
            UnidadGeografica::NIVEL_ENTIDAD => $key === UnidadGeografica::CLAVE_PUEBLA ? $this->state() : null,
            UnidadGeografica::NIVEL_MACRORREGION => $this->macrorregionFromKey($key),
            UnidadGeografica::NIVEL_MICRORREGION => $this->microrregionFromKey($key),
            default => UnidadGeografica::where('nivel', $level)->where('clave_inegi', $key)->where('activo', true)->first(),
        };
    }

    public function importReferences(): Collection
    {
        return collect([
            ['nivel' => UnidadGeografica::NIVEL_ENTIDAD, 'clave' => UnidadGeografica::CLAVE_PUEBLA, 'nombre' => 'Puebla'],
        ])->merge(
            Macrorregion::orderBy('nombre')->get()->map(fn (Macrorregion $region) => [
                'nivel' => UnidadGeografica::NIVEL_MACRORREGION,
                'clave' => $this->macrorregionKey($region->id),
                'nombre' => $region->nombre,
            ])
        )->merge(
            Microrregion::with('macrorregion')->orderBy('nombre')->get()->map(fn (Microrregion $region) => [
                'nivel' => UnidadGeografica::NIVEL_MICRORREGION,
                'clave' => $this->microrregionKey($region->id),
                'nombre' => $region->nombre . ($region->macrorregion ? ' (' . $region->macrorregion->nombre . ')' : ''),
            ])
        );
    }

    private function state(): UnidadGeografica
    {
        $country = UnidadGeografica::firstOrCreate(
            ['nivel' => UnidadGeografica::NIVEL_PAIS, 'clave_inegi' => UnidadGeografica::CLAVE_MEXICO],
            ['nombre' => 'Estados Unidos Mexicanos', 'slug' => 'mexico', 'activo' => true],
        );

        return UnidadGeografica::updateOrCreate(
            ['nivel' => UnidadGeografica::NIVEL_ENTIDAD, 'clave_inegi' => UnidadGeografica::CLAVE_PUEBLA],
            ['nombre' => 'Puebla', 'slug' => 'puebla', 'parent_id' => $country->id, 'activo' => true],
        );
    }

    private function macrorregionFromKey(string $key): ?UnidadGeografica
    {
        if (! preg_match('/^macro-(\d+)$/', $key, $matches)) {
            return null;
        }

        return $this->macrorregion((int) $matches[1]);
    }

    private function microrregionFromKey(string $key): ?UnidadGeografica
    {
        if (! preg_match('/^micro-(\d+)$/', $key, $matches)) {
            return null;
        }

        return $this->microrregion((int) $matches[1]);
    }

    private function macrorregion(int $id): ?UnidadGeografica
    {
        $region = Macrorregion::find($id);
        if (! $region) {
            return null;
        }

        return UnidadGeografica::updateOrCreate(
            ['nivel' => UnidadGeografica::NIVEL_MACRORREGION, 'clave_inegi' => $this->macrorregionKey($region->id)],
            ['nombre' => $region->nombre, 'slug' => 'macroregion-' . $region->id, 'parent_id' => $this->state()->id, 'activo' => true],
        );
    }

    private function microrregion(int $id): ?UnidadGeografica
    {
        $region = Microrregion::with('macrorregion')->find($id);
        if (! $region || ! $region->macrorregion) {
            return null;
        }

        $parent = $this->macrorregion($region->macrorregion->id);
        if (! $parent) {
            return null;
        }

        return UnidadGeografica::updateOrCreate(
            ['nivel' => UnidadGeografica::NIVEL_MICRORREGION, 'clave_inegi' => $this->microrregionKey($region->id)],
            ['nombre' => $region->nombre, 'slug' => 'microrregion-' . $region->id, 'parent_id' => $parent->id, 'activo' => true],
        );
    }

    private function macrorregionKey(int $id): string
    {
        return 'macro-' . $id;
    }

    private function microrregionKey(int $id): string
    {
        return 'micro-' . $id;
    }
}
