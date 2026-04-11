<?php
class App {
    protected $controller = 'HomeController';
    protected $method = 'index';
    protected $params = [];

    public function __construct() {
        $url = $this->parseUrl();

        // URL var mı kontrol et
        if (file_exists(ROOT . '/app/Controllers/' . ucfirst($url[0]) . 'Controller.php')) {
            $this->controller = ucfirst($url[0]) . 'Controller';
            unset($url[0]);
        }
        // Geçersiz link
        elseif ($url[0] != '') {
            header("HTTP/1.0 404 Not Found");
            echo "<div style='height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center; background:#FEF9E1; color:#6D2323; font-family:sans-serif;'>";
            echo "<h1 style='font-size:5rem; margin:0;'>404</h1>";
            echo "<p style='font-size:1.5rem; letter-spacing:3px; text-transform:uppercase;'>Bu Frekans Boş</p>";
            echo "<a href='/' style='margin-top:20px; color:#A31D1D; text-decoration:underline;'>Merkeze Dön</a>";
            echo "</div>";
            exit;
        }

        require_once ROOT . '/app/Controllers/' . $this->controller . '.php';
        
        $this->controller = new $this->controller;

        // Method kontrolü
        if (isset($url[1])) {
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            }
            else {
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
}