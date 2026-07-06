<?php
class PageController extends Controller {
    public function renderPage($page) {
        $page_alternates = [];

        switch ($page) {
            case 'about':
                $page_alternates = ['tr' => '/tr/hakkinda', 'en' => '/en/about'];
                break;
            case 'manifesto':
                $page_alternates = ['tr' => '/tr/manifesto', 'en' => '/en/manifesto'];
                break;
            case 'privacy':
                $page_alternates = ['tr' => '/tr/gizlilik-politikasi', 'en' => '/en/privacy'];
                break;
            case 'verification':
                $page_alternates = ['tr' => '/tr/teyit', 'en' => '/en/verification'];
                break;
        }

        $this->view('front/' . $page, ['page_alternates' => $page_alternates]);
    }
}
