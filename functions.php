<?php

// Registrar o tipo de post personalizado "imovel"
function register_real_estate_post_type() {
    $args = array(
        'public' => true,
        'label'  => 'Imóveis',
        'supports' => array('title', 'editor', 'custom-fields', 'thumbnail'),
        'menu_icon' => 'dashicons-building',
        'rewrite' => array('slug' => 'imoveis'),
    );
    register_post_type('imovel', $args);
}
add_action('init', 'register_real_estate_post_type');

// Função para processar a filtragem via AJAX
function filter_properties() {
    // Coletar os parâmetros da requisição
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : '';
    $rooms = isset($_POST['rooms']) ? intval($_POST['rooms']) : '';
    $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';

    // Argumentos para a query
    $args = array(
        'post_type' => 'imovel',
        'posts_per_page' => -1, // Mostrar todos os imóveis
        'meta_query' => array(
            'relation' => 'AND',
        ),
    );

    // Filtro de tipo de imóvel
    if ($type && $type != 'todos') {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'tipo_imovel',
                'field'    => 'slug',
                'terms'    => $type,
            ),
        );
    }

    // Filtro de preço máximo
    if ($price) {
        $args['meta_query'][] = array(
            'key' => 'preco',
            'value' => $price,
            'compare' => '<=',
            'type' => 'NUMERIC'
        );
    }

    // Filtro de número de quartos
    if ($rooms) {
        $args['meta_query'][] = array(
            'key' => 'num_quartos',
            'value' => $rooms,
            'compare' => '>=',
            'type' => 'NUMERIC'
        );
    }

    // Filtro de localização
    if ($location) {
        $args['meta_query'][] = array(
            'key' => 'localizacao',
            'value' => $location,
            'compare' => 'LIKE',
            'type' => 'CHAR'
        );
    }

    // Query para buscar os imóveis
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $preco = get_post_meta(get_the_ID(), 'preco', true);
            $quartos = get_post_meta(get_the_ID(), 'num_quartos', true);
            $localizacao = get_post_meta(get_the_ID(), 'localizacao', true);
            echo '<div class="property">';
            echo '<h3>' . get_the_title() . '</h3>';
            echo '<p>Preço: R$ ' . number_format($preco, 2, ',', '.') . '</p>';
            echo '<p>' . $quartos . ' Quartos</p>';
            echo '<p>Localização: ' . $localizacao . '</p>';
            echo '</div>';
        }
    } else {
        echo '<p>Nenhum imóvel encontrado.</p>';
    }

    wp_die(); // Encerra a requisição AJAX corretamente
}

// Registrando as ações do AJAX
add_action('wp_ajax_filter_properties', 'filter_properties');
add_action('wp_ajax_nopriv_filter_properties', 'filter_properties');

// Enqueue dos scripts necessários para o filtro funcionar
function enqueue_property_filter_script() {
    wp_enqueue_script('jquery'); // Garantir que o jQuery esteja carregado
    wp_enqueue_script('filter-properties', get_template_directory_uri() . '/js/filter-properties.js', array('jquery'), null, true);

    // Passar a URL do admin-ajax.php para o script JS
    wp_localize_script('filter-properties', 'ajaxurl', admin_url('admin-ajax.php'));
}
add_action('wp_enqueue_scripts', 'enqueue_property_filter_script');
