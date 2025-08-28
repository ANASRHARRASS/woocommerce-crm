<?php
/**
 * CSV Writer utility for data exports
 * Memory-safe CSV generation with streaming support
 */

namespace KS_CRM\Exports;

defined( 'ABSPATH' ) || exit;

class CSV_Writer {

    private $output_handle;
    private bool $headers_written = false;
    private array $headers = [];
    private string $filename;
    private bool $stream_to_browser;

    /**
     * Initialize CSV writer
     *
     * @param string $filename Output filename
     * @param bool $stream_to_browser Whether to stream directly to browser
     */
    public function __construct( string $filename, bool $stream_to_browser = true ) {
        $this->filename = sanitize_file_name( $filename );
        $this->stream_to_browser = $stream_to_browser;
        
        if ( $stream_to_browser ) {
            $this->init_browser_stream();
        } else {
            $this->init_file_stream();
        }
    }

    /**
     * Set CSV headers
     *
     * @param array $headers Column headers
     */
    public function set_headers( array $headers ): void {
        $this->headers = array_map( 'sanitize_text_field', $headers );
    }

    /**
     * Write a data row
     *
     * @param array $row Data row
     */
    public function write_row( array $row ): void {
        if ( ! $this->headers_written && ! empty( $this->headers ) ) {
            $this->write_csv_row( $this->headers );
            $this->headers_written = true;
        }

        $this->write_csv_row( $row );
    }

    /**
     * Write multiple rows
     *
     * @param array $rows Array of data rows
     */
    public function write_rows( array $rows ): void {
        foreach ( $rows as $row ) {
            $this->write_row( $row );
        }
    }

    /**
     * Finalize and close the CSV
     */
    public function close(): void {
        if ( is_resource( $this->output_handle ) ) {
            fclose( $this->output_handle );
        }
    }

    /**
     * Get temporary file path for non-streaming mode
     *
     * @return string|null File path or null if streaming
     */
    public function get_file_path(): ?string {
        if ( $this->stream_to_browser ) {
            return null;
        }

        return $this->filename;
    }

    /**
     * Initialize browser streaming
     */
    private function init_browser_stream(): void {
        if ( ! headers_sent() ) {
            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename="' . $this->filename . '"' );
            header( 'Cache-Control: no-cache, must-revalidate' );
            header( 'Expires: 0' );
        }

        $this->output_handle = fopen( 'php://output', 'w' );
        
        // Add UTF-8 BOM for Excel compatibility
        if ( $this->output_handle ) {
            fwrite( $this->output_handle, "\xEF\xBB\xBF" );
        }
    }

    /**
     * Initialize file streaming to temporary file
     */
    private function init_file_stream(): void {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/kscrm-exports';
        
        if ( ! file_exists( $temp_dir ) ) {
            wp_mkdir_p( $temp_dir );
            
            // Add .htaccess to protect directory
            $htaccess_content = "Order deny,allow\nDeny from all\n";
            file_put_contents( $temp_dir . '/.htaccess', $htaccess_content );
        }

        $this->filename = $temp_dir . '/' . uniqid( 'export_' ) . '_' . $this->filename;
        $this->output_handle = fopen( $this->filename, 'w' );
        
        // Add UTF-8 BOM
        if ( $this->output_handle ) {
            fwrite( $this->output_handle, "\xEF\xBB\xBF" );
        }
    }

    /**
     * Write a CSV row
     *
     * @param array $row Data row
     */
    private function write_csv_row( array $row ): void {
        if ( ! is_resource( $this->output_handle ) ) {
            return;
        }

        // Sanitize and prepare row data
        $sanitized_row = array_map( function( $value ) {
            if ( is_array( $value ) || is_object( $value ) ) {
                return wp_json_encode( $value );
            }
            return (string) $value;
        }, $row );

        fputcsv( $this->output_handle, $sanitized_row );
        
        // Flush output for streaming
        if ( $this->stream_to_browser ) {
            if ( ob_get_level() ) {
                ob_flush();
            }
            flush();
        }
    }

    /**
     * Create CSV from array data (convenience method)
     *
     * @param array $data Array of data rows
     * @param array $headers Column headers
     * @param string $filename Output filename
     * @param bool $stream_to_browser Whether to stream to browser
     * @return CSV_Writer|null Writer instance or null if streaming
     */
    public static function create_from_array( array $data, array $headers, string $filename, bool $stream_to_browser = true ): ?self {
        $writer = new self( $filename, $stream_to_browser );
        $writer->set_headers( $headers );
        $writer->write_rows( $data );
        
        if ( $stream_to_browser ) {
            $writer->close();
            return null; // Data was streamed, no file to return
        }
        
        return $writer;
    }

    /**
     * Export data with memory-safe batching
     *
     * @param callable $data_callback Callback that returns batches of data
     * @param array $headers Column headers
     * @param string $filename Output filename
     * @param int $batch_size Batch size for memory management
     * @param bool $stream_to_browser Whether to stream to browser
     * @return CSV_Writer|null Writer instance or null if streaming
     */
    public static function create_batched( callable $data_callback, array $headers, string $filename, int $batch_size = 1000, bool $stream_to_browser = true ): ?self {
        $writer = new self( $filename, $stream_to_browser );
        $writer->set_headers( $headers );
        
        $offset = 0;
        
        do {
            $batch = call_user_func( $data_callback, $offset, $batch_size );
            
            if ( ! empty( $batch ) ) {
                $writer->write_rows( $batch );
                $offset += $batch_size;
            }
            
            // Clear memory
            unset( $batch );
            
        } while ( ! empty( $batch ) );
        
        if ( $stream_to_browser ) {
            $writer->close();
            return null;
        }
        
        return $writer;
    }
}