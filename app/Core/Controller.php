<?php
class Controller {
    public function view($view, $data = []) {
        if (file_exists(ROOT . '/app/Views/' . $view . '.php')) {
            extract($data);
            require_once ROOT . '/app/Views/' . $view . '.php';
        } else {
            die("View dosyası bulunamadı: " . $view);
        }
    }
    
    // Slug fonksiyonu
    public function createSlug($str) {
        $find = array('Ç', 'Ş', 'Ğ', 'Ü', 'İ', 'Ö', 'ç', 'ş', 'ğ', 'ü', 'ö', 'ı');
        $replace = array('c', 's', 'g', 'u', 'i', 'o', 'c', 's', 'g', 'u', 'o', 'i');
        $str = strtolower(str_replace($find, $replace, $str));
        $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
        $str = preg_replace('/[\s-]+/', ' ', $str);
        $str = trim($str);
        $str = str_replace(' ', '-', $str);
        return $str;
    }
}