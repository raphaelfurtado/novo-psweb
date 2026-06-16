<?php

namespace App\Livewire\Concerns;

use App\Models\Anexo;
use App\Support\UploadStorage;
use Illuminate\Support\Facades\Storage;

/**
 * Persiste/remove anexos na pasta de uploads do novo-psweb (estrutura AAAA/MM),
 * com fallback de leitura para as pastas do legado (ver config/uploads.php).
 * O componente que usa este trait deve incluir também o trait Livewire\WithFileUploads.
 */
trait HandlesAnexos
{
    protected function persistirAnexo(
        $arquivo,
        string $form,
        int $identifier,
        int $typeAnex,
        ?int $idMorador = null,
        ?int $idFuncionario = null
    ): Anexo {
        $dir = now()->format('Y/m');

        // Disco montado na raiz principal de uploads (storage/app/uploads).
        $disk = Storage::build(['driver' => 'local', 'root' => UploadStorage::primaryRoot()]);
        $stored = $disk->putFile($dir, $arquivo); // "2026/06/<aleatorio>.ext"

        return Anexo::create([
            'original_name' => $arquivo->getClientOriginalName(),
            'stored_name' => $stored,
            'mime_type' => $arquivo->getMimeType(),
            'size' => $arquivo->getSize(),
            'type_anex' => $typeAnex,
            'id_morador' => $idMorador,
            'id_funcionario' => $idFuncionario,
            'form' => $form,
            'identifier' => $identifier,
        ]);
    }

    /** Remove o arquivo físico (procurando em todas as raízes) e o registro. */
    protected function removerAnexo(Anexo $anexo): void
    {
        $path = UploadStorage::find($anexo->stored_name);

        if ($path && is_file($path)) {
            @unlink($path);
        }

        $anexo->delete();
    }

    /** Remove todos os anexos de um formulário/identificador (ex.: ao excluir um pagamento). */
    protected function removerAnexosDe(string $form, int $identifier): void
    {
        Anexo::where('form', $form)->where('identifier', $identifier)->get()
            ->each(fn (Anexo $a) => $this->removerAnexo($a));
    }
}
