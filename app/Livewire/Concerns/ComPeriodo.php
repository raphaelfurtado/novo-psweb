<?php

namespace App\Livewire\Concerns;

use App\Support\Referencia;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;

/**
 * Filtro de período dos relatórios: mês único OU intervalo (espelha
 * Relatorio::handleDateRange do psweb). Use $this->periodo() para obter
 * ['inicio','fim','is_periodo','label'].
 */
trait ComPeriodo
{
    #[Url]
    public string $referencia = '';

    #[Url]
    public string $referencia_inicio = '';

    #[Url]
    public string $referencia_fim = '';

    #[Url]
    public bool $modoPeriodo = false;

    public function mountComPeriodo(): void
    {
        $atual = Referencia::atual();
        $this->referencia = $this->referencia ?: $atual;
        $this->referencia_inicio = $this->referencia_inicio ?: $atual;
        $this->referencia_fim = $this->referencia_fim ?: $atual;
    }

    public function periodo(): array
    {
        if ($this->modoPeriodo && $this->referencia_inicio && $this->referencia_fim) {
            return [
                'inicio' => $this->referencia_inicio,
                'fim' => $this->referencia_fim,
                'is_periodo' => true,
                'label' => Referencia::porExtenso($this->referencia_inicio) . ' até ' . Referencia::porExtenso($this->referencia_fim),
            ];
        }

        $ref = $this->referencia ?: Referencia::atual();

        return [
            'inicio' => $ref,
            'fim' => $ref,
            'is_periodo' => false,
            'label' => Referencia::porExtenso($ref),
        ];
    }

    /** Parâmetros do filtro para compor links de PDF/Excel. */
    public function paramsPeriodo(array $extra = []): array
    {
        return array_merge([
            'referencia' => $this->referencia,
            'referencia_inicio' => $this->referencia_inicio,
            'referencia_fim' => $this->referencia_fim,
            'modoPeriodo' => $this->modoPeriodo ? 1 : 0,
        ], $extra);
    }

    public function mesesDisponiveis(): array
    {
        $refs = DB::table('pagamento')->select('referencia')->distinct()
            ->union(DB::table('saida')->select('referencia')->distinct())
            ->pluck('referencia')->filter()->all();

        $refs[] = Referencia::atual();
        $refs = array_unique($refs);

        usort($refs, fn ($a, $b) => Referencia::ordenavel($b) <=> Referencia::ordenavel($a));

        return collect($refs)->mapWithKeys(fn ($r) => [$r => Referencia::abreviada($r)])->all();
    }
}
