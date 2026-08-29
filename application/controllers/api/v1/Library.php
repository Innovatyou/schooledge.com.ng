<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * Mobile digital-library API. The existing Library module (book/book_issues) is a
 * physical lending catalogue only - reading a PDF here is independent of physical
 * stock/issue status, so it doesn't touch book_issues at all.
 */
class Library extends Api_Controller
{
    public function books()
    {
        $membership = $this->requireAuth();
        $this->db->select('book.id,book.title,book.author,book.cover,book.category_id,book_category.name as category_name,book.total_stock,book.issued_copies,book.ebook_file,book.audiobook_file,book.audiobook_duration_seconds')
            ->from('book')
            ->join('book_category', 'book_category.id = book.category_id', 'left')
            ->where('book.branch_id', $membership['branch_id']);

        $categoryId = $this->input->get('category_id');
        if ($categoryId) $this->db->where('book.category_id', (int)$categoryId);
        $query = trim((string)$this->input->get('q'));
        if ($query !== '') $this->db->group_start()->like('book.title', $query)->or_like('book.author', $query)->group_end();

        $rows = $this->db->order_by('book.title', 'asc')->get()->result_array();
        $this->ok(array_map(array($this, 'bookPayload'), $rows));
    }

    public function categories()
    {
        $membership = $this->requireAuth();
        $rows = $this->db->select('id,name')->where('branch_id', $membership['branch_id'])->order_by('name', 'asc')->get('book_category')->result_array();
        $this->ok($rows);
    }

    public function issues()
    {
        $membership = $this->requireAuth();
        $roleId = (int)$membership['role_id'];
        $userId = (int)$membership['user_id'];
        if ($roleId === 6 && $this->input->get('student_id')) {
            $studentId = (int)$this->input->get('student_id');
            $owned = $this->db->where(array('id'=>$studentId, 'parent_id'=>$userId))->get('student')->row_array();
            if (!$owned) $this->fail('student_not_found', 'The selected student is not linked to this parent.', 404);
            $roleId = 7;
            $userId = $studentId;
        }
        $this->db->select('bi.id,bi.book_id,bi.date_of_issue,bi.date_of_expiry,bi.return_date,bi.status,bi.fine_amount,bi.is_lost,bi.lost_fine_amount,b.title,b.author,b.isbn_no,b.cover');
        $this->db->from('book_issues as bi')->join('book as b', 'b.id = bi.book_id', 'inner');
        $rows = $this->db->where(array('bi.branch_id'=>$membership['branch_id'], 'bi.role_id'=>$roleId, 'bi.user_id'=>$userId))->order_by('bi.id', 'desc')->get()->result_array();
        $this->ok(array_map(array($this, 'issuePayload'), $rows));
    }

    public function show($bookId)
    {
        $membership = $this->requireAuth();
        $book = $this->ownedBook($membership, $bookId);
        $this->db->select('description');
        $extra = $this->db->where('id', $bookId)->get('book')->row_array();
        $payload = $this->bookPayload($book);
        $payload['description'] = $extra['description'] ?? null;
        $this->ok($payload);
    }

    public function read($bookId)
    {
        $membership = $this->requireAuth();
        $book = $this->ownedBook($membership, $bookId);
        if (empty($book['ebook_file'])) $this->fail('ebook_not_available', 'No digital copy is available for this book.', 404);
        $path = FCPATH . 'uploads/book_ebook/' . $book['ebook_file'];
        if (!is_file($path)) $this->fail('ebook_not_available', 'No digital copy is available for this book.', 404);

        $this->logAudit('library.read', $membership, 'book', $bookId);

        $this->output->set_content_type('application/pdf')
            ->set_header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9_.-]+/', '-', $book['title']) . '.pdf"')
            ->set_header('Cache-Control: private, max-age=3600')
            ->set_output(file_get_contents($path));
        $this->output->_display();
        exit;
    }

    public function readAudio($bookId)
    {
        $membership = $this->requireAuth();
        $book = $this->ownedBook($membership, $bookId);
        if (empty($book['audiobook_file'])) $this->fail('audiobook_not_available', 'No audiobook is available for this book.', 404);
        $path = FCPATH . 'uploads/book_audio/' . $book['audiobook_file'];
        if (!is_file($path)) $this->fail('audiobook_not_available', 'No audiobook is available for this book.', 404);

        $this->logAudit('library.listen', $membership, 'book', $bookId);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = $extension === 'aac' ? 'audio/aac' : ($extension === 'm4a' ? 'audio/mp4' : 'audio/mpeg');
        $this->output->set_content_type($mime)
            ->set_header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9_.-]+/', '-', $book['title']) . '.' . $extension . '"')
            ->set_header('Cache-Control: private, max-age=3600')
            ->set_output(file_get_contents($path));
        $this->output->_display();
        exit;
    }

    private function ownedBook(array $membership, $bookId)
    {
        $book = $this->db->select('id,title,author,cover,category_id,total_stock,issued_copies,ebook_file,audiobook_file,audiobook_duration_seconds')
            ->where(array('id' => (int)$bookId, 'branch_id' => $membership['branch_id']))
            ->get('book')->row_array();
        if (!$book) $this->fail('book_not_found', 'Book not found.', 404);
        return $book;
    }

    private function bookPayload(array $row)
    {
        return array(
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'author' => $row['author'],
            'category' => $row['category_name'] ?? null,
            'cover_url' => !empty($row['cover']) ? base_url('uploads/book_cover/' . $row['cover']) : null,
            'available_copies' => max(0, (int)$row['total_stock'] - (int)$row['issued_copies']),
            'total_stock' => (int)$row['total_stock'],
            'has_ebook' => !empty($row['ebook_file']),
            'has_audiobook' => !empty($row['audiobook_file']),
            'audiobook_duration_seconds' => isset($row['audiobook_duration_seconds']) ? (int)$row['audiobook_duration_seconds'] : null,
        );
    }

    private function issuePayload(array $row)
    {
        $status = (int)$row['status'];
        $lost = (bool)$row['is_lost'];
        $overdue = !$lost && $status === 1 && !empty($row['date_of_expiry']) && strtotime($row['date_of_expiry']) < strtotime(date('Y-m-d'));
        $state = $lost ? 'lost' : ($status === 3 ? 'returned' : ($status === 1 ? ($overdue ? 'overdue' : 'issued') : ($status === 2 ? 'rejected' : 'pending')));
        return array(
            'id'=>(int)$row['id'], 'book_id'=>(int)$row['book_id'], 'title'=>$row['title'], 'author'=>$row['author'],
            'isbn'=>$row['isbn_no'], 'cover_url'=>!empty($row['cover']) ? base_url('uploads/book_cover/' . $row['cover']) : null,
            'issued_date'=>$row['date_of_issue'], 'due_date'=>$row['date_of_expiry'], 'return_date'=>$row['return_date'] ?: null,
            'status'=>$state, 'is_returned'=>$status === 3, 'is_overdue'=>$overdue, 'is_lost'=>$lost,
            'fine_amount'=>(float)$row['fine_amount'], 'lost_fine_amount'=>(float)$row['lost_fine_amount'],
            'outstanding_fine'=>$lost ? (float)$row['lost_fine_amount'] : (float)$row['fine_amount'],
        );
    }
}
