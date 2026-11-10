<?php

namespace App\Services;

use RuntimeException;
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
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($path);

            return $this->clean($pdf->getText());
        }

        $binary = config('services.resume.pdftotext_binary');

        if ($binary && is_file($binary)) {
            $outputFile = tempnam(sys_get_temp_dir(), 'resume_');
            $command = escapeshellarg($binary).' -layout '.escapeshellarg($path).' '.escapeshellarg($outputFile);
            exec($command, $output, $exitCode);

            if ($exitCode === 0 && is_file($outputFile)) {
                $text = file_get_contents($outputFile) ?: '';
                @unlink($outputFile);

                return $this->clean($text);
            }

            @unlink($outputFile);
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

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Não foi possível abrir o arquivo DOCX.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException('O DOCX não contém texto legível.');
        }

        $xml = str_replace(['</w:p>', '</w:tr>'], ["\n", "\n"], $xml);
        $text = strip_tags($xml);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return $this->clean($text);
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
