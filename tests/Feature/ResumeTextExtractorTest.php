<?php

namespace Tests\Feature;

use App\Services\ResumeTextExtractor;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ResumeTextExtractorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_docx_paragraphs_runs_entities_and_table_cells_are_read(): void
    {
        $path = $this->docx('<w:p><w:r><w:t>PHP &amp; Laravel</w:t></w:r></w:p><w:p><w:r><w:t>Dois anos</w:t></w:r><w:r><w:tab/><w:t>de experiência</w:t></w:r></w:p><w:tbl><w:tr><w:tc><w:p><w:r><w:t>MySQL</w:t></w:r></w:p></w:tc></w:tr></w:tbl>');
        $text = app(ResumeTextExtractor::class)->extract($path);
        $this->assertSame("PHP & Laravel\nDois anos de experiência\nMySQL", $text);
    }

    public function test_docx_external_entities_are_rejected(): void
    {
        $path = Storage::disk('local')->path('invalid.docx');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('word/document.xml', '<!DOCTYPE doc [<!ENTITY x SYSTEM "file:///secret">]><doc>&x;</doc>');
        $zip->close();
        $this->expectException(\RuntimeException::class);
        app(ResumeTextExtractor::class)->extract($path);
    }

    public function test_actual_pdf_text_is_read_without_external_binary(): void
    {
        $text = app(ResumeTextExtractor::class)->extract(base_path('output/pdf/curriculo-demonstracao.pdf'));
        $this->assertStringContainsString('Laravel', $text);
        $this->assertStringContainsString('MySQL', $text);
        $this->assertStringContainsString('PHP', $text);
    }

    public function test_missing_file_is_reported(): void
    {
        $this->expectException(\RuntimeException::class);
        app(ResumeTextExtractor::class)->extract(Storage::disk('local')->path('missing.pdf'));
    }

    private function docx(string $body): string
    {
        $path = Storage::disk('local')->path('test.docx');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$body.'</w:body></w:document>');
        $zip->close();

        return $path;
    }
}
