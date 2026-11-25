<?php
$editingArticle = isset($article);
$editingDraft = !$editingArticle || ($article['status'] ?? 'draft') === 'draft';
?>
<header class="editor-header">
    <div class="editor-header-title">
        <a href="/yonetim/dashboard" class="editor-back">← Panel</a>
        <div class="editor-header-copy">
            <h1><?= $editingArticle ? 'Makale düzenle' : 'Yeni makale' ?></h1>
            <span id="editorSaveState" class="editor-save-state" role="status" title="Yedek yalnızca bu tarayıcıdadır; dosyalar ve yayın ayarları dahil değildir.">Otomatik yedek etkin değil</span>
        </div>
    </div>
    <div class="editor-header-actions">
        <button type="button" id="tabWrite" aria-pressed="true" hidden>Yazı</button>
        <button type="button" id="editorFocusToggle" class="btn-secondary" aria-pressed="false" aria-controls="editorSettings">Odaklan</button>
        <button type="button" id="tabPreview" class="btn-secondary" aria-pressed="false" aria-haspopup="dialog" aria-controls="previewPanel">Önizleme</button>
        <a href="#editorSettings" id="editorSettingsJump" class="editor-settings-link">Yayın bilgileri</a>
        <button type="button" id="draftBtn" class="btn-secondary" <?= $editingDraft ? 'data-shortcut-save' : '' ?>><?= $editingDraft ? 'Taslak kaydet' : 'Taslağa al' ?></button>
        <button type="submit" id="submitBtn" class="btn-main" <?= !$editingDraft ? 'data-shortcut-save' : '' ?>><?= $editingDraft ? 'Yayınla' : 'Değişiklikleri kaydet' ?></button>
        <button type="button" class="theme-switch-wrapper" aria-label="Temayı değiştir"><span class="theme-switch" aria-hidden="true"></span></button>
    </div>
</header>
