<?php
class App {
    protected $controller = 'HomeController';
    protected $method = 'index';
    protected $params = [];

    public function __construct() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $url = $this->parseUrl();

        // Notlar subdomain routing.
        if (strpos($host, 'notlar.') === 0) {
            require_once ROOT . '/app/Controllers/NotlarController.php';
            $this->controller = new NotlarController();

            if (isset($url[0]) && $url[0] === 'not') {
                if (isset($url[1]) && $url[1] === 'download') {
                    $this->method = 'download';
                    $this->params = isset($url[2]) ? [$url[2]] : [];
                } elseif (isset($url[1]) && $url[1] === 'view') {
                    $this->method = 'viewPdf';
                    $this->params = isset($url[2]) ? [$url[2]] : [];
                } else {
                    $this->method = 'read';
                    $this->params = isset($url[1]) ? [$url[1]] : [];
                }
            } else {
                $this->method = 'index';
                $this->params = [];
            }

            call_user_func_array([$this->controller, $this->method], $this->params);
            return;
        }

        if (file_exists(ROOT . '/app/Controllers/' . ucfirst($url[0]) . 'Controller.php')) {
            $this->controller = ucfirst($url[0]) . 'Controller';
            unset($url[0]);
        } elseif ($url[0] !== '') {
            $this->render404();
        }

        require_once ROOT . '/app/Controllers/' . $this->controller . '.php';

        $this->controller = new $this->controller;

        if (isset($url[1])) {
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            } else {
                $camelCaseMethod = lcfirst(str_replace('-', '', ucwords($url[1], '-')));

                if (method_exists($this->controller, $camelCaseMethod)) {
                    $this->method = $camelCaseMethod;
                    unset($url[1]);
                }
            }
        }

        $this->params = $url ? array_values($url) : [];
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    public function parseUrl() {
        if (isset($_GET['url'])) {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return ['home'];
    }

    private function render404(): void {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        $view = ROOT . '/app/Views/errors/404.php';
        if (is_file($view)) {
            require $view;
        } else {
            echo 'Sayfa bulunamadı.';
        }
        exit;
    }
}
