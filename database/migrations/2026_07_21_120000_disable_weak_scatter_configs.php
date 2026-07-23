<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $titles = [
        'Eficiencia Presupuestal FAISMUN vs Pobreza',
        'Focalización FAISMUN: recursos aprobados vs pobreza extrema',
        'FAISMUN y carencia de servicios básicos en la vivienda',
        'FAISMUN y rezago educativo',
        'FORTAMUN frente a percepción de inseguridad',
        'Productividad agrícola frente a pobreza',
        'Accesibilidad carretera y pobreza territorial',
        'Disponibilidad médica frente a carencia de acceso a salud',
        'Inclusión financiera frente a pobreza por ingresos',
        'Violencia registrada frente a percepción de inseguridad',
        'Gestión de residuos: recolección selectiva vs disposición inadecuada',
        'Capacidad del sistema de salud ante la mortalidad general',
        'Índice de motorización y riesgo de mortalidad',
        'Tasa de mortalidad infantil por edad de la madre',
        'Disponibilidad de personal médico  y salud materna',
        'Escolaridad y participación económica',
        'Acceso a computadoras y alfabetización',
        'Rezago educativo y desocupación laboral',
        'Recursos devengados del FORTAMUN Per Cápita y la Tasa de Incidencia delictiva',
    ];

    public function up(): void
    {
        DB::table('configuracion_fichas')
            ->whereIn('titulo_reporte', $this->titles)
            ->update(['activo' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('configuracion_fichas')
            ->whereIn('titulo_reporte', $this->titles)
            ->update(['activo' => true, 'updated_at' => now()]);
    }
};
