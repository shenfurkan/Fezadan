<?php
class PageController extends Controller {
    public function renderPage($page) {
        if (!in_array($page, ['about', 'manifesto', 'privacy', 'donate', 'verification'], true)) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }
        $this->view('front/' . $page, ['page_alternates' => [
            'tr' => pageUrl($page, 'tr'),
            'en' => pageUrl($page, 'en'),
        ]]);
    }
}
