<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Smalot\PdfParser\Parser;
use ZipArchive;

class ResumeTextExtractor
{
    public function extract(string $path, ?string $mime = null): string
    {
        if (! is_file($path)) {
            throw new RuntimeException('O arquivo do currículo não foi encontrado no armazenamento.');
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => $this->extractPdf($path),
            'docx' => $this->extractDocx($path),
            default => throw new RuntimeException('Formato de currículo não suportado para análise automática.'),
        };
    }

    private function extractPdf(string $path): string
    {
        if (class_exists(Parser::class)) {
            $parser = new Parser;
            $pdf = $parser->parseFile($path);

            return $this->clean($pdf->getText());
        }

        $binary = config('services.resume.pdftotext_binary');

        if ($binary && is_file($binary)) {
            $outputFile = tempnam(sys_get_temp_dir(), 'resume_');
            try {
                $result = Process::timeout(30)->run([$binary, '-layout', $path, $outputFile]);
                if ($result->successful()) {
                    return $this->clean(file_get_contents($outputFile) ?: '');
                }
            } finally {
                @unlink($outputFile);
            }
        }

        throw new RuntimeException(
            'Não foi possível ler o PDF. Execute "composer require smalot/pdfparser" no projeto ou configure PDFTOTEXT_BINARY no .env.'
        );
    }

    private function extractDocx(string $path): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('A extensão PHP ZipArchive é necessária para analisar arquivos DOCX.');
        }

        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Não foi possível abrir o arquivo DOCX.');
        }

        $stat = $zip->statName('word/document.xml');
        if (! $stat || $stat['size'] > 5 * 1024 * 1024) {
            $zip->close();
            throw new RuntimeException('O DOCX não contém um documento válido ou é grande demais para leitura.');
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException('O DOCX não contém texto legível.');
        }

        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument;
            if (stripos($xml, '<!DOCTYPE') !== false || ! $document->loadXML($xml, LIBXML_NONET)) {
                throw new RuntimeException('O DOCX contém um documento inválido.');
            }
            $xpath = new DOMXPath($document);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $lines = [];
            foreach ($xpath->query('//w:p') as $paragraph) {
                $line = '';
                foreach ($xpath->query('.//w:t | .//w:tab | .//w:br', $paragraph) as $node) {
                    $line .= $node->localName === 't' ? $node->textContent : ' ';
                }
                $lines[] = $line;
            }

            return $this->clean(implode("\n", $lines));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function clean(string $text): string
    {
        $previous = mb_substitute_character();
        mb_substitute_character('none');
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        if ($previous !== false) {
            mb_substitute_character($previous);
        }

        $text = str_replace("\0", '', $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;
        $text = preg_replace('/[\t ]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\R{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
