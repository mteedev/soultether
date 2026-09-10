<?php
/**
 * SoulTether_Robust_DB
 * Manages the PDO connection to the OpenSim Robust MySQL database.
 *
 * Column names confirmed against standard OpenSimulator schema:
 *   UserAccounts — PrincipalID, FirstName, LastName
 *   userprofile  — useruuid (PK), profilePartner
 *   Friends      — PrincipalID, Friend
 *
 * If your grid uses different column names, adjust the queries below.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SoulTether_Robust_DB {

    private static ?PDO $instance = null;

    /**
     * Returns a singleton PDO connection to the Robust DB.
     * Automatically reads credentials from w4os WordPress settings.
     * Falls back to SOULTETHER_ROBUST_* constants if w4os is not available.
     */
    public static function get(): PDO {
        if ( self::$instance === null ) {

            // Read credentials from w4os settings (zero-config!)
            $host   = get_option( 'w4os_db_host',     defined('SOULTETHER_ROBUST_HOST')   ? SOULTETHER_ROBUST_HOST   : '127.0.0.1' );
            $port   = get_option( 'w4os_db_port',     defined('SOULTETHER_ROBUST_PORT')   ? SOULTETHER_ROBUST_PORT   : '3306' );
            $dbname = get_option( 'w4os_db_database', defined('SOULTETHER_ROBUST_DB')     ? SOULTETHER_ROBUST_DB     : 'robust' );
            $user   = get_option( 'w4os_db_user',     defined('SOULTETHER_ROBUST_USER')   ? SOULTETHER_ROBUST_USER   : '' );
            $pass   = get_option( 'w4os_db_pass',     defined('SOULTETHER_ROBUST_PASS')   ? SOULTETHER_ROBUST_PASS   : '' );

            // Use Unix socket if host is localhost/127.0.0.1 and socket is defined
            $socket = defined('SOULTETHER_ROBUST_SOCKET') ? SOULTETHER_ROBUST_SOCKET : '';

            if ( $socket && ( $host === 'localhost' || $host === '127.0.0.1' ) ) {
                $dsn = sprintf(
                    'mysql:unix_socket=%s;dbname=%s;charset=utf8',
                    $socket,
                    $dbname
                );
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8',
                    $host,
                    $port,
                    $dbname
                );
            }

            self::$instance = new PDO( $dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$instance;
    }

    /**
     * Look up an avatar's full name by UUID.
     * Returns "Firstname Lastname" or null if not found.
     */
    public static function get_avatar_name( string $uuid ): ?string {
        try {
            $db  = self::get();
            $sql = $db->prepare(
                "SELECT FirstName, LastName
                 FROM UserAccounts
                 WHERE PrincipalID = ?
                 LIMIT 1"
            );
            $sql->execute( [ $uuid ] );
            $row = $sql->fetch();
            if ( $row ) {
                return trim( $row['FirstName'] . ' ' . $row['LastName'] );
            }
        } catch ( PDOException $e ) {
            error_log( '[SoulTether] Robust DB avatar lookup failed: ' . $e->getMessage() );
        }
        return null;
    }

    /**
     * Get the current partner UUID for a given avatar UUID.
     * Returns UUID string, or null if no partner / null UUID.
     */
    public static function get_partner_uuid( string $uuid ): ?string {
        $null_uuid = '00000000-0000-0000-0000-000000000000';
        try {
            $db  = self::get();
            $sql = $db->prepare(
                "SELECT profilePartner
                 FROM userprofile
                 WHERE useruuid = ?
                 LIMIT 1"
            );
            $sql->execute( [ $uuid ] );
            $row = $sql->fetch();
            if ( $row && $row['profilePartner'] !== $null_uuid ) {
                return $row['profilePartner'];
            }
        } catch ( PDOException $e ) {
            error_log( '[SoulTether] Robust DB partner read failed: ' . $e->getMessage() );
        }
        return null;
    }

    /**
     * Set the profilePartner field for a given avatar UUID.
     * Pass the null UUID to clear a partnership.
     * Returns true on success, false on failure.
     */
    public static function set_partner_uuid( string $uuid, string $partner_uuid ): bool {
        try {
            $db  = self::get();
            $sql = $db->prepare(
                "UPDATE userprofile SET profilePartner = ? WHERE useruuid = ?"
            );
            $sql->execute( [ $partner_uuid, $uuid ] );
            return true;
        } catch ( PDOException $e ) {
            error_log( '[SoulTether] Robust DB partner write failed: ' . $e->getMessage() );
        }
        return false;
    }

    /**
     * Get the friend list for a given avatar UUID.
     * Returns array of [ 'uuid' => '...', 'name' => '...' ] sorted by name.
     */
    public static function get_friends( string $uuid ): array {
        $friends = [];
        try {
            $db  = self::get();
            $sql = $db->prepare(
                "SELECT f.Friend, ua.FirstName, ua.LastName
                 FROM Friends f
                 JOIN UserAccounts ua ON ua.PrincipalID = f.Friend
                 WHERE f.PrincipalID = ?
                 ORDER BY ua.FirstName, ua.LastName"
            );
            $sql->execute( [ $uuid ] );
            while ( $row = $sql->fetch() ) {
                $friends[] = [
                    'uuid' => $row['Friend'],
                    'name' => trim( $row['FirstName'] . ' ' . $row['LastName'] ),
                ];
            }
        } catch ( PDOException $e ) {
            error_log( '[SoulTether] Robust DB friends lookup failed: ' . $e->getMessage() );
        }
        return $friends;
    }
}
