<?php

namespace App\Livewire\Anexos;

use App\Livewire\Concerns\HandlesAnexos;
use App\Models\Anexo;
use App\Models\User;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use HandlesAnexos;
    use WithFileUploads;
    use WithPagination;

    #[Url]
    public string $tipo = '';

    #[Url(as: 'q')]
    public string $search = '';

    // Upload (somente admin)
    public array $arquivos = [];
    public string $subject = '';
    public int $type_anex = Anexo::TIPO_ASSOCIACAO;
    public ?int $id_morador = null;

    protected function rules(): array
    {
        return [
            'arquivos' => ['required', 'array', 'min:1'],
            'arquivos.*' => ['file', 'max:10240'],
            'subject' => ['nullable', 'max:512'],
            'type_anex' => ['required', 'integer'],
            'id_morador' => ['nullable', 'integer'],
        ];
    }

    protected function messages(): array
    {
        return [
            'arquivos.required' => 'Selecione ao menos um arquivo.',
            'arquivos.*.max' => 'Cada arquivo deve ter no máximo 10 MB.',
        ];
    }

    public function enviar(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->validate();

        foreach ($this->arquivos as $arquivo) {
            $anexo = $this->persistirAnexo(
                $arquivo,
                form: 'ANEXO',
                identifier: 0,
                typeAnex: $this->type_anex,
                idMorador: $this->type_anex === Anexo::TIPO_MORADOR ? $this->id_morador : null,
            );

            if ($this->subject) {
                $anexo->update(['subject' => $this->subject]);
            }
        }

        $this->reset('arquivos', 'subject');
        session()->flash('success', 'Anexo(s) enviado(s) com sucesso.');
    }

    public function excluir(int $id): void
    {
        $anexo = Anexo::findOrFail($id);

        if (! auth()->user()->isAdmin() && (int) $anexo->id_morador !== (int) auth()->id()) {
            abort(403);
        }

        $this->removerAnexo($anexo);
        session()->flash('success', 'Anexo excluído.');
    }

    public function render()
    {
        $isAdmin = auth()->user()->isAdmin();

        $anexos = Anexo::query()
            ->with(['morador', 'funcionario'])
            ->when(! $isAdmin, fn ($q) => $q->where('id_morador', auth()->id()))
            ->when($this->tipo !== '', fn ($q) => $q->where('type_anex', $this->tipo))
            ->when($this->search, fn ($q) => $q->where('original_name', 'like', "%{$this->search}%")
                ->orWhere('subject', 'like', "%{$this->search}%"))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.anexos.index', [
            'anexos' => $anexos,
            'isAdmin' => $isAdmin,
            'moradores' => $isAdmin ? User::orderBy('nome')->get(['id', 'nome']) : collect(),
        ]);
    }
}
