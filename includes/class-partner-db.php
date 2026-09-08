<?php
/**
 * SoulTether_Partner_DB
 * All CRUD operations on the soultether_requests WordPress table.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SoulTether_Partner_DB {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . SOULTETHER_TABLE;
    }

    public function create_request(
        string $requester_uuid,
        string $requester_name,
        string $target_uuid,
        string $target_name,
        string $message = ''
    ): int|false {
        global $wpdb;

        $inserted = $wpdb->insert(
            $this->table,
            [
                'requester_uuid'  => $requester_uuid,
                'requester_name'  => $requester_name,
                'target_uuid'     => $target_uuid,
                'target_name'     => $target_name,
                'request_message' => $message,
                'status'          => 'pending',
                'created_at'      => current_time( 'mysql' ),
                'updated_at'      => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );

        return $inserted ? $wpdb->insert_id : false;
    }

    public function update_status( int $id, string $status ): bool {
        global $wpdb;
        $updated = $wpdb->update(
            $this->table,
            [ 'status' => $status, 'updated_at' => current_time( 'mysql' ) ],
            [ 'id' => $id ],
            [ '%s', '%s' ],
            [ '%d' ]
        );
        return $updated !== false;
    }

    public function get_request( int $id ): ?array {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d LIMIT 1", $id ),
            ARRAY_A
        );
        return $row ?: null;
    }

    public function get_incoming_pending( string $uuid ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table}
                 WHERE target_uuid = %s AND status = 'pending'
                 ORDER BY created_at DESC",
                $uuid
            ),
            ARRAY_A
        ) ?: [];
    }

    public function get_outgoing_pending( string $uuid ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table}
                 WHERE requester_uuid = %s AND status = 'pending'
                 ORDER BY created_at DESC",
                $uuid
            ),
            ARRAY_A
        ) ?: [];
    }

    public function get_active_partnership( string $uuid ): ?array {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table}
                 WHERE (requester_uuid = %s OR target_uuid = %s)
                   AND status = 'approved'
                 ORDER BY updated_at DESC
                 LIMIT 1",
                $uuid, $uuid
            ),
            ARRAY_A
        );
        return $row ?: null;
    }

    public function pending_exists( string $uuid_a, string $uuid_b ): bool {
        global $wpdb;
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table}
                 WHERE status = 'pending'
                   AND (
                       (requester_uuid = %s AND target_uuid = %s)
                    OR (requester_uuid = %s AND target_uuid = %s)
                   )",
                $uuid_a, $uuid_b, $uuid_b, $uuid_a
            )
        );
        return (int) $count > 0;
    }

    public function dissolve_all( string $uuid_a, string $uuid_b ): void {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table}
                 SET status = 'dissolved', updated_at = %s
                 WHERE (
                     (requester_uuid = %s AND target_uuid = %s)
                  OR (requester_uuid = %s AND target_uuid = %s)
                 )
                 AND status IN ('approved', 'pending')",
                current_time( 'mysql' ),
                $uuid_a, $uuid_b, $uuid_b, $uuid_a
            )
        );
    }

    public function get_all( string $status = '', int $limit = 50, int $offset = 0 ): array {
        global $wpdb;
        $where = $status ? $wpdb->prepare( "WHERE status = %s", $status ) : '';
        return $wpdb->get_results(
            "SELECT * FROM {$this->table} {$where} ORDER BY updated_at DESC LIMIT {$limit} OFFSET {$offset}",
            ARRAY_A
        ) ?: [];
    }

    public function count( string $status = '' ): int {
        global $wpdb;
        $where = $status ? $wpdb->prepare( "WHERE status = %s", $status ) : '';
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table} {$where}" );
    }
}
