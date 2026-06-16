<?php

namespace App\Support;

/**
 * Resolve o caminho físico dos anexos (uploads do psweb) procurando em várias
 * raízes possíveis — ver config/uploads.php. Isso torna o download resiliente
 * ao layout do servidor (dev x produção) sem depender só do .env.
 */
class UploadStorage
{
    /** Raízes de busca, na ordem de preferência (apenas strings não vazias). */
    public static function roots(): array
    {
        return array_values(array_filter(
            (array) config('uploads.paths', []),
            fn ($p) => is_string($p) && $p !== ''
        ));
    }

    /**
     * Caminho absoluto do arquivo, procurando em todas as raízes. Tenta o
     * stored_name exato (ex.: 2026/03/x.jpg) e, como fallback, o nome solto.
     */
    public static function find(?string $storedName): ?string
    {
        if (! $storedName) {
            return null;
        }

        $relativo = ltrim(str_replace('\\', '/', $storedName), '/');
        $somenteNome = basename($relativo);

        foreach (self::roots() as $root) {
            $root = rtrim($root, "/\\");
            foreach (array_unique([$relativo, $somenteNome]) as $sufixo) {
                $caminho = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $sufixo);
                if (is_file($caminho)) {
                    return $caminho;
                }
            }
        }

        return null;
    }

    /** Raiz para GRAVAÇÃO de novos anexos: a primeira que existir como diretório. */
    public static function primaryRoot(): string
    {
        foreach (self::roots() as $root) {
            if (is_dir($root)) {
                return rtrim($root, "/\\");
            }
        }

        return storage_path('app/uploads');
    }
}
