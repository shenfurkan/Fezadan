<?php

require_once ROOT . '/app/Core/Controller.php';
require_once ROOT . '/app/Core/Db.php';
require_once ROOT . '/app/Core/ArtProvider.php';
require_once ROOT . '/app/Core/DailyArtwork.php';

class GaleriController extends Controller
{
    public function index($slug = null)
    {
        if (is_string($slug) && $slug !== '') {
            if (in_array($slug, ['arsiv', 'archive'], true)) {
                $this->archive();
                return;
            }

            $this->show($slug);
            return;
        }

        $pdo = Db::pdo();
        DailyArtwork::ensureSchema($pdo);
        $today = DailyArtwork::today();

        $todayArt = DailyArtwork::findByDate($pdo, $today);
        if (!$todayArt) {
            $artwork = ArtProvider::getRandomArtwork(false);
            if ($artwork) {
                try {
                    $todayArt = DailyArtwork::saveForDate($pdo, $artwork, $today);
                } catch (\Throwable $e) {
                    error_log('Galeri error: ' . $e->getMessage());
                    if (class_exists('AdminLog')) {
                        AdminLog::write('error', 'Galeri günün eseri kaydedilemedi.', [
                            'endpoint' => 'galeri',
                            'date' => $today,
                            'detail' => $e->getMessage(),
                        ]);
                    }
                }
            }

            if (!$todayArt) {
                $todayArt = DailyArtwork::latest($pdo);
            }
        }

        $gridArtworks = $todayArt
            ? DailyArtwork::recentBefore($pdo, $todayArt['date'], 30)
            : [];

        $this->view('front/galeri', [
            'todayArt' => $todayArt,
            'gridArtworks' => $gridArtworks,
            'page_title' => 'Günün Sanat Eseri — FEZADAN Galeri',
            'page_description' => 'Her gün dünya müzelerinden rastgele seçilmiş yeni bir sanat eserini keşfedin.',
            'og_image' => $todayArt ? $todayArt['image_url'] : null,
            'page_robots' => 'index, follow',
        ]);
    }

    public function show($slug)
    {
        $pdo = Db::pdo();
        DailyArtwork::ensureSchema($pdo);
        $art = DailyArtwork::findBySlug($pdo, (string)$slug);

        if (!$art) {
            header('Location: /galeri');
            exit;
        }

        $neighbors = DailyArtwork::neighborSlugs($pdo, $art['date']);
        $description = trim(strip_tags($art['description_tr'] ?: $art['description_en'] ?: ''));

        $this->view('front/galeri_detail', [
            'art' => $art,
            'prevDate' => $neighbors['prev'],
            'nextDate' => $neighbors['next'],
            'page_title' => $art['title'] . ' - ' . $art['artist'] . ' — FEZADAN Galeri',
            'page_description' => mb_substr($description, 0, 150, 'UTF-8') . ($description !== '' ? '...' : ''),
            'og_image' => $art['image_url'],
            'og_type' => 'article',
            'page_robots' => 'index, follow',
        ]);
    }

    public function archive()
    {
        $pdo = Db::pdo();
        DailyArtwork::ensureSchema($pdo);

        $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        $limit = 20;
        $offset = ($page - 1) * $limit;
        $total = DailyArtwork::countAll($pdo);
        $totalPages = (int)ceil($total / $limit);
        $artworks = DailyArtwork::page($pdo, $limit, $offset);

        $this->view('front/galeri_archive', [
            'artworks' => $artworks,
            'page' => $page,
            'totalPages' => $totalPages,
            'page_title' => 'Galeri Arşivi — FEZADAN',
            'page_description' => 'Geçmiş günlerin sanat eserleri arşivi.',
            'page_robots' => 'noindex, follow',
        ]);
    }
}
