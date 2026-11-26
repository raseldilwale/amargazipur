<?php
declare(strict_types=1);

class View
{
    public static function render(string $template, array $data = [], string $layout = 'main'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = APP_PATH . '/Views/' . $template . '.php';
        if (!file_exists($viewFile)) {
            http_response_code(404);
            exit('View not found');
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === 'admin') {
            require APP_PATH . '/Views/layouts/admin_layout.php';
        } elseif ($layout === 'none') {
            echo $content;
        } else {
            require APP_PATH . '/Views/layouts/main.php';
        }
    }
}
