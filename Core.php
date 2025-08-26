<?php
namespace WCP;

class Core {
    const VERSION = '1.0.0';

    public function init(): void {
        $this->register_hooks();
    }

    protected function register_hooks(): void {
        add_action( 'init', [ $this, 'register_post_types' ] );
    }

    public function register_post_types(): void {
        // Placeholder: add custom post types later.
    }
}
