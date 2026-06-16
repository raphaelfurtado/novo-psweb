<?php

namespace App\Livewire\Saidas;

use App\Livewire\Concerns\HandlesAnexos;
use App\Models\Anexo;
use App\Models\FormaPagamento;
use App\Models\Recebedor;
use App\Models\Saida;
use App\Models\TipoPagamento;
use App\Support\Referencia;
use App\Support\Valor;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use HandlesAnexos;
    use WithFileUploads;

    public ?Saida $saida = null;
    public bool $editando = false;

    public ?string $id_tipo_pagamento = null;
    public ?string $id_forma_pagamento = null;
    public ?int $id_recebedor = null;
    public ?string $data_pagamento = null;
    public string $mes = '';
    public string $ano = '';
    public string $valor = '';
    public string $situacao = 'PAGO';
    public string $observacao = '';

    public $anexo = null;

    public function mount(?Saida $saida = null): void
    {
        if ($saida && $saida->exists) {
            $this->saida = $saida;
            $this->editando = true;
            $this->fill([
                'id_tipo_pagamento' => (string) $saida->id_tipo_pagamento,
                'id_forma_pagamento' => $saida->id_forma_pagamento,
                'id_recebedor' => $saida->id_recebedor,
                'data_pagamento' => optional($saida->data_pagamento)->format('Y-m-d'),
                'mes' => Referencia::mes($saida->referencia ?? Referencia::atual()),
                'ano' => Referencia::ano($saida->referencia ?? Referencia::atual()),
                'valor' => Valor::br($saida->valor),
                'situacao' => $saida->situacao ?? 'PAGO',
                'observacao' => $saida->observacao ?? '',
            ]);
        } else {
            $this->mes = Carbon::now()->format('m');
            $this->ano = Carbon::now()->format('Y');
            $this->data_pagamento = Carbon::now()->format('Y-m-d');
        }
    }

    protected function rules(): array
    {
        return [
            'id_tipo_pagamento' => ['required'],
            'id_forma_pagamento' => ['required'],
            'id_recebedor' => ['nullable', 'integer'],
            'data_pagamento' => ['required', 'date'],
            'mes' => ['required'],
            'ano' => ['required', 'digits:4'],
            'valor' => ['required'],
            'situacao' => ['required'],
            'observacao' => ['nullable', 'max:100'],
            'anexo' => ['nullable', 'file', 'max:10240'],
        ];
    }

    protected function messages(): array
    {
        return ['required' => 'Campo obrigatório.', 'anexo.max' => 'O arquivo deve ter no máximo 10 MB.'];
    }

    public function removerComprovante(int $anexoId): void
    {
        $anexo = Anexo::where('id', $anexoId)->where('form', 'SAIDA')->firstOrFail();
        $this->removerAnexo($anexo);
        session()->flash('success', 'Comprovante removido.');
    }

    public function salvar()
    {
        $this->validate();

        $dados = [
            'id_funcionario' => null,
            'id_tipo_pagamento' => $this->id_tipo_pagamento,
            'id_forma_pagamento' => $this->id_forma_pagamento,
            'id_recebedor' => $this->id_recebedor ?: null,
            'data_pagamento' => $this->data_pagamento,
            'referencia' => $this->mes . $this->ano,
            'valor' => Valor::paraDecimal($this->valor),
            'situacao' => $this->situacao,
            'observacao' => $this->observacao ?: '',
        ];

        if ($this->editando) {
            $this->saida->update($dados);
            $saida = $this->saida;
            session()->flash('success', 'Despesa atualizada com sucesso.');
        } else {
            $dados['data_insert'] = now();
            $saida = Saida::create($dados);
            session()->flash('success', 'Despesa cadastrada com sucesso.');
        }

        if ($this->anexo) {
            $this->persistirAnexo($this->anexo, 'SAIDA', $saida->id, Anexo::TIPO_ASSOCIACAO);
        }

        return $this->redirectRoute('saidas.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.saidas.form', [
            'tipos' => TipoPagamento::ativo()->orderBy('descricao')->get(),
            'formas' => FormaPagamento::ativo()->orderBy('descricao')->get(),
            'recebedores' => Recebedor::orderBy('nome')->get(['id', 'nome']),
            'meses' => Referencia::MESES,
            'comprovantes' => $this->editando ? $this->saida->comprovantes()->get() : collect(),
        ]);
    }
}
