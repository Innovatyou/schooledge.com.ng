<?php
namespace Tests\Api;

use PHPUnit\Framework\TestCase;
use Tests\Support\Fixtures;
use Tests\Support\Http;

/**
 * Covers the digital-library reading/listening endpoints on api/v1/Library.php.
 * The critical property under test is the same branch/ownership gate every
 * file-serving endpoint in this app needs: a book (and its e-book/audiobook
 * bytes) must never be reachable by a membership in a different branch, and
 * a book with no digital copy uploaded must 404 rather than error.
 */
final class LibraryTest extends TestCase
{
    private Fixtures $fixtures;
    private int $branchId;
    private int $yearId;
    private int $classId;
    private int $sectionId;
    private int $categoryId;
    /** @var array<int,string> */
    private array $tempFiles = array();

    protected function setUp(): void
    {
        $this->fixtures = new Fixtures();
        $this->branchId = $this->fixtures->createBranch();
        $this->yearId = $this->fixtures->createSchoolYear();
        $this->classId = $this->fixtures->createClass($this->branchId);
        $this->sectionId = $this->fixtures->createSection($this->branchId);
        $this->categoryId = $this->fixtures->createBookCategory($this->branchId);
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (file_exists($path)) @unlink($path);
        }
        $this->fixtures->cleanupMobileAuthForBranch($this->branchId);
        $this->fixtures->cleanup();
    }

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }

    private function putEbookFile(string $filename, string $contents): void
    {
        $dir = $this->root() . '/uploads/book_ebook';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $path = $dir . '/' . $filename;
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;
    }

    private function putAudiobookFile(string $filename, string $contents): void
    {
        $dir = $this->root() . '/uploads/book_audio';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $path = $dir . '/' . $filename;
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;
    }

    public function test_a_student_can_read_the_ebook_of_a_book_in_their_own_branch(): void
    {
        $filename = 'ebook_' . $this->fixtures->randomSuffix() . '.pdf';
        $this->putEbookFile($filename, '%PDF-1.4 test ebook content');
        $bookId = $this->fixtures->createBook($this->branchId, $this->categoryId, array('ebook_file' => $filename));

        $student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $session = $this->fixtures->login($student['username'], $student['password']);

        $response = Http::get("library/books/$bookId/read", $session['access_token']);
        $this->assertSame(200, $response['status']);
        $this->assertStringContainsString('test ebook content', $response['body']['raw'] ?? '');
    }

    public function test_a_student_in_a_different_branch_cannot_read_the_ebook(): void
    {
        $filename = 'ebook_' . $this->fixtures->randomSuffix() . '.pdf';
        $this->putEbookFile($filename, '%PDF-1.4 test ebook content');
        $bookId = $this->fixtures->createBook($this->branchId, $this->categoryId, array('ebook_file' => $filename));

        $otherBranchId = $this->fixtures->createBranch();
        $otherClassId = $this->fixtures->createClass($otherBranchId);
        $otherSectionId = $this->fixtures->createSection($otherBranchId);
        $otherStudent = $this->fixtures->createStudent($otherBranchId, $otherClassId, $otherSectionId, $this->yearId, array('with_login' => true));
        $session = $this->fixtures->login($otherStudent['username'], $otherStudent['password']);

        $response = Http::get("library/books/$bookId/read", $session['access_token']);
        $this->assertSame(404, $response['status']);
        $this->assertSame('book_not_found', $response['body']['error']['code'] ?? null);

        $this->fixtures->cleanupMobileAuthForBranch($otherBranchId);
    }

    public function test_reading_a_book_with_no_ebook_uploaded_returns_404(): void
    {
        $bookId = $this->fixtures->createBook($this->branchId, $this->categoryId);
        $student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $session = $this->fixtures->login($student['username'], $student['password']);

        $response = Http::get("library/books/$bookId/read", $session['access_token']);
        $this->assertSame(404, $response['status']);
        $this->assertSame('ebook_not_available', $response['body']['error']['code'] ?? null);
    }

    public function test_a_student_can_listen_to_the_audiobook_of_a_book_in_their_own_branch(): void
    {
        $filename = 'audio_' . $this->fixtures->randomSuffix() . '.mp3';
        $this->putAudiobookFile($filename, 'ID3 test audiobook content');
        $bookId = $this->fixtures->createBook($this->branchId, $this->categoryId, array('audiobook_file' => $filename));

        $student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $session = $this->fixtures->login($student['username'], $student['password']);

        $response = Http::get("library/books/$bookId/listen", $session['access_token']);
        $this->assertSame(200, $response['status']);
        $this->assertStringContainsString('test audiobook content', $response['body']['raw'] ?? '');
    }

    public function test_listening_to_a_book_with_no_audiobook_uploaded_returns_404(): void
    {
        $bookId = $this->fixtures->createBook($this->branchId, $this->categoryId);
        $student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $session = $this->fixtures->login($student['username'], $student['password']);

        $response = Http::get("library/books/$bookId/listen", $session['access_token']);
        $this->assertSame(404, $response['status']);
        $this->assertSame('audiobook_not_available', $response['body']['error']['code'] ?? null);
    }

    public function test_the_catalogue_reflects_ebook_and_audiobook_availability(): void
    {
        $ebookFilename = 'ebook_' . $this->fixtures->randomSuffix() . '.pdf';
        $this->putEbookFile($ebookFilename, '%PDF-1.4 x');
        $bookWithEbook = $this->fixtures->createBook($this->branchId, $this->categoryId, array('ebook_file' => $ebookFilename));
        $bookWithNothing = $this->fixtures->createBook($this->branchId, $this->categoryId);

        $student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $session = $this->fixtures->login($student['username'], $student['password']);

        $response = Http::get('library/books', $session['access_token']);
        $this->assertSame(200, $response['status']);
        $byId = array();
        foreach ($response['body']['data'] as $book) {
            $byId[$book['id']] = $book;
        }
        $this->assertTrue($byId[$bookWithEbook]['has_ebook']);
        $this->assertFalse($byId[$bookWithEbook]['has_audiobook']);
        $this->assertFalse($byId[$bookWithNothing]['has_ebook']);
        $this->assertFalse($byId[$bookWithNothing]['has_audiobook']);
    }
}
