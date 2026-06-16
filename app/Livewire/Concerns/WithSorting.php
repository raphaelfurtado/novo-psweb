<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;

/** Ordenação reativa por coluna, com indicador de direção. */
trait WithSorting
{
    #[Url(as: 'ord')]
    public string $sortField = 'id';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }
}
