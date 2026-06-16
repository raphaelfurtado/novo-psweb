<?php

namespace App\Http\Controllers;

use App\Models\Anexo;
use App\Support\UploadStorage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AnexoController extends Controller
{
    /** Serve o anexo (inline), procurando o arquivo nas raízes configuradas. */
    public function download(Anexo $anexo): BinaryFileResponse
    {
        $user = auth()->user();

        // Morador só acessa os próprios anexos.
        if (! $user->isAdmin() && (int) $anexo->id_morador !== (int) $user->id) {
            abort(403, 'Acesso negado a este anexo.');
        }

        $path = UploadStorage::find($anexo->stored_name);

        abort_unless($path, 404, 'Arquivo não encontrado.');

        return response()->file($path, [
            'Content-Type' => $anexo->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . addslashes($anexo->original_name) . '"',
        ]);
    }
}
