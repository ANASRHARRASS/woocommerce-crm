<?php

namespace Anas\WCCRM\Contacts;

defined('ABSPATH') || exit;

/**
 * Contact repository for managing contact records
 */
class ContactRepository
{

    private string $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wccrm_contacts';
    }

    /**
     * Upsert contact by email or phone, returning contact ID
     */
    public function upsert_by_email_or_phone(array $data): ?int
    {
        $email = sanitize_email($data['email'] ?? '');
        $phone = sanitize_text_field($data['phone'] ?? '');

        if (empty($email) && empty($phone)) {
            return null;
        }

        // Try to find existing contact
        $existing_id = $this->find_by_email_or_phone($email, $phone);

        if ($existing_id) {
            // Update existing contact
            if ($this->update($existing_id, $data)) {
                return $existing_id;
            }
            return null;
        } else {
            // Create new contact
            return $this->create($data);
        }
    }

    public function create(array $data): ?int
    {
        global $wpdb;

        $insert_data = [
            'email' => sanitize_email($data['email'] ?? '') ?: null,
            'phone' => sanitize_text_field($data['phone'] ?? '') ?: null,
            'first_name' => sanitize_text_field($data['first_name'] ?? '') ?: null,
            'last_name' => sanitize_text_field($data['last_name'] ?? '') ?: null,
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $result = $wpdb->insert(
            $this->table_name,
            $insert_data,
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return $result !== false ? (int) $wpdb->insert_id : null;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;

        $update_data = [
            'updated_at' => current_time('mysql'),
        ];

        if (isset($data['email'])) {
            $update_data['email'] = sanitize_email($data['email']) ?: null;
        }

        if (isset($data['phone'])) {
            $update_data['phone'] = sanitize_text_field($data['phone']) ?: null;
        }

        if (isset($data['first_name'])) {
            $update_data['first_name'] = sanitize_text_field($data['first_name']) ?: null;
        }

        if (isset($data['last_name'])) {
            $update_data['last_name'] = sanitize_text_field($data['last_name']) ?: null;
        }

        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
        }

        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            ['id' => $id],
            array_fill(0, count($update_data), '%s'),
            ['%d']
        );

        return $result !== false;
    }

    public function find_by_id(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function find_by_email(string $email): ?array
    {
        global $wpdb;

        if (empty($email)) {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE email = %s",
                sanitize_email($email)
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function find_by_phone(string $phone): ?array
    {
        global $wpdb;

        if (empty($phone)) {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE phone = %s",
                sanitize_text_field($phone)
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    protected function find_by_email_or_phone(string $email, string $phone): ?int
    {
        global $wpdb;

        $conditions = [];
        $values = [];

        if (! empty($email)) {
            $conditions[] = 'email = %s';
            $values[] = sanitize_email($email);
        }

        if (! empty($phone)) {
            $conditions[] = 'phone = %s';
            $values[] = sanitize_text_field($phone);
        }

        if (empty($conditions)) {
            return null;
        }

        $where_clause = implode(' OR ', $conditions);

        $id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->table_name} WHERE {$where_clause} LIMIT 1",
                ...$values
            )
        );

        return $id ? (int) $id : null;
    }

    public function list_contacts(int $page = 1, int $per_page = 20, array $filters = []): array
    {
        global $wpdb;

        $offset = max(0, ($page - 1) * $per_page);
        $where_conditions = ['1=1'];
        $where_values = [];

        // Apply filters
        if (! empty($filters['status'])) {
            $where_conditions[] = 'status = %s';
            $where_values[] = sanitize_text_field($filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = '%' . $wpdb->esc_like(sanitize_text_field($filters['search'])) . '%';
            $where_conditions[] = '(email LIKE %s OR phone LIKE %s OR first_name LIKE %s OR last_name LIKE %s)';
            $where_values = array_merge($where_values, [$search, $search, $search, $search]);
        }

        $where_clause = implode(' AND ', $where_conditions);

        // Get items
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $prepare_values = array_merge($where_values, [$per_page, $offset]);

        $rows = $wpdb->get_results(
            $wpdb->prepare($query, ...$prepare_values),
            ARRAY_A
        );

        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
        $total = (int) $wpdb->get_var(
            empty($where_values) ? $count_query : $wpdb->prepare($count_query, ...$where_values)
        );

        return [
            'items' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
        ];
    }

    /**
     * Lightweight search returning id, name, email for autocomplete.
     */
    public function search(string $term, int $limit = 10): array
    {
        global $wpdb;
        $term = trim($term);
        if ($term === '') {
            return [];
        }
        $like = '%' . $wpdb->esc_like(sanitize_text_field($term)) . '%';
        $sql  = $wpdb->prepare(
            "SELECT id, first_name, last_name, email FROM {$this->table_name} WHERE email LIKE %s OR first_name LIKE %s OR last_name LIKE %s ORDER BY updated_at DESC LIMIT %d",
            $like,
            $like,
            $like,
            $limit
        );
        $rows = $wpdb->get_results($sql, ARRAY_A) ?: [];
        foreach ($rows as &$r) {
            $r['name'] = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
        }
        return $rows;
    }
}
