<?php

namespace App\Livewire\Pagamentos;

use App\Livewire\Concerns\HandlesAnexos;
use App\Models\Anexo;
use App\Models\FormaPagamento;
use App\Models\Pagamento;
use App\Models\Recebedor;
use App\Models\TipoPagamento;
use App\Models\User;
use App\Support\Referencia;
use App\Support\Valor;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use HandlesAnexos;
    use WithFileUploads;

    public ?Pagamento $pagamento = null;
    public bool $editando = false;

    public ?int $id_usuario = null;
    public ?int $id_recebedor = null;
    public ?string $id_tipo_pagamento = null;
    public ?string $id_forma_pagamento = null;
    public ?string $data_pagamento = null;
    public ?string $data_vencimento = null;
    public string $mes = '';
    public string $ano = '';
    public string $valor = '';
    public string $situacao = 'ABERTO';
    public string $observacao = '';

    public $anexo = null;

    /** Filtros da listagem para preservar o contexto ao voltar (morador, ano, etc.). */
    public array $voltar = [];

    public function mount(?Pagamento $pagamento = null): void
    {
        $this->voltar = array_filter(
            request()->only(['q', 'ano', 'mes', 'situacao', 'tipo', 'quadra', 'numero']),
            fn ($v) => $v !== '' && $v !== null
        );

        if ($pagamento && $pagamento->exists) {
            $this->pagamento = $pagamento;
            $this->editando = true;
            $this->fill([
                'id_usuario' => $pagamento->id_usuario,
                'id_recebedor' => $pagamento->id_recebedor,
                'id_tipo_pagamento' => (string) $pagamento->id_tipo_pagamento,
                'id_forma_pagamento' => $pagamento->id_forma_pagamento,
                'data_pagamento' => optional($pagamento->data_pagamento)->format('Y-m-d'),
                'data_vencimento' => optional($pagamento->data_vencimento)->format('Y-m-d'),
                'mes' => Referencia::mes($pagamento->referencia),
                'ano' => Referencia::ano($pagamento->referencia),
                'valor' => Valor::br($pagamento->valor),
                'situacao' => $pagamento->situacao,
                'observacao' => $pagamento->observacao ?? '',
            ]);
        } else {
            $this->mes = Carbon::now()->format('m');
            $this->ano = Carbon::now()->format('Y');
        }
    }

    protected function rules(): array
    {
        return [
            'id_usuario' => ['required', 'integer', 'exists:users,id'],
            'id_recebedor' => ['nullable', 'integer'],
            'id_tipo_pagamento' => ['required'],
            'id_forma_pagamento' => ['nullable'],
            'data_pagamento' => ['nullable', 'date'],
            'data_vencimento' => ['nullable', 'date'],
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
        return [
            'required' => 'Campo obrigatório.',
            'anexo.max' => 'O arquivo deve ter no máximo 10 MB.',
        ];
    }

    public function removerComprovante(int $anexoId): void
    {
        $anexo = Anexo::where('id', $anexoId)->where('form', 'PAGAMENTO')->firstOrFail();
        $this->removerAnexo($anexo);
        session()->flash('success', 'Comprovante removido.');
    }

    public function salvar()
    {
        $this->validate();

        $dados = [
            'id_usuario' => $this->id_usuario,
            'id_recebedor' => $this->id_recebedor ?: null,
            'id_tipo_pagamento' => $this->id_tipo_pagamento,
            'id_forma_pagamento' => $this->id_forma_pagamento ?: null,
            'data_pagamento' => $this->data_pagamento ?: null,
            'data_vencimento' => $this->data_vencimento ?: null,
            'referencia' => $this->mes . $this->ano,
            'valor' => Valor::paraDecimal($this->valor),
            'situacao' => $this->situacao,
            'observacao' => $this->observacao ?: null,
        ];

        if ($this->editando) {
            $this->pagamento->update($dados);
            $pag = $this->pagamento;
            session()->flash('success', 'Pagamento atualizado com sucesso.');
        } else {
            $dados['data_insert'] = now();
            $pag = Pagamento::create($dados);
            session()->flash('success', 'Pagamento cadastrado com sucesso.');
        }

        if ($this->anexo) {
            $this->persistirAnexo(
                $this->anexo, 'PAGAMENTO', $pag->id,
                Anexo::TIPO_MORADOR, idMorador: $pag->id_usuario
            );
        }

        return $this->redirectRoute('pagamentos.index', $this->paramsVoltar(), navigate: true);
    }

    /**
     * Parâmetros para voltar à listagem preservando o contexto.
     * Usa os filtros recebidos; se não houver, cai no morador + ano do lançamento.
     */
    public function paramsVoltar(): array
    {
        if (! empty($this->voltar)) {
            return $this->voltar;
        }

        $params = [];
        if ($this->ano) {
            $params['ano'] = $this->ano;
        }
        if ($this->id_usuario && ($u = User::find($this->id_usuario))) {
            $params['q'] = $u->nome;
        }

        return $params;
    }

    public function render()
    {
        return view('livewire.pagamentos.form', [
            'moradores' => User::orderBy('nome')->get(['id', 'nome']),
            'recebedores' => Recebedor::orderBy('nome')->get(['id', 'nome']),
            'tipos' => TipoPagamento::ativo()->orderBy('descricao')->get(),
            'formas' => FormaPagamento::ativo()->orderBy('descricao')->get(),
            'meses' => Referencia::MESES,
            'comprovantes' => $this->editando ? $this->pagamento->comprovantes()->get() : collect(),
        ]);
    }
}
