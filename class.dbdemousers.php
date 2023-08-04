<?php
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

Class DBTableUsers extends WP_List_Table {

    private $_items;

    function __construct($data) {
        parent::__construct();
        $this->_items = $data;
    }

    function get_columns() {
        return [
            'cb'    => '<input type="checkbox" />',
            'name'  => __( 'Name', 'database-demo' ),
            'email' => __( 'Email', 'database-demo' ),
            'action' => __( 'Action', 'database-demo' ),
        ];
    }

    function column_cb($item) {
        return "<input type='checkbox' value='{$item['id']}' />";
    }

    function column_name($item) {
        $nonce = wp_create_nonce('dbdemo_edit');
        $actions = [
            'edit'   => sprintf( '<a href="?page=dbdemo&pid=%s&n=%s">%s</a>', $item['id'], $nonce, __( 'Edit', 'database-demo' ) ),
			'hello'   => sprintf( '<a href="?page=dbdemo&pid=%s&n=%s">%s</a>', $item['id'], $nonce, __( 'Hello', 'database-demo' ) ),
			'delete' => sprintf( '<a href="?page=dbdemo&pid=%s&n=%s&action=%s">%s</a>', $item['id'], $nonce,'delete', __('Delete','database-demo') ),
        ];
        return sprintf('%s %s', $item['name'], $this->row_actions($actions));
    }

    function column_action($item) {
        $link = wp_nonce_url(admin_url('?page=dbdemo&pid=').$item['id'], 'dbdemo_edit', 'n');
        return "<a href='" . esc_url($link) . "'>Edit</a>";
    }

    function column_default($item, $column_name) {
        return $item[$column_name];
    }

    function prepare_items() {
        $paged = $_REQUEST['paged'] ?? 1;
        $per_page = 10;
        $total_items = count($this->_items);
        $this->_column_headers = array( $this->get_columns(), [], [] );
        $data_chunks = array_chunk($this->_items, $per_page);
        $this->items = $data_chunks[$paged - 1];
        $this->set_pagination_args([
            'total_items' => $total_items,
            'par_page' => $per_page,
            'total_pages' => ceil(count($this->_items) / $per_page)
        ]);
    }

}