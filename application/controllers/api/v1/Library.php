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
        $this->db->select('book.id,book.title,book.author,book.cover,book.category_id,book_category.name as category_name,book.total_stock,book.issued_copies,book.ebook_file')
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

        $this->db->insert('mobile_audit_log', array(
            'membership_id' => $membership['id'], 'branch_id' => $membership['branch_id'], 'action' => 'library.read',
            'resource_type' => 'book', 'resource_id' => $bookId, 'ip_address' => $this->input->ip_address(),
            'user_agent' => substr((string)$this->input->user_agent(), 0, 255), 'created_at' => date('Y-m-d H:i:s'),
        ));

        $this->output->set_content_type('application/pdf')
            ->set_header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9_.-]+/', '-', $book['title']) . '.pdf"')
            ->set_header('Cache-Control: private, max-age=3600')
            ->set_output(file_get_contents($path));
        $this->output->_display();
        exit;
    }

    private function ownedBook(array $membership, $bookId)
    {
        $book = $this->db->select('id,title,author,cover,category_id,total_stock,issued_copies,ebook_file')
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
        );
    }
}
